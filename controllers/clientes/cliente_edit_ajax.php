<?php

function clienteEditAjaxController() {
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
$id = intval(getPost('id'));
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

if ($id <= 0) {
    $errores[] = 'ID de cliente inválido';
}

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
        // Verificar si el email ya existe (excepto el cliente actual)
        $clienteModel = new Cliente($con);
        if ($clienteModel->existeEmail($email, $id)) {
            $errores[] = 'Ya existe otro cliente con ese email';
        }
    }
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    exit;
}

// Actualizar cliente
try {
    $clienteModel = new Cliente($con);
    
    // Verificar que el cliente existe
    $clienteExistente = $clienteModel->getClienteById($id);
    if (!$clienteExistente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
        exit;
    }
    
    $resultado = $clienteModel->updateCliente($id, $nombre, $apellidos, $direccion, $poblacion, $provincia, $codigo_postal, $pais, $telefono, $email);
    
    if ($resultado) {
        securityLog('INFO', 'Cliente actualizado', ['id' => $id, 'nombre' => $nombre . ' ' . $apellidos]);
        echo json_encode([
            'success' => true, 
            'message' => 'Cliente actualizado exitosamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el cliente');
    }
} catch (Exception $e) {
    securityLog('ERROR', 'Error actualizando cliente: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el cliente']);
}
}
