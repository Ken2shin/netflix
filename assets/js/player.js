// Reproductor de video estilo Netflix
class NetflixPlayer {
  constructor(config) {
    this.config = config
    this.video = document.getElementById("videoPlayer")
    this.container = document.getElementById("videoContainer")
    this.controls = document.getElementById("videoControls")
    this.isPlaying = false
    this.isMuted = false
    this.currentVolume = 1
    this.progressUpdateInterval = null
    this.inactivityTimer = null
    this.nextEpisodeTimer = null

    this.init()
  }

  init() {
    this.setupEventListeners()
    this.setupProgressBar()
    this.setupVolumeControl()
    this.setupKeyboardControls()
    this.setupInactivityTimer()

    // Establecer tiempo inicial si hay progreso guardado
    if (this.config.startTime > 0) {
      this.video.currentTime = this.config.startTime
    }

    // Configurar actualización automática de progreso
    this.startProgressUpdates()
  }

  setupEventListeners() {
    // Video events
    this.video.addEventListener("loadedmetadata", () => {
      this.updateTimeDisplay()
      this.hideLoading()
    })

    this.video.addEventListener("play", () => {
      this.isPlaying = true
      this.updatePlayButton()
    })

    this.video.addEventListener("pause", () => {
      this.isPlaying = false
      this.updatePlayButton()
    })

    this.video.addEventListener("timeupdate", () => {
      this.updateProgress()
      this.checkForNextEpisode()
    })

    this.video.addEventListener("ended", () => {
      this.handleVideoEnd()
    })

    this.video.addEventListener("waiting", () => {
      this.showLoading()
    })

    this.video.addEventListener("canplay", () => {
      this.hideLoading()
    })

    // Control buttons
    document.getElementById("centerPlayBtn").addEventListener("click", () => {
      this.togglePlay()
    })

    document.getElementById("playPauseBtn").addEventListener("click", () => {
      this.togglePlay()
    })

    document.getElementById("rewindBtn").addEventListener("click", () => {
      this.rewind()
    })

    document.getElementById("forwardBtn").addEventListener("click", () => {
      this.forward()
    })

    document.getElementById("fullscreenBtn").addEventListener("click", () => {
      this.toggleFullscreen()
    })

    document.getElementById("volumeBtn").addEventListener("click", () => {
      this.toggleMute()
    })

    // Quality and subtitles
    document.getElementById("qualityBtn").addEventListener("click", () => {
      this.toggleQualityMenu()
    })

    document.getElementById("subtitlesBtn").addEventListener("click", () => {
      this.toggleSubtitlesMenu()
    })

    // Next episode button
    const nextBtn = document.getElementById("nextEpisodeBtn")
    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        this.playNextEpisode()
      })
    }

    // Mouse movement for controls
    this.container.addEventListener("mousemove", () => {
      this.showControls()
      this.resetInactivityTimer()
    })

    this.container.addEventListener("mouseleave", () => {
      this.hideControls()
    })

    // Click to play/pause
    this.video.addEventListener("click", () => {
      this.togglePlay()
    })
  }

  setupProgressBar() {
    const progressBar = document.getElementById("progressBar")
    const progressFilled = document.getElementById("progressFilled")
    const progressHandle = document.getElementById("progressHandle")

    progressBar.addEventListener("click", (e) => {
      const rect = progressBar.getBoundingClientRect()
      const percent = (e.clientX - rect.left) / rect.width
      this.video.currentTime = percent * this.video.duration
    })

    // Drag functionality
    let isDragging = false

    progressHandle.addEventListener("mousedown", (e) => {
      isDragging = true
      e.preventDefault()
    })

    document.addEventListener("mousemove", (e) => {
      if (isDragging) {
        const rect = progressBar.getBoundingClientRect()
        let percent = (e.clientX - rect.left) / rect.width
        percent = Math.max(0, Math.min(1, percent))
        this.video.currentTime = percent * this.video.duration
      }
    })

    document.addEventListener("mouseup", () => {
      isDragging = false
    })
  }

  setupVolumeControl() {
    const volumeRange = document.getElementById("volumeRange")

    volumeRange.addEventListener("input", (e) => {
      const volume = e.target.value / 100
      this.video.volume = volume
      this.currentVolume = volume
      this.updateVolumeIcon()
    })
  }

  setupKeyboardControls() {
    document.addEventListener("keydown", (e) => {
      switch (e.code) {
        case "Space":
          e.preventDefault()
          this.togglePlay()
          break
        case "ArrowLeft":
          e.preventDefault()
          this.rewind()
          break
        case "ArrowRight":
          e.preventDefault()
          this.forward()
          break
        case "ArrowUp":
          e.preventDefault()
          this.volumeUp()
          break
        case "ArrowDown":
          e.preventDefault()
          this.volumeDown()
          break
        case "KeyF":
          e.preventDefault()
          this.toggleFullscreen()
          break
        case "KeyM":
          e.preventDefault()
          this.toggleMute()
          break
        case "Escape":
          if (document.fullscreenElement) {
            document.exitFullscreen()
          }
          break
      }
    })
  }

  setupInactivityTimer() {
    this.resetInactivityTimer()
  }

  resetInactivityTimer() {
    clearTimeout(this.inactivityTimer)
    this.container.classList.remove("inactive")

    this.inactivityTimer = setTimeout(() => {
      if (this.isPlaying) {
        this.container.classList.add("inactive")
      }
    }, 3000)
  }

  togglePlay() {
    if (this.video.paused) {
      this.video.play()
    } else {
      this.video.pause()
    }
  }

  updatePlayButton() {
    const centerBtn = document.getElementById("centerPlayBtn")
    const playPauseBtn = document.getElementById("playPauseBtn")

    if (this.isPlaying) {
      centerBtn.innerHTML = '<i class="fas fa-pause"></i>'
      playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>'
    } else {
      centerBtn.innerHTML = '<i class="fas fa-play"></i>'
      playPauseBtn.innerHTML = '<i class="fas fa-play"></i>'
    }
  }

  rewind() {
    this.video.currentTime = Math.max(0, this.video.currentTime - 10)
  }

  forward() {
    this.video.currentTime = Math.min(this.video.duration, this.video.currentTime + 10)
  }

  toggleMute() {
    if (this.isMuted) {
      this.video.volume = this.currentVolume
      this.isMuted = false
    } else {
      this.currentVolume = this.video.volume
      this.video.volume = 0
      this.isMuted = true
    }
    this.updateVolumeIcon()
  }

  updateVolumeIcon() {
    const volumeBtn = document.getElementById("volumeBtn")
    const volume = this.video.volume

    if (volume === 0 || this.isMuted) {
      volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>'
    } else if (volume < 0.5) {
      volumeBtn.innerHTML = '<i class="fas fa-volume-down"></i>'
    } else {
      volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>'
    }
  }

  volumeUp() {
    this.video.volume = Math.min(1, this.video.volume + 0.1)
    this.updateVolumeIcon()
    document.getElementById("volumeRange").value = this.video.volume * 100
  }

  volumeDown() {
    this.video.volume = Math.max(0, this.video.volume - 0.1)
    this.updateVolumeIcon()
    document.getElementById("volumeRange").value = this.video.volume * 100
  }

  toggleFullscreen() {
    if (!document.fullscreenElement) {
      this.container.requestFullscreen()
      document.getElementById("fullscreenBtn").innerHTML = '<i class="fas fa-compress"></i>'
    } else {
      document.exitFullscreen()
      document.getElementById("fullscreenBtn").innerHTML = '<i class="fas fa-expand"></i>'
    }
  }

  updateProgress() {
    if (this.video.duration) {
      const percent = (this.video.currentTime / this.video.duration) * 100
      document.getElementById("progressFilled").style.width = percent + "%"
      document.getElementById("progressHandle").style.left = percent + "%"
      this.updateTimeDisplay()
    }
  }

  updateTimeDisplay() {
    const current = this.formatTime(this.video.currentTime)
    const total = this.formatTime(this.video.duration)

    document.getElementById("currentTime").textContent = current
    document.getElementById("totalTime").textContent = total
  }

  formatTime(seconds) {
    if (isNaN(seconds)) return "0:00"

    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = Math.floor(seconds % 60)

    if (hours > 0) {
      return `${hours}:${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`
    } else {
      return `${minutes}:${secs.toString().padStart(2, "0")}`
    }
  }

  showControls() {
    this.controls.classList.add("show")
  }

  hideControls() {
    if (this.isPlaying) {
      this.controls.classList.remove("show")
    }
  }

  showLoading() {
    document.getElementById("loadingSpinner").style.display = "block"
  }

  hideLoading() {
    document.getElementById("loadingSpinner").style.display = "none"
  }

  startProgressUpdates() {
    this.progressUpdateInterval = setInterval(() => {
      if (this.isPlaying && this.video.currentTime > 0) {
        this.saveProgress()
      }
    }, 10000) // Guardar progreso cada 10 segundos
  }

  saveProgress() {
    const formData = new FormData()
    formData.append("content_id", this.config.contentId)
    formData.append("watch_time", Math.floor(this.video.currentTime))
    formData.append("total_time", Math.floor(this.video.duration))

    if (this.config.episodeId) {
      formData.append("episode_id", this.config.episodeId)
    }

    fetch("api/update-progress.php", {
      method: "POST",
      body: formData,
    })
  }

  checkForNextEpisode() {
    if (this.config.hasNextEpisode && this.video.duration > 0) {
      const timeLeft = this.video.duration - this.video.currentTime

      if (timeLeft <= 30 && timeLeft > 15) {
        this.showNextEpisodePreview()
      }
    }
  }

  showNextEpisodePreview() {
    const preview = document.getElementById("nextEpisodePreview")
    if (preview && !preview.classList.contains("show")) {
      preview.classList.add("show")
      this.startNextEpisodeCountdown()
    }
  }

  startNextEpisodeCountdown() {
    let countdown = 15
    const countdownElement = document.getElementById("countdown")

    this.nextEpisodeTimer = setInterval(() => {
      countdown--
      if (countdownElement) {
        countdownElement.textContent = countdown
      }

      if (countdown <= 0) {
        clearInterval(this.nextEpisodeTimer)
        this.playNextEpisode()
      }
    }, 1000)
  }

  playNextEpisode() {
    if (this.config.nextEpisodeId) {
      window.location.href = `play-episode.php?id=${this.config.nextEpisodeId}`
    }
  }

  hideNextEpisode() {
    const preview = document.getElementById("nextEpisodePreview")
    if (preview) {
      preview.classList.remove("show")
    }

    if (this.nextEpisodeTimer) {
      clearInterval(this.nextEpisodeTimer)
    }
  }

  handleVideoEnd() {
    this.saveProgress()

    if (this.config.hasNextEpisode) {
      setTimeout(() => {
        this.playNextEpisode()
      }, 3000)
    } else {
      this.showEndCredits()
    }
  }

  showEndCredits() {
    document.getElementById("endCreditsOverlay").classList.add("show")
  }

  toggleQualityMenu() {
    const menu = document.getElementById("qualityMenu")
    menu.classList.toggle("show")

    // Cerrar menú de subtítulos si está abierto
    document.getElementById("subtitlesMenu").classList.remove("show")
  }

  toggleSubtitlesMenu() {
    const menu = document.getElementById("subtitlesMenu")
    menu.classList.toggle("show")

    // Cerrar menú de calidad si está abierto
    document.getElementById("qualityMenu").classList.remove("show")
  }
}

// Funciones globales
function initializePlayer(config) {
  window.player = new NetflixPlayer(config)
}

function goBack() {
  if (window.player) {
    window.player.saveProgress()
  }
  window.history.back()
}

function playNextEpisode() {
  if (window.player) {
    window.player.playNextEpisode()
  }
}

function hideNextEpisode() {
  if (window.player) {
    window.player.hideNextEpisode()
  }
}

function goToContent(contentId) {
  window.location.href = `content.php?id=${contentId}`
}

// Event listeners para menús
document.addEventListener("DOMContentLoaded", () => {
  // Quality menu options
  document.querySelectorAll(".quality-option").forEach((option) => {
    option.addEventListener("click", function () {
      document.querySelectorAll(".quality-option").forEach((opt) => opt.classList.remove("active"))
      this.classList.add("active")
      document.getElementById("qualityMenu").classList.remove("show")
    })
  })

  // Subtitle menu options
  document.querySelectorAll(".subtitle-option").forEach((option) => {
    option.addEventListener("click", function () {
      document.querySelectorAll(".subtitle-option").forEach((opt) => opt.classList.remove("active"))
      this.classList.add("active")
      document.getElementById("subtitlesMenu").classList.remove("show")
    })
  })

  // Rating buttons
  document.querySelectorAll(".btn-rating").forEach((button) => {
    button.addEventListener("click", function () {
      const rating = this.dataset.rating
      // Aquí se puede implementar la lógica para guardar la calificación
      console.log("Rating:", rating)
    })
  })

  // Close menus when clicking outside
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".quality-menu") && !e.target.closest("#qualityBtn")) {
      document.getElementById("qualityMenu").classList.remove("show")
    }

    if (!e.target.closest(".subtitles-menu") && !e.target.closest("#subtitlesBtn")) {
      document.getElementById("subtitlesMenu").classList.remove("show")
    }
  })
})
