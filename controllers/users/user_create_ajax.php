<?php

function userCreateAjaxController() {
    global $con;
    require_once 'models/User.php';
    
    // Verificar autenticación
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit();
    }
    
    validateSessionTimeout();

    // Validar CSRF token
    $csrf_token = getPost('csrf_token');
    if (!verifyCSRFToken($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
        exit();
    }

    // Validar IP de sesión
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'IP sospechosa']);
        exit();
    }

    $name = getPost('name');
    $mail = getPost('mail');

    // Usar el modelo para crear usuario (sin contraseña)
    $userModel = new User($con);
    $result = $userModel->createUser($name, $mail);

    // Log de auditoría
    if ($result['success']) {
        error_log("[AUDIT] Usuario creado: {$name} por {$_SESSION['usuario']} desde {$_SERVER['REMOTE_ADDR']}");
    }

    header('Content-Type: application/json');
    echo json_encode($result);
}
?>
