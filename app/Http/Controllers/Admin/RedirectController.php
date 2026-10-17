<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Settings → Redirects: old addresses (as Google still has them indexed) → new ones, plus the
 * 404 log of broken addresses that still get visits. Every add/edit/import goes through
 * prepare(), which normalizes both addresses and enforces:
 *   - the source is an address on this site and not a protected one (admin, login, checkout…);
 *   - no loops (A → A, or A → B while B → A);
 *   - no chains: pointing at an address that itself redirects goes straight to its final
 *     target, and redirects that pointed at this source are re-pointed past it — one hop,
 *     which is what Google follows best.
 */
class RedirectController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q'));
        $redirects = Redirect::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('source', 'like', "%{$search}%")->orWhere('target', 'like', "%{$search}%")->orWhere('note', 'like', "%{$search}%")))
            ->orderByDesc('updated_at')
            ->paginate(50)->withQueryString();

        $redirected = Redirect::pluck('source')->all();
        $notFound = NotFoundLog::query()
            ->whereNotIn('path', $redirected)
            ->orderByDesc('hits')->orderByDesc('last_hit_at')
            ->limit(100)->get();

        return view('admin.settings.redirects', [
            'redirects' => $redirects,
            'notFound' => $notFound,
            'search' => $search,
            'totals' => ['redirects' => Redirect::count(), 'hits' => (int) Redirect::sum('hits'), 'notFound' => NotFoundLog::whereNotIn('path', $redirected)->count()],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->prepare($request->only('source', 'target', 'status_code', 'note'));
        Redirect::create($data);
        $this->afterSave($data);

        return back()->with('success', "Redirect added: {$data['source']}");
    }

    public function update(Request $request, Redirect $redirect)
    {
        $data = $this->prepare($request->only('source', 'target', 'status_code', 'note'), $redirect);
        $redirect->update($data + ['is_active' => $request->boolean('is_active', true)]);
        $this->afterSave($data);

        return back()->with('success', "Redirect updated: {$data['source']}");
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return back()->with('success', "Redirect removed: {$redirect->source}");
    }

    /** CSV: old address, new address[, 301|302|410[, note]] per line; a header row is skipped. */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $added = $updated = 0;
        $errors = [];
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $line = 0;
        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $line++;
            $row = array_map(fn ($v) => trim((string) $v, " \t\n\r\0\x0B\xEF\xBB\xBF"), $row);
            if (count(array_filter($row)) === 0) {
                continue;
            }
            // header row: a first line whose first cell isn't an address ("old_url", "Source"…)
            if ($line === 1 && !preg_match('#^(https?://|/|www\.|' . preg_quote((string) parse_url(config('app.url'), PHP_URL_HOST), '#') . ')#i', $row[0])) {
                continue;
            }
            try {
                $data = $this->prepare(['source' => $row[0] ?? '', 'target' => $row[1] ?? '', 'status_code' => $row[2] ?? 301, 'note' => $row[3] ?? 'CSV import'], null, true);
                $existing = Redirect::where('source', $data['source'])->first();
                $existing ? $existing->update($data + ['is_active' => true]) : Redirect::create($data);
                $existing ? $updated++ : $added++;
                $this->afterSave($data);
            } catch (ValidationException $e) {
                $errors[] = "Line {$line}: " . collect($e->errors())->flatten()->first();
            }
        }
        fclose($handle);

        $message = "Import finished — {$added} added, {$updated} updated" . ($errors ? ', ' . count($errors) . ' skipped' : '') . '.';

        return back()->with('success', $message)->with('import_errors', array_slice($errors, 0, 20));
    }

    public function dismissNotFound(NotFoundLog $notFoundLog)
    {
        $notFoundLog->delete();

        return back()->with('success', 'Removed from the 404 log.');
    }

    public function clearNotFound()
    {
        NotFoundLog::query()->delete();

        return back()->with('success', '404 log cleared.');
    }

    /**
     * Validated, normalized attributes for a redirect. $allowExistingSource: the CSV import
     * updates an existing redirect for the same source instead of rejecting it.
     */
    private function prepare(array $input, ?Redirect $current = null, bool $allowExistingSource = false): array
    {
        $fail = fn (string $field, string $message) => throw ValidationException::withMessages([$field => $message]);

        $source = Redirect::normalizeSource($input['source'] ?? '');
        if (!$source) {
            $fail('source', 'Enter the old address — a full mitavin.com link or a path like /old-page.');
        }
        if (Redirect::isProtected($source)) {
            $fail('source', "{$source} can't be redirected — the homepage, admin, login, cart and checkout always stay as they are.");
        }
        $taken = Redirect::where('source', $source)->when($current, fn ($q) => $q->whereKeyNot($current->id))->exists();
        if ($taken && !$allowExistingSource) {
            $fail('source', "A redirect for {$source} already exists — edit that one instead.");
        }

        $code = (int) ($input['status_code'] ?? 301);
        if (!in_array($code, [301, 302, 410], true)) {
            $fail('status_code', 'Choose 301, 302 or 410.');
        }

        $target = null;
        if ($code !== 410) {
            $target = Redirect::normalizeTarget($input['target'] ?? '');
            if (!$target) {
                $fail('target', 'Enter the new address — a full link or a path like /products/new-name.');
            }

            // Collapse chains: follow the target through any redirect it already has (max 5 hops).
            for ($hop = 0; $hop < 5; $hop++) {
                $asSource = Redirect::targetAsSource($target);
                if ($asSource === $source) {
                    $fail('target', 'That would send the address to itself (a redirect loop).');
                }
                $next = $asSource ? Redirect::where('source', $asSource)->where('is_active', true)->first() : null;
                if (!$next) {
                    break;
                }
                if ($next->status_code === 410 || !$next->target) {
                    $fail('target', "{$asSource} is marked as gone (410) — point to a live page instead.");
                }
                $target = $next->target;
            }
        }

        return [
            'source' => $source,
            'target' => $target,
            'status_code' => $code,
            'note' => mb_substr(trim(strip_tags((string) ($input['note'] ?? ''))), 0, 255) ?: null,
        ];
    }

    /** Re-point redirects that targeted this source straight to its new target; clear it from the 404 log. */
    private function afterSave(array $data): void
    {
        if ($data['target']) {
            Redirect::where('source', '!=', $data['source'])->get()
                ->filter(fn ($r) => Redirect::targetAsSource($r->target) === $data['source'])
                ->each(fn ($r) => $r->update(['target' => $data['target']]));
        }
        NotFoundLog::where('path', explode('?', $data['source'])[0])->delete();
    }
}
