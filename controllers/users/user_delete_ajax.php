<?php

function userDeleteAjaxController() {
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

    $currentUser = getCurrentUsername();
    $userModel = new User($con);
    $user = $userModel->getUserById($id);

    if($user && $user['name'] === $currentUser) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario']);
        exit();
    }

    $result = $userModel->deleteUser($id);

    if ($result['success']) {
        $deletedUser = $user['name'] ?? "ID:{$id}";
        error_log("[AUDIT] Usuario eliminado: {$deletedUser} por {$_SESSION['usuario']}");
    }

    header('Content-Type: application/json');
    echo json_encode($result);
}
?>
