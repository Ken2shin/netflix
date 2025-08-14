// Netflix Main JavaScript
document.addEventListener("DOMContentLoaded", () => {
  // Header scroll effect
  const header = document.getElementById("netflixHeader")

  window.addEventListener("scroll", () => {
    if (window.scrollY > 50) {
      header.classList.add("scrolled")
    } else {
      header.classList.remove("scrolled")
    }
  })

  // Search functionality
  const searchToggle = document.getElementById("searchToggle")
  const searchContainer = document.getElementById("searchContainer")
  const searchInput = document.getElementById("searchInput")
  const searchClose = document.getElementById("searchClose")

  if (searchToggle) {
    searchToggle.addEventListener("click", () => {
      searchContainer.classList.add("active")
      searchInput.focus()
    })
  }

  if (searchClose) {
    searchClose.addEventListener("click", () => {
      searchContainer.classList.remove("active")
      searchInput.value = ""
    })
  }

  // Search input functionality
  if (searchInput) {
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter" && this.value.trim()) {
        window.location.href = `search.php?q=${encodeURIComponent(this.value.trim())}`
      }
    })
  }

  // Initialize carousels
  initializeCarousels()

  // Content actions
  setupContentActions()
})

// Carousel functionality
function initializeCarousels() {
  const carousels = document.querySelectorAll(".content-carousel")

  carousels.forEach((carousel) => {
    const track = carousel.querySelector(".carousel-track")
    const prevBtn = carousel.querySelector(".prev-btn")
    const nextBtn = carousel.querySelector(".next-btn")
    const items = carousel.querySelectorAll(".content-item")

    if (!track || items.length === 0) return

    let currentIndex = 0
    const itemsPerView = getItemsPerView()
    const maxIndex = Math.max(0, items.length - itemsPerView)

    // Update button visibility
    function updateButtons() {
      prevBtn.style.display = currentIndex > 0 ? "flex" : "none"
      nextBtn.style.display = currentIndex < maxIndex ? "flex" : "none"
    }

    // Move carousel
    function moveCarousel(direction) {
      if (direction === "next" && currentIndex < maxIndex) {
        currentIndex++
      } else if (direction === "prev" && currentIndex > 0) {
        currentIndex--
      }

      const itemWidth = items[0].offsetWidth + 4 // 4px gap
      const translateX = -currentIndex * itemWidth
      track.style.transform = `translateX(${translateX}px)`

      updateButtons()
    }

    // Event listeners
    if (prevBtn) {
      prevBtn.addEventListener("click", () => moveCarousel("prev"))
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", () => moveCarousel("next"))
    }

    // Touch/swipe support
    let startX = 0
    let isDragging = false

    track.addEventListener("touchstart", (e) => {
      startX = e.touches[0].clientX
      isDragging = true
    })

    track.addEventListener("touchmove", (e) => {
      if (!isDragging) return
      e.preventDefault()
    })

    track.addEventListener("touchend", (e) => {
      if (!isDragging) return

      const endX = e.changedTouches[0].clientX
      const diff = startX - endX

      if (Math.abs(diff) > 50) {
        if (diff > 0) {
          moveCarousel("next")
        } else {
          moveCarousel("prev")
        }
      }

      isDragging = false
    })

    // Initialize
    updateButtons()

    // Update on resize
    window.addEventListener("resize", () => {
      const newItemsPerView = getItemsPerView()
      const newMaxIndex = Math.max(0, items.length - newItemsPerView)

      if (currentIndex > newMaxIndex) {
        currentIndex = newMaxIndex
        const itemWidth = items[0].offsetWidth + 4
        const translateX = -currentIndex * itemWidth
        track.style.transform = `translateX(${translateX}px)`
      }

      updateButtons()
    })
  })
}

// Get items per view based on screen size
function getItemsPerView() {
  const width = window.innerWidth
  if (width >= 1200) return 6
  if (width >= 992) return 5
  if (width >= 768) return 4
  if (width >= 576) return 3
  return 2
}

// Content actions setup
function setupContentActions() {
  // Play buttons
  document.addEventListener("click", (e) => {
    if (e.target.closest(".play-btn")) {
      const contentCard = e.target.closest(".content-card")
      const playLink = contentCard.querySelector("a")
      if (playLink) {
        window.location.href = playLink.href
      }
    }
  })

  // Add to watchlist buttons
  document.addEventListener("click", (e) => {
    if (e.target.closest(".add-btn")) {
      e.preventDefault()
      const button = e.target.closest(".add-btn")
      const contentId = button.dataset.contentId

      if (contentId) {
        toggleWatchlist(contentId)
      }
    }
  })

  // Info buttons
  document.addEventListener("click", (e) => {
    if (e.target.closest(".info-btn")) {
      e.preventDefault()
      const button = e.target.closest(".info-btn")
      const contentId = button.dataset.contentId

      if (contentId) {
        showContentInfo(contentId)
      }
    }
  })
}

// Global functions
function playContent(contentId, type) {
  if (type === "movie") {
    window.location.href = `play-movie.php?id=${contentId}`
  } else {
    window.location.href = `content.php?id=${contentId}`
  }
}

function toggleWatchlist(contentId) {
  const formData = new FormData()
  formData.append("content_id", contentId)

  fetch("api/toggle-watchlist.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Update button icon
        const buttons = document.querySelectorAll(`[data-content-id="${contentId}"] .add-btn i`)
        buttons.forEach((icon) => {
          if (data.action === "added") {
            icon.className = "fas fa-check"
          } else {
            icon.className = "fas fa-plus"
          }
        })

        // Show notification
        showNotification(data.action === "added" ? "Agregado a Mi lista" : "Eliminado de Mi lista")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("Error al actualizar la lista", "error")
    })
}

function showContentInfo(contentId) {
  window.location.href = `content.php?id=${contentId}`
}

function showNotification(message, type = "success") {
  const notification = document.createElement("div")
  notification.className = `netflix-notification ${type}`
  notification.textContent = message

  notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${type === "error" ? "#e50914" : "#46d369"};
        color: white;
        padding: 12px 20px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        z-index: 9999;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `

  document.body.appendChild(notification)

  // Animate in
  setTimeout(() => {
    notification.style.opacity = "1"
    notification.style.transform = "translateX(0)"
  }, 100)

  // Animate out and remove
  setTimeout(() => {
    notification.style.opacity = "0"
    notification.style.transform = "translateX(100%)"
    setTimeout(() => {
      notification.remove()
    }, 300)
  }, 3000)
}

// Keyboard navigation
document.addEventListener("keydown", (e) => {
  // ESC to close search
  if (e.key === "Escape") {
    const searchContainer = document.getElementById("searchContainer")
    if (searchContainer && searchContainer.classList.contains("active")) {
      searchContainer.classList.remove("active")
    }
  }
})

// Lazy loading for images
function setupLazyLoading() {
  const images = document.querySelectorAll("img[data-src]")

  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target
        img.src = img.dataset.src
        img.removeAttribute("data-src")
        observer.unobserve(img)
      }
    })
  })

  images.forEach((img) => imageObserver.observe(img))
}

// Initialize lazy loading if supported
if ("IntersectionObserver" in window) {
  setupLazyLoading()
}
