/*
 * Admin alerts service worker — receives Web Push messages (new order, new subscriber) and
 * shows them as system notifications, even when the admin app/tab is closed.
 * Served by AdminAlertController@serviceWorker at /admin/sw.js (scope: /admin/ only).
 */
const ADMIN_HOME = @json(url('/admin'));

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

const adminWindows = async () => {
    const all = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    return all.filter((c) => new URL(c.url).pathname.startsWith('/admin'));
};

self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'New alert', body: event.data ? event.data.text() : '' };
    }

    event.waitUntil((async () => {
        const windows = await adminWindows();

        // An admin page is open and in front: it already plays the sound and shows its own
        // toast, so a second system banner would just be a duplicate. Nudge it to fetch now.
        const visible = windows.filter((c) => c.visibilityState === 'visible');
        if (visible.length) {
            visible.forEach((c) => c.postMessage({ type: 'alert-arrived' }));
            return;
        }

        const icon = data.icon || undefined;
        await self.registration.showNotification(data.title || 'New alert', {
            body: data.body || '',
            icon,
            badge: icon,
            tag: data.tag || undefined,
            renotify: true,
            // Orders are the ones worth not missing — they stay on screen until dealt with.
            requireInteraction: data.type === 'order',
            vibrate: data.type === 'order' ? [220, 110, 220, 110, 420] : [180, 90, 180],
            timestamp: Date.now(),
            data: { url: data.url || ADMIN_HOME },
        });
    })());
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || ADMIN_HOME;

    event.waitUntil((async () => {
        const windows = await adminWindows();
        for (const client of windows) {
            if ('focus' in client) {
                await client.focus();
                // The page navigates itself: WindowClient.navigate() is not allowed for
                // clients this worker doesn't control yet.
                client.postMessage({ type: 'navigate', url: target });
                return;
            }
        }
        await self.clients.openWindow(target);
    })());
});
