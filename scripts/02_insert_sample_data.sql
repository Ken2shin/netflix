-- Cambiando comentario y base de datos de StreamFlix a Netflix
-- Insertando datos de ejemplo para el clon de Netflix
USE netflix;

-- Insertar géneros
INSERT INTO genres (name, slug) VALUES
('Acción', 'accion'),
('Aventura', 'aventura'),
('Comedia', 'comedia'),
('Drama', 'drama'),
('Terror', 'terror'),
('Ciencia Ficción', 'ciencia-ficcion'),
('Fantasía', 'fantasia'),
('Romance', 'romance'),
('Thriller', 'thriller'),
('Documental', 'documental'),
('Animación', 'animacion'),
('Crimen', 'crimen'),
('Misterio', 'misterio'),
('Guerra', 'guerra'),
('Western', 'western'),
('Música', 'musica'),
('Familia', 'familia'),
('Biografía', 'biografia'),
('Historia', 'historia'),
('Deporte', 'deporte');

-- Insertar usuario administrador
-- Cambiando email de admin de streamflix a netflix
INSERT INTO users (email, password, subscription_type, is_admin) VALUES
('admin@netflix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'premium', TRUE);

-- Insertar usuario de prueba
INSERT INTO users (email, password, subscription_type) VALUES
('usuario@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'standard');

-- Insertar perfiles para el usuario de prueba
INSERT INTO profiles (user_id, name, avatar, is_kids) VALUES
(2, 'Juan', 'avatar1.png', FALSE),
(2, 'María', 'avatar2.png', FALSE),
(2, 'Niños', 'kids_avatar.png', TRUE);

-- Insertar contenido de ejemplo (películas)
INSERT INTO content (title, description, type, release_year, duration, rating, imdb_rating, thumbnail, banner_image, trailer_url, video_url, is_featured, is_trending) VALUES
('El Padrino', 'La historia de una familia de la mafia italiana en Nueva York.', 'movie', 1972, 175, 'R', 9.2, 'padrino_thumb.jpg', 'padrino_banner.jpg', 'padrino_trailer.mp4', 'padrino_movie.mp4', TRUE, FALSE),
('Pulp Fiction', 'Historias entrelazadas de crimen en Los Ángeles.', 'movie', 1994, 154, 'R', 8.9, 'pulp_thumb.jpg', 'pulp_banner.jpg', 'pulp_trailer.mp4', 'pulp_movie.mp4', TRUE, TRUE),
('Forrest Gump', 'La extraordinaria vida de un hombre simple.', 'movie', 1994, 142, 'PG-13', 8.8, 'forrest_thumb.jpg', 'forrest_banner.jpg', 'forrest_trailer.mp4', 'forrest_movie.mp4', FALSE, TRUE),
('Matrix', 'Un programador descubre la realidad de su mundo.', 'movie', 1999, 136, 'R', 8.7, 'matrix_thumb.jpg', 'matrix_banner.jpg', 'matrix_trailer.mp4', 'matrix_movie.mp4', TRUE, FALSE),
('Titanic', 'Una historia de amor en el barco más famoso del mundo.', 'movie', 1997, 194, 'PG-13', 7.8, 'titanic_thumb.jpg', 'titanic_banner.jpg', 'titanic_trailer.mp4', 'titanic_movie.mp4', FALSE, FALSE);

-- Insertar series
INSERT INTO content (title, description, type, release_year, rating, imdb_rating, thumbnail, banner_image, trailer_url, is_featured, is_trending) VALUES
('Breaking Bad', 'Un profesor de química se convierte en fabricante de metanfetaminas.', 'series', 2008, 'TV-MA', 9.5, 'breaking_thumb.jpg', 'breaking_banner.jpg', 'breaking_trailer.mp4', TRUE, TRUE),
('Stranger Things', 'Misterios sobrenaturales en un pequeño pueblo.', 'series', 2016, 'TV-14', 8.7, 'stranger_thumb.jpg', 'stranger_banner.jpg', 'stranger_trailer.mp4', TRUE, TRUE),
('The Crown', 'La vida de la familia real británica.', 'series', 2016, 'TV-MA', 8.6, 'crown_thumb.jpg', 'crown_banner.jpg', 'crown_trailer.mp4', FALSE, FALSE);

-- Insertar temporadas para Breaking Bad
INSERT INTO seasons (content_id, season_number, title, description, release_year) VALUES
(6, 1, 'Temporada 1', 'Walter White comienza su transformación.', 2008),
(6, 2, 'Temporada 2', 'Las consecuencias de las decisiones de Walter.', 2009),
(6, 3, 'Temporada 3', 'La tensión aumenta en el negocio.', 2010);

-- Insertar episodios para Breaking Bad Temporada 1
INSERT INTO episodes (season_id, episode_number, title, description, duration, video_url, thumbnail) VALUES
(1, 1, 'Piloto', 'Walter White recibe un diagnóstico que cambia su vida.', 58, 'bb_s1e1.mp4', 'bb_s1e1_thumb.jpg'),
(1, 2, 'El gato está en la bolsa', 'Walter y Jesse lidian con las consecuencias.', 48, 'bb_s1e2.mp4', 'bb_s1e2_thumb.jpg'),
(1, 3, 'Y la bolsa está en el río', 'Decisiones difíciles deben ser tomadas.', 48, 'bb_s1e3.mp4', 'bb_s1e3_thumb.jpg');

-- Relacionar contenido con géneros
INSERT INTO content_genres (content_id, genre_id) VALUES
-- El Padrino: Drama, Crimen
(1, 4), (1, 12),
-- Pulp Fiction: Crimen, Drama
(2, 12), (2, 4),
-- Forrest Gump: Drama, Romance
(3, 4), (3, 8),
-- Matrix: Acción, Ciencia Ficción
(4, 1), (4, 6),
-- Titanic: Romance, Drama
(5, 8), (5, 4),
-- Breaking Bad: Drama, Crimen, Thriller
(6, 4), (6, 12), (6, 9),
-- Stranger Things: Drama, Fantasía, Terror
(7, 4), (7, 7), (7, 5),
-- The Crown: Drama, Biografía, Historia
(8, 4), (8, 18), (8, 19);
