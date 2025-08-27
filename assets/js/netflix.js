// Netflix-style JavaScript functionality
document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new window.bootstrap.Tooltip(tooltipTriggerEl))

  // Content row scrolling
  initializeContentRows()

  // Search functionality
  initializeSearch()

  // Video modals
  initializeVideoModals()

  // Watchlist functionality
  initializeWatchlist()
})

function initializeContentRows() {
  const contentRows = document.querySelectorAll(".content-row")

  contentRows.forEach((row) => {
    const scrollContainer = row.querySelector(".row-content") || row
    const leftBtn = row.querySelector(".scroll-btn.left")
    const rightBtn = row.querySelector(".scroll-btn.right")

    if (leftBtn && rightBtn) {
      leftBtn.addEventListener("click", () => {
        scrollContainer.scrollBy({ left: -300, behavior: "smooth" })
      })

      rightBtn.addEventListener("click", () => {
        scrollContainer.scrollBy({ left: 300, behavior: "smooth" })
      })

      // Update button visibility
      scrollContainer.addEventListener("scroll", () => {
        updateScrollButtons(scrollContainer, leftBtn, rightBtn)
      })

      // Initial button state
      updateScrollButtons(scrollContainer, leftBtn, rightBtn)
    }
  })
}

function updateScrollButtons(container, leftBtn, rightBtn) {
  const scrollLeft = container.scrollLeft
  const maxScroll = container.scrollWidth - container.clientWidth

  leftBtn.style.display = scrollLeft > 0 ? "flex" : "none"
  rightBtn.style.display = scrollLeft < maxScroll - 1 ? "flex" : "none"
}

function initializeSearch() {
  const searchInput = document.getElementById("searchInput")
  const searchResults = document.getElementById("searchResults")

  if (searchInput && searchResults) {
    let searchTimeout

    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout)
      const query = this.value.trim()

      if (query.length >= 2) {
        searchTimeout = setTimeout(() => {
          performSearch(query)
        }, 300)
      } else {
        searchResults.innerHTML = ""
        searchResults.style.display = "none"
      }
    })

    // Hide results when clicking outside
    document.addEventListener("click", (e) => {
      if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = "none"
      }
    })
  }
}

function performSearch(query) {
  fetch(`api/search.php?q=${encodeURIComponent(query)}`)
    .then((response) => response.json())
    .then((data) => {
      displaySearchResults(data)
    })
    .catch((error) => {
      console.error("Search error:", error)
    })
}

function displaySearchResults(results) {
  const searchResults = document.getElementById("searchResults")

  if (results.length === 0) {
    searchResults.innerHTML = '<div class="search-no-results">No se encontraron resultados</div>'
  } else {
    let html = ""
    results.forEach((item) => {
      html += `
                <div class="search-result-item" onclick="window.location.href='content-details.php?id=${item.id}'">
                    <img src="${item.poster_image ? "uploads/posters/" + item.poster_image : "/placeholder.svg?height=60&width=40"}" alt="${item.title}">
                    <div class="search-result-info">
                        <h6>${item.title}</h6>
                        <p>${item.release_year} • ${item.type === "movie" ? "Película" : "Serie"}</p>
                    </div>
                </div>
            `
    })
    searchResults.innerHTML = html
  }

  searchResults.style.display = "block"
}

function initializeVideoModals() {
  const videoModal = document.getElementById("videoModal")

  if (videoModal) {
    videoModal.addEventListener("hidden.bs.modal", function () {
      // Stop any playing videos when modal closes
      const videoContainer = this.querySelector("#videoPlayer")
      if (videoContainer) {
        videoContainer.innerHTML = ""
      }
    })
  }
}

function initializeWatchlist() {
  const watchlistBtns = document.querySelectorAll(".btn-watchlist")

  watchlistBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const contentId = this.dataset.contentId
      toggleWatchlist(contentId, this)
    })
  })
}

function toggleWatchlist(contentId, button) {
  fetch("api/toggle-watchlist.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `content_id=${contentId}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const icon = button.querySelector("i")
        const text = button.querySelector(".btn-text")

        if (data.added) {
          icon.className = "fas fa-check"
          if (text) text.textContent = "En mi lista"
          button.classList.add("added")
        } else {
          icon.className = "fas fa-plus"
          if (text) text.textContent = "Mi lista"
          button.classList.remove("added")
        }
      }
    })
    .catch((error) => {
      console.error("Watchlist error:", error)
    })
}

function playContent(contentId, type) {
  if (type === "movie") {
    window.location.href = `play-movie.php?id=${contentId}`
  } else {
    // For series, play first episode
    fetch(`api/get-episodes.php?content_id=${contentId}&limit=1`)
      .then((response) => response.json())
      .then((data) => {
        if (data.length > 0) {
          window.location.href = `play-episode.php?id=${data[0].id}`
        }
      })
  }
}

function showTrailer(videoUrl, title) {
  const modal = new window.bootstrap.Modal(document.getElementById("videoModal"))
  const videoContainer = document.getElementById("videoPlayer")

  // Determine video type and create appropriate player
  if (videoUrl.includes("youtube.com") || videoUrl.includes("youtu.be")) {
    const videoId = extractYouTubeId(videoUrl)
    videoContainer.innerHTML = `
            <iframe width="100%" height="400" 
                src="https://www.youtube.com/embed/${videoId}?autoplay=1" 
                frameborder="0" allowfullscreen>
            </iframe>
        `
  } else if (videoUrl.includes("vimeo.com")) {
    const videoId = extractVimeoId(videoUrl)
    videoContainer.innerHTML = `
            <iframe width="100%" height="400" 
                src="https://player.vimeo.com/video/${videoId}?autoplay=1" 
                frameborder="0" allowfullscreen>
            </iframe>
        `
  } else {
    // Direct video file
    videoContainer.innerHTML = `
            <video width="100%" height="400" controls autoplay>
                <source src="${videoUrl}" type="video/mp4">
                Tu navegador no soporta el elemento video.
            </video>
        `
  }

  modal.show()
}

function extractYouTubeId(url) {
  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/
  const match = url.match(regExp)
  return match && match[2].length === 11 ? match[2] : null
}

function extractVimeoId(url) {
  const regExp = /vimeo.com\/(\d+)/
  const match = url.match(regExp)
  return match ? match[1] : null
}

// Utility functions
function formatDuration(minutes) {
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60

  if (hours > 0) {
    return `${hours}h ${mins}m`
  } else {
    return `${mins}m`
  }
}

function showNotification(message, type = "info") {
  const notification = document.createElement("div")
  notification.className = `alert alert-${type} notification`
  notification.textContent = message

  document.body.appendChild(notification)

  setTimeout(() => {
    notification.classList.add("show")
  }, 100)

  setTimeout(() => {
    notification.classList.remove("show")
    setTimeout(() => {
      document.body.removeChild(notification)
    }, 300)
  }, 3000)
}

// Rating functionality
function rateContent(contentId, rating) {
  fetch("api/rate-content.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `content_id=${contentId}&rating=${rating}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification("Calificación guardada", "success")
      }
    })
    .catch((error) => {
      console.error("Rating error:", error)
    })
}
