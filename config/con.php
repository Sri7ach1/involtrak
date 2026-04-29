<?php
// Cargar variables de entorno
// Prefer .env ubicado fuera del docroot si existe (una carpeta arriba)
$env_file_root = dirname(__DIR__) . '/.env';
$env_file_outside = dirname(dirname(__DIR__)) . '/.env';

// Si existe .env fuera del docroot, usarlo
if (file_exists($env_file_outside)) {
    $env_file = $env_file_outside;
} else {
    $env_file = $env_file_root;
    // Intentar mover/copy a fuera del docroot para mayor seguridad
    if (file_exists($env_file_root) && is_writable(dirname(dirname(__DIR__)))) {
        @copy($env_file_root, $env_file_outside);
        if (file_exists($env_file_outside)) {
            @chmod($env_file_outside, 0600);
            error_log('[SECURITY] .env copiado fuera del docroot a: ' . $env_file_outside);
            // preferir la copia fuera
            $env_file = $env_file_outside;
        }
    }
}
// Proteger permisos de .env: si tiene permisos demasiado permisivos, intentar restringirlos
if (file_exists($env_file)) {
    $perms = fileperms($env_file) & 0777;
    if (($perms & 0o077) !== 0) {
        @chmod($env_file, 0600);
        error_log('[SECURITY] Permisos de .env eran permisivos (' . sprintf('%o', $perms) . '), se intentó chmod 600');
    }
}
if (!file_exists($env_file)) {
    error_log('ERROR: Archivo .env no encontrado');
    die('Error del sistema. Contacte al administrador.');
}

// Leer el archivo .env ignorando comentarios
$env_vars = [];
$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    // Ignorar líneas de comentario
    if (strpos(trim($line), '#') === 0) {
        continue;
    }
    if (strpos($line, '=') === false) {
        continue;
    }
    list($key, $value) = explode('=', $line, 2);
    $env_vars[trim($key)] = trim($value);
}

if (empty($env_vars)) {
    error_log('ERROR: No se pudo leer el archivo .env');
    die('Error del sistema. Contacte al administrador.');
}

// Hacer variables accesibles globalmente y como variables de entorno
foreach ($env_vars as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
}

$server = $env_vars['DB_HOST'] ?? 'localhost';
$user = $env_vars['DB_USER'] ?? '';
$pass = $env_vars['DB_PASS'] ?? '';
$db = $env_vars['DB_NAME'] ?? '';

if (empty($user) || empty($pass) || empty($db)) {
    error_log('ERROR: Variables de entorno incompletas en .env');
    die('Error del sistema. Contacte al administrador.');
}

try {
    $con = mysqli_connect($server, $user, $pass, $db);
    if (!$con) {
        throw new Exception('Error de conexión a base de datos');
    }
    // Configurar charset
    mysqli_set_charset($con, 'utf8mb4');
} catch (Exception $e) {
    error_log('DATABASE_ERROR: ' . $e->getMessage());
    die('Error del sistema. Contacte al administrador.');
}