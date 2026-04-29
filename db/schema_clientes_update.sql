-- Agregar campos faltantes a la tabla clientes
USE involtrak_test;

ALTER TABLE clientes 
ADD COLUMN poblacion VARCHAR(100) AFTER direccion,
ADD COLUMN codigo_postal VARCHAR(10) AFTER poblacion,
ADD COLUMN pais VARCHAR(100) DEFAULT 'España' AFTER codigo_postal;
