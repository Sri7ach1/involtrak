<?php

function userEditAjaxController() {
    global $con;
    require_once 'models/User.php';
    
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit();
    }

    validateSessionTimeout();

    $csrf_token = getPost('csrf_token');
    if (!verifyCSRFToken($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
        exit();
    }

    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'IP sospechosa']);
        exit();
    }

    $id = intval(getPost('id', 0));
    $name = getPost('name');
    $mail = getPost('mail');
    $pass1 = getPost('pass1');
    $pass2 = getPost('pass2');

    // Validar que el usuario solo pueda editar sus propios datos
    $userModel = new User($con);
    $userToEdit = $userModel->getUserById($id);
    
    if (!$userToEdit) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    if ($userToEdit['name'] !== $_SESSION['usuario']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar este usuario']);
        securityLog('WARNING', "Intento de editar otro usuario: {$userToEdit['name']} por {$_SESSION['usuario']}", [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        exit();
    }

    if(!empty($pass1) || !empty($pass2)) {
        if($pass1 !== $pass2) {
            echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
            exit();
        }
        if (!validatePasswordStrength($pass1)) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener mínimo 12 caracteres, mayúsculas, minúsculas, números y caracteres especiales']);
            exit();
        }
    }

    $password = !empty($pass1) ? $pass1 : null;
    $result = $userModel->updateUser($id, $name, $mail, $password);

    if ($result['success']) {
        // Actualizar nombre de usuario en la sesión si cambió su propio nombre
        if ($userToEdit['name'] === $_SESSION['usuario'] && $name !== $_SESSION['usuario']) {
            $_SESSION['usuario'] = $name;
            securityLog('INFO', "Usuario actualizado su propio nombre de {$userToEdit['name']} a {$name}", [
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
        }
        
        error_log("[AUDIT] Usuario actualizado: {$name} (ID:{$id}) por {$_SESSION['usuario']}");
    }

    header('Content-Type: application/json');
    echo json_encode($result);
}
?>
