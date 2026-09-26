<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAlert;
use App\Models\PushSubscription;
use App\Services\AdminAlerts\AdminAlerts;
use App\Services\AdminAlerts\WebPushSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminAlertController extends Controller
{
    /**
     * A browser's push endpoint is a URL this server will later POST to. Accepting an
     * arbitrary one would let any admin-panel account turn "subscribe" into a blind
     * server-side request to whatever host they like (internal services included), so only
     * the real browser push services are accepted.
     */
    private const PUSH_HOST_SUFFIXES = [
        '.googleapis.com',          // Chrome, Edge, Opera, Brave, Samsung Internet (FCM)
        '.mozilla.com',             // Firefox
        '.mozaws.net',              // Firefox (legacy autopush)
        '.push.apple.com',          // Safari / iOS home-screen apps
        '.notify.windows.com',      // legacy Edge / Windows
    ];

    public function index(Request $request): View
    {
        $query = AdminAlert::forUser($request->user()->id)->latest('id');

        if ($request->query('filter') === 'unread') {
            $query->unread();
        }

        return view('admin.alerts.index', [
            'alerts' => $query->paginate(25)->withQueryString(),
            'unread' => AdminAlert::forUser($request->user()->id)->unread()->count(),
            'filter' => $request->query('filter') === 'unread' ? 'unread' : 'all',
        ]);
    }

    /** Polled by every open admin page — kept to two cheap indexed queries. */
    public function feed(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $items = AdminAlert::forUser($userId)->latest('id')->limit(15)->get();

        return response()->json([
            'unread'    => AdminAlert::forUser($userId)->unread()->count(),
            'latest_id' => (int) ($items->first()?->id ?? 0),
            'items'     => $items->map->toFeedItem()->values(),
            'push'      => [
                'configured' => WebPushSender::isConfigured(),
                'devices'    => PushSubscription::where('scope', 'admin')->where('user_id', $userId)->count(),
            ],
        ]);
    }

    /** Mark-as-read then forward — the one URL a bell click, toast, and phone notification all use. */
    public function open(Request $request, AdminAlert $alert): RedirectResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 404);

        $alert->read_at ??= now();
        $alert->save();

        return redirect()->to($alert->url ?: route('admin.dashboard'));
    }

    public function read(Request $request, AdminAlert $alert): JsonResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 404);

        $alert->read_at ??= now();
        $alert->save();

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        AdminAlert::forUser($request->user()->id)->unread()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        // Validated by hand instead of $request->validate(): bootstrap/app.php only renders
        // JSON errors for api/* paths, so a failed validate() here would silently redirect
        // (and the browser would see a "200 OK" login/dashboard page and assume it worked).
        $validator = Validator::make($request->all(), [
            'endpoint'    => ['required', 'url', 'max:1000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth'   => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        $host = strtolower((string) parse_url($data['endpoint'], PHP_URL_HOST));
        $allowed = str_starts_with($data['endpoint'], 'https://')
            && collect(self::PUSH_HOST_SUFFIXES)->contains(fn ($suffix) => str_ends_with('.' . $host, $suffix));

        if (! $allowed) {
            return response()->json(['message' => "This browser's push service is not supported."], 422);
        }

        $agent = (string) $request->userAgent();

        // Keyed by endpoint (not user): if a second admin signs in on the same browser
        // profile, the subscription moves to them instead of duplicating or leaking the
        // previous person's alerts to whoever is now using the phone.
        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id'          => $request->user()->id,
                'scope'            => 'admin',
                'type'             => 'web',
                'endpoint'         => $data['endpoint'],
                'p256dh'           => $data['keys']['p256dh'],
                'auth'             => $data['keys']['auth'],
                'content_encoding' => 'aes128gcm',
                'device_type'      => preg_match('/Mobile|Android|iPhone|iPad/i', $agent) ? 'mobile' : 'desktop',
                'browser'          => $this->browserName($agent),
                'user_agent'       => mb_substr($agent, 0, 255),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint');

        PushSubscription::where('scope', 'admin')
            ->where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $endpoint))
            ->delete();

        return response()->json(['ok' => true]);
    }

    /** "Send me a test" — to the current admin only, so testing never pings the rest of the team. */
    public function test(Request $request): JsonResponse
    {
        $user = $request->user();

        AdminAlerts::notify(
            type: 'test',
            title: 'Test alert',
            body: 'Sound and phone alerts are working.',
            url: route('admin.alerts.index'),
            onlyTo: collect([$user]),
        );

        return response()->json([
            'ok'         => true,
            'configured' => WebPushSender::isConfigured(),
            'devices'    => PushSubscription::where('scope', 'admin')->where('user_id', $user->id)->count(),
        ]);
    }

    /**
     * Public on purpose (like the manifest): the browser fetches this itself, with no
     * session guarantees, when installing/updating the worker. It contains nothing secret.
     * Served from /admin/ so its default scope is the admin panel only.
     */
    public function serviceWorker(): Response
    {
        return response(view('admin.alerts.service-worker')->render(), 200, [
            'Content-Type'           => 'application/javascript; charset=utf-8',
            'Cache-Control'          => 'no-cache, max-age=0',
            'Service-Worker-Allowed' => '/admin/',
        ]);
    }

    private function browserName(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg/')           => 'Edge',
            str_contains($agent, 'OPR/')           => 'Opera',
            str_contains($agent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($agent, 'Firefox/')       => 'Firefox',
            str_contains($agent, 'Chrome/')        => 'Chrome',
            str_contains($agent, 'Safari/')        => 'Safari',
            default                                => 'Unknown',
        };
    }
}
