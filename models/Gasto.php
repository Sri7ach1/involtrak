<?php

class Gasto {
    private $con;

    public function __construct($connection) {
        $this->con = $connection;
    }

    /**
     * Crear nuevo gasto
     */
    public function createGasto($fecha, $importe, $period_type_id, $descripcion) {
        $sql = "INSERT INTO gastos (fecha, importe, period_type_id, descripcion) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "sdis", $fecha, $importe, $period_type_id, $descripcion);

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    /**
     * Actualizar gasto
     */
    public function updateGasto($id, $fecha, $importe, $period_type_id, $descripcion) {
        $sql = "UPDATE gastos SET fecha = ?, importe = ?, period_type_id = ?, descripcion = ? WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "sdisi", $fecha, $importe, $period_type_id, $descripcion, $id);

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    /**
     * Obtener todos los gastos
     */
    public function getAllGastos() {
        $sql = "SELECT g.id, g.fecha, g.importe, g.period_type_id, g.descripcion, pt.description as tipo_periodo 
                FROM gastos g 
                LEFT JOIN period_types pt ON g.period_type_id = pt.id 
                ORDER BY g.fecha DESC";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $gastos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $gastos[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $gastos;
    }

    /**
     * Obtener tipos de periodo
     */
    public function getPeriodTypes() {
        $sql = "SELECT id, code, description FROM period_types ORDER BY id";
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
     * Obtener gastos del mes actual
     */
    public function getGastosMesActual() {
        $sql = "SELECT SUM(importe) as total FROM gastos 
                WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row['total'] ?? 0;
    }

    /**
     * Obtener gastos acumulados del año
     */
    public function getGastosAnoActual() {
        $sql = "SELECT SUM(importe) as total FROM gastos WHERE YEAR(fecha) = YEAR(CURDATE())";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row['total'] ?? 0;
    }

    /**
     * Eliminar gasto
     */
    public function deleteGasto($id) {
        $id = intval($id);
        $sql = "DELETE FROM gastos WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
}
?>
