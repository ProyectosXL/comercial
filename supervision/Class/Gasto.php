<?php

class Gasto
{
    private $cid;
    private $cid_central;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
    } 

    private function retornarArray($sqlEnviado){
        $sql = $sqlEnviado;
        $stmt = sqlsrv_query($this->cid_central, $sql);
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;  
    }

    public function traerConceptos(){
        $sql = "SELECT ID_CONC, CONCEPTO FROM RO_T_CONCEPTOS_GASTOS_SUP";
        return $this->retornarArray($sql);
    }

    public function traerSemanasRetail(){
        $sql = "EXEC RO_SP_SEMANAS_RETAIL";
        return $this->retornarArray($sql);
    }

    public function insertarGastos($semana, $anio, $gastos, $supervisora) {
        try {
            sqlsrv_begin_transaction($this->cid_central);
    
            foreach ($gastos as $index => $gasto) {

     
                $importeEfectivo = 0;
                $importeTarjeta = 0;
                foreach ($gasto['gastosArray'] as $key => $value) {
                  if($value['type'] == 'efectivo'){
                    $importeEfectivo += $value['importe'];
                  }else{
                    $importeTarjeta += $value['importe'];
                  }
                }
         
                $sql = "{CALL RO_SP_INSERTAR_GASTO_SUPERVISION (?, ?, ?, ?, ?, ?, ?, ?)}";

                $id_insertado = 0;
                $params = array(
                    array($semana, SQLSRV_PARAM_IN),
                    array($anio, SQLSRV_PARAM_IN),
                    array($gasto['id_conc'], SQLSRV_PARAM_IN),
                    array($importeEfectivo, SQLSRV_PARAM_IN),
                    array($importeTarjeta, SQLSRV_PARAM_IN),
                    array($gasto['observaciones'], SQLSRV_PARAM_IN),
                    array($supervisora, SQLSRV_PARAM_IN),
                    array(&$id_insertado, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
                );
    
                $stmt = sqlsrv_query($this->cid_central, $sql, $params);
                if ($stmt === false) {
                    throw new Exception("Error al insertar gasto $index: " . print_r(sqlsrv_errors(), true));
                }
            }
    
            sqlsrv_commit($this->cid_central);
            return ['success' => true, 'message' => 'Gastos guardados correctamente'];
        } catch (Exception $e) {
            sqlsrv_rollback($this->cid_central);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function insertarAdelanto ($semana, $anio, $importeAdelanto, $supervisora){

        $sql = "INSERT INTO FU_T_ADELANTOS_SUPERVISION (SEMANA, ANIO, IMPORTE_ADELANTO, SUPERVISORA) VALUES (?, ?, ?, ?)";
        $params = array($semana, $anio, $importeAdelanto, $supervisora);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Error al insertar adelanto: " . print_r(sqlsrv_errors(), true));
        }

        return sqlsrv_rows_affected($stmt);

    }

    public function actualizarCAE($idGasto, $caeTarjeta, $caeEfectivo, $supervisora, $semana, $anio) {
        try {
            error_log("Iniciando actualizarCAE:");
            error_log("ID: $idGasto, CAE_T: $caeTarjeta, CAE_E: $caeEfectivo");
            error_log("Sup: $supervisora, Sem: $semana, Año: $anio");
            
            if (!$supervisora || !$semana || !$anio) {
                throw new Exception("Los parámetros supervisora, semana y año son obligatorios");
            }
            
            // Iniciamos transacción
            if (sqlsrv_begin_transaction($this->cid_central) === false) {
                $error = "Error al iniciar la transacción: " . print_r(sqlsrv_errors(), true);
                error_log($error);
                throw new Exception($error);
            }

            // Preparamos la consulta SQL con todos los filtros
            $sql = "UPDATE RO_T_GASTOS_SUPERVISION 
                   SET CAE_Tarjeta = ?, 
                       CAE_Efectivo = ?,
                       FECHA_CARGADO = GETDATE()
                   WHERE ID_CONC = ? 
                   AND SUPERVISORA = ? 
                   AND SEMANA = ? 
                   AND ANIO = ?";

            // Preparamos los parámetros incluyendo todos los filtros
            $params = array(
                array($caeTarjeta, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(20)),
                array($caeEfectivo, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(20)),
                array($idGasto, SQLSRV_PARAM_IN),
                array($supervisora, SQLSRV_PARAM_IN),
                array($semana, SQLSRV_PARAM_IN),
                array($anio, SQLSRV_PARAM_IN)
            );
            
            error_log("SQL a ejecutar: " . $sql);
            error_log("Parámetros: " . print_r($params, true));

            // Ejecutamos la consulta
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                $error = "Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true);
                error_log($error);
                throw new Exception($error);
            }
            
            // Verificamos que se haya actualizado al menos una fila
            $rows_affected = sqlsrv_rows_affected($stmt);
            error_log("Filas afectadas: " . ($rows_affected === false ? 'false' : $rows_affected));
            
            if ($rows_affected === false || $rows_affected === 0) {
                throw new Exception("No se encontró el registro para actualizar (ID_CONC: $idGasto)");
            }
            
            // Confirmamos la transacción
            if (sqlsrv_commit($this->cid_central) === false) {
                $error = "Error al confirmar la transacción: " . print_r(sqlsrv_errors(), true);
                error_log($error);
                throw new Exception($error);
            }
            
            return true;
        } catch (Exception $e) {
            // Si hay error, hacemos rollback
            if (sqlsrv_rollback($this->cid_central) === false) {
                // Si falla el rollback, agregamos esto al mensaje de error
                $rollbackError = "Error adicional al hacer rollback: " . print_r(sqlsrv_errors(), true);
                throw new Exception($e->getMessage() . ". " . $rollbackError);
            }
            throw $e;
        }
    }

    public function obtenerGastosExistentes($semana, $anio, $supervisora) {
        $sql = "SELECT ID_CONC, IMPORTE, IMPORTE_TARJETA, OBSERVACIONES, ESTADO, OBSERVACIONES_AUT 
                FROM RO_T_GASTOS_SUPERVISION 
                WHERE SEMANA = ? AND ANIO = ? AND SUPERVISORA = ?";
        
        $params = array($semana, $anio, $supervisora);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener gastos existentes: " . print_r(sqlsrv_errors(), true));
        }
        
        $gastos = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $gastos[$row['ID_CONC']] = $row;
            $gastos[$row['ID_CONC']]['tieneImagenes'] = $this->tieneImagenes($row['ID_CONC'], $semana, $anio, $supervisora);
        }
        
        return $gastos;
    }

    public function obtenerAdelanto($semana, $anio, $supervisora = null) {
        if ($supervisora) {
            $sql = "SELECT SUM(IMPORTE_ADELANTO) as TOTAL_ADELANTO 
                    FROM FU_T_ADELANTOS_SUPERVISION 
                    WHERE SEMANA = ? AND ANIO = ? AND SUPERVISORA = ?";
            $params = array($semana, $anio, $supervisora);
        } else {
            $sql = "SELECT SUM(IMPORTE_ADELANTO) as TOTAL_ADELANTO 
                    FROM FU_T_ADELANTOS_SUPERVISION 
                    WHERE SEMANA = ? AND ANIO = ?";
            $params = array($semana, $anio);
        }
    
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener adelantos: " . print_r(sqlsrv_errors(), true));
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if($row['TOTAL_ADELANTO'] == null){
            return 0;
        }
        
        return $row['TOTAL_ADELANTO'] ;
    }
    
    private function tieneImagenes($id_conc, $semana, $anio, $supervisora) {
        // Ajusta esta ruta según la estructura de directorios de tu proyecto
        $directorio = __DIR__ . '/../../imagenes/compSupervision/';
        
        // Verifica si el directorio existe y contiene archivos
        if (is_dir($directorio)) {
            $archivos = glob($directorio . "*");
            return !empty($archivos);
        }
        
        return false;
    }

    public function obtenerImagenesSup($id_conc, $semana, $anio, $supervisora, $type) {
        // Define the base URL for images
        $baseUrl = '/comercial/imagenes/compSupervision/';
        
        // Construct the file name pattern

        $nombreArchivo = $semana . '_' . str_replace(' ', '_', strtoupper($supervisora)) . '_' . $id_conc. '_' . $type;

        
        // Get the server's document root
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        
        // Construct the full server path
        $fullPath = $documentRoot . $baseUrl;
        
        // Search for matching files with multiple extensions
        $extensiones = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $imagenes = [];
        
        foreach ($extensiones as $ext) {
            $patron = $fullPath . $nombreArchivo . '_*.' . $ext;
            $archivos = glob($patron);
            if ($archivos) {
                $imagenes = array_merge($imagenes, $archivos);
            }
        }
        // Convert server paths to URLs
        $imageUrls = array_map(function($imagen) use ($documentRoot, $baseUrl) {
            $relativePath = str_replace($documentRoot, '', $imagen);
            return $baseUrl . basename($relativePath);
        }, $imagenes);
        
        // Debug information
        $debug = [
            'baseUrl' => $baseUrl,
            'fullPath' => $fullPath,
            'nombreArchivo' => $nombreArchivo,
            'extensiones_buscadas' => $extensiones,
            'archivos_encontrados' => count($imagenes)
        ];
        
        return [
            'imagenes' => $imageUrls,
            'debug' => $debug
        ];
    }

    public function obtenerImagenes($id_conc, $semana, $anio, $supervisora) {
    // Ajusta esta ruta según la estructura de tu proyecto
    $directorio = "../imagenes/compSupervision/$supervisora/$anio/$semana/$id_conc/";
    
    if (!is_dir($directorio)) {
        return [];
    }

    $imagenes = glob($directorio . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
    
    // Convertir las rutas absolutas a relativas o URLs
    $imagenes = array_map(function($imagen) {
        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $imagen);
    }, $imagenes);

    return $imagenes;
}

    public function traerSupervisoras() {
        $sql = "SELECT ID, NOMBRE NOMBRE_SUPERVISORA FROM [LAKERBIS].LOCALES_LAKERS.DBO.RO_T_SUPERVISORAS_COMERCIAL 
                WHERE ACTIVA = 1
                ORDER BY NOMBRE";
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener supervisoras: " . print_r(sqlsrv_errors(), true));
        }
        
        $supervisoras = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $supervisoras[] = $row;
        }
        
        return $supervisoras;
    }

    public function procesarGastos($semana, $anio, $supervisora, $gastos) {
        $resultados = [];
        foreach ($gastos as $gasto) {
            $sql = "UPDATE RO_T_GASTOS_SUPERVISION 
                    SET ESTADO = ?, FECHA_AUTO = GETDATE()
                    WHERE SEMANA = ? AND ID_CONC = ? AND SUPERVISORA = ? AND ANIO = ?";
            
            $autorizado = $gasto['autorizado']; // Asegúrate de que esto sea 1 o 2
            $params = array($autorizado, $semana, $gasto['id_conc'], $supervisora, $anio);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al procesar el gasto: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados[$gasto['id_conc']] = [
                'ID_CONC' => $gasto['id_conc'],
                'ESTADO' => $autorizado
            ];
        }
        return $resultados;
    }

    public function gastosPendientesAutorizar($semana, $anio){

        $sql = "SELECT g.SUPERVISORA, c.CONCEPTO, g.IMPORTE, g.CARGADO, g.OBSERVACIONES, g.ID_CONC
                FROM RO_T_GASTOS_SUPERVISION g
                JOIN RO_T_CONCEPTOS_GASTOS_SUP c ON g.ID_CONC = c.ID_CONC
                WHERE g.SEMANA = ? AND g.ANIO = ? AND g.ESTADO is null";
        $params = array($semana, $anio);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener gastos pendientes de autorizar: " . print_r(sqlsrv_errors(), true));
        }
        
        $gastos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $gastos[] = $row;
        }
        
        return $gastos;
    }

    public function actualizarAutorizacionGasto($semana, $anio, $supervisora, $id_conc, $autorizado, $observaciones_aut) {
    $sql = "UPDATE RO_T_GASTOS_SUPERVISION 
            SET ESTADO = ?, FECHA_AUTO = GETDATE(), OBSERVACIONES_AUT = ?
            WHERE SEMANA = ? AND ANIO = ? AND SUPERVISORA = ? AND ID_CONC = ?";
    
    $params = array($autorizado, $observaciones_aut, $semana, $anio, $supervisora, $id_conc);
    $stmt = sqlsrv_query($this->cid_central, $sql, $params);
    
    if ($stmt === false) {
        throw new Exception("Error al actualizar la autorización del gasto: " . print_r(sqlsrv_errors(), true));
    }
    
    return sqlsrv_rows_affected($stmt);
}

public function obtenerGastosAutorizados($semana, $anio, $supervisora = null)
    {
        $sql = "SELECT g.ID_CONC, g.SUPERVISORA, c.CONCEPTO, g.IMPORTE, g.IMPORTE_TARJETA, 
                       g.CAE_Tarjeta, g.CAE_Efectivo, g.CARGADO 
                FROM RO_T_GASTOS_SUPERVISION g
                JOIN RO_T_CONCEPTOS_GASTOS_SUP c ON g.ID_CONC = c.ID_CONC
                WHERE g.SEMANA = ? AND g.ANIO = ? AND g.ESTADO = 1";
        $params = [$semana, $anio];

        if ($supervisora) {
            $sql .= " AND g.SUPERVISORA = ?";
            $params[] = $supervisora;
        }

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener gastos autorizados: " . print_r(sqlsrv_errors(), true));
        }
        
        $gastos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $gastos[] = $row;
        }
        
        return $gastos;
    }

    public function actualizarEstadoCargado($id, $supervisora, $semana, $anio, $cargado)
    {
        $sql = "UPDATE RO_T_GASTOS_SUPERVISION 
                SET CARGADO = ?, FECHA_CARGADO = GETDATE()
                WHERE ID_CONC = ? AND SUPERVISORA = ? AND SEMANA = ? AND ANIO = ?";
        
        $params = [($cargado ? 1 : 0), $id, $supervisora, $semana, $anio];
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al actualizar el estado cargado: " . print_r(sqlsrv_errors(), true));
        }
        
        $rowsAffected = sqlsrv_rows_affected($stmt);
        if ($rowsAffected === false) {
            throw new Exception("Error al obtener el número de filas afectadas: " . print_r(sqlsrv_errors(), true));
        }
        
        return $rowsAffected > 0;
    }


    public function traerEmailSupervisora($supervisora) {
        $sql = "SELECT MAIL FROM SOF_USUARIOS WHERE DESCRIPCION = '$supervisora'";

       
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener el email de la supervisora: " . print_r(sqlsrv_errors(), true));
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['MAIL'];
    }

    public function obtenerGastosPendientes() {
        $sql = "SELECT SEMANA, ANIO, SUPERVISORA, ROUND(IMPORTE, 0) as IMPORTE, 
                       ROUND(IMPORTE_TARJETA, 0) as IMPORTE_TARJETA, OBSERVACIONES 
                FROM RO_T_GASTOS_SUPERVISION 
                WHERE ESTADO IS NULL
                ORDER BY ANIO DESC, SEMANA DESC, SUPERVISORA";
        
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener gastos pendientes: " . print_r(sqlsrv_errors(), true));
        }
        
        $gastos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Asegurarse de que los valores numéricos sean tratados como tales
            $row['IMPORTE'] = floatval($row['IMPORTE']);
            $row['IMPORTE_TARJETA'] = floatval($row['IMPORTE_TARJETA'] ?? 0);
            $gastos[] = $row;
        }
        
        return $gastos;
    }

}