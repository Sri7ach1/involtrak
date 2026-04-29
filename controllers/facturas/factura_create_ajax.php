<?php

function facturaCreateAjaxController() {
    global $con;
    
    try {
    require_once 'models/Factura.php';
    require_once 'models/Cliente.php';
    require_once 'models/User.php';

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
$clienteId = intval(getPost('cliente_id'));
$lineasJSON = getPost('lineas');
$porcentajeImpuesto = floatval(getPost('porcentaje_impuesto', 21.00)); // Default 21%

// Validaciones básicas
$errores = [];

if ($clienteId <= 0) {
    $errores[] = 'Debe seleccionar un cliente';
}

if (empty($lineasJSON)) {
    $errores[] = 'Debe agregar al menos un artículo a la factura';
}

// Validar porcentaje de impuesto
if ($porcentajeImpuesto < 0 || $porcentajeImpuesto > 100) {
    $errores[] = 'El porcentaje de impuesto debe estar entre 0% y 100%';
}

// Verificar que el cliente existe y está activo
$clienteModel = new Cliente($con);
$cliente = $clienteModel->getClienteById($clienteId);

if (!$cliente) {
    $errores[] = 'El cliente seleccionado no existe';
} elseif ($cliente['estado'] !== 'activo') {
    $errores[] = 'No se puede emitir factura a un cliente inactivo';
}

// Parsear y validar líneas
$lineas = json_decode($lineasJSON, true);
if (!is_array($lineas) || empty($lineas)) {
    $errores[] = 'Las líneas de factura no son válidas';
} else {
    foreach ($lineas as $index => $linea) {
        if (empty($linea['articulo'])) {
            $errores[] = "Línea " . ($index + 1) . ": El artículo es obligatorio";
        }
        
        if (empty($linea['descripcion'])) {
            $errores[] = "Línea " . ($index + 1) . ": La descripción es obligatoria";
        } elseif (!validateDescripcion($linea['descripcion'])) {
            $errores[] = "Línea " . ($index + 1) . ": La descripción debe tener entre 3 y 500 caracteres";
        }
        
        if (!validateCantidad($linea['cantidad'])) {
            $errores[] = "Línea " . ($index + 1) . ": La cantidad debe ser un número positivo";
        }
        
        if (!validatePrecio($linea['precio_unitario'])) {
            $errores[] = "Línea " . ($index + 1) . ": El precio unitario debe ser un número válido";
        }
        
        // Calcular y validar subtotal
        $cantidad = floatval($linea['cantidad']);
        $precioUnitario = floatval($linea['precio_unitario']);
        $subtotalCalculado = round($cantidad * $precioUnitario, 2);
        
        if (abs($subtotalCalculado - floatval($linea['subtotal'])) > 0.01) {
            $errores[] = "Línea " . ($index + 1) . ": El subtotal no coincide con cantidad x precio";
        }
    }
}

if (!empty($errores)) {
    securityLog('WARNING', 'Errores de validación al crear factura', ['errores' => $errores]);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    exit;
}

securityLog('DEBUG', 'Pasó validaciones para crear factura', ['usuario_session' => $_SESSION['usuario'] ?? 'anonymous']);

// Obtener ID del usuario actual
$userModel = new User($con);
$usuario = $userModel->getUserByName($_SESSION['usuario']);
    $usuarioId = $usuario['id'];

securityLog('DEBUG', 'Datos para crear factura', [
    'usuario_id' => $usuarioId,
    'cliente_id' => $clienteId,
    'lineas_count' => is_array($lineas) ? count($lineas) : 0
]);

// Crear factura
try {
    $facturaModel = new Factura($con);
    securityLog('DEBUG', 'Invocando Factura::createFactura', ['cliente_id' => $clienteId, 'usuario_id' => $usuarioId, 'porcentaje_impuesto' => $porcentajeImpuesto]);
    $facturaId = $facturaModel->createFactura($clienteId, $usuarioId, $lineas, $porcentajeImpuesto);
    securityLog('DEBUG', 'Resultado createFactura', ['factura_id' => $facturaId ?: false]);
    
    if ($facturaId) {
        $factura = $facturaModel->getFacturaById($facturaId);
        securityLog('INFO', 'Factura creada', [
            'id' => $facturaId, 
            'numero' => $factura['numero_factura'],
            'cliente' => $clienteId,
            'total' => $factura['total']
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Factura creada exitosamente',
            'id' => $facturaId,
            'numero_factura' => $factura['numero_factura']
        ]);
    } else {
        throw new Exception('Error al crear la factura');
    }
} catch (Exception $e) {
    securityLog('ERROR', 'Error creando factura: ' . $e->getMessage());
    error_log('[FACTURA CREATE ERROR] Exception: ' . $e->getMessage());
    error_log('[FACTURA CREATE ERROR] File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    error_log('[FACTURA CREATE ERROR] Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear la factura: ' . $e->getMessage()]);
}
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fatal: ' . $t->getMessage(), 'file' => $t->getFile(), 'line' => $t->getLine()]);
}
}
