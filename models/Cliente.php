<?php

class Cliente {
    private $con;

    public function __construct($connection) {
        $this->con = $connection;
    }

    /**
     * Obtener todos los clientes
     */
    public function getAllClientes($estado = null) {
        if ($estado !== null) {
            $sql = "SELECT * FROM clientes WHERE estado = ? ORDER BY id DESC";
            $stmt = mysqli_prepare($this->con, $sql);
            mysqli_stmt_bind_param($stmt, "s", $estado);
        } else {
            $sql = "SELECT * FROM clientes ORDER BY id DESC";
            $stmt = mysqli_prepare($this->con, $sql);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $clientes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $clientes[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $clientes;
    }

    /**
     * Obtener cliente por ID
     */
    public function getClienteById($id) {
        $id = intval($id);
        $sql = "SELECT * FROM clientes WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $cliente = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $cliente;
    }

    /**
     * Buscar clientes por nombre, apellidos o email
     */
    public function searchClientes($search) {
        $searchTerm = "%{$search}%";
        $sql = "SELECT * FROM clientes 
                WHERE nombre LIKE ? 
                OR apellidos LIKE ? 
                OR email LIKE ? 
                ORDER BY id DESC";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $searchTerm, $searchTerm, $searchTerm);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $clientes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $clientes[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $clientes;
    }

    /**
     * Crear nuevo cliente
     */
    public function createCliente($nombre, $apellidos, $direccion, $poblacion, $provincia, $codigo_postal, $pais, $telefono, $email) {
        $sql = "INSERT INTO clientes (nombre, apellidos, direccion, poblacion, provincia, codigo_postal, pais, telefono, email, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')";
        $stmt = mysqli_prepare($this->con, $sql);
        
        if (!$stmt) {
            error_log('[ERROR] Error preparando statement: ' . mysqli_error($this->con));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "sssssssss", $nombre, $apellidos, $direccion, $poblacion, $provincia, $codigo_postal, $pais, $telefono, $email);
        $result = mysqli_stmt_execute($stmt);
        
        if (!$result) {
            error_log('[ERROR] Error ejecutando statement: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        $insertId = mysqli_insert_id($this->con);
        mysqli_stmt_close($stmt);
        
        error_log('[INFO] Cliente creado con ID: ' . $insertId);
        return $insertId;
    }

    /**
     * Actualizar cliente
     */
    public function updateCliente($id, $nombre, $apellidos, $direccion, $poblacion, $provincia, $codigo_postal, $pais, $telefono, $email) {
        $id = intval($id);
        $sql = "UPDATE clientes 
                SET nombre = ?, apellidos = ?, direccion = ?, poblacion = ?, provincia = ?, codigo_postal = ?, pais = ?, telefono = ?, email = ? 
                WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        
        if (!$stmt) {
            error_log('[ERROR] Error preparando statement: ' . mysqli_error($this->con));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "sssssssssi", $nombre, $apellidos, $direccion, $poblacion, $provincia, $codigo_postal, $pais, $telefono, $email, $id);
        $result = mysqli_stmt_execute($stmt);
        
        if (!$result) {
            error_log('[ERROR] Error ejecutando statement: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        mysqli_stmt_close($stmt);
        error_log('[INFO] Cliente actualizado con ID: ' . $id);
        return true;
    }

    /**
     * Cambiar estado de cliente (activar/desactivar)
     */
    public function cambiarEstadoCliente($id, $estado) {
        $id = intval($id);
        
        // Validar estado
        if (!in_array($estado, ['activo', 'inactivo'])) {
            error_log('[ERROR] Estado inválido: ' . $estado);
            return false;
        }
        
        $sql = "UPDATE clientes SET estado = ? WHERE id = ?";
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
        error_log('[INFO] Estado de cliente ' . $id . ' cambiado a: ' . $estado);
        return true;
    }

    /**
     * Verificar si un cliente está activo
     */
    public function isClienteActivo($id) {
        $cliente = $this->getClienteById($id);
        return $cliente && $cliente['estado'] === 'activo';
    }

    /**
     * Verificar si existe un cliente con el mismo email
     */
    public function existeEmail($email, $exceptoId = null) {
        if ($exceptoId !== null) {
            $sql = "SELECT id FROM clientes WHERE email = ? AND id != ?";
            $stmt = mysqli_prepare($this->con, $sql);
            mysqli_stmt_bind_param($stmt, "si", $email, $exceptoId);
        } else {
            $sql = "SELECT id FROM clientes WHERE email = ?";
            $stmt = mysqli_prepare($this->con, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $existe = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        return $existe;
    }

    /**
     * Eliminar cliente (solo si no tiene facturas asociadas)
     */
    public function deleteCliente($id) {
        $id = intval($id);
        
        // Verificar si tiene facturas
        $sqlCheck = "SELECT COUNT(*) as total FROM facturas WHERE cliente_id = ?";
        $stmtCheck = mysqli_prepare($this->con, $sqlCheck);
        mysqli_stmt_bind_param($stmtCheck, "i", $id);
        mysqli_stmt_execute($stmtCheck);
        $resultCheck = mysqli_stmt_get_result($stmtCheck);
        $row = mysqli_fetch_assoc($resultCheck);
        mysqli_stmt_close($stmtCheck);
        
        if ($row['total'] > 0) {
            error_log('[ERROR] No se puede eliminar el cliente ' . $id . ' porque tiene facturas asociadas');
            return false;
        }
        
        // Eliminar cliente
        $sql = "DELETE FROM clientes WHERE id = ?";
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
        error_log('[INFO] Cliente eliminado con ID: ' . $id);
        return true;
    }

    /**
     * Obtener estadísticas de clientes
     */
    public function getEstadisticas() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END), 0) as activos,
                    COALESCE(SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END), 0) as inactivos
                FROM clientes";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Asegurar que siempre devuelve valores numéricos
        return [
            'total' => intval($stats['total'] ?? 0),
            'activos' => intval($stats['activos'] ?? 0),
            'inactivos' => intval($stats['inactivos'] ?? 0)
        ];
    }
}
