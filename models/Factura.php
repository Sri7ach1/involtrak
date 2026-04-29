<?php

class Factura {
    private $con;

    public function __construct($connection) {
        $this->con = $connection;
    }

    /**
     * Generar siguiente número de factura (formato DC-1000, DC-1001, etc.)
     */
    private function generarNumeroFactura() {
        $sql = "SELECT numero_factura FROM facturas ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($this->con, $sql);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Extraer el número de la última factura (DC-1000 -> 1000)
            $ultimoNumero = intval(substr($row['numero_factura'], 3));
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            // Primera factura
            $nuevoNumero = 1000;
        }
        
        return 'DC-' . $nuevoNumero;
    }

    /**
     * Obtener todas las facturas con datos de cliente y usuario
     */
    public function getAllFacturas($estado = null) {
        if ($estado !== null) {
            $sql = "SELECT f.*, 
                           CONCAT(c.nombre, ' ', c.apellidos) as cliente_nombre,
                           c.email as cliente_email,
                           u.name as usuario_nombre
                    FROM facturas f
                    INNER JOIN clientes c ON f.cliente_id = c.id
                    INNER JOIN usuarios u ON f.usuario_id = u.id
                    WHERE f.estado = ?
                    ORDER BY f.id DESC";
            $stmt = mysqli_prepare($this->con, $sql);
            mysqli_stmt_bind_param($stmt, "s", $estado);
        } else {
            $sql = "SELECT f.*, 
                           CONCAT(c.nombre, ' ', c.apellidos) as cliente_nombre,
                           c.email as cliente_email,
                           u.name as usuario_nombre
                    FROM facturas f
                    INNER JOIN clientes c ON f.cliente_id = c.id
                    INNER JOIN usuarios u ON f.usuario_id = u.id
                    ORDER BY f.id DESC";
            $stmt = mysqli_prepare($this->con, $sql);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $facturas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $facturas[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $facturas;
    }

    /**
     * Obtener factura por ID con todos sus detalles
     */
    public function getFacturaById($id) {
        $id = intval($id);
        $sql = "SELECT f.*, 
                       c.nombre as cliente_nombre,
                       c.apellidos as cliente_apellidos,
                       c.direccion as cliente_direccion,
                       c.poblacion as cliente_poblacion,
                       c.provincia as cliente_provincia,
                       c.codigo_postal as cliente_codigo_postal,
                       c.pais as cliente_pais,
                       c.telefono as cliente_telefono,
                       c.email as cliente_email,
                       u.name as usuario_nombre,
                       u.mail as usuario_email
                FROM facturas f
                INNER JOIN clientes c ON f.cliente_id = c.id
                INNER JOIN usuarios u ON f.usuario_id = u.id
                WHERE f.id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $factura = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Obtener líneas de la factura
        if ($factura) {
            $factura['lineas'] = $this->getLineasFactura($id);
        }
        
        return $factura;
    }

    /**
     * Obtener líneas de una factura
     */
    public function getLineasFactura($facturaId) {
        $facturaId = intval($facturaId);
        $sql = "SELECT * FROM facturas_lineas WHERE factura_id = ? ORDER BY id ASC";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $facturaId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $lineas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $lineas[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $lineas;
    }

    /**
     * Crear nueva factura con sus líneas
     */
    public function createFactura($clienteId, $usuarioId, $lineas, $porcentajeImpuesto = 21.00) {
        require_once 'models/Cliente.php';
        
        $clienteId = intval($clienteId);
        $usuarioId = intval($usuarioId);
        $porcentajeImpuesto = floatval($porcentajeImpuesto);
        
        // Validar que el porcentaje esté en un rango razonable
        if ($porcentajeImpuesto < 0 || $porcentajeImpuesto > 100) {
            error_log('[ERROR] Porcentaje de impuesto inválido: ' . $porcentajeImpuesto);
            return false;
        }
        
        // Obtener datos del cliente
        $clienteModel = new Cliente($this->con);
        $cliente = $clienteModel->getClienteById($clienteId);
        
        if (!$cliente || $cliente['estado'] !== 'activo') {
            error_log('[ERROR] No se puede emitir factura a cliente inactivo o inexistente: ' . $clienteId);
            return false;
        }
        
        // Calcular totales
        $subtotal = 0;
        foreach ($lineas as $linea) {
            $subtotal += floatval($linea['subtotal']);
        }
        
        $impuesto = round($subtotal * ($porcentajeImpuesto / 100), 2);
        $total = round($subtotal + $impuesto, 2);
        
        // Generar número de factura
        $numeroFactura = $this->generarNumeroFactura();
        $fechaEmision = date('Y-m-d H:i:s');
        
        // Iniciar transacción
        mysqli_begin_transaction($this->con);
        
        try {
            // Insertar factura
            $sql = "INSERT INTO facturas 
                    (numero_factura, cliente_id, usuario_id, porcentaje_impuesto, fecha_emision, subtotal, impuesto, total, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')";
            $stmt = mysqli_prepare($this->con, $sql);
            
            if (!$stmt) {
                throw new Exception('Error preparando statement de factura: ' . mysqli_error($this->con));
            }
            
            mysqli_stmt_bind_param($stmt, "siiisddd", 
                $numeroFactura, $clienteId, $usuarioId, $porcentajeImpuesto, $fechaEmision, $subtotal, $impuesto, $total);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error ejecutando statement de factura: ' . mysqli_stmt_error($stmt));
            }
            
            $facturaId = mysqli_insert_id($this->con);
            mysqli_stmt_close($stmt);
            
            // Insertar líneas de factura
            $sqlLinea = "INSERT INTO facturas_lineas 
                         (factura_id, articulo, descripcion, cantidad, precio_unitario, subtotal) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            $stmtLinea = mysqli_prepare($this->con, $sqlLinea);
            
            if (!$stmtLinea) {
                throw new Exception('Error preparando statement de líneas: ' . mysqli_error($this->con));
            }
            
            foreach ($lineas as $linea) {
                mysqli_stmt_bind_param($stmtLinea, "issddd",
                    $facturaId,
                    $linea['articulo'],
                    $linea['descripcion'],
                    $linea['cantidad'],
                    $linea['precio_unitario'],
                    $linea['subtotal']
                );
                
                if (!mysqli_stmt_execute($stmtLinea)) {
                    throw new Exception('Error ejecutando statement de líneas: ' . mysqli_stmt_error($stmtLinea));
                }
            }
            
            mysqli_stmt_close($stmtLinea);
            
            // Commit transacción
            mysqli_commit($this->con);
            
            error_log('[INFO] Factura creada: ' . $numeroFactura . ' (ID: ' . $facturaId . ') con IVA manual: ' . $porcentajeImpuesto . '%');
            return $facturaId;
            
        } catch (Exception $e) {
            // Rollback en caso de error
            mysqli_rollback($this->con);
            error_log('[ERROR] ' . $e->getMessage());
            securityLog('ERROR', 'Exception en createFactura', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * Actualizar estado de factura
     */
    public function cambiarEstadoFactura($id, $estado) {
        $id = intval($id);
        
        // Validar estado
        if (!in_array($estado, ['pendiente', 'pagada', 'anulada'])) {
            error_log('[ERROR] Estado inválido: ' . $estado);
            return false;
        }
        
        $sql = "UPDATE facturas SET estado = ? WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        
        if (!$stmt) {
            error_log('[ERROR] Error preparando statement: ' . mysqli_error($this->con));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "si", $estado, $id);
        $result = mysqli_stmt_execute($stmt);
        
        if (!$result) {
            error_log('[ERROR] Error ejecutando statement: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        mysqli_stmt_close($stmt);
        error_log('[INFO] Estado de factura ' . $id . ' cambiado a: ' . $estado);
        return true;
    }

    /**
     * Buscar facturas por cliente o número de factura
     */
    public function searchFacturas($search) {
        $searchTerm = "%{$search}%";
        $sql = "SELECT f.*, 
                       CONCAT(c.nombre, ' ', c.apellidos) as cliente_nombre,
                       c.email as cliente_email,
                       u.name as usuario_nombre
                FROM facturas f
                INNER JOIN clientes c ON f.cliente_id = c.id
                INNER JOIN usuarios u ON f.usuario_id = u.id
                WHERE f.numero_factura LIKE ? 
                OR c.nombre LIKE ? 
                OR c.apellidos LIKE ?
                ORDER BY f.id DESC";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $searchTerm, $searchTerm, $searchTerm);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $facturas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $facturas[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $facturas;
    }

    /**
     * Obtener facturas de un cliente específico
     */
    public function getFacturasByCliente($clienteId) {
        $clienteId = intval($clienteId);
        $sql = "SELECT f.*, u.name as usuario_nombre
                FROM facturas f
                INNER JOIN usuarios u ON f.usuario_id = u.id
                WHERE f.cliente_id = ?
                ORDER BY f.fecha_emision DESC";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $clienteId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $facturas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $facturas[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $facturas;
    }

    /**
     * Obtener estadísticas de facturas
     */
    public function getEstadisticas() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END), 0) as pendientes,
                    COALESCE(SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END), 0) as pagadas,
                    COALESCE(SUM(CASE WHEN estado = 'anulada' THEN 1 ELSE 0 END), 0) as anuladas,
                    COALESCE(SUM(CASE WHEN estado != 'anulada' THEN total ELSE 0 END), 0) as total_facturado,
                    COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN total ELSE 0 END), 0) as total_pendiente,
                    COALESCE(SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END), 0) as total_cobrado
                FROM facturas";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Asegurar que siempre devuelve valores numéricos
        return [
            'total' => intval($stats['total'] ?? 0),
            'pendientes' => intval($stats['pendientes'] ?? 0),
            'pagadas' => intval($stats['pagadas'] ?? 0),
            'anuladas' => intval($stats['anuladas'] ?? 0),
            'total_facturado' => floatval($stats['total_facturado'] ?? 0),
            'total_pendiente' => floatval($stats['total_pendiente'] ?? 0),
            'total_cobrado' => floatval($stats['total_cobrado'] ?? 0)
        ];
    }

    /**
     * Eliminar factura (solo si está en estado 'anulada')
     */
    public function deleteFactura($id) {
        $id = intval($id);
        
        // Verificar estado
        $sqlCheck = "SELECT estado FROM facturas WHERE id = ?";
        $stmtCheck = mysqli_prepare($this->con, $sqlCheck);
        mysqli_stmt_bind_param($stmtCheck, "i", $id);
        mysqli_stmt_execute($stmtCheck);
        $resultCheck = mysqli_stmt_get_result($stmtCheck);
        $factura = mysqli_fetch_assoc($resultCheck);
        mysqli_stmt_close($stmtCheck);
        
        if (!$factura) {
            error_log('[ERROR] Factura no encontrada: ' . $id);
            return false;
        }
        
        if ($factura['estado'] !== 'anulada') {
            error_log('[ERROR] Solo se pueden eliminar facturas anuladas. Factura ' . $id . ' está en estado: ' . $factura['estado']);
            return false;
        }
        
        // Eliminar factura (las líneas se eliminan automáticamente por CASCADE)
        $sql = "DELETE FROM facturas WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        
        if (!$stmt) {
            error_log('[ERROR] Error preparando statement: ' . mysqli_error($this->con));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "i", $id);
        $result = mysqli_stmt_execute($stmt);
        
        if (!$result) {
            error_log('[ERROR] Error ejecutando statement: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        mysqli_stmt_close($stmt);
        error_log('[INFO] Factura eliminada con ID: ' . $id);
        return true;
    }
}
