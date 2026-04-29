<?php

function clienteDeleteAjaxController() {
    global $con;
    require_once 'models/Cliente.php';

// Verificar autenticación y CSRF
if (!isLoggedIn() || !validateSessionIntegrity()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!verifyCSRFToken(getPost('csrf_token'))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

// Obtener ID y estado
$id = intval(getPost('id'));
$estado = trim(getPost('estado'));

// Validaciones
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cliente inválido']);
    exit;
}

if (!in_array($estado, ['activo', 'inactivo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit;
}

// Cambiar estado del cliente
try {
    $clienteModel = new Cliente($con);
    
    // Verificar que el cliente existe
    $clienteExistente = $clienteModel->getClienteById($id);
    if (!$clienteExistente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
        exit;
    }
    
    $resultado = $clienteModel->cambiarEstadoCliente($id, $estado);
    
    if ($resultado) {
        securityLog('INFO', 'Estado de cliente cambiado', ['id' => $id, 'estado' => $estado]);
        echo json_encode([
            'success' => true, 
            'message' => 'Estado del cliente actualizado exitosamente'
        ]);
    } else {
        throw new Exception('Error al cambiar el estado del cliente');
    }
} catch (Exception $e) {
    securityLog('ERROR', 'Error cambiando estado del cliente: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado del cliente']);
}
}
