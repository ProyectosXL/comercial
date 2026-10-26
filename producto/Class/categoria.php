<?php

class Categoria
{
    private $cid_central;

    function __construct(){
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');
    } 

    public function traerRubros() {
        $sql = "SELECT * FROM RO_T_RUBROS_CODIFICACION ORDER BY DESC_RUBRO ASC";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {
            $rows = array();
    
            while($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en traerRubros: " . print_r($th, true));
            return [];
        }     
    }

    public function traerVistaRubroCategoriaCodificacion($sigla) {
        // CORREGIDO: Consultar directamente las tablas con JOIN para obtener el campo ACTIVO
        $sql = "SELECT 
                    r.SIGLA as RUBRO,
                    r.DESC_RUBRO,
                    c.CATEGORIA,
                    c.DESC_CATEGORIA,
                    c.DESC_CATEGORIA_PADRE,
                    ISNULL(c.ACTIVO, 1) AS ESTADO_ACTIVO
                FROM RO_T_RUBROS_CODIFICACION r
                INNER JOIN SJ_CATEGORIAS c ON r.SIGLA = c.RUBRO COLLATE Modern_Spanish_CI_AI
                WHERE r.SIGLA = ?
                ORDER BY c.CATEGORIA ASC";

        $stmt = sqlsrv_prepare($this->cid_central, $sql, array($sigla));
        
        if (!$stmt) {
            error_log("Error preparando consulta: " . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $result = sqlsrv_execute($stmt);
        
        if (!$result) {
            error_log("Error ejecutando consulta: " . print_r(sqlsrv_errors(), true));
            return [];
        }

        try {
            $rows = array();
    
            while($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en traerVistaRubroCategoriaCodificacion: " . print_r($th, true));
            return [];
        }     
    }

    public function insertarNuevaCategoria($codCategoria, $descCategoria, $descCategoriaPadre, $siglaRubro) {
        // Verificar si ya existe
        $checkSql = "SELECT 1 FROM SJ_CATEGORIAS WHERE CATEGORIA = ? AND RUBRO = ?";
        $checkParams = array($codCategoria, $siglaRubro);

        try {
            $checkStmt = sqlsrv_prepare($this->cid_central, $checkSql, $checkParams);
            
            if (!$checkStmt) {
                throw new Exception("Error al preparar verificación: " . print_r(sqlsrv_errors(), true));
            }
            
            $checkResult = sqlsrv_execute($checkStmt);
            
            if (!$checkResult) {
                throw new Exception("Error al ejecutar verificación: " . print_r(sqlsrv_errors(), true));
            }
   
            if (sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC) !== null) {
                return false; // Ya existe
            }

            // Insertar nueva categoría
            $insertSql = "INSERT INTO SJ_CATEGORIAS (CATEGORIA, DESC_CATEGORIA, DESC_CATEGORIA_PADRE, RUBRO) VALUES (?, ?, ?, ?)";
            $insertParams = array($codCategoria, $descCategoria, $descCategoriaPadre, $siglaRubro);
                            
            $stmt = sqlsrv_prepare($this->cid_central, $insertSql, $insertParams);
            
            if (!$stmt) {
                throw new Exception("Error al preparar inserción: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar inserción: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;

        } catch (\Throwable $th) {
            error_log("Error en insertarNuevaCategoria: " . print_r($th, true));
            return false;
        }
    }

    public function actualizarDescripcionCategoria($descCategoria, $descCategoriaPadre, $siglaRubro, $codCategoria) {
        $sql = "UPDATE SJ_CATEGORIAS SET DESC_CATEGORIA = ?, DESC_CATEGORIA_PADRE = ? WHERE CATEGORIA = ? AND RUBRO = ?";
        $params = array($descCategoria, $descCategoriaPadre, $codCategoria, $siglaRubro);

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error al preparar actualización: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar actualización: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;

        } catch (\Throwable $th) {
            error_log("Error en actualizarDescripcionCategoria: " . print_r($th, true));
            return false;
        }     
    }
    // MÉTODO ACTUALIZADO: Actualizar estado de categoría con mejor manejo
    public function actualizarEstadoCategoria($siglaRubro, $codCategoria, $nuevoEstado) {
        // Primero verificar que el registro existe
        $checkSql = "SELECT COUNT(*) as COUNT FROM SJ_CATEGORIAS WHERE CATEGORIA = ? AND RUBRO = ?";
        $checkParams = array($codCategoria, $siglaRubro);
        
        try {
            $checkStmt = sqlsrv_prepare($this->cid_central, $checkSql, $checkParams);
            
            if (!$checkStmt) {
                throw new Exception("Error al preparar verificación: " . print_r(sqlsrv_errors(), true));
            }
            
            $checkResult = sqlsrv_execute($checkStmt);
            
            if (!$checkResult) {
                throw new Exception("Error al ejecutar verificación: " . print_r(sqlsrv_errors(), true));
            }
            
            $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
            if ($row['COUNT'] == 0) {
                error_log("No existe la categoría $codCategoria en el rubro $siglaRubro");
                return false;
            }
            
            // Actualizar el estado
            $sql = "UPDATE SJ_CATEGORIAS SET ACTIVO = ? WHERE CATEGORIA = ? AND RUBRO = ?";
            $params = array($nuevoEstado, $codCategoria, $siglaRubro);

            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error al preparar actualización de estado: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar actualización de estado: " . print_r(sqlsrv_errors(), true));
            }
            
            // Verificar que se actualizó al menos una fila
            $rowsAffected = sqlsrv_rows_affected($stmt);
            
            if ($rowsAffected === false || $rowsAffected == 0) {
                error_log("No se actualizó ninguna fila. Rubro: $siglaRubro, Categoria: $codCategoria, Estado: $nuevoEstado");
                return false;
            }
            
            error_log("Estado actualizado correctamente. Rubro: $siglaRubro, Categoria: $codCategoria, Nuevo Estado: $nuevoEstado, Filas afectadas: $rowsAffected");
            return true;

        } catch (\Throwable $th) {
            error_log("Error en actualizarEstadoCategoria: " . print_r($th, true));
            return false;
        }     
    }

    public function insertarNuevoRubro($sigla, $descripcion) {
        // Verificar si ya existe
        $checkSql = "SELECT 1 FROM RO_T_RUBROS_CODIFICACION WHERE SIGLA = ?";
        $checkParams = array($sigla);

        try {
            $checkStmt = sqlsrv_prepare($this->cid_central, $checkSql, $checkParams);
            
            if (!$checkStmt) {
                throw new Exception("Error al preparar verificación de rubro: " . print_r(sqlsrv_errors(), true));
            }
            
            $checkResult = sqlsrv_execute($checkStmt);
            
            if (!$checkResult) {
                throw new Exception("Error al ejecutar verificación de rubro: " . print_r(sqlsrv_errors(), true));
            }
   
            if (sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC) !== null) {
                return false; // Ya existe
            }

            // Insertar nuevo rubro
            $insertSql = "INSERT INTO RO_T_RUBROS_CODIFICACION (SIGLA, DESC_RUBRO) VALUES (?, ?)";
            $insertParams = array($sigla, $descripcion);
                            
            $stmt = sqlsrv_prepare($this->cid_central, $insertSql, $insertParams);
            
            if (!$stmt) {
                throw new Exception("Error al preparar inserción de rubro: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar inserción de rubro: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;

        } catch (\Throwable $th) {
            error_log("Error en insertarNuevoRubro: " . print_r($th, true));
            return false;
        }
    }

    public function obtenerSiglasDisponibles() {
        // Obtener todas las letras del alfabeto que no están en uso
        $sql = "SELECT CHAR(number) as SIGLA 
                FROM master..spt_values 
                WHERE type = 'P' 
                AND number BETWEEN 65 AND 90 
                AND CHAR(number) NOT IN (SELECT SIGLA FROM RO_T_RUBROS_CODIFICACION)
                ORDER BY CHAR(number)";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                throw new Exception("Error al ejecutar consulta de siglas: " . print_r(sqlsrv_errors(), true));
            }

            $siglas = array();
            
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $siglas[] = $row['SIGLA'];
            }
            
            return $siglas;

        } catch (\Throwable $th) {
            error_log("Error en obtenerSiglasDisponibles: " . print_r($th, true));
            // Fallback: devolver letras básicas si falla la consulta
            $todasLasLetras = range('A', 'Z');
            $letrasUsadas = $this->obtenerSiglasEnUso();
            return array_diff($todasLasLetras, $letrasUsadas);
        }
    }

    private function obtenerSiglasEnUso() {
        $sql = "SELECT SIGLA FROM RO_T_RUBROS_CODIFICACION";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                return [];
            }

            $siglas = array();
            
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $siglas[] = $row['SIGLA'];
            }
            
            return $siglas;

        } catch (\Throwable $th) {
            error_log("Error en obtenerSiglasEnUso: " . print_r($th, true));
            return [];
        }
    }

    // ===== Reasignación masiva de categorías por Excel =====
    // SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA_AUX guarda solo las correcciones manuales; la vista
    // SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA las aplica sobre el maestro. Si el artículo ya tiene
    // corrección se actualiza, si no se inserta. Se valida contra SJ_CATEGORIAS dentro del rubro del artículo.

    const MAX_FILAS_IMPORTACION = 5000;

    // Recibe filas [{fila, COD_ARTICU, CATEGORIA, CATEGORIA_PADRE}] y devuelve la vista previa por fila.
    public function validarImportacionCategorias(array $filas) {
        if (count($filas) === 0) {
            return ['ok' => false, 'mensaje' => 'El archivo no tiene filas para procesar.'];
        }
        if (count($filas) > self::MAX_FILAS_IMPORTACION) {
            return ['ok' => false, 'mensaje' => 'El archivo supera el máximo de ' . self::MAX_FILAS_IMPORTACION . ' filas.'];
        }

        try {
            // Códigos normalizados y repetidos dentro del Excel
            $ocurrencias = [];
            foreach ($filas as $i => $f) {
                $cod = strtoupper(trim(strval($f['COD_ARTICU'] ?? '')));
                $filas[$i]['COD_ARTICU'] = $cod;
                if ($cod !== '') {
                    $ocurrencias[$cod][] = $f['fila'] ?? ($i + 2);
                }
            }

            $articulos = $this->traerArticulosConCategoria(array_keys($ocurrencias));
            $catalogo = $this->traerCatalogoCategoriasActivas();

            $resultado = [];
            $resumen = ['ok' => 0, 'sin_cambios' => 0, 'error' => 0];

            foreach ($filas as $i => $f) {
                $cod = $f['COD_ARTICU'];
                $catExcel = trim(strval($f['CATEGORIA'] ?? ''));
                $padreExcel = trim(strval($f['CATEGORIA_PADRE'] ?? ''));

                $r = [
                    'fila'                  => $f['fila'] ?? ($i + 2),
                    'COD_ARTICU'            => $cod,
                    'DESCRIPCIO'            => '',
                    'RUBRO'                 => '',
                    'CATEGORIA_ACTUAL'      => '',
                    'CATEGORIA_PADRE_ACTUAL'=> '',
                    'CATEGORIA_EXCEL'       => $catExcel,
                    'CATEGORIA_PADRE_EXCEL' => $padreExcel,
                    'CATEGORIA_NUEVA'       => '',
                    'CATEGORIA_PADRE_NUEVA' => '',
                    'EN_AUX'                => false,
                    'estado'                => 'ERROR',
                    'mensaje'               => ''
                ];

                if (isset($articulos[$cod])) {
                    $r['DESCRIPCIO'] = $articulos[$cod]['DESCRIPCIO'];
                    $r['RUBRO'] = $articulos[$cod]['RUBRO'];
                    $r['CATEGORIA_ACTUAL'] = $articulos[$cod]['CATEGORIA'];
                    $r['CATEGORIA_PADRE_ACTUAL'] = $articulos[$cod]['CATEGORIA_PADRE'];
                    $r['EN_AUX'] = $articulos[$cod]['EN_AUX'];
                }

                if ($cod === '') {
                    $r['mensaje'] = 'Código de artículo vacío';
                } elseif (count($ocurrencias[$cod]) > 1) {
                    $r['mensaje'] = 'Código repetido en el Excel (filas ' . implode(', ', $ocurrencias[$cod]) . ')';
                } elseif (!isset($articulos[$cod])) {
                    $r['mensaje'] = 'Artículo inexistente en el maestro de categorías';
                } elseif (strpos($cod, '-') !== false) {
                    $r['mensaje'] = 'Artículo de la codificación anterior (con guion): la vista no toma correcciones para estos códigos';
                } elseif ($catExcel === '') {
                    $r['mensaje'] = 'Categoría vacía';
                } else {
                    $sigla = $this->siglaRubroArticulo($cod);
                    $candidatas = $catalogo[$sigla]['categorias'][$this->normalizarTexto($catExcel)] ?? [];
                    $descRubro = $catalogo[$sigla]['desc_rubro'] ?? $articulos[$cod]['RUBRO'];

                    if (!isset($catalogo[$sigla])) {
                        $r['mensaje'] = 'El rubro ' . $sigla . ' del código no tiene categorías activas en la codificación';
                    } elseif (count($candidatas) === 0) {
                        $r['mensaje'] = 'Categoría inexistente o inactiva para el rubro ' . $sigla . ' - ' . $descRubro;
                    } else {
                        $elegida = $this->elegirCategoria($candidatas, $padreExcel, $r['mensaje']);

                        if ($elegida !== null) {
                            $r['CATEGORIA_NUEVA'] = $elegida['DESC_CATEGORIA'];
                            $r['CATEGORIA_PADRE_NUEVA'] = $elegida['DESC_CATEGORIA_PADRE'];

                            $sinCambios = $this->normalizarTexto($r['CATEGORIA_ACTUAL']) === $this->normalizarTexto($r['CATEGORIA_NUEVA'])
                                && $this->normalizarTexto($r['CATEGORIA_PADRE_ACTUAL']) === $this->normalizarTexto($r['CATEGORIA_PADRE_NUEVA']);

                            $r['estado'] = $sinCambios ? 'SIN_CAMBIOS' : 'OK';
                            $r['mensaje'] = $sinCambios ? 'Ya tiene esa categoría'
                                : ($r['EN_AUX'] ? 'Se actualiza la corrección existente' : 'Se agrega como corrección');
                        }
                    }
                }

                $resumen[$r['estado'] === 'OK' ? 'ok' : ($r['estado'] === 'SIN_CAMBIOS' ? 'sin_cambios' : 'error')]++;
                $resultado[] = $r;
            }

            return ['ok' => true, 'filas' => $resultado, 'resumen' => $resumen];

        } catch (\Throwable $th) {
            error_log("Error en validarImportacionCategorias: " . print_r($th, true));
            return ['ok' => false, 'mensaje' => 'Error al validar el archivo: ' . $th->getMessage()];
        }
    }

    // Vuelve a validar en el servidor y actualiza en una transacción las filas OK, dejando log por artículo.
    public function aplicarImportacionCategorias(array $filas, $usuario) {
        $validacion = $this->validarImportacionCategorias($filas);
        if (!$validacion['ok']) {
            return $validacion;
        }

        $aActualizar = array_values(array_filter($validacion['filas'], function ($r) {
            return $r['estado'] === 'OK';
        }));
        $rechazadas = array_values(array_filter($validacion['filas'], function ($r) {
            return $r['estado'] === 'ERROR';
        }));

        if (count($aActualizar) === 0) {
            return ['ok' => false, 'mensaje' => 'No hay artículos válidos para actualizar.', 'errores' => $rechazadas];
        }

        if (!sqlsrv_begin_transaction($this->cid_central)) {
            error_log("Error al iniciar transacción: " . print_r(sqlsrv_errors(), true));
            return ['ok' => false, 'mensaje' => 'No se pudo iniciar la transacción.'];
        }

        try {
            $stmtLote = $this->ejecutar("SELECT CONVERT(VARCHAR(36), NEWID()) AS LOTE", []);
            $lote = sqlsrv_fetch_array($stmtLote, SQLSRV_FETCH_ASSOC)['LOTE'];

            $sqlUpdate = "UPDATE SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA_AUX
                          SET CATEGORIA = ?, CATEGORIA_PADRE = ?, FECHA_MODIF = GETDATE()
                          WHERE COD_ARTICU = ?";
            $sqlInsert = "INSERT INTO SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA_AUX
                              (COD_ARTICU, DESCRIPCIO, RUBRO, CATEGORIA, CATEGORIA_PADRE, FECHA_MODIF)
                          VALUES (?, ?, ?, ?, ?, GETDATE())";
            $sqlLog = "INSERT INTO RO_LOG_CAMBIO_CATEGORIA_ARTICULO
                          (LOTE, USUARIO, ACCION, COD_ARTICU, CATEGORIA_ANT, CATEGORIA_PADRE_ANT, CATEGORIA_NUEVA, CATEGORIA_PADRE_NUEVA)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            foreach ($aActualizar as $r) {
                if ($r['EN_AUX']) {
                    $stmt = $this->ejecutar($sqlUpdate, [$r['CATEGORIA_NUEVA'], $r['CATEGORIA_PADRE_NUEVA'], $r['COD_ARTICU']]);
                } else {
                    $stmt = $this->ejecutar($sqlInsert, [
                        $r['COD_ARTICU'], $r['DESCRIPCIO'], $r['RUBRO'], $r['CATEGORIA_NUEVA'], $r['CATEGORIA_PADRE_NUEVA']
                    ]);
                }
                if (sqlsrv_rows_affected($stmt) < 1) {
                    throw new Exception("No se grabó el artículo " . $r['COD_ARTICU']);
                }

                $this->ejecutar($sqlLog, [
                    $lote, $usuario, $r['EN_AUX'] ? 'UPDATE' : 'INSERT', $r['COD_ARTICU'],
                    $r['CATEGORIA_ACTUAL'], $r['CATEGORIA_PADRE_ACTUAL'],
                    $r['CATEGORIA_NUEVA'], $r['CATEGORIA_PADRE_NUEVA']
                ]);
            }

            sqlsrv_commit($this->cid_central);

        } catch (\Throwable $th) {
            sqlsrv_rollback($this->cid_central);
            error_log("Error en aplicarImportacionCategorias: " . print_r($th, true));
            return ['ok' => false, 'mensaje' => 'No se aplicó ningún cambio. ' . $th->getMessage()];
        }

        return [
            'ok'                  => true,
            'lote'                => $lote,
            'actualizados'        => count($aActualizar),
            'sin_cambios'         => $validacion['resumen']['sin_cambios'],
            'errores'             => $rechazadas,
            'maestro_actualizado' => $this->regenerarMaestroCategorias()
        ];
    }

    // Regenera SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA en LAKER_SA para que el cambio se vea enseguida.
    // Las demás bases las actualiza el job "PROY - ACTUALIZAR MAESTROS". Si falla, la corrección ya quedó grabada.
    private function regenerarMaestroCategorias() {
        try {
            $stmt = $this->ejecutar("EXEC RO_SP_SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA", []);
            // Recorrer todos los resultados para que el SP termine y aparezcan sus errores
            while (($siguiente = sqlsrv_next_result($stmt)) === true);
            if ($siguiente === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
            return true;
        } catch (\Throwable $th) {
            error_log("Error en regenerarMaestroCategorias: " . print_r($th, true));
            return false;
        }
    }

    // Elige la categoría del catálogo según la categoría padre del Excel (o la deduce si viene vacía).
    private function elegirCategoria(array $candidatas, $padreExcel, &$mensaje) {
        $padres = array_values(array_unique(array_map(function ($c) {
            return $c['DESC_CATEGORIA_PADRE'];
        }, $candidatas)));

        if ($padreExcel === '') {
            if (count($padres) > 1) {
                $mensaje = 'La categoría tiene más de una categoría padre posible (' . implode(' / ', $padres) . '); indicala en el Excel';
                return null;
            }
            return $candidatas[0];
        }

        foreach ($candidatas as $c) {
            if ($this->normalizarTexto($c['DESC_CATEGORIA_PADRE']) === $this->normalizarTexto($padreExcel)) {
                return $c;
            }
        }

        $mensaje = 'Categoría padre no corresponde (esperada: ' . implode(' / ', $padres) . ')';
        return null;
    }

    // Trae los artículos pedidos con su categoría vigente, indexados por código: primero del maestro
    // y, si ya tienen corrección en la AUX, esa pisa los datos (EN_AUX = true).
    // Va en tandas por el límite de parámetros de SQL Server.
    private function traerArticulosConCategoria(array $codigos) {
        $articulos = [];

        foreach (array_chunk($codigos, 500) as $tanda) {
            $marcas = implode(',', array_fill(0, count($tanda), '?'));

            $sqlMaestro = "SELECT LTRIM(RTRIM(COD_ARTICU)) AS COD_ARTICU, DESCRIPCION AS DESCRIPCIO, RUBRO, CATEGORIA, CATEGORIA_PADRE
                           FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                           WHERE COD_ARTICU IN ($marcas)";
            $sqlAux = "SELECT LTRIM(RTRIM(COD_ARTICU)) AS COD_ARTICU, DESCRIPCIO, RUBRO, CATEGORIA, CATEGORIA_PADRE
                       FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA_AUX
                       WHERE COD_ARTICU IN ($marcas)";

            foreach ([$sqlMaestro => false, $sqlAux => true] as $sql => $enAux) {
                $stmt = $this->ejecutar($sql, $tanda);
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $articulos[strtoupper($row['COD_ARTICU'])] = [
                        'DESCRIPCIO'      => trim(strval($row['DESCRIPCIO'])),
                        'RUBRO'           => trim(strval($row['RUBRO'])),
                        'CATEGORIA'       => trim(strval($row['CATEGORIA'])),
                        'CATEGORIA_PADRE' => trim(strval($row['CATEGORIA_PADRE'])),
                        'EN_AUX'          => $enAux
                    ];
                }
            }
        }

        return $articulos;
    }

    // Sigla de rubro con la que la vista SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA busca la categoría en SJ_CATEGORIAS
    // (posición 9 del código, con las mismas reasignaciones que hace la vista). Mantener alineado con la vista.
    private function siglaRubroArticulo($cod) {
        $siglaRubro = substr($cod, 8, 1);
        $siglaCat = substr($cod, 9, 2);

        if ($siglaRubro === 'G') return 'P';
        if ($siglaRubro === 'T' && $siglaCat === '02') return 'F';
        if (substr($siglaCat, 0, 1) === 'A') return 'V';
        return $siglaRubro;
    }

    // Catálogo de categorías activas por sigla de rubro:
    // [sigla] => ['desc_rubro' => ..., 'categorias' => [categoría normalizada => [{DESC_CATEGORIA, DESC_CATEGORIA_PADRE}]]]
    private function traerCatalogoCategoriasActivas() {
        $sql = "SELECT c.RUBRO AS SIGLA, r.DESC_RUBRO, c.DESC_CATEGORIA, c.DESC_CATEGORIA_PADRE
                FROM SJ_CATEGORIAS c
                LEFT JOIN RO_T_RUBROS_CODIFICACION r ON r.SIGLA = c.RUBRO COLLATE Modern_Spanish_CI_AI
                WHERE ISNULL(c.ACTIVO, 1) = 1";

        $catalogo = [];
        $stmt = $this->ejecutar($sql, []);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $sigla = strtoupper(trim(strval($row['SIGLA'])));
            $cat = $this->normalizarTexto($row['DESC_CATEGORIA']);
            if ($cat === '') {
                continue;
            }
            $catalogo[$sigla]['desc_rubro'] = trim(strval($row['DESC_RUBRO']));
            $catalogo[$sigla]['categorias'][$cat][] = [
                'DESC_CATEGORIA'       => trim(strval($row['DESC_CATEGORIA'])),
                'DESC_CATEGORIA_PADRE' => trim(strval($row['DESC_CATEGORIA_PADRE']))
            ];
        }

        return $catalogo;
    }

    // Mayúsculas, sin tildes y con espacios simples (equivalente a comparar con Modern_Spanish_CI_AI).
    private function normalizarTexto($texto) {
        $texto = mb_strtoupper(trim(strval($texto)), 'UTF-8');
        $texto = strtr($texto, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U']);
        return preg_replace('/\s+/u', ' ', $texto);
    }

    private function ejecutar($sql, array $params) {
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        if ($stmt === false) {
            $errores = sqlsrv_errors() ?: [];
            throw new Exception("Error SQL: " . implode(' | ', array_column($errores, 'message')));
        }
        return $stmt;
    }

    // MÉTODO OPCIONAL: Para normalizar todos los NULL a 1 (activo)
    public function normalizarEstadosActivo() {
        $sql = "UPDATE SJ_CATEGORIAS SET ACTIVO = 1 WHERE ACTIVO IS NULL";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                throw new Exception("Error al normalizar estados: " . print_r(sqlsrv_errors(), true));
            }
            
            $rowsAffected = sqlsrv_rows_affected($stmt);
            error_log("Normalizados $rowsAffected registros de NULL a activo");
            
            return true;

        } catch (\Throwable $th) {
            error_log("Error en normalizarEstadosActivo: " . print_r($th, true));
            return false;
        }
    }
}


?>