<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;

/**
 * Admin → Security → Error Log: the server's recent errors from storage/logs, grouped by message
 * with how often and when last — so a problem (a page answering "Something went wrong") can be
 * seen and reported without opening the hosting file manager. Read-only; reads only the tail of
 * the newest log files, so a large log never slows the page.
 */
class ErrorLogController extends Controller
{
    private const TAIL_BYTES = 1_500_000;

    private const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning'];

    public function index()
    {
        $showWarnings = request()->boolean('warnings');
        $levels = $showWarnings ? self::LEVELS : array_diff(self::LEVELS, ['warning']);
        $files = collect(glob(storage_path('logs/*.log')) ?: [])->sortByDesc(fn ($f) => filemtime($f))->take(2)->values();

        $groups = [];
        foreach ($files as $file) {
            foreach ($this->entries($file) as $e) {
                if (!in_array($e['level'], $levels, true)) {
                    continue;
                }
                // the same error with different ids / numbers counts as one
                // (the query's own values — a product slug, an id — don't make it a different problem)
                $sig = preg_replace(['/\(Connection:.*$/s', "/'[^']*'|\"[^\"]*\"|`[^`]*`/", '/\d+/'], ['', "'…'", '#'], $e['message']);
                $key = $e['level'] . '|' . mb_substr(trim($sig), 0, 300);
                $g = $groups[$key] ?? ['level' => $e['level'], 'message' => $e['message'], 'where' => $e['where'], 'count' => 0, 'first' => $e['at'], 'last' => $e['at'], 'urls' => []];
                $g['count']++;
                $g['first'] = min($g['first'], $e['at']);
                if ($e['at'] >= $g['last']) {
                    $g['last'] = $e['at'];
                    $g['message'] = $e['message'];
                    $g['where'] = $e['where'] ?: $g['where'];
                }
                if ($e['url'] && count($g['urls']) < 3 && !in_array($e['url'], $g['urls'], true)) {
                    $g['urls'][] = $e['url'];
                }
                $groups[$key] = $g;
            }
        }
        $groups = collect($groups)->sortByDesc('last')->take(60)->values();

        $since24h = now()->subDay()->format('Y-m-d H:i:s');

        return view('admin.error-log.index', [
            'groups' => $groups,
            'files' => $files->map(fn ($f) => ['name' => basename($f), 'size' => filesize($f), 'modified' => Carbon::createFromTimestamp(filemtime($f))]),
            'last24h' => $groups->where('last', '>=', $since24h)->sum('count'),
            'showWarnings' => $showWarnings,
        ]);
    }

    /** [{at, level, message, where, url}] from the end of one log file. */
    private function entries(string $file): array
    {
        $size = filesize($file);
        $fh = @fopen($file, 'rb');
        if (!$fh || !$size) {
            return [];
        }
        fseek($fh, max(0, $size - self::TAIL_BYTES));
        $text = (string) stream_get_contents($fh);
        fclose($fh);

        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})/m', $text) ?: [];
        $out = [];
        foreach ($parts as $chunk) {
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2}:\d{2})[^\]]*\] [\w-]+\.(\w+): (.*)/s', $chunk, $m)) {
                continue;
            }
            $body = $m[4];
            $first = strtok($body, "\n");
            // drop the trailing JSON context ("{"exception":…}") from the one-line message
            $message = trim(preg_replace('/ \{"(?:exception|userId|url).*$/s', '', $first));
            preg_match('/#\d+ (\S*\/app\/[^\s(]+)\((\d+)\)/', $body, $w);
            preg_match('/"url":"([^"]+)"/', $body, $u);
            $out[] = [
                'at' => $m[1] . ' ' . $m[2],
                'level' => strtolower($m[3]),
                'message' => mb_substr($message, 0, 600),
                'where' => $w ? preg_replace('#^.*?/(app/)#', '$1', $w[1]) . ':' . $w[2] : null,
                'url' => $u[1] ?? null,
            ];
        }

        return $out;
    }
}
