class SearchManager {
  constructor() {
    this.searchTimeout = null
    this.currentQuery = ""
    this.currentType = "all"
    this.init()
  }

  init() {
    this.bindEvents()
    this.loadUrlParams()
  }

  bindEvents() {
    // Búsqueda en tiempo real en el header
    const searchInput = document.getElementById("search-input")
    if (searchInput) {
      searchInput.addEventListener("input", (e) => {
        this.handleSearchInput(e.target.value)
      })

      searchInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          this.performSearch(e.target.value)
        }
      })
    }

    // Filtros de tipo de contenido
    document.querySelectorAll(".filter-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        this.handleFilterChange(e.target.dataset.type)
      })
    })

    // Sugerencias populares
    document.querySelectorAll(".popular-tag").forEach((tag) => {
      tag.addEventListener("click", (e) => {
        this.performSearch(e.target.textContent.trim())
      })
    })
  }

  handleSearchInput(query) {
    clearTimeout(this.searchTimeout)

    if (query.length >= 2) {
      this.searchTimeout = setTimeout(() => {
        this.showSuggestions(query)
      }, 300)
    } else {
      this.hideSuggestions()
    }
  }

  async showSuggestions(query) {
    try {
      const response = await fetch(`api/suggestions.php?q=${encodeURIComponent(query)}`)
      const suggestions = await response.json()

      this.renderSuggestions(suggestions)
    } catch (error) {
      console.error("Error al obtener sugerencias:", error)
    }
  }

  renderSuggestions(suggestions) {
    let suggestionsContainer = document.getElementById("search-suggestions")

    if (!suggestionsContainer) {
      suggestionsContainer = document.createElement("div")
      suggestionsContainer.id = "search-suggestions"
      suggestionsContainer.className = "search-suggestions"

      const searchInput = document.getElementById("search-input")
      searchInput.parentNode.appendChild(suggestionsContainer)
    }

    if (suggestions.length === 0) {
      suggestionsContainer.style.display = "none"
      return
    }

    const suggestionsHTML = suggestions
      .map(
        (item) => `
            <div class="suggestion-item" onclick="searchContent('${item.title}')">
                <img src="${item.poster_url}" alt="${item.title}" class="suggestion-poster">
                <div class="suggestion-info">
                    <div class="suggestion-title">${item.title}</div>
                    <div class="suggestion-type">${item.type === "movie" ? "Película" : "Serie"}</div>
                </div>
            </div>
        `,
      )
      .join("")

    suggestionsContainer.innerHTML = suggestionsHTML
    suggestionsContainer.style.display = "block"
  }

  hideSuggestions() {
    const suggestionsContainer = document.getElementById("search-suggestions")
    if (suggestionsContainer) {
      suggestionsContainer.style.display = "none"
    }
  }

  performSearch(query) {
    if (!query.trim()) return

    this.hideSuggestions()
    window.location.href = `search.php?q=${encodeURIComponent(query)}&type=${this.currentType}`
  }

  handleFilterChange(type) {
    this.currentType = type

    // Actualizar botones activos
    document.querySelectorAll(".filter-btn").forEach((btn) => {
      btn.classList.remove("active")
    })
    document.querySelector(`[data-type="${type}"]`).classList.add("active")

    // Realizar nueva búsqueda si hay query
    const urlParams = new URLSearchParams(window.location.search)
    const currentQuery = urlParams.get("q")

    if (currentQuery) {
      window.location.href = `search.php?q=${encodeURIComponent(currentQuery)}&type=${type}`
    }
  }

  loadUrlParams() {
    const urlParams = new URLSearchParams(window.location.search)
    const type = urlParams.get("type") || "all"
    this.currentType = type

    // Activar filtro correcto
    document.querySelectorAll(".filter-btn").forEach((btn) => {
      btn.classList.remove("active")
    })
    const activeBtn = document.querySelector(`[data-type="${type}"]`)
    if (activeBtn) {
      activeBtn.classList.add("active")
    }
  }
}

// Funciones globales para compatibilidad
function searchContent(query) {
  window.location.href = `search.php?q=${encodeURIComponent(query)}`
}

function playContent(contentId, type) {
  if (type === "movie") {
    window.location.href = `play-movie.php?id=${contentId}`
  } else {
    window.location.href = `play-episode.php?content_id=${contentId}`
  }
}

function showContentInfo(contentId) {
  window.location.href = `content-details.php?id=${contentId}`
}

function toggleWatchlist(contentId) {
  fetch("api/toggle-watchlist.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ content_id: contentId }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Actualizar UI
        const btn = event.target.closest(".btn-watchlist")
        const icon = btn.querySelector("i")

        if (data.added) {
          icon.className = "fas fa-check"
          btn.title = "Quitar de Mi Lista"
        } else {
          icon.className = "fas fa-plus"
          btn.title = "Agregar a Mi Lista"
        }
      }
    })
    .catch((error) => console.error("Error:", error))
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  new SearchManager()
})

// Cerrar sugerencias al hacer clic fuera
document.addEventListener("click", (e) => {
  const searchContainer = e.target.closest(".search-container")
  const suggestionsContainer = document.getElementById("search-suggestions")

  if (!searchContainer && suggestionsContainer) {
    suggestionsContainer.style.display = "none"
  }
})
