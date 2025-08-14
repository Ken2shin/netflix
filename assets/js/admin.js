// JavaScript para el panel de administración
document.addEventListener("DOMContentLoaded", () => {
  // Toggle sidebar en móvil
  const sidebarToggle = document.getElementById("sidebarToggle")
  const sidebar = document.querySelector(".sidebar")

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("show")
    })
  }

  // Cerrar sidebar al hacer click fuera en móvil
  document.addEventListener("click", (e) => {
    if (window.innerWidth <= 768) {
      if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove("show")
      }
    }
  })

  // Marcar enlace activo en sidebar
  const currentPath = window.location.pathname
  const navLinks = document.querySelectorAll(".sidebar-nav .nav-link")

  navLinks.forEach((link) => {
    link.classList.remove("active")
    if (link.getAttribute("href") && currentPath.includes(link.getAttribute("href"))) {
      link.classList.add("active")
    }
  })

  // Auto-hide alerts
  const alerts = document.querySelectorAll(".alert")
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0"
      setTimeout(() => {
        alert.remove()
      }, 300)
    }, 5000)
  })

  // File upload preview
  const fileInputs = document.querySelectorAll('input[type="file"]')
  fileInputs.forEach((input) => {
    input.addEventListener("change", (e) => {
      const file = e.target.files[0]
      if (file) {
        const reader = new FileReader()
        reader.onload = (e) => {
          // Crear preview si es imagen
          if (file.type.startsWith("image/")) {
            let preview = input.parentNode.querySelector(".file-preview")
            if (!preview) {
              preview = document.createElement("div")
              preview.className = "file-preview mt-2"
              input.parentNode.appendChild(preview)
            }
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 4px;">`
          }
        }
        reader.readAsDataURL(file)
      }
    })
  })

  // Confirmación para acciones destructivas
  const deleteButtons = document.querySelectorAll(".btn-danger, .delete-btn")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      if (!confirm("¿Estás seguro de que quieres realizar esta acción?")) {
        e.preventDefault()
      }
    })
  })

  // Tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  const bootstrap = window.bootstrap // Declare the bootstrap variable
  tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))
})

// Función para mostrar notificaciones
function showNotification(message, type = "success") {
  const notification = document.createElement("div")
  notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`
  notification.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;"
  notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `

  document.body.appendChild(notification)

  setTimeout(() => {
    notification.remove()
  }, 5000)
}

// Función para formatear números
function formatNumber(num) {
  return new Intl.NumberFormat("es-ES").format(num)
}

// Función para formatear fechas
function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString("es-ES", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })
}
