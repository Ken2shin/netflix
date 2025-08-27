const CACHE_NAME = "netflix-clone-v1"
const urlsToCache = ["/", "/assets/css/style.css", "/assets/js/notifications.js", "/assets/images/netflix-logo.png"]

self.addEventListener("install", (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(urlsToCache)))
})

self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      if (response) {
        return response
      }
      return fetch(event.request)
    }),
  )
})

// Handle push notifications
self.addEventListener("push", (event) => {
  const options = {
    body: event.data ? event.data.text() : "Nuevo contenido disponible",
    icon: "/assets/images/netflix-logo.png",
    badge: "/assets/images/netflix-logo.png",
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1,
    },
    actions: [
      {
        action: "explore",
        title: "Ver ahora",
        icon: "/assets/images/play-icon.png",
      },
      {
        action: "close",
        title: "Cerrar",
        icon: "/assets/images/close-icon.png",
      },
    ],
  }

  event.waitUntil(self.registration.showNotification("Netflix Clone", options))
})

// Handle notification clicks
self.addEventListener("notificationclick", (event) => {
  event.notification.close()

  if (event.action === "explore") {
    event.waitUntil(clients.openWindow("/"))
  }
})
