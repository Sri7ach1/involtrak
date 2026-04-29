<?php

// Seguridad crítica: exigir clave de encriptación en entorno
$encKey = getenv('ENCRYPTION_KEY');
if (empty($encKey)) {
    error_log('[SECURITY] ENCRYPTION_KEY no definida en .env o variables de entorno. Abortando arranque.');
    die('Error crítico de configuración. Contacte al administrador.');
}

// -----------------------------------------------------------------------------
// Constantes de configuración derivadas del entorno
// Todas las "magic numbers" centralizadas aquí.
// Si la variable de entorno no existe, se aplica el valor por defecto documentado.
// -----------------------------------------------------------------------------

define('APP_SESSION_TIMEOUT',    (int)(getenv('SESSION_TIMEOUT')      ?: 1800)); // 30 min
define('APP_LOGIN_MAX_ATTEMPTS', (int)(getenv('LOGIN_MAX_ATTEMPTS')    ?: 5));
define('APP_LOGIN_LOCKOUT_SECS', (int)(getenv('LOGIN_LOCKOUT_MINUTES') ?: 15) * 60);
define('APP_CSRF_TTL',           (int)(getenv('CSRF_TOKEN_TTL')        ?: 3600)); // 1 hora
define('APP_PASSWORD_MIN_LEN',   (int)(getenv('PASSWORD_MIN_LENGTH')   ?: 12));


// =============================================================================
// Autenticación y sesión
// =============================================================================

function isLoggedIn() {
    return isset($_SESSION['login']) && $_SESSION['login'] === true;
}

function getCurrentUsername() {
    return isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
}

function getCurrentUserEmail() {
    return isset($_SESSION['correo']) ? $_SESSION['correo'] : null;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getPost($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function getGet($key, $default = '') {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}


// =============================================================================
// CSRF
// =============================================================================

function generateCSRFToken($force_regenerate = false) {
    if ($force_regenerate || empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token']      = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        error_log('[SECURITY] CSRF token missing in session from IP: ' . $_SERVER['REMOTE_ADDR'] . ' | Session ID: ' . session_id());
        return false;
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        error_log('[SECURITY] CSRF token mismatch from IP: ' . $_SERVER['REMOTE_ADDR']);
        return false;
    }

    if (time() - $_SESSION['csrf_token_time'] > APP_CSRF_TTL) {
        error_log('[SECURITY] CSRF token expired from IP: ' . $_SERVER['REMOTE_ADDR']);
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }

    return true;
}

function getCSRFField() {
    return '<input type="hidden" name="csrf_token" value="' . escape(generateCSRFToken()) . '">';
}


// =============================================================================
// Validaciones
// =============================================================================

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_-]{3,}$/', $username);
}

/**
 * Validar longitud mínima de contraseña (usa constante de entorno por defecto)
 */
function validatePasswordLength($password, $minLength = null) {
    $min = $minLength ?? APP_PASSWORD_MIN_LEN;
    return strlen($password) >= $min;
}

/**
 * Validar fortaleza completa de contraseña
 */
function validatePasswordStrength($password) {
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasNumbers   = preg_match('/[0-9]/', $password);
    $hasSpecial   = preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.\\<>?\/\\|`~]/', $password);

    return strlen($password) >= APP_PASSWORD_MIN_LEN
        && $hasUppercase && $hasLowercase && $hasNumbers && $hasSpecial;
}

function validateNombre($nombre) {
    return preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,100}$/', $nombre);
}

function validateTelefono($telefono) {
    return preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/', $telefono);
}

function validateDireccion($direccion) {
    return strlen(trim($direccion)) >= 5 && strlen(trim($direccion)) <= 255;
}

function validateCantidad($cantidad) {
    return is_numeric($cantidad) && floatval($cantidad) > 0;
}

function validatePrecio($precio) {
    return is_numeric($precio) && floatval($precio) >= 0;
}

function validateDescripcion($descripcion, $minLength = 3, $maxLength = 500) {
    $len = strlen(trim($descripcion));
    return $len >= $minLength && $len <= $maxLength;
}


// =============================================================================
// Escapado y formato
// =============================================================================

function escape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function escapeAttr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function formatPrecio($precio) {
    return number_format(floatval($precio), 2, ',', '.') . ' €';
}

function formatFechaES($fecha) {
    $timestamp = strtotime($fecha);
    $meses = [
        1 => 'enero',  2 => 'febrero', 3 => 'marzo',    4 => 'abril',
        5 => 'mayo',   6 => 'junio',   7 => 'julio',     8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];

    $dia  = date('d', $timestamp);
    $mes  = $meses[(int)date('m', $timestamp)];
    $anio = date('Y', $timestamp);
    $hora = date('H:i', $timestamp);

    return "$dia de $mes de $anio a las $hora";
}


// =============================================================================
// Sesión
// =============================================================================

function validateSessionIntegrity() {
    if (!isLoggedIn()) {
        return false;
    }

    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        error_log('[SECURITY] IP change detected for user: ' . $_SESSION['usuario']);
        return false;
    }

    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        error_log('[SECURITY] User Agent change detected for user: ' . $_SESSION['usuario']);
        return false;
    }

    return true;
}

/**
 * Validar timeout de sesión.
 * Usa $_SESSION['timeout'] si está definido (fijado en login), o la constante APP_SESSION_TIMEOUT.
 */
function validateSessionTimeout() {
    if (!isset($_SESSION['ultimoAcceso'])) {
        return true;
    }

    $timeout = $_SESSION['timeout'] ?? APP_SESSION_TIMEOUT;
    if (time() - $_SESSION['ultimoAcceso'] > $timeout) {
        session_destroy();
        redirect('login');
    }

    $_SESSION['ultimoAcceso'] = time();
    return true;
}


// =============================================================================
// Rate limiting
// =============================================================================

function checkLoginRateLimit($identifier) {
    global $con;

    $cleanup_sql = "DELETE FROM login_attempts WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    mysqli_query($con, $cleanup_sql);

    $identifier_hash = md5($identifier);
    $check_sql = "SELECT attempts, locked_until FROM login_attempts
                  WHERE identifier = ? AND (locked_until IS NULL OR locked_until > NOW())
                  ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($con, $check_sql);
    mysqli_stmt_bind_param($stmt, "s", $identifier_hash);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $record = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($record && $record['locked_until'] !== null) {
        error_log('[SECURITY] Login rate limit active for: ' . $identifier . ' until ' . $record['locked_until']);
        return false;
    }

    $ip_address = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    if ($record) {
        $new_attempts = $record['attempts'] + 1;

        if ($new_attempts > APP_LOGIN_MAX_ATTEMPTS) {
            $locked_until = date('Y-m-d H:i:s', time() + APP_LOGIN_LOCKOUT_SECS);
            $update_sql = "UPDATE login_attempts
                           SET attempts = ?, locked_until = ?, last_attempt = NOW(), ip_address = ?, user_agent = ?
                           WHERE identifier = ?";
            $stmt = mysqli_prepare($con, $update_sql);
            mysqli_stmt_bind_param($stmt, "issss", $new_attempts, $locked_until, $ip_address, $user_agent, $identifier_hash);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            error_log('[SECURITY] Login rate limit exceeded for: ' . $identifier . ' - locked until ' . $locked_until);
            return false;
        }

        $update_sql = "UPDATE login_attempts
                       SET attempts = ?, last_attempt = NOW(), ip_address = ?, user_agent = ?
                       WHERE identifier = ?";
        $stmt = mysqli_prepare($con, $update_sql);
        mysqli_stmt_bind_param($stmt, "isss", $new_attempts, $ip_address, $user_agent, $identifier_hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $insert_sql = "INSERT INTO login_attempts (identifier, attempts, ip_address, user_agent)
                       VALUES (?, 1, ?, ?)";
        $stmt = mysqli_prepare($con, $insert_sql);
        mysqli_stmt_bind_param($stmt, "sss", $identifier_hash, $ip_address, $user_agent);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    return true;
}

function clearLoginRateLimit($identifier) {
    global $con;

    $identifier_hash = md5($identifier);
    $delete_sql = "DELETE FROM login_attempts WHERE identifier = ?";
    $stmt = mysqli_prepare($con, $delete_sql);
    mysqli_stmt_bind_param($stmt, "s", $identifier_hash);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    error_log('[SECURITY] Login rate limit cleared for: ' . $identifier);
}


// =============================================================================
// Criptografía
// =============================================================================

function encryptData($data, $key = null) {
    if ($key === null) {
        $key = getenv('ENCRYPTION_KEY');
    }

    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt(
        $data, 'aes-256-gcm',
        hash('sha256', $key, true),
        OPENSSL_RAW_DATA, $iv, $tag
    );

    return base64_encode($iv . $encrypted . $tag);
}

function decryptData($encryptedData, $key = null) {
    if ($key === null) {
        $key = getenv('ENCRYPTION_KEY');
    }

    try {
        if (!is_string($encryptedData)
            || !preg_match('/^[A-Za-z0-9+\/\r\n]+=*$/', $encryptedData)
            || (strlen($encryptedData) % 4) !== 0
        ) {
            securityLog('WARNING', 'decryptData received invalid base64 string');
            return null;
        }

        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false || strlen($decoded) < 33) {
            securityLog('WARNING', 'decryptData base64_decode failed or decoded data too short');
            return null;
        }

        $iv        = substr($decoded, 0, 16);
        $tag       = substr($decoded, -16);
        $encrypted = substr($decoded, 16, -16);

        $decrypted = openssl_decrypt(
            $encrypted, 'aes-256-gcm',
            hash('sha256', $key, true),
            OPENSSL_RAW_DATA, $iv, $tag
        );

        return $decrypted === false ? null : $decrypted;
    } catch (Exception $e) {
        error_log('[SECURITY] Decryption error: ' . $e->getMessage());
        return null;
    }
}

function hashData($data, $key = null) {
    if ($key === null) {
        $key = getenv('ENCRYPTION_KEY');
    }
    return hash_hmac('sha256', $data, $key);
}

function verifyDataIntegrity($data, $hash, $key = null) {
    if ($key === null) {
        $key = getenv('ENCRYPTION_KEY');
    }
    return hash_equals($hash, hashData($data, $key));
}


// =============================================================================
// Logging
// =============================================================================

function securityLog($level, $message, $context = []) {
    $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];

    if (!in_array($level, $levels)) {
        $level = 'INFO';
    }

    $timestamp  = date('Y-m-d H:i:s');
    $user       = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'anonymous';
    $ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $contextStr = !empty($context) ? json_encode($context) : '';

    error_log("[$timestamp] [$level] [User:$user] [IP:$ip] $message $contextStr");

    if ($level === 'CRITICAL') {
        error_log('[ALERT] CRITICAL SECURITY EVENT: ' . $message);
    }
}

function getLogPath() {
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    return $log_dir;
}


// =============================================================================
// Archivos subidos
// =============================================================================

function validateUploadedFile($file, $allowedExtensions = [], $maxSize = 5242880) {
    if (!isset($file['error']) || is_array($file['error'])) {
        securityLog('WARNING', 'Invalid file upload parameters');
        return ['valid' => false, 'error' => 'Parámetros de archivo inválidos'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        securityLog('WARNING', 'File upload error code: ' . $file['error']);
        return ['valid' => false, 'error' => 'Error en la subida del archivo'];
    }

    if ($file['size'] > $maxSize) {
        securityLog('WARNING', 'File size exceeds limit', ['size' => $file['size'], 'max' => $maxSize]);
        return ['valid' => false, 'error' => 'Archivo demasiado grande'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedExtensions) && !in_array($ext, $allowedExtensions)) {
        securityLog('WARNING', 'Invalid file extension', ['ext' => $ext, 'allowed' => $allowedExtensions]);
        return ['valid' => false, 'error' => 'Tipo de archivo no permitido'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        securityLog('CRITICAL', 'Possible file upload attack detected');
        return ['valid' => false, 'error' => 'Archivo no válido'];
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    securityLog('INFO', 'File upload validated', ['name' => $file['name'], 'size' => $file['size'], 'mime' => $mimeType]);

    return ['valid' => true, 'extension' => $ext, 'mime_type' => $mimeType, 'size' => $file['size']];
}

function generateSecureFilename($originalName) {
    $ext       = pathinfo($originalName, PATHINFO_EXTENSION);
    $timestamp = time();
    $random    = bin2hex(random_bytes(8));
    return $timestamp . '_' . $random . '.' . $ext;
}


// =============================================================================
// Email
// =============================================================================

/**
 * Función interna centralizada de envío de email.
 * Usa Brevo si BREVO_API_KEY está configurado; fallback a mail() de PHP.
 * Evita duplicar la lógica en cada función de email.
 */
function _sendEmail($to, $toName, $subject, $htmlContent) {
    $brevo_api_key  = getenv('BREVO_API_KEY')     ?: '';
    $mail_from      = getenv('MAIL_FROM_ADDRESS')  ?: '';
    $mail_from_name = getenv('MAIL_FROM_NAME')     ?: (getenv('APP_NAME') ?: 'App');

    // Construir from address si no está en .env
    if (empty($mail_from)) {
        $host      = parse_url(getenv('APP_URL') ?: 'http://localhost', PHP_URL_HOST) ?: 'localhost';
        $mail_from = 'noreply@' . $host;
    }

    if (!empty($brevo_api_key) && $brevo_api_key !== 'your_brevo_api_key_here') {
        $data = [
            'sender'      => ['email' => $mail_from, 'name' => $mail_from_name],
            'to'          => [['email' => $to, 'name' => $toName]],
            'replyTo'     => ['email' => $mail_from, 'name' => $mail_from_name],
            'subject'     => $subject,
            'htmlContent' => $htmlContent,
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST,           true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $brevo_api_key,
            'Content-Type: application/json',
            'accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT,        10);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErr) {
            securityLog('ERROR', 'cURL error sending email via Brevo', ['error' => $curlErr, 'to' => $to]);
            return ['success' => false];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            securityLog('INFO', "Email sent via Brevo to: {$to}");
            return ['success' => true];
        }

        securityLog('ERROR', "Brevo failed: HTTP {$httpCode}", ['response' => $response, 'to' => $to]);
        return ['success' => false];
    }

    // Fallback: mail() de PHP
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$mail_from_name} <{$mail_from}>\r\n";
    $headers .= "Reply-To: {$mail_from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    if (mail($to, $subject, $htmlContent, $headers)) {
        securityLog('INFO', "Email sent via PHP mail() to: {$to}");
        return ['success' => true];
    }

    securityLog('WARNING', 'PHP mail() failed', ['to' => $to]);
    return ['success' => false];
}

/**
 * Enviar email de recuperación de contraseña
 */
function sendPasswordResetEmail($to, $name, $resetLink, $token) {
    $appName = getenv('APP_NAME') ?: 'Involtrak';
    $subject = "Recuperación de contraseña - {$appName}";

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Recuperación de Contraseña</title></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f4f4f4;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background-color:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
      <tr><td style="background-color:#6f42c1;padding:30px 20px;text-align:center;">
        <h1 style="margin:0;color:#fff;font-size:24px;">Recuperación de Contraseña</h1>
      </td></tr>
      <tr><td style="padding:30px 40px;color:#333;line-height:1.6;">
        <p>Hola <strong><?= escape($name) ?></strong>,</p>
        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr><td align="center" style="padding:20px 0;">
            <a href="<?= escape($resetLink) ?>" style="display:inline-block;padding:14px 30px;background-color:#6f42c1;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;">Restablecer Contraseña</a>
          </td></tr>
        </table>
        <p style="font-size:12px;word-break:break-all;background:#f8f9fa;padding:12px;border:1px solid #dee2e6;border-radius:4px;"><?= escape($resetLink) ?></p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
          <tr><td style="background-color:#fff3cd;border-left:4px solid #ffc107;padding:15px;">
            <p style="margin:0 0 8px 0;font-weight:bold;color:#856404;">⚠️ Importante:</p>
            <ul style="margin:0;padding-left:20px;color:#856404;font-size:13px;">
              <li>Este enlace expirará en <strong>1 hora</strong></li>
              <li>Solo se puede usar <strong>una vez</strong></li>
              <li>Si no solicitaste este cambio, ignora este email</li>
            </ul>
          </td></tr>
        </table>
        <p style="font-size:13px;color:#666;">Código de verificación: <code style="background:#f8f9fa;padding:2px 6px;border-radius:3px;"><?= escape(substr($token, 0, 8)) ?></code></p>
      </td></tr>
      <tr><td style="background-color:#f8f9fa;padding:20px;text-align:center;border-top:1px solid #dee2e6;">
        <p style="margin:0;font-size:12px;color:#6c757d;">&copy; <?= date('Y') ?> <?= escape($appName) ?> - Sistema de Gestión Económica</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
    <?php
    $htmlMessage = ob_get_clean();

    return _sendEmail($to, $name, $subject, $htmlMessage);
}

/**
 * Enviar email de activación de cuenta para nuevo usuario
 */
function sendAccountActivationEmail($to, $username, $activationLink, $token) {
    $appName = getenv('APP_NAME') ?: 'Involtrak';
    $subject = "Activa tu cuenta - {$appName}";

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Activa tu cuenta</title></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f4f4f4;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background-color:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
      <tr><td style="background-color:#6f42c1;padding:30px 20px;text-align:center;">
        <h1 style="margin:0;color:#fff;font-size:24px;">¡Bienvenido a <?= escape($appName) ?>!</h1>
      </td></tr>
      <tr><td style="padding:30px 40px;color:#333;line-height:1.6;">
        <p>Hola <strong><?= escape($username) ?></strong>,</p>
        <p>Se ha creado una cuenta para ti en el sistema de gestión económica <?= escape($appName) ?>.</p>
        <div style="background-color:#e7f3ff;border-left:4px solid #2196F3;padding:15px;margin:20px 0;">
          <p style="margin:0;font-weight:bold;color:#0066cc;">📋 Datos de tu cuenta:</p>
          <p style="margin:8px 0 0 0;"><strong>Usuario:</strong> <code style="background:#f8f9fa;padding:2px 6px;border-radius:3px;"><?= escape($username) ?></code></p>
        </div>
        <p>Para activar tu cuenta y establecer tu contraseña, haz clic en el siguiente botón:</p>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr><td align="center" style="padding:20px 0;">
            <a href="<?= escape($activationLink) ?>" style="display:inline-block;padding:14px 30px;background-color:#6f42c1;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;">Activar Cuenta</a>
          </td></tr>
        </table>
        <p style="font-size:12px;word-break:break-all;background:#f8f9fa;padding:12px;border:1px solid #dee2e6;border-radius:4px;"><?= escape($activationLink) ?></p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
          <tr><td style="background-color:#fff3cd;border-left:4px solid #ffc107;padding:15px;">
            <p style="margin:0 0 8px 0;font-weight:bold;color:#856404;">🔒 Requisitos de contraseña:</p>
            <ul style="margin:0;padding-left:20px;color:#856404;font-size:13px;">
              <li>Mínimo <strong><?= APP_PASSWORD_MIN_LEN ?> caracteres</strong></li>
              <li>Al menos una <strong>letra mayúscula</strong></li>
              <li>Al menos una <strong>letra minúscula</strong></li>
              <li>Al menos un <strong>número</strong></li>
              <li>Al menos un <strong>carácter especial</strong> (!@#$%^&amp;*)</li>
            </ul>
          </td></tr>
        </table>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
          <tr><td style="background-color:#f8d7da;border-left:4px solid #dc3545;padding:15px;">
            <p style="margin:0 0 8px 0;font-weight:bold;color:#721c24;">⚠️ Importante:</p>
            <ul style="margin:0;padding-left:20px;color:#721c24;font-size:13px;">
              <li>Este enlace expirará en <strong>24 horas</strong></li>
              <li>Solo se puede usar <strong>una vez</strong></li>
              <li>Si no solicitaste esta cuenta, ignora este email</li>
            </ul>
          </td></tr>
        </table>
        <p style="font-size:13px;color:#666;">Código de verificación: <code style="background:#f8f9fa;padding:2px 6px;border-radius:3px;"><?= escape(substr($token, 0, 8)) ?></code></p>
      </td></tr>
      <tr><td style="background-color:#f8f9fa;padding:20px;text-align:center;border-top:1px solid #dee2e6;">
        <p style="margin:0;font-size:12px;color:#6c757d;">&copy; <?= date('Y') ?> <?= escape($appName) ?> - Sistema de Gestión Económica</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
    <?php
    $htmlMessage = ob_get_clean();

    return _sendEmail($to, $username, $subject, $htmlMessage);
}
