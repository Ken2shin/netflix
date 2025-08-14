-- Creando base de datos principal de Netflix
DROP DATABASE IF EXISTS netflix;
CREATE DATABASE netflix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE netflix;

-- Tabla de usuarios principales
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    subscription_type ENUM('basic', 'standard', 'premium') DEFAULT 'basic',
    subscription_status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_admin BOOLEAN DEFAULT FALSE
);

-- Tabla de perfiles de usuario (Netflix permite múltiples perfiles)
CREATE TABLE profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    is_kids BOOLEAN DEFAULT FALSE,
    language VARCHAR(10) DEFAULT 'es',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de géneros
CREATE TABLE genres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de contenido (películas y series)
CREATE TABLE content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('movie', 'series') NOT NULL,
    release_year YEAR,
    duration INT, -- en minutos para películas, NULL para series
    rating ENUM('G', 'PG', 'PG-13', 'R', 'NC-17', 'TV-Y', 'TV-G', 'TV-PG', 'TV-14', 'TV-MA') DEFAULT 'PG',
    imdb_rating DECIMAL(3,1) DEFAULT 0.0,
    thumbnail VARCHAR(255),
    banner_image VARCHAR(255),
    trailer_url VARCHAR(255),
    video_url VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    is_trending BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de temporadas (solo para series)
CREATE TABLE seasons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    content_id INT NOT NULL,
    season_number INT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    release_year YEAR,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    UNIQUE KEY unique_season (content_id, season_number)
);

-- Tabla de episodios (solo para series)
CREATE TABLE episodes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    season_id INT NOT NULL,
    episode_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL, -- en minutos
    video_url VARCHAR(255),
    thumbnail VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_episode (season_id, episode_number)
);

-- Tabla de relación contenido-géneros (muchos a muchos)
CREATE TABLE content_genres (
    content_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (content_id, genre_id),
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

-- Tabla de mi lista (watchlist)
CREATE TABLE watchlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profile_id INT NOT NULL,
    content_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    UNIQUE KEY unique_watchlist (profile_id, content_id)
);

-- Tabla de historial de visualización
CREATE TABLE viewing_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profile_id INT NOT NULL,
    content_id INT NOT NULL,
    episode_id INT NULL, -- NULL para películas
    watch_time INT DEFAULT 0, -- tiempo visto en segundos
    total_time INT NOT NULL, -- duración total en segundos
    completed BOOLEAN DEFAULT FALSE,
    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Tabla de calificaciones
CREATE TABLE ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profile_id INT NOT NULL,
    content_id INT NOT NULL,
    rating ENUM('thumbs_up', 'thumbs_down') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rating (profile_id, content_id)
);

-- Índices para optimización
CREATE INDEX idx_content_type ON content(type);
CREATE INDEX idx_content_featured ON content(is_featured);
CREATE INDEX idx_content_trending ON content(is_trending);
CREATE INDEX idx_viewing_history_profile ON viewing_history(profile_id);
CREATE INDEX idx_viewing_history_last_watched ON viewing_history(last_watched);
CREATE INDEX idx_watchlist_profile ON watchlist(profile_id);
