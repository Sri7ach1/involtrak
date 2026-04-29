<?php

function clienteCreateAjaxController() {
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

// Obtener datos del formulario
$nombre = trim(getPost('nombre'));
$apellidos = trim(getPost('apellidos'));
$direccion = trim(getPost('direccion'));
$poblacion = trim(getPost('poblacion'));
$provincia = trim(getPost('provincia'));
$codigo_postal = trim(getPost('codigo_postal'));
$pais = trim(getPost('pais')) ?: 'España';
$telefono = trim(getPost('telefono'));
$email = trim(getPost('email'));

// Validaciones
$errores = [];

if (empty($nombre)) {
    $errores[] = 'El nombre es obligatorio';
} elseif (!validateNombre($nombre)) {
    $errores[] = 'El nombre solo puede contener letras y espacios';
}

if (empty($apellidos)) {
    $errores[] = 'Los apellidos son obligatorios';
} elseif (!validateNombre($apellidos)) {
    $errores[] = 'Los apellidos solo pueden contener letras y espacios';
}

if (!empty($direccion) && !validateDireccion($direccion)) {
    $errores[] = 'La dirección debe tener entre 5 y 255 caracteres';
}

if (!empty($telefono) && !validateTelefono($telefono)) {
    $errores[] = 'El formato del teléfono no es válido';
}

if (!empty($email)) {
    if (!validateEmail($email)) {
        $errores[] = 'El formato del email no es válido';
    } else {
        // Verificar si el email ya existe
        $clienteModel = new Cliente($con);
        if ($clienteModel->existeEmail($email)) {
            $errores[] = 'Ya existe un cliente con ese email';
        }
    }
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    exit;
}

// Crear cliente
try {
    $clienteModel = new Cliente($con);
    $clienteId = $clienteModel->createCliente($nombre, $apellidos, $direccion, $poblacion, $provincia, $codigo_postal, $pais, $telefono, $email);
    
    if ($clienteId) {
        securityLog('INFO', 'Cliente creado', ['id' => $clienteId, 'nombre' => $nombre . ' ' . $apellidos]);
        echo json_encode([
            'success' => true, 
            'message' => 'Cliente creado exitosamente',
            'id' => $clienteId
        ]);
    } else {
        throw new Exception('Error al crear el cliente');
    }
} catch (Exception $e) {
    securityLog('ERROR', 'Error creando cliente: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear el cliente']);
}
}
