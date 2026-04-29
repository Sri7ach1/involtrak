#!/bin/bash

################################################################################
# Involtrak - Script de Instalación en Producción
# 
# Este script automatiza la instalación y configuración completa del sistema
# de gestión económica Involtrak en un servidor de producción.
#
# Requisitos:
# - Ubuntu/Debian
# - Ejecutar como root o con sudo
# - Puerto 80 y 443 abiertos
################################################################################

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_message() {
    echo -e "${GREEN}[Involtrak]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_step() {
    echo -e "\n${BLUE}===================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===================================================${NC}\n"
}

# Verificar que se ejecuta como root
if [[ $EUID -ne 0 ]]; then
   print_error "Este script debe ejecutarse como root (usa sudo)"
   exit 1
fi

# Banner
clear
echo -e "${GREEN}"
cat << "EOF"
 _____ _     ___ _    ___ _  _   _  _  ___  _  _ ___ _   _ 
|  ___/ \   |   | |  |   | \| | | || |/ _ \| \| | __\ \ / /
| |_ / _ \  | | | |  | | | .  | | __ | (_) | .  | _| \ V / 
|  _/ ___ \ | | | |__| | | |\  | |__||_|\___/|_|\_|___| |_|  
|_|/_/   \_\|___|____/___/|_| \_|                            
                                                              
        Sistema de Gestión Económica - Instalador v1.0
EOF
echo -e "${NC}\n"

print_message "Iniciando instalación de Involtrak..."
sleep 2

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

################################################################################
# PASO 0: Verificar si existe .env y hacer backup
################################################################################

ENV_FILE="${SCRIPT_DIR}/.env"
ENV_BACKUP=""
EXISTING_BREVO_API_KEY=""
EXISTING_MAIL_FROM_ADDRESS=""
EXISTING_MAIL_FROM_NAME=""
EXISTING_DB_NAME=""
EXISTING_DB_USER=""
EXISTING_DB_PASS=""
EXISTING_APP_URL=""
EXISTING_ENCRYPTION_KEY=""

if [[ -f "$ENV_FILE" ]]; then
    print_step "PASO 0: Respaldo de Configuración Existente"
    ENV_BACKUP="${SCRIPT_DIR}/.env.backup.$(date +%Y%m%d_%H%M%S)"
    cp "$ENV_FILE" "$ENV_BACKUP"
    print_message "Backup del .env creado en: $ENV_BACKUP"
    
    # Leer configuraciones existentes
    while IFS='=' read -r key value; do
        [[ "$key" =~ ^#.*$ ]] && continue
        [[ -z "$key" ]] && continue
        case "$key" in
            DB_HOST) EXISTING_DB_HOST="$value" ;;
            DB_USER) EXISTING_DB_USER="$value" ;;
            DB_PASS) EXISTING_DB_PASS="$value" ;;
            DB_NAME) EXISTING_DB_NAME="$value" ;;
            APP_URL) EXISTING_APP_URL="$value" ;;
            BREVO_API_KEY) EXISTING_BREVO_API_KEY="$value" ;;
            MAIL_FROM_ADDRESS) EXISTING_MAIL_FROM_ADDRESS="$value" ;;
            MAIL_FROM_NAME) EXISTING_MAIL_FROM_NAME="$value" ;;
            ENCRYPTION_KEY) EXISTING_ENCRYPTION_KEY="$value" ;;
        esac
    done < "$ENV_FILE"
    
    print_message "Configuraciones existentes detectadas:"
    [[ -n "$EXISTING_DB_NAME" ]] && echo "  - Base de datos: $EXISTING_DB_NAME"
    [[ -n "$EXISTING_DB_USER" ]] && echo "  - Usuario BD: $EXISTING_DB_USER"
    [[ -n "$EXISTING_APP_URL" ]] && echo "  - URL: $EXISTING_APP_URL"
    [[ -n "$EXISTING_BREVO_API_KEY" && "$EXISTING_BREVO_API_KEY" != "your_brevo_api_key_here" ]] && echo "  - Brevo API configurado"
    echo ""
fi

################################################################################
# PASO 1: Solicitar información al usuario
################################################################################

print_step "PASO 1: Recopilación de Información"

# Dominio
if [[ -n "$EXISTING_APP_URL" ]]; then
    DEFAULT_DOMAIN=$(echo "$EXISTING_APP_URL" | sed -E 's|https?://||' | sed 's|/.*||')
    read -p "Introduce el dominio [$DEFAULT_DOMAIN]: " DOMAIN
    DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}
else
    read -p "Introduce el dominio (ejemplo: fallen-honey.casa): " DOMAIN
    while [[ -z "$DOMAIN" ]]; do
        print_error "El dominio no puede estar vacío"
        read -p "Introduce el dominio: " DOMAIN
    done
fi

# Email para Let's Encrypt
read -p "Introduce tu email para Let's Encrypt: " LETSENCRYPT_EMAIL
while [[ -z "$LETSENCRYPT_EMAIL" ]]; do
    print_error "El email no puede estar vacío"
    read -p "Introduce tu email: " LETSENCRYPT_EMAIL
done

# Base de datos
if [[ -n "$EXISTING_DB_NAME" ]]; then
    read -p "Introduce el nombre de la base de datos [$EXISTING_DB_NAME]: " DB_NAME
    DB_NAME=${DB_NAME:-$EXISTING_DB_NAME}
else
    read -p "Introduce el nombre de la base de datos [involtrak]: " DB_NAME
    DB_NAME=${DB_NAME:-involtrak}
fi

if [[ -n "$EXISTING_DB_USER" ]]; then
    read -p "Introduce el nombre de usuario de la base de datos [$EXISTING_DB_USER]: " DB_USER
    DB_USER=${DB_USER:-$EXISTING_DB_USER}
else
    read -p "Introduce el nombre de usuario de la base de datos [fh_user]: " DB_USER
    DB_USER=${DB_USER:-fh_user}
fi

# Si existe contraseña anterior, preguntar si quiere mantenerla
if [[ -n "$EXISTING_DB_PASS" ]]; then
    read -p "¿Mantener la contraseña de BD existente? (s/n): " KEEP_PASS
    if [[ "$KEEP_PASS" == "s" || "$KEEP_PASS" == "S" ]]; then
        DB_PASS="$EXISTING_DB_PASS"
    else
        while true; do
            read -sp "Introduce la nueva contraseña de la base de datos: " DB_PASS
            echo
            read -sp "Confirma la contraseña de la base de datos: " DB_PASS_CONFIRM
            echo
            if [[ "$DB_PASS" == "$DB_PASS_CONFIRM" ]]; then
                if [[ ${#DB_PASS} -lt 8 ]]; then
                    print_error "La contraseña debe tener al menos 8 caracteres"
                else
                    break
                fi
            else
                print_error "Las contraseñas no coinciden"
            fi
        done
    fi
else
    while true; do
        read -sp "Introduce la contraseña de la base de datos: " DB_PASS
        echo
        read -sp "Confirma la contraseña de la base de datos: " DB_PASS_CONFIRM
        echo
        if [[ "$DB_PASS" == "$DB_PASS_CONFIRM" ]]; then
            if [[ ${#DB_PASS} -lt 8 ]]; then
                print_error "La contraseña debe tener al menos 8 caracteres"
            else
                break
            fi
        else
            print_error "Las contraseñas no coinciden"
        fi
    done
fi

# Usuario administrador de la plataforma
read -p "Introduce el nombre de usuario administrador [admin]: " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}

read -p "Introduce el email del administrador: " ADMIN_EMAIL
while [[ -z "$ADMIN_EMAIL" ]]; do
    print_error "El email no puede estar vacío"
    read -p "Introduce el email del administrador: " ADMIN_EMAIL
done

while true; do
    read -sp "Introduce la contraseña del administrador (mín. 12 caracteres, mayúsculas, minúsculas, números y símbolos): " ADMIN_PASS
    echo
    read -sp "Confirma la contraseña del administrador: " ADMIN_PASS_CONFIRM
    echo
    if [[ "$ADMIN_PASS" == "$ADMIN_PASS_CONFIRM" ]]; then
        if [[ ${#ADMIN_PASS} -lt 12 ]]; then
            print_error "La contraseña debe tener al menos 12 caracteres"
        else
            break
        fi
    else
        print_error "Las contraseñas no coinciden"
    fi
done

# Resumen
print_step "Resumen de la Configuración"
echo "Dominio: $DOMAIN"
echo "Email Let's Encrypt: $LETSENCRYPT_EMAIL"
echo "Base de datos: $DB_NAME"
echo "Usuario BD: $DB_USER"
echo "Usuario Admin: $ADMIN_USER"
echo "Email Admin: $ADMIN_EMAIL"
echo ""

read -p "¿Es correcta esta información? (s/n): " CONFIRM
if [[ "$CONFIRM" != "s" && "$CONFIRM" != "S" ]]; then
    print_error "Instalación cancelada por el usuario"
    exit 1
fi

################################################################################
# PASO 2: Actualizar sistema e instalar dependencias
################################################################################

print_step "PASO 2: Instalando Dependencias del Sistema"

print_message "Actualizando repositorios..."
apt update -qq

print_message "Instalando Apache2, PHP y MariaDB..."
DEBIAN_FRONTEND=noninteractive apt install -y apache2 \
    php php-mysql php-cli php-common php-mbstring php-xml php-curl \
    mariadb-server mariadb-client \
    certbot python3-certbot-apache \
    git curl unzip at

print_message "Habilitando módulos de Apache..."
a2enmod rewrite
a2enmod ssl
a2enmod headers

################################################################################
# PASO 3: Configurar MariaDB
################################################################################

print_step "PASO 3: Configurando MariaDB"

print_message "Iniciando servicio MariaDB..."
systemctl start mariadb
systemctl enable mariadb

# Generar contraseña root de MySQL
MYSQL_ROOT_PASS=$(openssl rand -base64 32)

print_message "Configurando seguridad de MariaDB..."

# Configurar root password y seguridad
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASS}';" 2>/dev/null || \
mysql -e "SET PASSWORD FOR 'root'@'localhost' = PASSWORD('${MYSQL_ROOT_PASS}');"

mysql -u root -p"${MYSQL_ROOT_PASS}" <<-EOSQL
    DELETE FROM mysql.user WHERE User='';
    DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
    DROP DATABASE IF EXISTS test;
    DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
    FLUSH PRIVILEGES;
EOSQL

print_message "MariaDB configurado correctamente"

################################################################################
# PASO 4: Crear base de datos y usuario
################################################################################

print_step "PASO 4: Creando Base de Datos"

print_message "Creando base de datos '$DB_NAME' y usuario '$DB_USER'..."

mysql -u root -p"${MYSQL_ROOT_PASS}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
    FLUSH PRIVILEGES;
EOSQL

print_message "Base de datos creada correctamente"

################################################################################
# PASO 5: Importar schema de base de datos
################################################################################

print_step "PASO 5: Importando Schema de Base de Datos"

SCHEMA_FILE="${SCRIPT_DIR}/db/schema.sql"

if [[ -f "$SCHEMA_FILE" ]]; then
    print_message "Importando schema desde $SCHEMA_FILE..."
    mysql -u root -p"${MYSQL_ROOT_PASS}" "$DB_NAME" < "$SCHEMA_FILE"
    print_message "Schema importado correctamente"
else
    print_error "No se encuentra el archivo schema.sql en ${SCRIPT_DIR}/db/"
    exit 1
fi

################################################################################
# PASO 6: Configurar archivo .env
################################################################################

print_step "PASO 6: Configurando Archivo .env"

# Generar clave de encriptación si no existe
if [[ -n "$EXISTING_ENCRYPTION_KEY" ]]; then
    ENCRYPTION_KEY="$EXISTING_ENCRYPTION_KEY"
else
    ENCRYPTION_KEY=$(openssl rand -base64 32)
fi

# Preservar configuraciones de email existentes
if [[ -n "$EXISTING_BREVO_API_KEY" && "$EXISTING_BREVO_API_KEY" != "your_brevo_api_key_here" ]]; then
    BREVO_API_KEY="$EXISTING_BREVO_API_KEY"
else
    BREVO_API_KEY="your_brevo_api_key_here"
fi

if [[ -n "$EXISTING_MAIL_FROM_ADDRESS" ]]; then
    MAIL_FROM_ADDRESS="$EXISTING_MAIL_FROM_ADDRESS"
else
    MAIL_FROM_ADDRESS="noreply@${DOMAIN}"
fi

if [[ -n "$EXISTING_MAIL_FROM_NAME" ]]; then
    MAIL_FROM_NAME="$EXISTING_MAIL_FROM_NAME"
else
    MAIL_FROM_NAME="Involtrak"
fi

print_message "Creando archivo .env..."

cat > "$ENV_FILE" <<-EOENV
# Database Configuration
DB_HOST=localhost
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_NAME=${DB_NAME}

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${DOMAIN}

# Session Configuration
SESSION_TIMEOUT=1800
SESSION_NAME=fh_session

# Security Configuration
CSRF_TOKEN_NAME=csrf_token
CSRF_TOKEN_LIFETIME=3600
ENCRYPTION_KEY=${ENCRYPTION_KEY}

# Email Configuration (Brevo)
MAIL_DRIVER=brevo
BREVO_API_KEY=${BREVO_API_KEY}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME=${MAIL_FROM_NAME}
EOENV

chmod 600 "$ENV_FILE"
print_message "Archivo .env creado correctamente"

################################################################################
# PASO 7: Crear usuario administrador
################################################################################

print_step "PASO 7: Creando Usuario Administrador"

print_message "Creando usuario administrador '$ADMIN_USER'..."

# Hash de la contraseña
ADMIN_PASS_HASH=$(php -r "echo password_hash('${ADMIN_PASS}', PASSWORD_ARGON2ID);")

# Insertar usuario en la base de datos
mysql -u root -p"${MYSQL_ROOT_PASS}" "$DB_NAME" <<-EOSQL
    INSERT INTO users (name, mail, pass, estado, created_at, updated_at) 
    VALUES (
        '${ADMIN_USER}',
        '${ADMIN_EMAIL}',
        '${ADMIN_PASS_HASH}',
        'activo',
        NOW(),
        NOW()
    );
EOSQL

print_message "Usuario administrador creado correctamente"

################################################################################
# PASO 8: Configurar Apache VirtualHost
################################################################################

print_step "PASO 8: Configurando Apache VirtualHost"

VHOST_FILE="/etc/apache2/sites-available/${DOMAIN}.conf"

print_message "Creando VirtualHost para $DOMAIN..."

cat > "$VHOST_FILE" <<-EOVHOST
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAdmin ${LETSENCRYPT_EMAIL}
    DocumentRoot ${SCRIPT_DIR}
    
    <Directory ${SCRIPT_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Protección adicional
        <FilesMatch "^\.">
            Require all denied
        </FilesMatch>
    </Directory>
    
    # Denegar acceso a archivos sensibles
    <FilesMatch "\.(env|sh|sql|md|log|txt)$">
        Require all denied
    </FilesMatch>
    
    # Logs
    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}-access.log combined
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
EOVHOST

# Habilitar sitio
a2dissite 000-default.conf 2>/dev/null || true
a2ensite "${DOMAIN}.conf"

print_message "VirtualHost configurado correctamente"

################################################################################
# PASO 9: Instalar certificado SSL con Let's Encrypt
################################################################################

print_step "PASO 9: Instalando Certificado SSL con Let's Encrypt"

print_message "Reiniciando Apache..."
systemctl reload apache2

print_message "Solicitando certificado SSL para $DOMAIN..."
print_warning "Asegúrate de que el dominio $DOMAIN apunta a este servidor"

sleep 3

certbot --apache \
    --non-interactive \
    --agree-tos \
    --email "$LETSENCRYPT_EMAIL" \
    --domains "$DOMAIN" \
    --redirect

print_message "Certificado SSL instalado correctamente"

# Configurar renovación automática
print_message "Configurando renovación automática de certificados..."
systemctl enable certbot.timer
systemctl start certbot.timer

################################################################################
# PASO 10: Limpiar archivos innecesarios
################################################################################

print_step "PASO 10: Limpiando Archivos Innecesarios"

cd "$SCRIPT_DIR"

print_message "Eliminando archivos de desarrollo y testing..."

# Eliminar archivos y directorios innecesarios
rm -rf tests/
rm -f README.md
rm -f SECURITY_AUDIT_COMPONENTS.md
rm -f rec_code.txt
rm -rf .git
rm -f .gitignore

# NO eliminar install.sh inmediatamente
print_warning "El archivo install.sh NO ha sido eliminado. Puedes eliminarlo manualmente cuando quieras."

print_message "Archivos innecesarios eliminados"

################################################################################
# PASO 11: Establecer permisos correctos
################################################################################

print_step "PASO 11: Configurando Permisos de Archivos"

print_message "Estableciendo permisos correctos..."

# Propietario: www-data
chown -R www-data:www-data "$SCRIPT_DIR"

# Permisos generales
find "$SCRIPT_DIR" -type d -exec chmod 755 {} \;
find "$SCRIPT_DIR" -type f -exec chmod 644 {} \;

# Permisos especiales
chmod 600 "${SCRIPT_DIR}/.env"
[[ -n "$ENV_BACKUP" ]] && chmod 600 "$ENV_BACKUP"
mkdir -p "${SCRIPT_DIR}/logs" && chmod 755 "${SCRIPT_DIR}/logs"
chown www-data:www-data "${SCRIPT_DIR}/logs"

print_message "Permisos configurados correctamente"

################################################################################
# PASO 12: Reiniciar servicios
################################################################################

print_step "PASO 12: Reiniciando Servicios"

print_message "Reiniciando Apache..."
systemctl restart apache2

print_message "Verificando estado de servicios..."
systemctl is-active --quiet apache2 && echo "Apache: ✓ Activo" || echo "Apache: ✗ Inactivo"
systemctl is-active --quiet mariadb && echo "MariaDB: ✓ Activo" || echo "MariaDB: ✗ Inactivo"

################################################################################
# PASO 13: Guardar credenciales de forma segura
################################################################################

print_step "PASO 13: Guardando Información de Instalación"

CREDENTIALS_FILE="/root/.involtrak_credentials"

cat > "$CREDENTIALS_FILE" <<-EOCRED
=======================================================
Involtrak - Credenciales de Instalación
=======================================================
Fecha: $(date)

APLICACIÓN:
-----------
URL: https://${DOMAIN}
Usuario Admin: ${ADMIN_USER}
Email Admin: ${ADMIN_EMAIL}
Contraseña Admin: ${ADMIN_PASS}

BASE DE DATOS:
--------------
Host: localhost
Nombre BD: ${DB_NAME}
Usuario BD: ${DB_USER}
Contraseña BD: ${DB_PASS}

MYSQL ROOT:
-----------
Usuario: root
Contraseña: ${MYSQL_ROOT_PASS}

ARCHIVOS:
---------
Directorio: ${SCRIPT_DIR}
Archivo .env: ${SCRIPT_DIR}/.env
Backup .env: ${ENV_BACKUP}

CONFIGURACIÓN PRESERVADA:
-------------------------
Brevo API Key: ${BREVO_API_KEY}
Mail From: ${MAIL_FROM_ADDRESS}
Encryption Key: Preservada del .env anterior

IMPORTANTE:
-----------
- Guarda esta información en un lugar seguro
- Cambia la contraseña del administrador después del primer login
- Configuración de email preservada del .env anterior
- Este archivo se eliminará en 24 horas por seguridad

=======================================================
EOCRED

chmod 600 "$CREDENTIALS_FILE"

print_message "Credenciales guardadas en: $CREDENTIALS_FILE"

# Programar eliminación de credenciales
echo "rm -f $CREDENTIALS_FILE" | at now + 24 hours 2>/dev/null || print_warning "No se pudo programar la eliminación automática de credenciales"

################################################################################
# FINALIZACIÓN
################################################################################

print_step "INSTALACIÓN COMPLETADA"

echo -e "${GREEN}"
cat << "EOF"
   _____ _  _  ___ ___ ___ ___ ___  
  / ____| || |/ __/ __| __/ __|  _ \ 
  \__ \| || | (_| (__| _|\__ \ | | |
  |___/\___/ \___\___|___|___/_| |_|
                                     
EOF
echo -e "${NC}"

print_message "¡Involtrak se ha instalado correctamente!"
echo ""
echo -e "${GREEN}Información de acceso:${NC}"
echo -e "  🌐 URL: ${BLUE}https://${DOMAIN}${NC}"
echo -e "  👤 Usuario: ${BLUE}${ADMIN_USER}${NC}"
echo -e "  📧 Email: ${BLUE}${ADMIN_EMAIL}${NC}"
echo -e "  🔑 Contraseña: ${BLUE}${ADMIN_PASS}${NC}"
echo ""
echo -e "${YELLOW}Credenciales completas guardadas en:${NC} ${CREDENTIALS_FILE}"
echo -e "${YELLOW}Este archivo se eliminará automáticamente en 24 horas${NC}"
echo ""
if [[ -n "$ENV_BACKUP" ]]; then
    echo -e "${GREEN}Backup del .env anterior:${NC} ${ENV_BACKUP}"
fi
echo ""
echo -e "${GREEN}Próximos pasos:${NC}"
echo "  1. Accede a https://${DOMAIN} e inicia sesión"
echo "  2. Cambia la contraseña del administrador"
echo "  3. Revisa la configuración de email en ${SCRIPT_DIR}/.env"
echo "  4. Revisa los logs en ${SCRIPT_DIR}/logs/"
echo ""
print_message "¡Disfruta de Involtrak! "
