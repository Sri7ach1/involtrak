<?php

function facturaDeleteAjaxController() {
    global $con;
    require_once 'models/Factura.php';

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

// Obtener ID
$id = intval(getPost('id'));

// Validaciones
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de factura inválido']);
    exit;
}

// Nota: Las facturas normalmente NO se eliminan, se anulan
// Este endpoint solo cambia el estado a "anulada"
try {
    $facturaModel = new Factura($con);
    
    // Verificar que la factura existe
    $facturaExistente = $facturaModel->getFacturaById($id);
    if (!$facturaExistente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
        exit;
    }
    
    // Verificar que no esté ya anulada
    if ($facturaExistente['estado'] === 'anulada') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La factura ya está anulada']);
        exit;
    }
    
    // Cambiar estado a anulada
    $resultado = $facturaModel->cambiarEstadoFactura($id, 'anulada');
    
    if ($resultado) {
        securityLog('INFO', 'Factura anulada', ['id' => $id]);
        echo json_encode([
            'success' => true, 
            'message' => 'Factura anulada exitosamente'
        ]);
    } else {
        throw new Exception('Error al anular la factura');
    }
} catch (Exception $e) {
    securityLog('ERROR', 'Error anulando factura: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al anular la factura']);
}
}
