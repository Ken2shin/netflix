-- Tabla para historial de búsquedas
CREATE TABLE IF NOT EXISTS search_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profile_id INT NOT NULL,
    search_term VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    INDEX idx_profile_search (profile_id, created_at),
    INDEX idx_search_term (search_term)
);

-- Agregar métodos de búsqueda al modelo Content
-- Estas funciones se implementan en el modelo Content.php
