<?php
/**
 * Punto de entrada principal de la aplicación
 * Todas las solicitudes se redirigen aquí mediante .htaccess
 */

// Definir constante de seguridad para proteger accesos directos a AJAX
define('SECURE_PATH', true);

// Detectar si es acceso desde red local (IP privada)
$isLocalIP = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    $_SERVER['HTTP_HOST'] === '127.0.0.1' ||
    strpos($_SERVER['HTTP_HOST'], '192.168.') === 0
);

// Detectar si es dominio de desarrollo local
$isLocalDomain = (
    strpos($_SERVER['HTTP_HOST'], '.casa') !== false
);

// HTTPS ya está forzado por Apache (redirección en VirtualHost)
// No forzar aquí para evitar bucles de redirección

// Headers de seguridad (A05 - Security Misconfiguration)
// HSTS: Activar cuando se use HTTPS (incluso en local con certificado autofirmado)
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    // En desarrollo local, usar max-age menor para facilitar testing
    $hstsMaxAge = $isLocalIP ? 300 : 31536000; // 5 min local, 1 año producción
    header('Strict-Transport-Security: max-age=' . $hstsMaxAge . '; includeSubDomains' . ($isLocalIP ? '' : '; preload'));
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');

// Content Security Policy - usando 'unsafe-inline' para permitir event handlers (onclick)
// Nota: No podemos usar nonce con 'unsafe-inline' porque el nonce hace que 'unsafe-inline' se ignore
// Los event handlers inline (onclick) NO soportan nonces, solo <script> tags
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self'");

// Headers adicionales de seguridad
header('X-Permitted-Cross-Domain-Policies: none');
header('Expect-CT: max-age=86400, enforce');

// Prevenir cache de páginas sensibles
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Iniciar sesión con configuración segura
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => !empty($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax'
    ]);
}

// Iniciar output buffering para mejor manejo de errores
ob_start();

// Error reporting - nunca mostrar en producción
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configurar archivo de logs
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/php_errors.log';
ini_set('error_log', $log_file);

// Asegurar que el directorio de logs sea escribible
if (!is_writable($log_dir)) {
    @chmod($log_dir, 0755); // Silenciar warning si no hay permisos
}

require_once 'router.php';
require_once 'config/con.php';
require_once 'helpers/helpers.php';

// Crear instancia del router
$router = new Router();

// Registrar rutas
$router->add('login', 'loginController');
$router->add('', 'loginController');
$router->add('panel', 'dashboardController');
$router->add('usuarios', 'usersController');
$router->add('ingresos', 'ingresosController');
$router->add('gastos', 'gastosController');
$router->add('clientes', 'clientesController');
$router->add('facturas', 'facturasController');
$router->add('logout', 'logoutController');
$router->add('forgot-password', 'forgotPasswordController');
$router->add('reset-password', 'resetPasswordController');

// Rutas AJAX
$router->add('api/user/create', 'userCreateAjaxController');
$router->add('api/user/edit', 'userEditAjaxController');
$router->add('api/user/delete', 'userDeleteAjaxController');
$router->add('api/ingreso/create', 'ingresoCreateAjaxController');
$router->add('api/ingreso/edit', 'ingresoEditAjaxController');
$router->add('api/ingreso/delete', 'ingresoDeleteAjaxController');
$router->add('api/gasto/create', 'gastoCreateAjaxController');
$router->add('api/gasto/edit', 'gastoEditAjaxController');
$router->add('api/gasto/delete', 'gastoDeleteAjaxController');
$router->add('api/cliente/create', 'clienteCreateAjaxController');
$router->add('api/cliente/edit', 'clienteEditAjaxController');
$router->add('api/cliente/delete', 'clienteDeleteAjaxController');
$router->add('api/cliente/get', 'clienteGetController');
$router->add('api/cliente/activos', 'clientesActivosController');
$router->add('api/factura/create', 'facturaCreateAjaxController');
$router->add('api/factura/edit', 'facturaEditAjaxController');
$router->add('api/factura/delete', 'facturaDeleteAjaxController');
$router->add('api/factura/get', 'facturaGetController');
$router->add('api/factura/pdf', 'facturaPDFController');

// Incluir controladores
require 'controllers/pages/login.php';
require 'controllers/pages/dashboard.php';
require 'controllers/pages/users.php';
require 'controllers/pages/ingresos.php';
require 'controllers/pages/gastos.php';
require 'controllers/pages/clientes.php';
require 'controllers/pages/facturas.php';
require 'controllers/pages/logout.php';
require 'controllers/pages/forgot_password.php';
require 'controllers/pages/reset_password.php';
require 'controllers/users/user_create_ajax.php';
require 'controllers/users/user_edit_ajax.php';
require 'controllers/users/user_delete_ajax.php';
require 'controllers/ingresos/ingreso_create_ajax.php';
require 'controllers/ingresos/ingreso_edit_ajax.php';
require 'controllers/ingresos/ingreso_delete_ajax.php';
require 'controllers/gastos/gasto_create_ajax.php';
require 'controllers/gastos/gasto_edit_ajax.php';
require 'controllers/gastos/gasto_delete_ajax.php';
require 'controllers/clientes/cliente_create_ajax.php';
require 'controllers/clientes/cliente_edit_ajax.php';
require 'controllers/clientes/cliente_delete_ajax.php';
require 'controllers/clientes/cliente_get.php';
require 'controllers/clientes/clientes_activos.php';
require 'controllers/facturas/factura_create_ajax.php';
require 'controllers/facturas/factura_edit_ajax.php';
require 'controllers/facturas/factura_delete_ajax.php';
require 'controllers/facturas/factura_get.php';
require 'controllers/facturas/factura_pdf.php';

// Ejecutar el router
$router->dispatch();

// Limpiar output buffering
ob_end_flush();
?>
