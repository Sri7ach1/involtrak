<?php

function clienteGetController() {
    global $con;
    require_once 'models/Cliente.php';

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
    $clienteModel = new Cliente($con);
    $cliente = $clienteModel->getClienteById($id);
    
    if ($cliente) {
        echo json_encode($cliente);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener el cliente']);
}
}
