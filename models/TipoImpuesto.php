<?php

class TipoImpuesto {
    private $con;

    public function __construct($connection) {
        $this->con = $connection;
    }

    /**
     * Obtener todos los tipos de impuestos activos
     */
    public function getAllTiposImpuestos() {
        $sql = "SELECT * FROM tipos_impuestos WHERE activo = 1 ORDER BY codigo";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $tipos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['provincias_aplicables'] = json_decode($row['provincias_aplicables'], true);
            $tipos[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $tipos;
    }

    /**
     * Obtener tipo de impuesto por código (IVA, IPSI, IGIC)
     */
    public function getTipoImpuestoByCodigo($codigo) {
        $sql = "SELECT * FROM tipos_impuestos WHERE codigo = ? AND activo = 1";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $tipo = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($tipo) {
            $tipo['provincias_aplicables'] = json_decode($tipo['provincias_aplicables'], true);
        }
        
        return $tipo;
    }

    /**
     * Obtener tipo de impuesto por ID
     */
    public function getTipoImpuestoById($id) {
        $id = intval($id);
        $sql = "SELECT * FROM tipos_impuestos WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $tipo = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($tipo) {
            $tipo['provincias_aplicables'] = json_decode($tipo['provincias_aplicables'], true);
        }
        
        return $tipo;
    }

    /**
     * Determinar tipo de impuesto según provincia
     */
    public function getTipoImpuestoPorProvincia($provincia) {
        if (empty($provincia)) {
            // Por defecto, IVA si no se especifica provincia
            return $this->getTipoImpuestoByCodigo('IVA');
        }

        // Obtener todos los tipos de impuestos
        $tipos = $this->getAllTiposImpuestos();
        
        foreach ($tipos as $tipo) {
            if (is_array($tipo['provincias_aplicables']) && 
                in_array($provincia, $tipo['provincias_aplicables'])) {
                return $tipo;
            }
        }
        
        // Si no encuentra coincidencia, devolver IVA por defecto
        return $this->getTipoImpuestoByCodigo('IVA');
    }

    /**
     * Calcular impuesto a partir de una base imponible
     */
    public function calcularImpuesto($baseImponible, $tipoImpuestoId) {
        $tipo = $this->getTipoImpuestoById($tipoImpuestoId);
        
        if (!$tipo) {
            error_log('[ERROR] Tipo de impuesto no encontrado: ' . $tipoImpuestoId);
            return 0;
        }
        
        return round(($baseImponible * $tipo['porcentaje']) / 100, 2);
    }

    /**
     * Obtener porcentaje de impuesto
     */
    public function getPorcentaje($tipoImpuestoId) {
        $tipo = $this->getTipoImpuestoById($tipoImpuestoId);
        return $tipo ? floatval($tipo['porcentaje']) : 0;
    }

    /**
     * Verificar si una provincia pertenece a un tipo de impuesto específico
     */
    public function provinciaPertenece($provincia, $codigo) {
        $tipo = $this->getTipoImpuestoByCodigo($codigo);
        
        if (!$tipo || !is_array($tipo['provincias_aplicables'])) {
            return false;
        }
        
        return in_array($provincia, $tipo['provincias_aplicables']);
    }

    /**
     * Obtener resumen de tipos de impuestos
     */
    public function getResumen() {
        $tipos = $this->getAllTiposImpuestos();
        $resumen = [];
        
        foreach ($tipos as $tipo) {
            $resumen[] = [
                'codigo' => $tipo['codigo'],
                'nombre' => $tipo['nombre'],
                'porcentaje' => $tipo['porcentaje'],
                'num_provincias' => count($tipo['provincias_aplicables'])
            ];
        }
        
        return $resumen;
    }
}
