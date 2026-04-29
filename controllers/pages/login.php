<?php

/**
 * Controlador: Login
 * Ruta: /login
 */
function loginController() {
    global $con;
    
    // Prevenir login si ya está autenticado
    if (isLoggedIn() && validateSessionIntegrity()) {
        redirect('panel');
    }
    
    require_once 'models/User.php';

    // Output buffering para manejar redirecciones
    ob_start();

    $errores = [];
    $usuario = getPost('usuario');
    $password = getPost('password');

    // Procesar login si se envía el formulario
    if(isset($_POST['entrar'])) {
        // Rate limiting
        if (!checkLoginRateLimit($usuario)) {
            $errores['otro'] = 'Demasiados intentos. Intenta de nuevo en 15 minutos.';
        }
        
        // Validar CSRF token PRIMERO
        $csrf_token = getPost('csrf_token');
        if (!verifyCSRFToken($csrf_token)) {
            $errores['csrf'] = 'Token de seguridad inválido o expirado. Por favor, intenta nuevamente.';
        }
        
        // Validar campos
        if(empty($usuario)) {
            $errores['usuario'] = 'El campo usuario es obligatorio.';
        }
        if(empty($password)) {
            $errores['contra'] = 'El campo contraseña es obligatorio.';
        }

        // Si no hay errores, intentar autenticar
        if(empty($errores)) {
            $userModel = new User($con);
            $user = $userModel->getUserByName($usuario);

            if($user && $userModel->verifyPassword($password, $user['pass'])) {
                // Login exitoso - limpiar rate limit
                clearLoginRateLimit($usuario);
                ob_end_clean();
                
                // Regenerar ID de sesión
                session_regenerate_id(true);
                
                // Guardar datos del usuario
                $_SESSION['usuario'] = $user['name'];
                $_SESSION['correo'] = $user['mail'];
                $_SESSION['login'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['ultimoAcceso'] = time();
                $_SESSION['timeout']      = APP_SESSION_TIMEOUT;
                
                // Registrar login exitoso
                error_log('[AUTH] User login successful: ' . $user['name'] . ' from IP: ' . $_SERVER['REMOTE_ADDR']);
                
                // Redirigir ANTES de cualquier output
                redirect('panel');
            } else {
                // Login fallido
                error_log('[AUTH] Failed login attempt for user: ' . $usuario . ' from IP: ' . $_SERVER['REMOTE_ADDR']);
                $errores['otro'] = 'Usuario o contraseña incorrectos.';
            }
        }
    }

    // Mostrar formulario de login (con o sin errores)
    require 'templates/login.php';
    ob_end_flush();
}

?>
