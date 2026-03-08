-- Migración: añadir temporada y tipo_representacion a obras (para instalaciones existentes)
-- Ejecutar solo si la tabla obras ya existe sin estas columnas.
-- Si aparece error "Duplicate column name", las columnas ya existen.

ALTER TABLE obras ADD COLUMN temporada VARCHAR(100) DEFAULT NULL AFTER venta_boletos_habilitada;
ALTER TABLE obras ADD COLUMN tipo_representacion ENUM('temporada','un_dia') NOT NULL DEFAULT 'temporada' AFTER temporada;
