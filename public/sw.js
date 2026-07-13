self.addEventListener('push', function (event) {
    const payload = event.data ? event.data.json() : {};
    const title = payload.title || 'Flovig';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/flovig_logo.webp',
        badge: '/flovig_logo.webp',
        data: payload.data || {},
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const data = event.notification.data || {};
    const url = data.project_id ? `/projects/${data.project_id}` : '/dashboard';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
