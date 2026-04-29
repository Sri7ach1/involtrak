<?php

function facturaEditAjaxController() {
    global $con;
    require_once 'models/Factura.php';
    require_once 'models/Ingreso.php';

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
    echo json_encode(['success' => false, 'message' => 'ID de factura inválido']);
    exit;
}

if (!in_array($estado, ['pendiente', 'pagada', 'anulada'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit;
}

// Cambiar estado de la factura
try {
    $facturaModel = new Factura($con);
    $ingresoModel = new Ingreso($con);
    
    // Verificar que la factura existe
    $facturaExistente = $facturaModel->getFacturaById($id);
    if (!$facturaExistente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
        exit;
    }
    
    $estadoAnterior = $facturaExistente['estado'];
    $resultado = $facturaModel->cambiarEstadoFactura($id, $estado);
    
    if ($resultado) {
        // Gestión automática de ingresos según el estado
        $period_type_puntual = 3; // ID del tipo "puntual" en period_types
        
        // Si cambia a PAGADA y NO tenía ingreso, crear ingreso
        if ($estado === 'pagada' && $estadoAnterior !== 'pagada') {
            if (!$ingresoModel->existeIngresoParaFactura($id)) {
                $descripcion = "Factura " . $facturaExistente['numero_factura'] . " - " . $facturaExistente['cliente_nombre'] . " " . $facturaExistente['cliente_apellidos'];
                $fechaIngreso = date('Y-m-d', strtotime($facturaExistente['fecha_emision']));
                
                $ingresoId = $ingresoModel->createIngresoDesdeFactura(
                    $fechaIngreso,
                    $facturaExistente['total'],
                    $period_type_puntual,
                    $descripcion,
                    $id
                );
                
                if ($ingresoId) {
                    securityLog('INFO', 'Ingreso creado automáticamente desde factura', [
                        'factura_id' => $id,
                        'ingreso_id' => $ingresoId,
                        'importe' => $facturaExistente['total']
                    ]);
                }
            }
        }
        
        // Si cambia de PAGADA a otro estado, eliminar el ingreso asociado
        if ($estadoAnterior === 'pagada' && $estado !== 'pagada') {
            if ($ingresoModel->existeIngresoParaFactura($id)) {
                $ingresoModel->deleteIngresoPorFactura($id);
                securityLog('INFO', 'Ingreso eliminado al cambiar estado de factura', [
                    'factura_id' => $id,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $estado
                ]);
            }
        }
        
        securityLog('INFO', 'Estado de factura cambiado', [
            'id' => $id, 
            'numero' => $facturaExistente['numero_factura'],
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estado
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Estado de la factura actualizado exitosamente'
        ]);
    } else {
        throw new Exception('Error al cambiar el estado de la factura');
    }
} catch (Exception $e) {
    securityLog('ERROR', 'Error cambiando estado de factura: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado de la factura']);
}
}
