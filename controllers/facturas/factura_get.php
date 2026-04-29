<?php

function facturaGetController() {
    global $con;
    require_once 'models/Factura.php';

// Verificar autenticación
if (!isLoggedIn() || !validateSessionIntegrity()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id = intval(getGet('id'));

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $facturaModel = new Factura($con);
    $factura = $facturaModel->getFacturaById($id);
    
    if ($factura) {
        echo json_encode($factura);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener la factura']);
}
}
