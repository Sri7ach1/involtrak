-- Migración: IVA manual en facturas
-- Fecha: 2026-01-01
-- Descripción: Permitir que el porcentaje de IVA sea editable manualmente

-- Añadir campo para porcentaje de impuesto manual
ALTER TABLE facturas 
ADD COLUMN porcentaje_impuesto DECIMAL(5,2) NOT NULL DEFAULT 21.00 COMMENT 'Porcentaje de impuesto (editable manualmente)' 
AFTER subtotal;

-- Actualizar facturas existentes para que tengan el porcentaje correcto basado en el IVA actual
UPDATE facturas 
SET porcentaje_impuesto = ROUND((iva / subtotal) * 100, 2)
WHERE subtotal > 0;

-- Para facturas con subtotal 0, dejar el default 21%
UPDATE facturas 
SET porcentaje_impuesto = 21.00
WHERE subtotal = 0;

-- Renombrar columna 'iva' a 'importe_impuesto' para mayor claridad
ALTER TABLE facturas 
CHANGE COLUMN iva importe_impuesto DECIMAL(10,2) NOT NULL COMMENT 'Importe del impuesto aplicado';
