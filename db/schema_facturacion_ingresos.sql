-- Modificación de la tabla ingresos para vincular con facturas
-- Ejecutar después de crear las tablas de facturación

USE involtrak_test;

-- Agregar columna factura_id a la tabla ingresos si no existe
ALTER TABLE ingresos 
ADD COLUMN IF NOT EXISTS factura_id INT NULL COMMENT 'ID de la factura si el ingreso proviene de facturación',
ADD INDEX IF NOT EXISTS idx_factura_id (factura_id);

-- Agregar foreign key si no existe (MySQL 8.0+)
-- Si da error, ignorar (significa que ya existe o versión anterior de MySQL)
ALTER TABLE ingresos 
ADD CONSTRAINT fk_ingresos_factura 
FOREIGN KEY (factura_id) REFERENCES facturas(id) 
ON UPDATE CASCADE 
ON DELETE SET NULL;
