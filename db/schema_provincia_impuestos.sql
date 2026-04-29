-- Actualización de base de datos para provincia y tipos de impuestos
USE involtrak_test;

-- 1. Agregar campo provincia a la tabla clientes (si no existe)
ALTER TABLE clientes 
ADD COLUMN IF NOT EXISTS provincia VARCHAR(100) AFTER poblacion;

-- 2. Crear tabla de tipos de impuestos
CREATE TABLE IF NOT EXISTS tipos_impuestos (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(10) NOT NULL UNIQUE COMMENT 'IVA, IPSI, IGIC',
    nombre VARCHAR(50) NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    descripcion VARCHAR(255) NULL,
    provincias_aplicables TEXT NULL COMMENT 'JSON con array de provincias',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tipos de impuestos (IVA, IPSI, IGIC)';

-- 3. Insertar los 3 tipos de impuestos
INSERT INTO tipos_impuestos (codigo, nombre, porcentaje, descripcion, provincias_aplicables) VALUES
('IVA', 'Impuesto sobre el Valor Añadido', 21.00, 'IVA aplicable a península y Baleares', '["A Coruña","Álava","Albacete","Alicante","Almería","Asturias","Ávila","Badajoz","Barcelona","Burgos","Cáceres","Cádiz","Cantabria","Castellón","Ciudad Real","Córdoba","Cuenca","Girona","Granada","Guadalajara","Guipúzcoa","Huelva","Huesca","Islas Baleares","Jaén","La Rioja","León","Lleida","Lugo","Madrid","Málaga","Murcia","Navarra","Ourense","Palencia","Pontevedra","Salamanca","Segovia","Sevilla","Soria","Tarragona","Teruel","Toledo","Valencia","Valladolid","Vizcaya","Zamora","Zaragoza"]'),
('IPSI', 'IPSI - Ceuta y Melilla', 10.00, 'IPSI aplicable a Ceuta y Melilla', '["Ceuta","Melilla"]'),
('IGIC', 'IGIC - Canarias', 7.00, 'IGIC aplicable a Canarias', '["Las Palmas","Santa Cruz de Tenerife"]');

-- 4. Modificar tabla facturas para usar tipo de impuesto dinámico
ALTER TABLE facturas 
ADD COLUMN tipo_impuesto_id INT NULL AFTER usuario_id,
ADD COLUMN porcentaje_impuesto DECIMAL(5,2) NULL COMMENT 'Porcentaje aplicado en el momento de la factura',
ADD INDEX idx_tipo_impuesto (tipo_impuesto_id),
ADD CONSTRAINT fk_facturas_tipo_impuesto FOREIGN KEY (tipo_impuesto_id) REFERENCES tipos_impuestos(id) ON UPDATE CASCADE ON DELETE RESTRICT;

-- 5. Renombrar columna iva a impuesto en facturas (para mayor claridad)
ALTER TABLE facturas 
CHANGE COLUMN iva impuesto DECIMAL(10,2) NOT NULL COMMENT 'Impuesto aplicado (IVA/IPSI/IGIC)';
