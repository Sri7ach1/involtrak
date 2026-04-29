#!/bin/bash
# Script para aplicar la migración de provincia e impuestos

echo "====================================="
echo "Aplicando migración: Provincia e Impuestos"
echo "====================================="

# Verificar que existe el archivo SQL
if [ ! -f "schema_provincia_impuestos.sql" ]; then
    echo "Error: No se encuentra el archivo schema_provincia_impuestos.sql"
    exit 1
fi

# Solicitar credenciales de MySQL
read -p "Usuario de MySQL [root]: " MYSQL_USER
MYSQL_USER=${MYSQL_USER:-root}

read -sp "Contraseña de MySQL: " MYSQL_PASS
echo ""

read -p "Base de datos [involtrak_test]: " MYSQL_DB
MYSQL_DB=${MYSQL_DB:-involtrak_test}

echo ""
echo "Aplicando migración a la base de datos '$MYSQL_DB'..."

# Aplicar el script SQL
mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" "$MYSQL_DB" < schema_provincia_impuestos.sql

# Verificar el resultado
if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Migración aplicada exitosamente"
    echo ""
    echo "Cambios realizados:"
    echo "  - Campo 'provincia' agregado a tabla 'clientes'"
    echo "  - Tabla 'tipos_impuestos' creada con IVA (21%), IPSI (10%), IGIC (7%)"
    echo "  - Tabla 'facturas' actualizada con campos 'tipo_impuesto_id' y 'porcentaje_impuesto'"
    echo "  - Columna 'iva' renombrada a 'impuesto' en tabla 'facturas'"
    echo ""
    echo "IMPORTANTE: Actualiza los clientes existentes para asignarles una provincia."
else
    echo ""
    echo "✗ Error al aplicar la migración"
    echo "Revisa el mensaje de error anterior"
    exit 1
fi
