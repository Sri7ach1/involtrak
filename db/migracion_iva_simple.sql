-- Migración: Simplificar IVA a manual (sin relación con tipos_impuestos)
-- Fecha: 2026-01-01

-- Actualizar porcentaje_impuesto basado en tipo_impuesto_id existente (si existe)
UPDATE facturas f
LEFT JOIN tipos_impuestos ti ON f.tipo_impuesto_id = ti.id
SET f.porcentaje_impuesto = COALESCE(ti.porcentaje, 21.00)
WHERE f.porcentaje_impuesto IS NULL;

-- Asegurar que todas las facturas tengan un porcentaje (default 21%)
UPDATE facturas 
SET porcentaje_impuesto = 21.00
WHERE porcentaje_impuesto IS NULL;

-- Hacer el campo NOT NULL ahora que todos tienen valor
ALTER TABLE facturas 
MODIFY COLUMN porcentaje_impuesto DECIMAL(5,2) NOT NULL DEFAULT 21.00 COMMENT 'Porcentaje de impuesto (editable manualmente)';

-- Eliminar la foreign key de tipo_impuesto_id
ALTER TABLE facturas 
DROP FOREIGN KEY IF EXISTS fk_facturas_tipo_impuesto;

-- Eliminar el índice
ALTER TABLE facturas 
DROP INDEX IF EXISTS idx_tipo_impuesto;

-- Eliminar la columna tipo_impuesto_id (ya no necesaria)
ALTER TABLE facturas 
DROP COLUMN IF EXISTS tipo_impuesto_id;
