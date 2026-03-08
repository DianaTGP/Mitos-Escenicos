-- Mitos Escénicos - Esquema de base de datos
-- Ejecutar en MySQL (XAMPP) después de crear la base de datos:
--   CREATE DATABASE mitos_escenicos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   USE mitos_escenicos;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Tabla usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(255) NOT NULL,
  telefono VARCHAR(50) DEFAULT NULL,
  direccion TEXT DEFAULT NULL,
  rol ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla obras
-- ============================================================
CREATE TABLE IF NOT EXISTS obras (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  imagen_url VARCHAR(500) DEFAULT NULL,
  duracion_min INT UNSIGNED DEFAULT NULL,
  venta_boletos_habilitada TINYINT(1) NOT NULL DEFAULT 1,
  temporada VARCHAR(100) DEFAULT NULL,
  tipo_representacion ENUM('temporada','un_dia') NOT NULL DEFAULT 'temporada',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_venta_habilitada (venta_boletos_habilitada),
  INDEX idx_temporada (temporada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla lugares
-- ============================================================
CREATE TABLE IF NOT EXISTS lugares (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  direccion TEXT DEFAULT NULL,
  capacidad INT UNSIGNED DEFAULT NULL,
  mapa_url VARCHAR(500) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla funciones
-- ============================================================
CREATE TABLE IF NOT EXISTS funciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  obra_id INT UNSIGNED NOT NULL,
  lugar_id INT UNSIGNED NOT NULL,
  fecha_hora DATETIME NOT NULL,
  precio_base DECIMAL(10,2) NOT NULL,
  aforo INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
  FOREIGN KEY (lugar_id) REFERENCES lugares(id) ON DELETE RESTRICT,
  INDEX idx_obra (obra_id),
  INDEX idx_fecha (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla mercadería
-- ============================================================
CREATE TABLE IF NOT EXISTS mercaderia (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  imagen_url VARCHAR(500) DEFAULT NULL,
  precio DECIMAL(10,2) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla órdenes
-- ============================================================
CREATE TABLE IF NOT EXISTS ordenes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  estado ENUM('pendiente', 'pagado', 'enviado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  tipo ENUM('boletos', 'tienda', 'mixto') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
  INDEX idx_usuario (usuario_id),
  INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla ítems de orden
-- ============================================================
CREATE TABLE IF NOT EXISTS orden_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_id INT UNSIGNED NOT NULL,
  tipo_item ENUM('boleto', 'mercancia') NOT NULL,
  funcion_id INT UNSIGNED DEFAULT NULL,
  mercancia_id INT UNSIGNED DEFAULT NULL,
  cantidad INT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  detalles JSON DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
  FOREIGN KEY (funcion_id) REFERENCES funciones(id) ON DELETE SET NULL,
  FOREIGN KEY (mercancia_id) REFERENCES mercaderia(id) ON DELETE SET NULL,
  INDEX idx_orden (orden_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla pagos
-- ============================================================
CREATE TABLE IF NOT EXISTS pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_id INT UNSIGNED NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  metodo VARCHAR(50) NOT NULL DEFAULT 'openpay',
  openpay_charge_id VARCHAR(100) DEFAULT NULL,
  openpay_order_id VARCHAR(100) DEFAULT NULL,
  estado VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE RESTRICT,
  INDEX idx_orden (orden_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla tarjetas guardadas (solo referencias Openpay; nunca número completo ni CVV)
-- ============================================================
CREATE TABLE IF NOT EXISTS tarjetas_guardadas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  openpay_customer_id VARCHAR(100) NOT NULL,
  openpay_card_id VARCHAR(100) NOT NULL,
  ultimos_4 VARCHAR(4) NOT NULL,
  marca VARCHAR(50) DEFAULT NULL,
  alias VARCHAR(100) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uk_usuario_card (usuario_id, openpay_card_id),
  INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla talleres (nuevos talleres formativos)
-- ============================================================
CREATE TABLE IF NOT EXISTS talleres (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  instructor VARCHAR(255) DEFAULT NULL,
  imagen_url VARCHAR(500) DEFAULT NULL,
  modalidad ENUM('presencial', 'video', 'hibrido') NOT NULL DEFAULT 'presencial',
  duracion_horas DECIMAL(5,1) DEFAULT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cupo_maximo INT UNSIGNED DEFAULT NULL,
  fecha_inicio DATE DEFAULT NULL,
  fecha_fin DATE DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_modalidad (modalidad),
  INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla inscripciones a talleres
-- ============================================================
CREATE TABLE IF NOT EXISTS inscripciones_talleres (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  taller_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  nombre_completo VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  telefono VARCHAR(50) DEFAULT NULL,
  experiencia TEXT DEFAULT NULL,
  estado ENUM('pendiente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendiente',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (taller_id) REFERENCES talleres(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uk_taller_usuario (taller_id, usuario_id),
  INDEX idx_taller (taller_id),
  INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Tabla artistas (miembros de Mitos Escénicos)
-- ============================================================
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

-- ============================================================
-- Tabla obra_media (imágenes/videos de obras, múltiples)
-- ============================================================
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

-- ============================================================
-- Tabla mercaderia_media (imágenes/videos de mercancía, múltiples)
-- ============================================================
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

-- ============================================================
-- Tabla funcion_artistas (elenco por función)
-- ============================================================
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

-- ============================================================
-- Datos iniciales: Usuario administrador
-- Contraseña: Admin123!
-- Hash generado con: php -r "echo password_hash('Admin123!', PASSWORD_DEFAULT);"
-- ============================================================
INSERT INTO usuarios (email, password_hash, nombre, rol, activo) VALUES
('admin@mitosescenicos.com',
 '$2y$10$TQfQmBcHpks5./Jg/c7lYObodqObTx3IHbGZSbXpIFvqX.5HSm4We',
 'Administrador', 'admin', 1)
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash),
  rol = 'admin',
  activo = 1;

