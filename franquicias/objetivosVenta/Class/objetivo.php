<?php

class Objetivo
{
    private $cid;
    private $cid_central;
    private $usuarioModificacion = '6402EC3F-A0D9-4113-B393-2759004BCAC8';

    function __construct(){
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $this->cid = new Conexion();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
        $this->cid_central = $this->cid->conectar($db);
        
        if (!$this->cid_central) {
            $errors = sqlsrv_errors();
            error_log("Error de conexión en Objetivo::__construct: " . print_r($errors, true));
            throw new Exception("No se pudo establecer conexión a la base de datos: " . print_r($errors, true));
        }
    }

    private function retornarArray($sqlEnviado){
        $sql = $sqlEnviado;
        error_log("Ejecutando SQL: " . $sql);
        
        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log("Error en SQL: " . print_r($errors, true));
            error_log("SQL que falló: " . $sql);
            throw new Exception("Error en consulta SQL: " . print_r($errors, true));
        }

        $rows = array();
        while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }

        error_log("Consulta ejecutada correctamente. Filas retornadas: " . count($rows));
        return $rows;
    }

    /**
     * Obtener lista de sucursales franquicias
     */
    public function getSucursales() {
        try {
            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL 
                    FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS 
                    WHERE CANAL = 'FRANQUICIAS' 
                    AND HABILITADO = 1 
                    AND NRO_SUC_MADRE IS NULL
                    AND NRO_SUCURSAL NOT IN ('812','912','933')
                    ORDER BY DESC_SUCURSAL";
            
            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error en getSucursales: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener objetivos con filtros
     */
    public function getObjetivos($mes = null, $anio = null, $sucursal = null) {
        try {
            $mesActual = $mes ?: date('n');
            $anioActual = $anio ?: date('Y');
            
            $sql = "SELECT 
                        s.NRO_SUCURSAL,
                        s.DESC_SUCURSAL,
                        o.mes,
                        o.anio,
                        o.importeObjetivos,
                        CONVERT(varchar(23), o.fechaUltMod, 121) AS fechaUltMod,
                        pv.idTango
                    FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS s
                    LEFT JOIN [Servidortesting].dbXLSales.DBO.PuntosDeVenta pv ON s.NRO_SUCURSAL = pv.idTango
                    LEFT JOIN [Servidortesting].dbXLSales.DBO.Objetivos o ON pv.id = o.idPOS 
                        AND o.mes = $mesActual AND o.anio = $anioActual
                    WHERE s.CANAL = 'FRANQUICIAS' 
                    AND s.HABILITADO = 1 
                    AND s.NRO_SUC_MADRE IS NULL
                    AND pv.activo = 1
                    AND s.NRO_SUCURSAL NOT IN ('812','912','933')";

            if ($sucursal) {
                $sql .= " AND s.NRO_SUCURSAL = '$sucursal'";
            }

            $sql .= " ORDER BY s.DESC_SUCURSAL";

            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error en getObjetivos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar objetivo de franquicia
     */
    public function editarObjetivo($idTango, $mes, $anio, $nuevoImporteObjetivo) {
        try {
            $sql = "EXEC [Servidortesting].dbXLSales.DBO.RO_EDITAR_OBJETIVO_FRANQUICIA 
                    @idTango = '$idTango',
                    @mes = $mes,
                    @anio = $anio,
                    @nuevoImporteObjetivo = $nuevoImporteObjetivo,
                    @usuarioModificacion = '$this->usuarioModificacion'";

            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en editarObjetivo: " . print_r($errors, true));
                return ['success' => false, 'message' => 'Error al ejecutar el procedimiento almacenado'];
            }

            return ['success' => true, 'message' => 'Objetivo actualizado correctamente'];
        } catch (Exception $e) {
            error_log("Error en editarObjetivo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    /**
     * Obtener años disponibles
     */
    public function getAniosDisponibles() {
        $anioActual = date('Y');
        $anios = [];
        
        for ($i = $anioActual - 2; $i <= $anioActual + 1; $i++) {
            $anios[] = $i;
        }
        
        return $anios;
    }

    /**
     * Obtener meses
     */
    public function getMeses() {
        return [
            1 => 'Enero',
            2 => 'Febrero', 
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];
    }
}

?>