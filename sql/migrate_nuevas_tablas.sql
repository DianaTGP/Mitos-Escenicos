USE mitos_escenicos;

CREATE TABLE IF NOT EXISTS artistas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  rol ENUM('actor','director','disenador','bailarin','otro') NOT NULL DEFAULT 'actor',
  especialidad VARCHAR(255) DEFAULT NULL,
  biografia TEXT DEFAULT NULL,
  foto_url VARCHAR(500) DEFAULT NULL,
  orden INT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_activo (activo),
  INDEX idx_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS obra_media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  obra_id INT UNSIGNED NOT NULL,
  tipo ENUM('imagen','video') NOT NULL DEFAULT 'imagen',
  ruta VARCHAR(500) NOT NULL,
  orden INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
  INDEX idx_obra (obra_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mercaderia_media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mercaderia_id INT UNSIGNED NOT NULL,
  tipo ENUM('imagen','video') NOT NULL DEFAULT 'imagen',
  ruta VARCHAR(500) NOT NULL,
  orden INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mercaderia_id) REFERENCES mercaderia(id) ON DELETE CASCADE,
  INDEX idx_mercaderia (mercaderia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funcion_artistas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  funcion_id INT UNSIGNED NOT NULL,
  artista_id INT UNSIGNED NOT NULL,
  rol_en_funcion ENUM('actor','director') NOT NULL DEFAULT 'actor',
  personaje VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (funcion_id) REFERENCES funciones(id) ON DELETE CASCADE,
  FOREIGN KEY (artista_id) REFERENCES artistas(id) ON DELETE CASCADE,
  UNIQUE KEY uk_funcion_artista (funcion_id, artista_id),
  INDEX idx_funcion (funcion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;
