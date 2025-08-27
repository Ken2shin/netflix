class NotificationManager {
  constructor() {
    this.ws = null
    this.reconnectAttempts = 0
    this.maxReconnectAttempts = 5
    this.init()
  }

  init() {
    this.requestNotificationPermission()
    this.connectWebSocket()
    this.setupServiceWorker()
  }

  requestNotificationPermission() {
    if ("Notification" in window && Notification.permission === "default") {
      Notification.requestPermission().then((permission) => {
        console.log("[v0] Notification permission:", permission)
      })
    }
  }

  connectWebSocket() {
    try {
      this.ws = new WebSocket("ws://localhost:8080")

      this.ws.onopen = () => {
        console.log("[v0] WebSocket connected for notifications")
        this.reconnectAttempts = 0
      }

      this.ws.onmessage = (event) => {
        const data = JSON.parse(event.data)
        this.handleNotification(data)
      }

      this.ws.onclose = () => {
        console.log("[v0] WebSocket disconnected")
        this.reconnect()
      }

      this.ws.onerror = (error) => {
        console.error("[v0] WebSocket error:", error)
      }
    } catch (error) {
      console.error("[v0] Failed to connect WebSocket:", error)
      this.reconnect()
    }
  }

  reconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++
      setTimeout(() => {
        console.log(`[v0] Reconnecting WebSocket (attempt ${this.reconnectAttempts})`)
        this.connectWebSocket()
      }, 3000 * this.reconnectAttempts)
    }
  }

  handleNotification(data) {
    console.log("[v0] Received notification:", data)

    if (data.type === "new_content") {
      this.showToast({
        title: "¡Nuevo contenido!",
        message: data.message,
        type: "success",
        duration: 5000,
      })

      // Show browser notification if permission granted
      if (Notification.permission === "granted") {
        new Notification(data.title, {
          body: data.message,
          icon: "/assets/images/netflix-logo.png",
          tag: "new-content",
        })
      }
    }
  }

  showToast({ title, message, type = "info", duration = 3000 }) {
    const toast = document.createElement("div")
    toast.className = `toast-notification toast-${type}`
    toast.innerHTML = `
            <div class="toast-header">
                <strong>${title}</strong>
                <button type="button" class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
            <div class="toast-body">${message}</div>
        `

    // Add to page
    let container = document.getElementById("toast-container")
    if (!container) {
      container = document.createElement("div")
      container.id = "toast-container"
      container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 350px;
            `
      document.body.appendChild(container)
    }

    container.appendChild(toast)

    // Auto remove
    setTimeout(() => {
      if (toast.parentElement) {
        toast.remove()
      }
    }, duration)
  }

  setupServiceWorker() {
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker
        .register("/sw.js")
        .then((registration) => {
          console.log("[v0] Service Worker registered:", registration)
        })
        .catch((error) => {
          console.log("[v0] Service Worker registration failed:", error)
        })
    }
  }
}

// Initialize notification manager
document.addEventListener("DOMContentLoaded", () => {
  window.notificationManager = new NotificationManager()
})
