-- Tablas para el sistema de facturación
-- Ejecutar después de tener la base de datos configurada

USE involtrak_test;

CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    direccion VARCHAR(255) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_email (email),
    INDEX idx_nombre (nombre, apellidos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1000 COMMENT='Tabla de clientes';

CREATE TABLE IF NOT EXISTS facturas (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    numero_factura VARCHAR(20) NOT NULL UNIQUE COMMENT 'Formato DC-1000',
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL COMMENT 'Usuario que emite la factura',
    fecha_emision DATETIME NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'Total sin IVA',
    iva DECIMAL(10,2) NOT NULL COMMENT 'IVA aplicado (21%)',
    total DECIMAL(10,2) NOT NULL COMMENT 'Total con IVA',
    estado ENUM('pendiente', 'pagada', 'anulada') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero_factura (numero_factura),
    INDEX idx_cliente (cliente_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_emision),
    CONSTRAINT fk_facturas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_facturas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1000 COMMENT='Tabla de facturas';

CREATE TABLE IF NOT EXISTS facturas_lineas (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    factura_id INT NOT NULL,
    articulo VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'cantidad * precio_unitario',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_factura (factura_id),
    CONSTRAINT fk_facturas_lineas_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Líneas de detalle de facturas';
