<?php

function clientesActivosController() {
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

try {
    $clienteModel = new Cliente($con);
    $clientes = $clienteModel->getAllClientes('activo');
    echo json_encode($clientes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener clientes']);
}
}
