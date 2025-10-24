
<?php

class Kit
{
    private $cid_central;
    private $nombreTabla = 'KITS_PUBLICADOS'; // Nombre de tabla parametrizable

    function __construct(){
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');
        
        if (!$this->cid_central) {
            error_log("Kit::__construct - Error: No se pudo conectar a la base de datos");
            throw new Exception("Error de conexión a la base de datos");
        }
    }

    /**
     * Búsqueda de kits por código
     * @param string $q Término de búsqueda
     * @return array
     */
    public function searchKits($q) {
        $sql = "SELECT DISTINCT A.COD_ARTICU AS COD_KIT,
                       B.DESCRIPCIO AS DESCRIP_KIT
                FROM STA03 A
                INNER JOIN STA11 B ON A.COD_ARTICU = B.COD_ARTICU";
        
        $params = [];
        
        if (!empty($q)) {
            $sql .= " WHERE A.COD_ARTICU LIKE ?";
            $params[] = '%' . $q . '%';
        }
        
        $sql .= " ORDER BY A.COD_ARTICU";

        try {
            if (!empty($params)) {
                $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
                if (!$stmt) {
                    throw new Exception("Error al preparar consulta de kits: " . print_r(sqlsrv_errors(), true));
                }
                $result = sqlsrv_execute($stmt);
            } else {
                $stmt = sqlsrv_query($this->cid_central, $sql);
                $result = ($stmt !== false);
            }
            
            if (!$result) {
                throw new Exception("Error al ejecutar consulta de kits: " . print_r(sqlsrv_errors(), true));
            }

            $rows = array();
            
            while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
            
            return $rows;

        } catch (Exception $e) {
            error_log("Error en searchKits: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Búsqueda de artículos por descripción
     * @param string $q Término de búsqueda
     * @return array
     */
    public function searchArticulos($q) {
        $sql = "SELECT COD_ARTICU, DESCRIPCIO AS DESCRIP_ARTICU
                FROM STA11";
        
        $params = [];
        
        if (!empty($q)) {
            $sql .= " WHERE DESCRIPCIO LIKE ? OR COD_ARTICU LIKE ?";
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        
        $sql .= " ORDER BY DESCRIPCIO";

        try {
            if (!empty($params)) {
                $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
                if (!$stmt) {
                    throw new Exception("Error al preparar consulta de artículos: " . print_r(sqlsrv_errors(), true));
                }
                $result = sqlsrv_execute($stmt);
            } else {
                $stmt = sqlsrv_query($this->cid_central, $sql);
                $result = ($stmt !== false);
            }
            
            if (!$result) {
                throw new Exception("Error al ejecutar consulta de artículos: " . print_r(sqlsrv_errors(), true));
            }

            $rows = array();
            
            while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
            
            return $rows;

        } catch (Exception $e) {
            error_log("Error en searchArticulos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener artículos de un kit específico desde STA03
     * @param string $codKit Código del kit
     * @return array
     */
    public function getArticulosByKit($codKit) {
        $sql = "SELECT A.COD_INSUMO AS COD_ARTICU,
                       B.DESCRIPCIO AS DESCRIP_ARTICU,
                       1 AS MULTIPLO
                FROM STA03 A
                INNER JOIN STA11 B ON A.COD_INSUMO = B.COD_ARTICU
                WHERE A.COD_ARTICU = ?
                ORDER BY B.DESCRIPCIO";
        
        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, [$codKit]);
            if (!$stmt) {
                throw new Exception("Error al preparar consulta de artículos del kit: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            if (!$result) {
                throw new Exception("Error al ejecutar consulta de artículos del kit: " . print_r(sqlsrv_errors(), true));
            }

            $rows = array();
            while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
            
            return $rows;

        } catch (Exception $e) {
            error_log("Error en getArticulosByKit: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Insertar lote de kits
     * @param string $codKit
     * @param array $items Array de objetos con codArticu y multiplo
     * @return array
     */
    public function insertBatch($codKit, $items) {
        // Validaciones
        if (empty($codKit) || empty($items)) {
            return [
                'ok' => false,
                'message' => 'Datos incompletos'
            ];
        }

        $inserted = 0;
        $duplicates = [];
        $errors = [];

        // Iniciar transacción
        sqlsrv_begin_transaction($this->cid_central);

        try {
            foreach ($items as $item) {
                $codArticu = $item['codArticu'];
                $multiplo = isset($item['multiplo']) && $item['multiplo'] > 0 ? $item['multiplo'] : 1;

                // Verificar si ya existe
                if ($this->existeKit($codKit, $codArticu)) {
                    $duplicates[] = [
                        'codKit' => $codKit,
                        'codArticu' => $codArticu
                    ];
                    continue;
                }

                // Obtener descripción del artículo
                $descripcion = $this->getDescripcionArticulo($codArticu);

                // Insertar
                $sqlInsert = "INSERT INTO {$this->nombreTabla} 
                              (COD_KIT, COD_ARTICU, DESCRIPCIO, MULTIPLO, INHABILITADO) 
                              VALUES (?, ?, ?, ?, 0)";
                
                $params = [
                    $codKit,
                    $codArticu,
                    $descripcion,
                    $multiplo
                ];

                $stmt = sqlsrv_prepare($this->cid_central, $sqlInsert, $params);
                
                if (!$stmt) {
                    throw new Exception("Error al preparar inserción: " . print_r(sqlsrv_errors(), true));
                }
                
                $result = sqlsrv_execute($stmt);
                
                if (!$result) {
                    throw new Exception("Error al ejecutar inserción: " . print_r(sqlsrv_errors(), true));
                }

                $inserted++;
            }

            // Commit si todo salió bien
            sqlsrv_commit($this->cid_central);

            $message = "Se insertaron $inserted registro(s)";
            if (count($duplicates) > 0) {
                $message .= ". Se omitieron " . count($duplicates) . " duplicado(s)";
            }

            return [
                'ok' => true,
                'inserted' => $inserted,
                'duplicates' => $duplicates,
                'message' => $message
            ];

        } catch (Exception $e) {
            // Rollback en caso de error
            sqlsrv_rollback($this->cid_central);
            error_log("Error en insertBatch: " . $e->getMessage());
            
            return [
                'ok' => false,
                'message' => 'Error al insertar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si existe un kit-artículo
     * @param string $codKit
     * @param string $codArticu
     * @return bool
     */
    private function existeKit($codKit, $codArticu) {
        $sql = "SELECT COUNT(*) AS TOTAL 
                FROM {$this->nombreTabla} 
                WHERE COD_KIT = ? AND COD_ARTICU = ?";
        
        $params = [$codKit, $codArticu];

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                return false;
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                return false;
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            return ($row && $row['TOTAL'] > 0);

        } catch (Exception $e) {
            error_log("Error en existeKit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el kit tiene algún artículo registrado
     * @param string $codKit
     * @return bool
     */
    public function kitTieneArticulos($codKit) {
        $sql = "SELECT COUNT(*) AS TOTAL 
                FROM {$this->nombreTabla} 
                WHERE COD_KIT = ?";
        
        $params = [$codKit];

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                return false;
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                return false;
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            return ($row && $row['TOTAL'] > 0);

        } catch (Exception $e) {
            error_log("Error en kitTieneArticulos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener descripción de artículo
     * @param string $codArticu
     * @return string
     */
    private function getDescripcionArticulo($codArticu) {
        $sql = "SELECT DESCRIPCIO FROM STA11 WHERE COD_ARTICU = ?";
        $params = [$codArticu];

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                return '';
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                return '';
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            return $row ? $row['DESCRIPCIO'] : '';

        } catch (Exception $e) {
            error_log("Error en getDescripcionArticulo: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Listado de kits con DataTables server-side
     * @param array $dtParams Parámetros de DataTables
     * @param array $filters Filtros adicionales
     * @return array
     */
    public function list($dtParams, $filters) {
        error_log("Kit::list - Inicio");
        error_log("Kit::list - dtParams: " . print_r($dtParams, true));
        error_log("Kit::list - filters: " . print_r($filters, true));
        error_log("Kit::list - nombreTabla: " . $this->nombreTabla);
        
        // Mapeo de columnas para ordenamiento
        $columns = ['ID', 'COD_KIT', 'COD_ARTICU', 'DESCRIPCIO', 'MULTIPLO', 'INHABILITADO', 'FECHA_INHA'];
        
        $orderColumn = $columns[$dtParams['orderColumn']] ?? 'ID';
        $orderDir = strtoupper($dtParams['orderDir']) === 'DESC' ? 'DESC' : 'ASC';

        // Construcción de WHERE
        $whereConditions = [];
        $params = [];

        // Filtro de búsqueda global
        if (!empty($dtParams['search'])) {
            $searchTerm = '%' . $dtParams['search'] . '%';
            $whereConditions[] = "(COD_KIT LIKE ? OR COD_ARTICU LIKE ? OR DESCRIPCIO LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filtro por kit
        if (!empty($filters['codKit'])) {
            $whereConditions[] = "COD_KIT LIKE ?";
            $params[] = '%' . $filters['codKit'] . '%';
        }

        // Filtro por estado
        if ($filters['estado'] !== '' && $filters['estado'] !== null) {
            $whereConditions[] = "INHABILITADO = ?";
            $params[] = $filters['estado'];
        }

        $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        try {
            // Contar total sin filtros
            $sqlTotal = "SELECT COUNT(*) AS TOTAL FROM {$this->nombreTabla}";
            $stmtTotal = sqlsrv_query($this->cid_central, $sqlTotal);
            $rowTotal = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC);
            $total = $rowTotal['TOTAL'];

            // Contar con filtros
            $sqlFiltered = "SELECT COUNT(*) AS TOTAL FROM {$this->nombreTabla} $whereClause";
            
            if (count($params) > 0) {
                $stmtFiltered = sqlsrv_prepare($this->cid_central, $sqlFiltered, $params);
                sqlsrv_execute($stmtFiltered);
            } else {
                $stmtFiltered = sqlsrv_query($this->cid_central, $sqlFiltered);
            }
            
            $rowFiltered = sqlsrv_fetch_array($stmtFiltered, SQLSRV_FETCH_ASSOC);
            $filtered = $rowFiltered['TOTAL'];

            // Consulta principal con paginación
            $sql = "SELECT * FROM {$this->nombreTabla} 
                    $whereClause 
                    ORDER BY $orderColumn $orderDir 
                    OFFSET ? ROWS 
                    FETCH NEXT ? ROWS ONLY";
            
            $paramsWithPaging = array_merge($params, [$dtParams['start'], $dtParams['length']]);
            
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $paramsWithPaging);
            
            if (!$stmt) {
                throw new Exception("Error al preparar consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar consulta: " . print_r(sqlsrv_errors(), true));
            }

            $data = [];
            
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Formatear fecha si existe
                $fechaInha = '';
                if ($row['FECHA_INHA'] instanceof DateTime) {
                    $fechaInha = $row['FECHA_INHA']->format('d/m/Y H:i');
                }

                $data[] = [
                    'ID' => (int)$row['ID'],
                    'COD_KIT' => trim($row['COD_KIT']),
                    'COD_ARTICU' => trim($row['COD_ARTICU']),
                    'DESCRIPCIO' => trim($row['DESCRIPCIO']),
                    'MULTIPLO' => (int)$row['MULTIPLO'],
                    'INHABILITADO' => (int)($row['INHABILITADO'] ?? 0),
                    'FECHA_INHA' => $fechaInha
                ];
            }

            return [
                'total' => $total,
                'filtered' => $filtered,
                'data' => $data
            ];

        } catch (Exception $e) {
            error_log("Error en list: " . $e->getMessage());
            return [
                'total' => 0,
                'filtered' => 0,
                'data' => []
            ];
        }
    }

    /**
     * Habilitar/Inhabilitar kit
     * @param int $id
     * @param bool $inhabilitar
     * @return bool
     */
    public function toggle($id, $inhabilitar) {
        try {
            if ($inhabilitar) {
                // Inhabilitar: INHABILITADO=1, FECHA_INHA=GETDATE()
                $sql = "UPDATE {$this->nombreTabla} 
                        SET INHABILITADO = 1, FECHA_INHA = GETDATE() 
                        WHERE ID = ?";
            } else {
                // Habilitar: INHABILITADO=0, FECHA_INHA=NULL
                $sql = "UPDATE {$this->nombreTabla} 
                        SET INHABILITADO = 0, FECHA_INHA = NULL 
                        WHERE ID = ?";
            }

            $params = [$id];
            
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error al preparar toggle: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar toggle: " . print_r(sqlsrv_errors(), true));
            }

            $rowsAffected = sqlsrv_rows_affected($stmt);
            
            return ($rowsAffected > 0);

        } catch (Exception $e) {
            error_log("Error en toggle: " . $e->getMessage());
            return false;
        }
    }
}

?>