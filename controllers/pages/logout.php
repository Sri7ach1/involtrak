<?php

/**
 * Controlador: Logout
 * Ruta: /logout
 * Método: POST recomendado, GET soportado como fallback
 */
function logoutController() {
    // Si es GET, mostrar confirmación o redirigir directamente
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Opción 1: Redirigir directamente (más simple)
        // Opción 2: Mostrar página de confirmación (más seguro)
        // Por simplicidad, hacemos logout directo en GET también
    }
    
    // Destruir la sesión
    error_log('[AUTH] User logout: ' . (isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'unknown'));
    
    session_destroy();
    
    // Limpiar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    redirect('login');
}

?>
