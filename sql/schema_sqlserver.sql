-- Mitos Escénicos - Esquema de base de datos para SQL Server
-- Ejecutar usando sqlcmd -S .\SQLEXPRESS -E -i schema_sqlserver.sql

USE master;
GO

IF DB_ID('mitos_escenicos') IS NOT NULL
BEGIN
    ALTER DATABASE mitos_escenicos SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE mitos_escenicos;
END
GO

CREATE DATABASE mitos_escenicos;
GO

USE mitos_escenicos;
GO

-- ============================================================
-- Tabla usuarios
-- ============================================================
CREATE TABLE usuarios (
  id INT IDENTITY(1,1) PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(255) NOT NULL,
  telefono VARCHAR(50) DEFAULT NULL,
  direccion TEXT DEFAULT NULL,
  rol VARCHAR(50) NOT NULL DEFAULT 'usuario' CHECK (rol IN ('admin', 'usuario')),
  activo BIT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT GETDATE()
);
GO
CREATE INDEX idx_email ON usuarios(email);
CREATE INDEX idx_rol ON usuarios(rol);
GO

-- ============================================================
-- Tabla obras
-- ============================================================
CREATE TABLE obras (
  id INT IDENTITY(1,1) PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  imagen_url VARCHAR(500) DEFAULT NULL,
  duracion_min INT DEFAULT NULL,
  venta_boletos_habilitada BIT NOT NULL DEFAULT 1,
  temporada VARCHAR(100) DEFAULT NULL,
  tipo_representacion VARCHAR(50) NOT NULL DEFAULT 'temporada' CHECK (tipo_representacion IN ('temporada','un_dia')),
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  updated_at DATETIME DEFAULT NULL
);
GO
CREATE INDEX idx_venta_habilitada ON obras(venta_boletos_habilitada);
CREATE INDEX idx_temporada ON obras(temporada);
GO

-- ============================================================
-- Tabla lugares
-- ============================================================
CREATE TABLE lugares (
  id INT IDENTITY(1,1) PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  direccion TEXT DEFAULT NULL,
  capacidad INT DEFAULT NULL,
  mapa_url VARCHAR(500) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  updated_at DATETIME DEFAULT NULL
);
GO

-- ============================================================
-- Tabla funciones
-- ============================================================
CREATE TABLE funciones (
  id INT IDENTITY(1,1) PRIMARY KEY,
  obra_id INT NOT NULL,
  lugar_id INT NOT NULL,
  fecha_hora DATETIME NOT NULL,
  precio_base DECIMAL(10,2) NOT NULL,
  aforo INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  updated_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_funciones_obra FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
  CONSTRAINT fk_funciones_lugar FOREIGN KEY (lugar_id) REFERENCES lugares(id) ON DELETE NO ACTION
);
GO
CREATE INDEX idx_obra ON funciones(obra_id);
CREATE INDEX idx_fecha ON funciones(fecha_hora);
GO

-- ============================================================
-- Tabla mercadería
-- ============================================================
CREATE TABLE mercaderia (
  id INT IDENTITY(1,1) PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  imagen_url VARCHAR(500) DEFAULT NULL,
  precio DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  activo BIT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  updated_at DATETIME DEFAULT NULL
);
GO
CREATE INDEX idx_activo ON mercaderia(activo);
GO

-- ============================================================
-- Tabla órdenes
-- ============================================================
CREATE TABLE ordenes (
  id INT IDENTITY(1,1) PRIMARY KEY,
  usuario_id INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  estado VARCHAR(50) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'pagado', 'enviado', 'cancelado')),
  tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('boletos', 'tienda', 'mixto')),
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  updated_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_ordenes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE NO ACTION
);
GO
CREATE INDEX idx_orden_usuario ON ordenes(usuario_id);
CREATE INDEX idx_estado ON ordenes(estado);
GO

-- ============================================================
-- Tabla ítems de orden
-- ============================================================
CREATE TABLE orden_items (
  id INT IDENTITY(1,1) PRIMARY KEY,
  orden_id INT NOT NULL,
  tipo_item VARCHAR(50) NOT NULL CHECK (tipo_item IN ('boleto', 'mercancia')),
  funcion_id INT DEFAULT NULL,
  mercancia_id INT DEFAULT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  detalles NVARCHAR(MAX) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  CONSTRAINT fk_items_orden FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_funcion FOREIGN KEY (funcion_id) REFERENCES funciones(id) ON DELETE NO ACTION,
  CONSTRAINT fk_items_mercaderia FOREIGN KEY (mercancia_id) REFERENCES mercaderia(id) ON DELETE NO ACTION
);
GO
CREATE INDEX idx_items_orden ON orden_items(orden_id);
GO

-- ============================================================
-- Tabla pagos
-- ============================================================
CREATE TABLE pagos (
  id INT IDENTITY(1,1) PRIMARY KEY,
  orden_id INT NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  metodo VARCHAR(50) NOT NULL DEFAULT 'openpay',
  openpay_charge_id VARCHAR(100) DEFAULT NULL,
  openpay_order_id VARCHAR(100) DEFAULT NULL,
  estado VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  CONSTRAINT fk_pagos_orden FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE NO ACTION
);
GO
CREATE INDEX idx_pagos_orden ON pagos(orden_id);
GO

-- ============================================================
-- Tabla tarjetas guardadas
-- ============================================================
CREATE TABLE tarjetas_guardadas (
  id INT IDENTITY(1,1) PRIMARY KEY,
  usuario_id INT NOT NULL,
  openpay_customer_id VARCHAR(100) NOT NULL,
  openpay_card_id VARCHAR(100) NOT NULL,
  ultimos_4 VARCHAR(4) NOT NULL,
  marca VARCHAR(50) DEFAULT NULL,
  alias VARCHAR(100) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  CONSTRAINT fk_tarjetas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT uk_usuario_card UNIQUE (usuario_id, openpay_card_id)
);
GO
CREATE INDEX idx_tarjetas_usuario ON tarjetas_guardadas(usuario_id);
GO

-- ============================================================
-- Tabla talleres
-- ============================================================
CREATE TABLE talleres (
  id INT IDENTITY(1,1) PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  instructor VARCHAR(255) DEFAULT NULL,
  imagen_url VARCHAR(500) DEFAULT NULL,
  modalidad VARCHAR(50) NOT NULL DEFAULT 'presencial' CHECK (modalidad IN ('presencial', 'video', 'hibrido')),
  duracion_horas DECIMAL(5,1) DEFAULT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cupo_maximo INT DEFAULT NULL,
  fecha_inicio DATE DEFAULT NULL,
  fecha_fin DATE DEFAULT NULL,
  activo BIT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  updated_at DATETIME DEFAULT NULL
);
GO
CREATE INDEX idx_modalidad ON talleres(modalidad);
CREATE INDEX idx_talleres_activo ON talleres(activo);
GO

-- ============================================================
-- Tabla inscripciones a talleres
-- ============================================================
CREATE TABLE inscripciones_talleres (
  id INT IDENTITY(1,1) PRIMARY KEY,
  taller_id INT NOT NULL,
  usuario_id INT NOT NULL,
  nombre_completo VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  telefono VARCHAR(50) DEFAULT NULL,
  experiencia TEXT DEFAULT NULL,
  estado VARCHAR(50) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'confirmada', 'cancelada')),
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  updated_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_inscr_taller FOREIGN KEY (taller_id) REFERENCES talleres(id) ON DELETE CASCADE,
  CONSTRAINT fk_inscr_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE NO ACTION,
  CONSTRAINT uk_taller_usuario UNIQUE (taller_id, usuario_id)
);
GO
CREATE INDEX idx_inscr_taller ON inscripciones_talleres(taller_id);
CREATE INDEX idx_inscr_usuario ON inscripciones_talleres(usuario_id);
GO

-- ============================================================
-- Tabla artistas
-- ============================================================
CREATE TABLE artistas (
  id INT IDENTITY(1,1) PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  rol VARCHAR(50) NOT NULL DEFAULT 'actor',
  -- Nota: Permitir cualquier rol ya que anteriormente modificamos esta feature
  especialidad VARCHAR(255) DEFAULT NULL,
  biografia TEXT DEFAULT NULL,
  foto_url VARCHAR(500) DEFAULT NULL,
  orden INT NOT NULL DEFAULT 0,
  activo BIT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  updated_at DATETIME DEFAULT NULL
);
GO
CREATE INDEX idx_artistas_activo ON artistas(activo);
CREATE INDEX idx_artistas_orden ON artistas(orden);
GO

-- ============================================================
-- Tabla obra_media
-- ============================================================
CREATE TABLE obra_media (
  id INT IDENTITY(1,1) PRIMARY KEY,
  obra_id INT NOT NULL,
  tipo VARCHAR(50) NOT NULL DEFAULT 'imagen' CHECK (tipo IN ('imagen','video')),
  ruta VARCHAR(500) NOT NULL,
  orden INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  CONSTRAINT fk_obra_media FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE
);
GO
CREATE INDEX idx_obra_media ON obra_media(obra_id);
GO

-- ============================================================
-- Tabla mercaderia_media
-- ============================================================
CREATE TABLE mercaderia_media (
  id INT IDENTITY(1,1) PRIMARY KEY,
  mercaderia_id INT NOT NULL,
  tipo VARCHAR(50) NOT NULL DEFAULT 'imagen' CHECK (tipo IN ('imagen','video')),
  ruta VARCHAR(500) NOT NULL,
  orden INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  CONSTRAINT fk_merca_media FOREIGN KEY (mercaderia_id) REFERENCES mercaderia(id) ON DELETE CASCADE
);
GO
CREATE INDEX idx_merca_media ON mercaderia_media(mercaderia_id);
GO

-- ============================================================
-- Tabla funcion_artistas
-- ============================================================
CREATE TABLE funcion_artistas (
  id INT IDENTITY(1,1) PRIMARY KEY,
  funcion_id INT NOT NULL,
  artista_id INT NOT NULL,
  rol_en_funcion VARCHAR(50) NOT NULL DEFAULT 'actor' CHECK (rol_en_funcion IN ('actor','director')),
  personaje VARCHAR(255) DEFAULT NULL,
  CONSTRAINT fk_funart_funcion FOREIGN KEY (funcion_id) REFERENCES funciones(id) ON DELETE CASCADE,
  CONSTRAINT fk_funart_artista FOREIGN KEY (artista_id) REFERENCES artistas(id) ON DELETE CASCADE,
  CONSTRAINT uk_funcion_artista UNIQUE (funcion_id, artista_id)
);
GO
CREATE INDEX idx_funart_funcion ON funcion_artistas(funcion_id);
GO

-- ============================================================
-- Datos iniciales: Usuario administrador
-- ============================================================

-- MERGE equivalente para UPSERT
MERGE INTO usuarios AS target
USING (VALUES ('admin@mitosescenicos.com', '$2y$10$TQfQmBcHpks5./Jg/c7lYObodqObTx3IHbGZSbXpIFvqX.5HSm4We', 'Administrador', 'admin', 1)) 
    AS source (email, password_hash, nombre, rol, activo)
ON target.email = source.email
WHEN MATCHED THEN
    UPDATE SET password_hash = source.password_hash, rol = source.rol, activo = source.activo
WHEN NOT MATCHED THEN
    INSERT (email, password_hash, nombre, rol, activo)
    VALUES (source.email, source.password_hash, source.nombre, source.rol, source.activo);
GO
