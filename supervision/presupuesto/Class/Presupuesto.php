<?php

/**
 * Clase para gestionar presupuestos de supervisión
 */
class Presupuesto {

    private $cid;
    private $cid_central;
    private const DB_PREFIX = '[XL-LAKERBIS].LOCALES_LAKERS.DBO.';
    private const DB_PREFIX2 = '[XL-TANGO].LAKER_SA.DBO.';

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
    } 

private function retornarArray($sqlEnviado){
    $sql = $sqlEnviado;
    $stmt = sqlsrv_query($this->cid_central, $sql);

    // --- ¡AÑADE ESTE BLOQUE! ---
    if ($stmt === false) {
        throw new Exception("Error en la consulta SQL: " . print_r(sqlsrv_errors(), true));
    }
    // -------------------------

    $rows = array();
    while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $v;
    }
    return $rows;  
}

     public function traerSupervisoras(){
        $sql = "SELECT ID, NOMBRE FROM " . self::DB_PREFIX . "RO_T_SUPERVISORAS_COMERCIAL WHERE ACTIVA = 1 ORDER BY ID";
        return $this->retornarArray($sql);
    }

     public function traerConceptos(){
        $sql = "SELECT ID_CONC, CONCEPTO FROM " . self::DB_PREFIX2 . "RO_T_CONCEPTOS_GASTOS_SUP";
        return $this->retornarArray($sql);
    }
    
    /**
     * Obtiene los presupuestos para un período específico
     * 
     * @param string $periodo Período en formato AAAA-MM
     * @return array Array asociativo con los presupuestos
     */
    public function obtenerPresupuestos($periodo) {
        // Extraer año y mes del período
        $anio = substr($periodo, 0, 4);
        $mes = intval(substr($periodo, 5, 2)); // Convertir a entero para eliminar ceros iniciales
        
        $sql = "SELECT p.ID_SUPERVISORA, s.NOMBRE AS NOMBRE_SUPERVISORA, 
                    p.ID_CONC, c.CONCEPTO, p.IMP_PRESUPUESTO
                FROM RO_T_PRESUPUESTOS_SUPERVISION p
                JOIN [XL-LAKERBIS].LOCALES_LAKERS.DBO.RO_T_SUPERVISORAS_COMERCIAL s ON p.ID_SUPERVISORA = s.ID
                JOIN RO_T_CONCEPTOS_GASTOS_SUP c ON p.ID_CONC = c.ID_CONC
                WHERE p.ANIO = ? AND p.MES = ?
                ORDER BY s.NOMBRE, c.ID_CONC";
        
        $params = array($anio, $mes);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener presupuestos: " . print_r(sqlsrv_errors(), true));
        }
        
        // Preparar array para devolver los datos estructurados
        $presupuestos = array();
        
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
              
            // Usar ID_SUPERVISORA como clave principal
            if (!isset($presupuestos[$row['ID_SUPERVISORA']])) {
                $presupuestos[$row['ID_SUPERVISORA']] = array();
            }
            
            // Usar ID_CONC como clave secundaria
            $presupuestos[$row['ID_SUPERVISORA']][$row['ID_CONC']] = $row['IMP_PRESUPUESTO'];
        }
               
        return $presupuestos;
    }
        
    /**
     * Guarda presupuestos para un período
     * 
     * @param string $periodo Período en formato AAAA-MM
     * @param array $presupuestos Array con datos de presupuestos
     * @param string $usuario Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function guardarPresupuestos($periodo, $presupuestos, $usuario) {
        try {
            // Extraer año y mes del período
            $anio = substr($periodo, 0, 4);
            $mes = intval(substr($periodo, 5, 2)); // Convertir a entero para eliminar ceros iniciales
            
            // Iniciar transacción
            sqlsrv_begin_transaction($this->cid_central);
            
            // Eliminar presupuestos existentes para este período
            $sqlDelete = "DELETE FROM RO_T_PRESUPUESTOS_SUPERVISION 
                          WHERE ANIO = ? AND MES = ?";
            $paramsDelete = array($anio, $mes);
            $stmtDelete = sqlsrv_query($this->cid_central, $sqlDelete, $paramsDelete);
            
            if ($stmtDelete === false) {
                throw new Exception("Error al eliminar presupuestos existentes: " . print_r(sqlsrv_errors(), true));
            }
            
            // Insertar nuevos presupuestos
            foreach ($presupuestos as $idSupervisora => $conceptos) {
                foreach ($conceptos as $idConcepto => $importe) {
                    // Solo insertar si el importe es mayor que cero
                    if (floatval($importe) > 0) {
                        $sqlInsert = "INSERT INTO RO_T_PRESUPUESTOS_SUPERVISION 
                                    (ANIO, MES, ID_CONC, ID_SUPERVISORA, IMP_PRESUPUESTO, 
                                    FECHA_MODIF, USUARIO_MODIF) 
                                    VALUES (?, ?, ?, ?, ?, GETDATE(), ?)";
                        
                        $paramsInsert = array(
                            $anio,
                            $mes,
                            $idConcepto,
                            $idSupervisora,
                            floatval($importe),
                            $usuario
                        );
                        
                        $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert, $paramsInsert);
                        
                        if ($stmtInsert === false) {
                            throw new Exception("Error al insertar presupuesto: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }
            }
            
            // Confirmar transacción
            sqlsrv_commit($this->cid_central);
            
            return array(
                'success' => true,
                'message' => 'Presupuestos guardados correctamente'
            );
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            sqlsrv_rollback($this->cid_central);
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Copia los presupuestos de un período anterior al actual
     * 
     * @param string $periodoDestino Período destino en formato AAAA-MM
     * @param string $usuario Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function copiarPresupuestoAnterior($periodoDestino, $usuario) {
        try {
            // Extraer año y mes del período destino
            $anioDestino = substr($periodoDestino, 0, 4);
            $mesDestino = intval(substr($periodoDestino, 5, 2)); // Convertir a entero
            
            // Calcular período anterior
            $periodoAnterior = $this->calcularPeriodoAnterior($periodoDestino);
            $anioAnterior = substr($periodoAnterior, 0, 4);
            $mesAnterior = intval(substr($periodoAnterior, 5, 2)); // Convertir a entero
            
            // Iniciar transacción
            sqlsrv_begin_transaction($this->cid_central);
            
            // Eliminar presupuestos existentes para el período destino
            $sqlDelete = "DELETE FROM RO_T_PRESUPUESTOS_SUPERVISION 
                          WHERE ANIO = ? AND MES = ?";
            $paramsDelete = array($anioDestino, $mesDestino);
            $stmtDelete = sqlsrv_query($this->cid_central, $sqlDelete, $paramsDelete);
            
            if ($stmtDelete === false) {
                throw new Exception("Error al eliminar presupuestos existentes: " . print_r(sqlsrv_errors(), true));
            }
            
            // Copiar presupuestos del período anterior
            $sqlInsert = "INSERT INTO RO_T_PRESUPUESTOS_SUPERVISION 
                          (ANIO, MES, ID_SUPERVISORA, ID_CONC, IMP_PRESUPUESTO, 
                           FECHA_MODIF, USUARIO_MODIF)
                          SELECT ?, ?, ID_SUPERVISORA, ID_CONC, IMP_PRESUPUESTO, 
                                 GETDATE(), ?
                          FROM RO_T_PRESUPUESTOS_SUPERVISION
                          WHERE ANIO = ? AND MES = ?";
            
            $paramsInsert = array(
                $anioDestino,
                $mesDestino,
                $usuario,
                $anioAnterior,
                $mesAnterior
            );
            
            $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert, $paramsInsert);
            
            if ($stmtInsert === false) {
                throw new Exception("Error al copiar presupuestos: " . print_r(sqlsrv_errors(), true));
            }
            
            // Verificar si se copió algún presupuesto
            $rowsAffected = sqlsrv_rows_affected($stmtInsert);
            
            if ($rowsAffected === 0) {
                // Si no hay presupuestos para el período anterior, rollback
                sqlsrv_rollback($this->cid_central);
                
                return array(
                    'success' => false,
                    'message' => 'No hay presupuestos definidos para el período anterior'
                );
            }
            
            // Confirmar transacción
            sqlsrv_commit($this->cid_central);
            
            return array(
                'success' => true,
                'message' => 'Presupuestos copiados correctamente',
                'rows' => $rowsAffected
            );
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            sqlsrv_rollback($this->cid_central);
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Calcula el período anterior a uno dado
     * 
     * @param string $periodo Período en formato AAAA-MM
     * @return string Período anterior en formato AAAA-MM
     */
    private function calcularPeriodoAnterior($periodo) {
        $anio = intval(substr($periodo, 0, 4));
        $mes = intval(substr($periodo, 5, 2));
        
        // Restar un mes
        $mes--;
        
        // Si el mes llega a cero, volver a diciembre del año anterior
        if ($mes == 0) {
            $mes = 12;
            $anio--;
        }
        
        // Formatear el resultado
        return sprintf('%04d-%02d', $anio, $mes);
    }
    
    /**
     * Compara gastos reales vs presupuestos para un período
     * 
     * @param string $periodo Período en formato AAAA-MM
     * @return array Datos comparativos de gastos vs presupuestos
     */
    public function compararGastosVsPresupuesto($periodo) {
        // Extraer año y mes del período
        $anio = substr($periodo, 0, 4);
        $mes = substr($periodo, 5, 2);
        
        // Obtener semanas que corresponden al mes
        $semanas = $this->obtenerSemanasPorMes($anio, $mes);
        
        if (empty($semanas)) {
            return array(
                'success' => false,
                'message' => 'No se encontraron semanas para el período indicado'
            );
        }
        
        // Construir condición para semanas
        $condicionSemanas = "";
        $paramsSemanas = array();
        
        foreach ($semanas as $index => $semana) {
            if ($index > 0) {
                $condicionSemanas .= " OR ";
            }
            $condicionSemanas .= "(g.SEMANA = ? AND g.ANIO = ?)";
            $paramsSemanas[] = $semana['semana'];
            $paramsSemanas[] = $semana['anio'];
        }
        
        // Consulta SQL para obtener gastos por supervisora y concepto
        $sqlGastos = "SELECT g.SUPERVISORA, s.ID as ID_SUPERVISORA, g.ID_CONC, 
                             c.CONCEPTO, SUM(g.IMPORTE + ISNULL(g.IMPORTE_TARJETA, 0)) as GASTO_TOTAL
                      FROM RO_T_GASTOS_SUPERVISION g
                      JOIN RO_T_CONCEPTOS_GASTOS_SUP c ON g.ID_CONC = c.ID_CONC
                      JOIN SOF_USUARIOS s ON g.SUPERVISORA = s.DESCRIPCION
                      WHERE g.ESTADO = 1 AND ($condicionSemanas)
                      GROUP BY g.SUPERVISORA, s.ID, g.ID_CONC, c.CONCEPTO";
        
        $stmtGastos = sqlsrv_query($this->cid_central, $sqlGastos, $paramsSemanas);
        
        if ($stmtGastos === false) {
            return array(
                'success' => false,
                'message' => 'Error al obtener gastos: ' . print_r(sqlsrv_errors(), true)
            );
        }
        
        // Obtener presupuestos
        $sqlPresupuestos = "SELECT p.ID_SUPERVISORA, s.DESCRIPCION as SUPERVISORA, 
                                   p.ID_CONC, c.CONCEPTO, p.IMP_PRESUPUESTO
                            FROM RO_T_PRESUPUESTOS_SUPERVISION p
                            JOIN SOF_USUARIOS s ON p.ID_SUPERVISORA = s.ID
                            JOIN RO_T_CONCEPTOS_GASTOS_SUP c ON p.ID_CONC = c.ID_CONC
                            WHERE p.ANIO = ? AND p.MES = ?";
        
        $paramsPresupuestos = array($anio, $mes);
        $stmtPresupuestos = sqlsrv_query($this->cid_central, $sqlPresupuestos, $paramsPresupuestos);
        
        if ($stmtPresupuestos === false) {
            return array(
                'success' => false,
                'message' => 'Error al obtener presupuestos: ' . print_r(sqlsrv_errors(), true)
            );
        }
        
        // Procesar datos
        $gastos = array();
        while ($row = sqlsrv_fetch_array($stmtGastos, SQLSRV_FETCH_ASSOC)) {
            $gastos[$row['ID_SUPERVISORA']][$row['ID_CONC']] = array(
                'supervisora' => $row['SUPERVISORA'],
                'concepto' => $row['CONCEPTO'],
                'gasto' => floatval($row['GASTO_TOTAL'])
            );
        }
        
        $presupuestos = array();
        while ($row = sqlsrv_fetch_array($stmtPresupuestos, SQLSRV_FETCH_ASSOC)) {
            $presupuestos[$row['ID_SUPERVISORA']][$row['ID_CONC']] = array(
                'supervisora' => $row['SUPERVISORA'],
                'concepto' => $row['CONCEPTO'],
                'presupuesto' => floatval($row['IMP_PRESUPUESTO'])
            );
        }
        
        // Combinar datos de gastos y presupuestos
        $resultado = array();
        
        // Procesar todos los presupuestos
        foreach ($presupuestos as $idSupervisora => $conceptos) {
            foreach ($conceptos as $idConcepto => $datosPresupuesto) {
                $gasto = isset($gastos[$idSupervisora][$idConcepto]) 
                         ? $gastos[$idSupervisora][$idConcepto]['gasto'] 
                         : 0;
                
                $resultado[] = array(
                    'id_supervisora' => $idSupervisora,
                    'supervisora' => $datosPresupuesto['supervisora'],
                    'id_concepto' => $idConcepto,
                    'concepto' => $datosPresupuesto['concepto'],
                    'presupuesto' => $datosPresupuesto['presupuesto'],
                    'gasto' => $gasto,
                    'diferencia' => $datosPresupuesto['presupuesto'] - $gasto,
                    'porcentaje' => $datosPresupuesto['presupuesto'] > 0 
                                    ? round(($gasto / $datosPresupuesto['presupuesto']) * 100, 2) 
                                    : 0
                );
            }
        }
        
        // Procesar gastos sin presupuesto asignado
        foreach ($gastos as $idSupervisora => $conceptos) {
            foreach ($conceptos as $idConcepto => $datosGasto) {
                if (!isset($presupuestos[$idSupervisora][$idConcepto])) {
                    $resultado[] = array(
                        'id_supervisora' => $idSupervisora,
                        'supervisora' => $datosGasto['supervisora'],
                        'id_concepto' => $idConcepto,
                        'concepto' => $datosGasto['concepto'],
                        'presupuesto' => 0,
                        'gasto' => $datosGasto['gasto'],
                        'diferencia' => -$datosGasto['gasto'],
                        'porcentaje' => 100 // 100% sobre presupuesto
                    );
                }
            }
        }
        
        return array(
            'success' => true,
            'datos' => $resultado,
            'semanas' => $semanas
        );
    }
    
    /**
     * Obtiene las semanas correspondientes a un mes y año
     * 
     * @param int $anio Año
     * @param int $mes Mes
     * @return array Array con las semanas del mes
     */
    private function obtenerSemanasPorMes($anio, $mes) {
        $sql = "EXEC RO_SP_SEMANAS_POR_MES ?, ?";
        $params = array($anio, $mes);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener semanas: " . print_r(sqlsrv_errors(), true));
        }
        
        $semanas = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $semanas[] = array(
                'semana' => $row['SEMANA'],
                'anio' => $row['ANIO']
            );
        }
        
        return $semanas;
    }

    /**
     * Obtiene todas las observaciones de una supervisora para un período específico
     * 
     * @param int $idSupervisora ID de la supervisora
     * @param string $periodo Período en formato AAAA-MM
     * @return array Lista de observaciones
     */
    public function obtenerObservaciones($idSupervisora, $periodo) {
        try {
            // Extraer año y mes del período
            $anio = substr($periodo, 0, 4);
            $mes = intval(substr($periodo, 5, 2));
            
            $sql = "SELECT ID, TITULO, OBSERVACIONES, PRIORIDAD, 
                           FECHA_CREACION, FECHA_MODIF, USUARIO_CREACION, USUARIO_MODIF
                    FROM RO_T_OBSERVACIONES_SUPERVISORAS 
                    WHERE ID_SUPERVISORA = ? AND ANIO = ? AND MES = ? AND ACTIVO = 1
                    ORDER BY FECHA_CREACION DESC";
            
            $params = array($idSupervisora, $anio, $mes);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener observaciones: " . print_r(sqlsrv_errors(), true));
            }
            
            $observaciones = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Formatear fechas si existen
                if ($row['FECHA_CREACION'] instanceof DateTime) {
                    $row['FECHA_CREACION'] = $row['FECHA_CREACION']->format('Y-m-d H:i:s');
                }
                if ($row['FECHA_MODIF'] instanceof DateTime) {
                    $row['FECHA_MODIF'] = $row['FECHA_MODIF']->format('Y-m-d H:i:s');
                }
                
                $observaciones[] = $row;
            }
            
            return $observaciones;
            
        } catch (Exception $e) {
            error_log("Error en obtenerObservaciones: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Obtiene una observación específica por ID
     * 
     * @param int $idObservacion ID de la observación
     * @return array|null Datos de la observación o null si no existe
     */
    public function obtenerObservacionPorId($idObservacion) {
        try {
            $sql = "SELECT ID, ID_SUPERVISORA, ANIO, MES, TITULO, OBSERVACIONES, PRIORIDAD,
                           FECHA_CREACION, FECHA_MODIF, USUARIO_CREACION, USUARIO_MODIF
                    FROM RO_T_OBSERVACIONES_SUPERVISORAS 
                    WHERE ID = ? AND ACTIVO = 1";
            
            $params = array($idObservacion);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener observación: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            // Formatear fechas si existen
            if ($resultado) {
                if ($resultado['FECHA_CREACION'] instanceof DateTime) {
                    $resultado['FECHA_CREACION'] = $resultado['FECHA_CREACION']->format('Y-m-d H:i:s');
                }
                if ($resultado['FECHA_MODIF'] instanceof DateTime) {
                    $resultado['FECHA_MODIF'] = $resultado['FECHA_MODIF']->format('Y-m-d H:i:s');
                }
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error en obtenerObservacionPorId: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Guarda una nueva observación para una supervisora
     * 
     * @param int $idSupervisora ID de la supervisora
     * @param string $periodo Período en formato AAAA-MM
     * @param string $titulo Título de la observación
     * @param string $observaciones Texto de las observaciones
     * @param int $prioridad Prioridad (1=Baja, 2=Media, 3=Alta)
     * @param string $usuario Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function guardarNuevaObservacion($idSupervisora, $periodo, $titulo, $observaciones, $prioridad, $usuario) {
        try {
            // Extraer año y mes del período
            $anio = substr($periodo, 0, 4);
            $mes = intval(substr($periodo, 5, 2));
            
            // Validar que los datos no estén vacíos
            if (empty(trim($titulo))) {
                return array(
                    'success' => false,
                    'message' => 'El título no puede estar vacío'
                );
            }
            
            if (empty(trim($observaciones))) {
                return array(
                    'success' => false,
                    'message' => 'Las observaciones no pueden estar vacías'
                );
            }
            
            // Validar prioridad
            $prioridad = intval($prioridad);
            if ($prioridad < 1 || $prioridad > 3) {
                $prioridad = 1; // Por defecto baja
            }
            
            // Iniciar transacción
            sqlsrv_begin_transaction($this->cid_central);
            
            // Insertar nueva observación
            $sqlInsert = "INSERT INTO RO_T_OBSERVACIONES_SUPERVISORAS 
                         (ID_SUPERVISORA, ANIO, MES, TITULO, OBSERVACIONES, PRIORIDAD, 
                          USUARIO_CREACION, USUARIO_MODIF)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $paramsInsert = array($idSupervisora, $anio, $mes, $titulo, $observaciones, $prioridad, $usuario, $usuario);
            $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert, $paramsInsert);
            
            if ($stmtInsert === false) {
                throw new Exception("Error al insertar observación: " . print_r(sqlsrv_errors(), true));
            }
            
            // Obtener el ID de la observación insertada
            $sqlId = "SELECT SCOPE_IDENTITY() as ID";
            $stmtId = sqlsrv_query($this->cid_central, $sqlId);
            $rowId = sqlsrv_fetch_array($stmtId, SQLSRV_FETCH_ASSOC);
            $idObservacion = $rowId['ID'];
            
            // Confirmar transacción
            sqlsrv_commit($this->cid_central);
            
            return array(
                'success' => true,
                'message' => 'Observación guardada correctamente',
                'id' => $idObservacion
            );
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            sqlsrv_rollback($this->cid_central);
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Actualiza una observación existente
     * 
     * @param int $idObservacion ID de la observación
     * @param string $titulo Título de la observación
     * @param string $observaciones Texto de las observaciones
     * @param int $prioridad Prioridad (1=Baja, 2=Media, 3=Alta)
     * @param string $usuario Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function actualizarObservacion($idObservacion, $titulo, $observaciones, $prioridad, $usuario) {
        try {
            // Validar que los datos no estén vacíos
            if (empty(trim($titulo))) {
                return array(
                    'success' => false,
                    'message' => 'El título no puede estar vacío'
                );
            }
            
            if (empty(trim($observaciones))) {
                return array(
                    'success' => false,
                    'message' => 'Las observaciones no pueden estar vacías'
                );
            }
            
            // Validar prioridad
            $prioridad = intval($prioridad);
            if ($prioridad < 1 || $prioridad > 3) {
                $prioridad = 1;
            }
            
            // Iniciar transacción
            sqlsrv_begin_transaction($this->cid_central);
            
            // Actualizar observación existente
            $sqlUpdate = "UPDATE RO_T_OBSERVACIONES_SUPERVISORAS 
                         SET TITULO = ?, OBSERVACIONES = ?, PRIORIDAD = ?, 
                             FECHA_MODIF = GETDATE(), USUARIO_MODIF = ?
                         WHERE ID = ? AND ACTIVO = 1";
            
            $paramsUpdate = array($titulo, $observaciones, $prioridad, $usuario, $idObservacion);
            $stmtUpdate = sqlsrv_query($this->cid_central, $sqlUpdate, $paramsUpdate);
            
            if ($stmtUpdate === false) {
                throw new Exception("Error al actualizar observación: " . print_r(sqlsrv_errors(), true));
            }
            
            $filasAfectadas = sqlsrv_rows_affected($stmtUpdate);
            
            if ($filasAfectadas === 0) {
                throw new Exception("No se encontró la observación a actualizar");
            }
            
            // Confirmar transacción
            sqlsrv_commit($this->cid_central);
            
            return array(
                'success' => true,
                'message' => 'Observación actualizada correctamente'
            );
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            sqlsrv_rollback($this->cid_central);
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Elimina una observación (marca como inactiva)
     * 
     * @param int $idObservacion ID de la observación
     * @param string $usuario Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function eliminarObservacion($idObservacion, $usuario) {
        try {
            // Iniciar transacción
            sqlsrv_begin_transaction($this->cid_central);
            
            $sql = "UPDATE RO_T_OBSERVACIONES_SUPERVISORAS 
                    SET ACTIVO = 0, FECHA_MODIF = GETDATE(), USUARIO_MODIF = ?
                    WHERE ID = ? AND ACTIVO = 1";
            
            $params = array($usuario, $idObservacion);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al eliminar observación: " . print_r(sqlsrv_errors(), true));
            }
            
            $filasAfectadas = sqlsrv_rows_affected($stmt);
            
            if ($filasAfectadas === 0) {
                throw new Exception("No se encontró la observación a eliminar");
            }
            
            // Confirmar transacción
            sqlsrv_commit($this->cid_central);
            
            return array(
                'success' => true,
                'message' => 'Observación eliminada correctamente'
            );
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            sqlsrv_rollback($this->cid_central);
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Obtiene el conteo de observaciones por supervisora para un período
     * 
     * @param string $periodo Período en formato AAAA-MM
     * @return array Array asociativo [ID_SUPERVISORA => cantidad]
     */
    public function contarObservacionesPorSupervisora($periodo) {
        try {
            // Extraer año y mes del período
            $anio = substr($periodo, 0, 4);
            $mes = intval(substr($periodo, 5, 2));
            
            $sql = "SELECT ID_SUPERVISORA, COUNT(*) as CANTIDAD
                    FROM RO_T_OBSERVACIONES_SUPERVISORAS 
                    WHERE ANIO = ? AND MES = ? AND ACTIVO = 1
                    GROUP BY ID_SUPERVISORA";
            
            $params = array($anio, $mes);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al contar observaciones: " . print_r(sqlsrv_errors(), true));
            }
            
            $conteos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $conteos[$row['ID_SUPERVISORA']] = intval($row['CANTIDAD']);
            }
            
            return $conteos;
            
        } catch (Exception $e) {
            error_log("Error en contarObservacionesPorSupervisora: " . $e->getMessage());
            return array();
        }
    }
}