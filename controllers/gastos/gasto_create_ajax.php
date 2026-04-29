<?php

function gastoCreateAjaxController() {
    global $con;
    require_once 'models/Gasto.php';
    
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
        error_log('[SECURITY] CSRF token inválido en gasto create para usuario: ' . getCurrentUsername());
        echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
        exit;
    }
    
    // Validar datos requeridos
    if(!isset($_POST['fecha']) || !isset($_POST['importe']) || !isset($_POST['period_type_id'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
        exit;
    }
    
    $fecha = trim($_POST['fecha']);
    $importe = floatval($_POST['importe']);
    $period_type_id = intval($_POST['period_type_id']);
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    
    // Validaciones
    if(empty($fecha)) {
        echo json_encode(['success' => false, 'message' => 'La fecha es obligatoria']);
        exit;
    }
    
    if($importe <= 0) {
        echo json_encode(['success' => false, 'message' => 'El importe debe ser mayor a 0']);
        exit;
    }
    
    if($period_type_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un tipo de periodo']);
        exit;
    }
    
    try {
        $gastoModel = new Gasto($con);
        $result = $gastoModel->createGasto($fecha, $importe, $period_type_id, $descripcion);
        
        if($result) {
            echo json_encode(['success' => true, 'message' => 'Gasto creado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el gasto']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    mysqli_close($con);
}
?>
