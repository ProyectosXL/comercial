
<?php

require_once __DIR__ . '/../presupuesto/Class/Presupuesto.php';

class GastoNew
{
    private $cid;
    private $cid_central;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
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
    /**
     * Inserta gastos con validación automática contra presupuesto
     */
    public function insertarGastosConValidacion($semana, $anio, $gastos, $supervisora, $validacion) {
        try {
            sqlsrv_begin_transaction($this->cid_central);

            $gastosAprobados = 0;
            $gastosExcedidos = 0;
            $detalleValidacion = [];

            foreach ($gastos as $index => $gasto) {
                $importeEfectivo = 0;
                $importeTarjeta = 0;
                
                foreach ($gasto['gastosArray'] as $key => $value) {
                    if($value['type'] == 'efectivo'){
                        $importeEfectivo += $value['importe'];
                    } else {
                        $importeTarjeta += $value['importe'];
                    }
                }

                // Calcular total del gasto (efectivo + tarjeta)
                $totalGastoConcepto = $importeEfectivo + $importeTarjeta;

                // Determinar estado basado en validación
                $estado = 1; // Por defecto aprobado
                $observacionesAut = '';

                // Verificar si este concepto excede el presupuesto
                $conceptoExcedido = false;
                if (isset($validacion['excedidos'])) {
                    foreach ($validacion['excedidos'] as $excedido) {
                        if ($this->conceptoCorresponde($gasto['concepto'], $excedido['concepto'])) {
                            $conceptoExcedido = true;
                            $totalAcumulado = isset($excedido['totalGastoAcumulado']) ? $excedido['totalGastoAcumulado'] : $totalGastoConcepto;
                            $observacionesAut = "Excede presupuesto - Requiere auditoría gerencia comercial (Total Acumulado: $" . number_format($totalAcumulado, 2) . " vs Presupuesto: $" . number_format($excedido['presupuesto'], 2) . ")";
                            break;
                        }
                    }
                }

                if ($conceptoExcedido) {
                    $gastosExcedidos++;
                    $detalleValidacion[] = [
                        'concepto' => $gasto['concepto'],
                        'estado' => 'Requiere auditoría',
                        'motivo' => 'Excede presupuesto'
                    ];
                } else {
                    $gastosAprobados++;
                    $detalleValidacion[] = [
                        'concepto' => $gasto['concepto'],
                        'estado' => 'Aprobado automáticamente',
                        'motivo' => 'Dentro del presupuesto'
                    ];
                }
         
                // Usar INSERT directo con verificación de columnas
                $sql = "INSERT INTO RO_T_GASTOS_SUPERVISION (
                    SEMANA, ANIO, ID_CONC, IMPORTE, IMPORTE_TARJETA, 
                    OBSERVACIONES, SUPERVISORA, ESTADO, FECHA_AUTO";
                
                // Verificar si existen las columnas adicionales
                $checkObsAut = sqlsrv_query($this->cid_central, 
                    "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_NAME = 'RO_T_GASTOS_SUPERVISION' AND COLUMN_NAME = 'OBSERVACIONES_AUT'");
                $hasObsAut = false;
                if ($checkObsAut) {
                    $row = sqlsrv_fetch_array($checkObsAut, SQLSRV_FETCH_ASSOC);
                    $hasObsAut = $row['count'] > 0;
                }
                
                $checkFechaMod = sqlsrv_query($this->cid_central, 
                    "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_NAME = 'RO_T_GASTOS_SUPERVISION' AND COLUMN_NAME = 'FECHA_MODIF'");
                $hasFechaMod = false;
                if ($checkFechaMod) {
                    $row = sqlsrv_fetch_array($checkFechaMod, SQLSRV_FETCH_ASSOC);
                    $hasFechaMod = $row['count'] > 0;
                }
                
               $params = array(
                    $semana,
                    $anio,
                    $gasto['id_conc'],
                    $importeEfectivo,
                    $importeTarjeta,
                    $gasto['observaciones'],
                    $supervisora,
                    $estado,
                    $estado == 1 ? new DateTime() : null
                );

                if ($hasObsAut) {
                    $sql .= ", OBSERVACIONES_AUT";
                    $params[] = $observacionesAut;
                }

                if ($hasFechaMod) {
                    $sql .= ", FECHA_MODIF";
                    $params[] = new DateTime();
                }

                
                $sql .= ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?";
                if ($hasObsAut) $sql .= ", ?";
                if ($hasFechaMod) $sql .= ", ?";
                $sql .= ")";

                $stmt = sqlsrv_query($this->cid_central, $sql, $params);
                if ($stmt === false) {
                    $errors = sqlsrv_errors();
                    throw new Exception("Error al insertar gasto $index: " . print_r($errors, true));
                }
            }

            sqlsrv_commit($this->cid_central);
            
            $mensaje = "Gastos procesados correctamente. ";
            $mensaje .= "Aprobados automáticamente: $gastosAprobados. ";
            if ($gastosExcedidos > 0) {
                $mensaje .= "Requieren auditoría: $gastosExcedidos.";
            }

            return [
                'success' => true, 
                'message' => $mensaje,
                'gastos_aprobados' => $gastosAprobados,
                'gastos_excedidos' => $gastosExcedidos,
                'detalle_validacion' => $detalleValidacion
            ];
        } catch (Exception $e) {
            sqlsrv_rollback($this->cid_central);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Inserta adelanto (copiado de la clase Gasto original)
     */
    public function insertarAdelanto($semana, $anio, $importeAdelanto, $supervisora) {
        $sql = "INSERT INTO FU_T_ADELANTOS_SUPERVISION (SEMANA, ANIO, IMPORTE_ADELANTO, SUPERVISORA) VALUES (?, ?, ?, ?)";
        $params = array($semana, $anio, $importeAdelanto, $supervisora);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Error al insertar adelanto: " . print_r(sqlsrv_errors(), true));
        }

        return sqlsrv_rows_affected($stmt);
    }

    /**
     * Verifica si un concepto corresponde con otro (manejo de nombres similares)
     */
    private function conceptoCorresponde($concepto1, $concepto2) {
        return strtolower(trim($concepto1)) === strtolower(trim($concepto2));
    }

    /**
     * Obtiene presupuesto para una supervisora y concepto específico
     */
    public function obtenerPresupuestoConcepto($semana, $anio, $supervisora, $idConcepto) {
        try {
            // Convertir semana a mes
            $fechaSemana = $this->obtenerFechaDeSemana($semana, $anio);
            $mes = date('n', strtotime($fechaSemana));

            // Obtener ID de supervisora
            $presupuesto = new Presupuesto();
            $supervisoras = $presupuesto->traerSupervisoras();
            $idSupervisora = null;
            
            foreach ($supervisoras as $sup) {
                $nombreDB = trim(strtoupper(str_replace(['.', ' '], '', $sup['NOMBRE'])));
                $nombreSesion = trim(strtoupper(str_replace(['.', ' '], '', $supervisora)));

                if ($nombreDB === $nombreSesion || 
                    (strlen($nombreSesion) > 3 && strpos($nombreDB, $nombreSesion) === 0) || 
                    (strlen($nombreDB) > 3 && strpos($nombreSesion, $nombreDB) === 0)) {
                    $idSupervisora = $sup['ID'];
                    break;
                }
            }

            if (!$idSupervisora) {
                return 0;
            }

            $sql = "SELECT IMP_PRESUPUESTO 
                    FROM RO_T_PRESUPUESTOS_SUPERVISION 
                    WHERE ANIO = ? AND MES = ? AND ID_SUPERVISORA = ? AND ID_CONC = ?";
            
            $params = [$anio, $mes, $idSupervisora, $idConcepto];
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error al obtener presupuesto: " . print_r(sqlsrv_errors(), true));
                return 0;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row ? floatval($row['IMP_PRESUPUESTO']) : 0;

        } catch (Exception $e) {
            error_log("Error en obtenerPresupuestoConcepto: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene la fecha del lunes de una semana específica
     */
    private function obtenerFechaDeSemana($semana, $anio) {
        $fecha = new DateTime();
        $fecha->setISODate($anio, $semana, 1);
        return $fecha->format('Y-m-d');
    }
}