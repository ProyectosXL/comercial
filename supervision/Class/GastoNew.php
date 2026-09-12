
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
     * Consume la reapertura ABIERTA de un periodo con compare-and-swap.
     *
     * Es el punto critico de la feature. El UPDATE filtra por ESTADO = 'ABIERTA',
     * asi que si dos pestañas guardan a la vez ambas ejecutan el mismo UPDATE y
     * solo una obtiene una fila afectada: la otra ve 0, y quien llama hace
     * rollback. Sin esto, las dos pasarian la validacion y la segunda pisaria la
     * carga de la primera usando una reapertura ya gastada.
     *
     * Debe ejecutarse DENTRO de la transaccion y ANTES de insertar.
     *
     * @return bool true si se consumio; false si no habia ninguna abierta.
     */
    private function consumirReapertura($semana, $anio, $supervisora, $usuario) {
        $sql = "UPDATE RO_T_REAPERTURAS_GASTOS_SUP
                   SET ESTADO = 'CONSUMIDA', FECHA_USO = GETDATE(), USUARIO_USO = ?
                 WHERE SEMANA = ? AND ANIO = ? AND SUPERVISORA = ? AND ESTADO = 'ABIERTA'";

        $params = array($usuario, $semana, $anio, $supervisora);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Error al consumir la reapertura: " . print_r(sqlsrv_errors(), true));
        }

        $filas = sqlsrv_rows_affected($stmt);
        if ($filas === false) {
            throw new Exception("Error al verificar el consumo de la reapertura: " . print_r(sqlsrv_errors(), true));
        }

        return $filas === 1;
    }

    /**
     * Anula logicamente las filas previas de un periodo.
     *
     * Las filas anuladas se conservan como historial de lo que habia antes de la
     * recarga. El indice unico filtrado UQ_GASTO_SUPERVISION_VIGENTE es lo que
     * permite insertar las nuevas: al quedar en VIGENTE = 0 las viejas dejan de
     * ocupar la clave (SEMANA, ANIO, ID_CONC, SUPERVISORA).
     */
    private function anularPeriodo($semana, $anio, $supervisora) {
        $params = array($semana, $anio, $supervisora);

        $sqlGastos = "UPDATE RO_T_GASTOS_SUPERVISION
                         SET VIGENTE = 0
                       WHERE SEMANA = ? AND ANIO = ? AND SUPERVISORA = ? AND VIGENTE = 1";
        $stmt = sqlsrv_query($this->cid_central, $sqlGastos, $params);
        if ($stmt === false) {
            throw new Exception("Error al anular los gastos previos: " . print_r(sqlsrv_errors(), true));
        }
        $gastosAnulados = sqlsrv_rows_affected($stmt);

        // El adelanto tambien se anula: obtenerAdelanto() hace SUM, y sin esto el
        // adelanto de la carga nueva se sumaria al de la carga original.
        $sqlAdelantos = "UPDATE FU_T_ADELANTOS_SUPERVISION
                            SET VIGENTE = 0
                          WHERE SEMANA = ? AND ANIO = ? AND SUPERVISORA = ? AND VIGENTE = 1";
        $stmt = sqlsrv_query($this->cid_central, $sqlAdelantos, $params);
        if ($stmt === false) {
            throw new Exception("Error al anular los adelantos previos: " . print_r(sqlsrv_errors(), true));
        }
        $adelantosAnulados = sqlsrv_rows_affected($stmt);

        return array(
            'gastos' => $gastosAnulados === false ? 0 : $gastosAnulados,
            'adelantos' => $adelantosAnulados === false ? 0 : $adelantosAnulados
        );
    }

    /**
     * Inserta gastos con validación automática contra presupuesto
     *
     * Si el periodo esta cerrado (por fecha o porque ya tiene gastos vigentes),
     * exige una reapertura ABIERTA y la consume en la misma transaccion. El orden
     * importa: primero se consume, despues se anula lo anterior y por ultimo se
     * inserta. Si cualquiera de los tres pasos falla, el rollback deja el periodo
     * cerrado y la reapertura sin usar.
     */
    public function insertarGastosConValidacion($semana, $anio, $gastos, $supervisora, $validacion,
                                                $usuario = null, $importeAdelanto = null,
                                                $periodoCerrado = false) {
        try {
            sqlsrv_begin_transaction($this->cid_central);

            $reaperturaConsumida = false;

            if ($periodoCerrado) {
                if (!$this->consumirReapertura($semana, $anio, $supervisora, $usuario)) {
                    throw new Exception(
                        "El periodo (semana $semana/$anio) esta cerrado y no tiene una reapertura habilitada. " .
                        "Si otra pestaña acaba de guardar, la reapertura ya fue consumida."
                    );
                }
                $reaperturaConsumida = true;
                $this->anularPeriodo($semana, $anio, $supervisora);
            }

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

            // El adelanto va dentro de la misma transaccion. Antes se insertaba
            // despues del commit desde el controller, asi que un error ahi dejaba
            // los gastos guardados, el periodo cerrado y el adelanto perdido.
            if ($importeAdelanto !== null && floatval($importeAdelanto) > 0) {
                $this->insertarAdelanto($semana, $anio, $importeAdelanto, $supervisora);
            }

            sqlsrv_commit($this->cid_central);

            $mensaje = "Gastos procesados correctamente. ";
            $mensaje .= "Aprobados automáticamente: $gastosAprobados. ";
            if ($gastosExcedidos > 0) {
                $mensaje .= "Requieren auditoría: $gastosExcedidos.";
            }
            if ($reaperturaConsumida) {
                $mensaje .= " Se usó la reapertura del período, que vuelve a quedar cerrado.";
            }

            return [
                'success' => true,
                'message' => $mensaje,
                'gastos_aprobados' => $gastosAprobados,
                'gastos_excedidos' => $gastosExcedidos,
                'detalle_validacion' => $detalleValidacion,
                'reapertura_consumida' => $reaperturaConsumida
            ];
        } catch (Exception $e) {
            sqlsrv_rollback($this->cid_central);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Inserta adelanto (copiado de la clase Gasto original)
     *
     * Se llama desde insertarGastosConValidacion, dentro de su transaccion.
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