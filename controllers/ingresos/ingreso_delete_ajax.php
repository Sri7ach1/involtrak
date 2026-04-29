<?php

function ingresoDeleteAjaxController() {
    global $con;
    require_once 'models/Ingreso.php';
    
    header('Content-Type: application/json');
    
    // Verificar autenticación
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }
    
    validateSessionTimeout();
    
    // Validar token CSRF
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCSRFToken($csrf_token)) {
        error_log('[SECURITY] CSRF token inválido en ingreso delete para usuario: ' . getCurrentUsername());
        echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
        exit;
    }
    
    // Validar ID
    if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }
    
    $id = intval($_POST['id']);
    
    try {
        $ingresoModel = new Ingreso($con);
        $result = $ingresoModel->deleteIngreso($id);
        
        if($result) {
            echo json_encode(['success' => true, 'message' => 'Ingreso eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el ingreso']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    mysqli_close($con);
}
?>
