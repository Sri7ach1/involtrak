<?php

class Ingreso {
    private $con;

    public function __construct($connection) {
        $this->con = $connection;
    }

    /**
     * Crear nuevo ingreso
     */
    public function createIngreso($fecha, $importe, $period_type_id, $descripcion) {
        $sql = "INSERT INTO ingresos (fecha, importe, period_type_id, descripcion) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "sdis", $fecha, $importe, $period_type_id, $descripcion);

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    /**
     * Crear ingreso desde factura (nuevo método)
     */
    public function createIngresoDesdeFactura($fecha, $importe, $period_type_id, $descripcion, $factura_id) {
        $sql = "INSERT INTO ingresos (fecha, importe, period_type_id, descripcion, factura_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->con, $sql);
        
        if (!$stmt) {
            error_log('[ERROR] Error preparando statement: ' . mysqli_error($this->con));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "sdisi", $fecha, $importe, $period_type_id, $descripcion, $factura_id);
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            $insertId = mysqli_insert_id($this->con);
            mysqli_stmt_close($stmt);
            return $insertId;
        } else {
            error_log('[ERROR] Error ejecutando statement: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Verificar si ya existe un ingreso para una factura
     */
    public function existeIngresoParaFactura($factura_id) {
        $sql = "SELECT id FROM ingresos WHERE factura_id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $factura_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $existe = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        return $existe;
    }

    /**
     * Eliminar ingreso por factura_id
     */
    public function deleteIngresoPorFactura($factura_id) {
        $sql = "DELETE FROM ingresos WHERE factura_id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $factura_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    /**
     * Actualizar ingreso
     */
    public function updateIngreso($id, $fecha, $importe, $period_type_id, $descripcion) {
        $sql = "UPDATE ingresos SET fecha = ?, importe = ?, period_type_id = ?, descripcion = ? WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "sdisi", $fecha, $importe, $period_type_id, $descripcion, $id);

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    /**
     * Obtener todos los ingresos
     */
    public function getAllIngresos() {
        $sql = "SELECT i.id, i.fecha, i.importe, i.period_type_id, i.descripcion, pt.description as tipo_periodo 
                FROM ingresos i 
                LEFT JOIN period_types pt ON i.period_type_id = pt.id 
                ORDER BY i.fecha DESC";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $ingresos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $ingresos[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $ingresos;
    }

    /**
     * Obtener ingresos del mes actual
     */
    public function getIngresosMesActual() {
        $sql = "SELECT SUM(importe) as total FROM ingresos 
                WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row['total'] ?? 0;
    }

    /**
     * Obtener ingresos acumulados del año
     */
    public function getIngresosAnoActual() {
        $sql = "SELECT SUM(importe) as total FROM ingresos WHERE YEAR(fecha) = YEAR(CURDATE())";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row['total'] ?? 0;
    }

    /**
     * Obtener tipos de periodo
     */
    public function getPeriodTypes() {
        $sql = "SELECT id, code, description FROM period_types ORDER BY id ASC";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $types = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $types[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $types;
    }

    /**
     * Eliminar ingreso
     */
    public function deleteIngreso($id) {
        $id = intval($id);
        $sql = "DELETE FROM ingresos WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
}
?>
