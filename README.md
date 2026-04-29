# Involtrak — Sistema de Gestión Económica

Aplicación web de gestión económica personal/empresarial desarrollada en PHP con patrón MVC. Permite llevar el control de ingresos, gastos, clientes y facturas desde un panel centralizado.

---

## Características

- **Dashboard** con resumen de ingresos, gastos y balance del período
- **Ingresos y gastos** con categorización por tipo de período (mensual, anual, puntual)
- **Gestión de clientes** con ficha completa
- **Facturación** con generación de PDF imprimible
- **Gestión de usuarios** con activación por email y recuperación de contraseña
- **Autenticación segura**: rate limiting, CSRF, timeout de sesión, validación de integridad IP/User-Agent
- **Envío de email** vía Brevo (Sendinblue) o `mail()` de PHP como fallback

---

## Requisitos

| Componente | Versión mínima |
|------------|----------------|
| PHP | 8.0+ |
| MariaDB / MySQL | 10.4+ |
| Apache | 2.4+ con `mod_rewrite` |
| Extensiones PHP | `mysqli`, `openssl`, `curl`, `fileinfo`, `mbstring` |

> El script `install.sh` instala y configura todo automáticamente en Ubuntu/Debian.

---

## Instalación rápida (producción)

```bash
# Clonar el repositorio
git clone https://github.com/Sri7ach1/involtrak/
cd involtrak

# Ejecutar el instalador (requiere root)
sudo bash install.sh
```

El instalador se encarga de: instalar dependencias del sistema, crear la base de datos, configurar Apache con VirtualHost, obtener certificado SSL con Let's Encrypt y crear el usuario administrador.

---

## Instalación manual (desarrollo)

**1. Clonar y configurar el entorno:**

```bash
git clone https://github.com/Sri7ach1/involtrak.git
cd involtrak
cp .env.example .env
```

**2. Editar `.env`** con tus credenciales de base de datos y datos de empresa:

```ini
DB_HOST=localhost
DB_USER=tu_usuario
DB_PASS=tu_contraseña
DB_NAME=involtrak

APP_NAME=Involtrak
APP_URL=http://localhost
APP_DEBUG=true

COMPANY_NAME=Mi Empresa
COMPANY_CIF=B-XXXXXXXX
COMPANY_ADDRESS=Calle Ejemplo, 1
COMPANY_EMAIL=info@miempresa.com
COMPANY_PHONE=+34 600 000 000

ENCRYPTION_KEY=cambia_esto_por_una_clave_aleatoria_segura
```

**3. Importar el esquema de base de datos:**

```bash
mysql -u root -p involtrak < db/schema.sql
```

**4. Configurar Apache** para que el `DocumentRoot` apunte a la raíz del proyecto y habilitar `mod_rewrite`:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /ruta/al/proyecto
    <Directory /ruta/al/proyecto>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**5. Acceder** en el navegador a `http://localhost` e iniciar sesión.

---

## Estructura del proyecto

```
involtrak/
├── config/
│   └── con.php                 # Conexión a BD (carga variables desde .env)
├── controllers/
│   ├── pages/                  # Controladores de vistas (login, dashboard, ...)
│   ├── clientes/               # CRUD clientes (AJAX)
│   ├── facturas/               # CRUD facturas + generación PDF (AJAX)
│   ├── gastos/                 # CRUD gastos (AJAX)
│   ├── ingresos/               # CRUD ingresos (AJAX)
│   └── users/                  # CRUD usuarios (AJAX)
├── db/
│   ├── schema.sql              # Esquema principal
│   └── *.sql                   # Migraciones adicionales
├── helpers/
│   └── helpers.php             # Funciones globales: auth, CSRF, email, crypto...
├── logs/
│   └── .gitkeep                # Carpeta de logs (excluida del repo)
├── models/
│   ├── Cliente.php
│   ├── Factura.php
│   ├── Gasto.php
│   ├── Ingreso.php
│   ├── TipoImpuesto.php
│   └── User.php
├── public/
│   ├── dist/                   # Assets AdminLTE (CSS, JS, imágenes)
│   └── plugins/                # Librerías de terceros
├── templates/
│   ├── head.php                # Cabecera HTML común
│   ├── foot.php                # Pie HTML común
│   ├── menu.php                # Menú lateral
│   ├── login.php
│   ├── forgot_password.php
│   └── reset_password.php
├── tests/
│   ├── Unit/
│   └── Integration/
├── .env.example                # Plantilla de configuración
├── .gitignore
├── .htaccess                   # Enrutamiento y protección de archivos
├── index.php                   # Punto de entrada único
├── install.sh                  # Instalador automático para producción
└── router.php                  # Router HTTP
```

---

## Base de datos

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Cuentas de acceso al sistema |
| `clientes` | Ficha de clientes |
| `ingresos` | Registro de ingresos |
| `gastos` | Registro de gastos |
| `facturas` | Cabecera de facturas |
| `facturas_lineas` | Líneas de detalle de cada factura |
| `period_types` | Tipos de período (mensual, anual, puntual) |
| `password_reset_tokens` | Tokens de recuperación/activación de cuenta |
| `login_attempts` | Control de rate limiting de login |

---

## Variables de entorno

Copia `.env.example` a `.env` y ajusta los valores. Las variables más importantes:

| Variable | Descripción | Defecto |
|----------|-------------|---------|
| `DB_HOST` | Host de la base de datos | `localhost` |
| `DB_USER` | Usuario de la BD | — |
| `DB_PASS` | Contraseña de la BD | — |
| `DB_NAME` | Nombre de la BD | — |
| `APP_URL` | URL pública de la aplicación | — |
| `APP_DEBUG` | Activa logs de debug en router | `false` |
| `COMPANY_NAME` | Nombre de empresa (para PDFs) | — |
| `COMPANY_CIF` | CIF/NIF (para PDFs) | — |
| `COMPANY_EMAIL` | Email de empresa (para PDFs) | — |
| `ENCRYPTION_KEY` | Clave AES-256 para datos sensibles | **obligatoria** |
| `BREVO_API_KEY` | API key de Brevo para emails | vacío = usa `mail()` |
| `MAIL_FROM_ADDRESS` | Remitente de emails | — |
| `SESSION_TIMEOUT` | Segundos de inactividad antes de cerrar sesión | `1800` |
| `LOGIN_MAX_ATTEMPTS` | Intentos antes de bloquear login | `5` |
| `LOGIN_LOCKOUT_MINUTES` | Minutos de bloqueo tras exceder intentos | `15` |
| `CSRF_TOKEN_TTL` | Tiempo de vida del token CSRF en segundos | `3600` |
| `PASSWORD_MIN_LENGTH` | Longitud mínima de contraseña | `12` |

---

## Seguridad

- Contraseñas hasheadas con **bcrypt** (`PASSWORD_BCRYPT`)
- Todas las consultas SQL con **prepared statements** (mysqli)
- Protección **CSRF** en todos los formularios con tokens de sesión y `hash_equals`
- **Rate limiting** de login persistido en base de datos con bloqueo temporal
- Cifrado simétrico **AES-256-GCM** para datos sensibles
- Validación de integridad de sesión por IP y User-Agent
- Headers de seguridad HTTP: `X-Frame-Options`, `X-Content-Type-Options`, `CSP`, `HSTS`
- Archivo `.env` protegido fuera del docroot y con permisos `600`
- Tokens de recuperación de contraseña de un solo uso con expiración de 1 hora

---

## Tecnologías

- **Backend**: PHP 8, MySQLi
- **Frontend**: AdminLTE 3, Bootstrap 4, jQuery, DataTables, Chart.js
- **Email**: Brevo (Sendinblue) API v3 / PHP `mail()`
- **PDF**: Generación HTML imprimible (compatible con la función de impresión del navegador)
- **Servidor**: Apache 2.4 + mod_rewrite, MariaDB

---

## Licencia

MIT License — libre para uso personal y comercial.
