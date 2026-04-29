USE involtrak;

CREATE TABLE usuarios (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    pass VARCHAR(255) NOT NULL,
    mail VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE period_types (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO period_types (code, description) VALUES
    ('mensual', 'Ingresos mensuales'),
    ('anual', 'Ingresos anuales'),
    ('puntual', 'Pago puntual');

CREATE TABLE ingresos (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    period_type_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    descripcion VARCHAR(255) NULL,
    INDEX (fecha),
    INDEX (period_type_id),
    CONSTRAINT fk_ingresos_period_type FOREIGN KEY (period_type_id) REFERENCES period_types(id) ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE TABLE gastos (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    period_type_id INT NULL,
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (fecha),
    INDEX (period_type_id),
    CONSTRAINT fk_gastos_period_type FOREIGN KEY (period_type_id) REFERENCES period_types(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE password_reset_tokens (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    INDEX (token),
    INDEX (expires_at),
    INDEX (user_id),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE login_attempts (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL COMMENT 'Username, email o IP',
    attempts INT NOT NULL DEFAULT 1,
    locked_until TIMESTAMP NULL,
    last_attempt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier),
    INDEX idx_locked_until (locked_until),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Control de rate limiting de intentos de login';

CREATE TABLE clientes (
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

CREATE TABLE facturas (
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

CREATE TABLE facturas_lineas (
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