<?php

class Articulo
{
    
    private $cid;
    private $cid_central;

    
    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
        $this->cid_central = $this->cid->conectar($db);


    } 


    private function retornarArray($sqlEnviado){

        $sql = $sqlEnviado;

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
            $rows[] = $v;
        }

        return $rows;  

    }

    public function traerArticulos($rubro, $temporada){

        $sql = "SELECT A.COD_ARTICU, DESCRIPCION, DESTINO, TEMPORADA, B.RUBRO FROM MAESTRO_DESTINOS A
                LEFT JOIN SOF_RUBROS_TANGO B ON A.COD_ARTICU = B.COD_ARTICU
                WHERE TEMPORADA LIKE '$temporada' AND RUBRO LIKE '$rubro'

        ";

        $rows = $this->retornarArray($sql);

        return $rows;

    }  

        public function traerProductosMultiple($rubroCondition, $categoriaCondition, $temporadaCondition, $destinoCondition) {
        try {
            // Validar parámetros
            if (!is_string($rubroCondition) || !is_string($categoriaCondition) || !is_string($temporadaCondition) || !is_string($destinoCondition)) {
                error_log("traerProductosMultiple: Parámetros inválidos");
                throw new Exception("Parámetros inválidos en traerProductosMultiple");
            }

            // Validar sesión
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Determinar base de datos según entorno
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $base = 'SUCURSALES_URUGUAY';
            } else {
                $base = 'LOCALES_LAKERS';
            }

            error_log("traerProductosMultiple - Base: $base, Condiciones: Rubro=$rubroCondition, Temporada=$temporadaCondition, Destino=$destinoCondition");

            $sql = "SELECT ARTICULO, 
                        DESCRIPCIO, 
                        COLOR, 
                        PRECIO, 
                        STOCK_CENTRAL, 
                        STOCK_CENTRAL_FISCAL,
                        STOCK_LOCALES, 
                        STOCK_TOTAL, 
                        STOCK_EXCLUIDO, 
                        STOCK_RESERVA, 
                        CANT_VENTA, 
                        IMP_VENTA, 
                        CAST(FECHA_INGRESO_SUC AS DATE) FECHA_INGRESO_SUC,
                        RUBRO, 
                        CATEGORIA,
                        DESTINO, 
                        TEMPORADA, 
                        LIQUIDACION,
                        COD_EQUIVALENCIA
                    FROM [LAKERBIS].$base.DBO.RO_ANALISIS_PRODUCTO_COMERCIAL
                    WHERE $rubroCondition 
                    AND $categoriaCondition 
                    AND $temporadaCondition 
                    AND $destinoCondition
                    ORDER BY RUBRO, CATEGORIA, TEMPORADA, DESTINO";

            error_log("SQL generado: " . $sql);
            return $this->retornarArray($sql);
            
        } catch (Exception $e) {
            error_log("Error en traerProductosMultiple: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene todos los artículos equivalentes por código de equivalencia
     * @param int $codEquivalencia
     * @return array
     */
    public function obtenerArticulosEquivalentes($codEquivalencia) {
        try {
            // Validar parámetro
            if (!is_numeric($codEquivalencia) || $codEquivalencia <= 0) {
                throw new Exception("Código de equivalencia inválido");
            }

            // Determinar base de datos según entorno
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $base = 'SUCURSALES_URUGUAY';
            } else {
                $base = 'LOCALES_LAKERS';
            }

            $sql = "SELECT 
                        p.ARTICULO,
                        p.DESCRIPCIO,
                        p.COLOR,
                        p.PRECIO,
                        p.STOCK_CENTRAL,
                        p.STOCK_CENTRAL_FISCAL,
                        p.STOCK_LOCALES,
                        p.STOCK_TOTAL,
                        p.CANT_VENTA,
                        p.IMP_VENTA,
                        p.DESTINO,
                        p.TEMPORADA,
                        p.COD_EQUIVALENCIA,
                        ae.ARTICULO_PRINCIPAL,
                        ae.DESCRIPCION_GRUPO
                    FROM [LAKERBIS].$base.DBO.RO_ANALISIS_PRODUCTO_COMERCIAL p
                    INNER JOIN [SERVIDOR].LAKER_SA.DBO.RO_T_ARTICULOS_EQUIVALENTES ae 
                        ON ae.COD_ARTICULO COLLATE Modern_Spanish_CI_AI = LEFT(p.ARTICULO, 11) COLLATE Modern_Spanish_CI_AI
                        AND ae.ACTIVO = 1
                    WHERE ae.COD_EQUIVALENCIA = ?
                    ORDER BY ae.ARTICULO_PRINCIPAL DESC, p.ARTICULO";

            error_log("SQL equivalencias: " . $sql);
            error_log("Código equivalencia: " . $codEquivalencia);

            // Preparar y ejecutar la consulta
            $stmt = sqlsrv_prepare($this->cid_central, $sql, array($codEquivalencia));
            
            if ($stmt === false) {
                throw new Exception("Error al preparar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            if (!sqlsrv_execute($stmt)) {
                throw new Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }

            sqlsrv_free_stmt($stmt);

            error_log("Equivalencias encontradas: " . count($rows));
            return $rows;
            
        } catch (Exception $e) {
            error_log("Error en obtenerArticulosEquivalentes: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * REEMPLAZAR el método listarGruposEquivalencias en Articulo.php
     */
    public function listarGruposEquivalencias() {
    try {
        error_log("=== listarGruposEquivalencias INICIADO ===");
        
        if (!$this->cid_central) {
            error_log("ERROR: Conexión cid_central es NULL");
            return [];
        }
        
        // Query con campo RUBRO
        $sql = "SELECT 
                    ae.COD_EQUIVALENCIA,
                    MAX(ae.DESCRIPCION_GRUPO) AS DESCRIPCION_GRUPO,
                    MAX(ae.RUBRO) AS RUBRO,
                    COUNT(ae.COD_ARTICULO) AS TOTAL_ARTICULOS,
                    MAX(ae.FECHA_CREACION) AS FECHA_CREACION,
                    MAX(ae.FECHA_MODIFICACION) AS FECHA_MODIFICACION,
                    ae.ACTIVO AS ACTIVO
                FROM RO_T_ARTICULOS_EQUIVALENTES ae
                WHERE ae.ACTIVO = 1
                GROUP BY ae.COD_EQUIVALENCIA, ae.ACTIVO
                ORDER BY ae.COD_EQUIVALENCIA DESC";
        
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log("ERROR en sqlsrv_query: " . print_r($errors, true));
            return [];
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        
        sqlsrv_free_stmt($stmt);
        
        // Procesar fechas DateTime si es necesario
        foreach ($rows as &$row) {
            if (isset($row['FECHA_CREACION']) && $row['FECHA_CREACION'] instanceof DateTime) {
                $row['FECHA_CREACION'] = $row['FECHA_CREACION']->format('Y-m-d H:i:s');
            }
            if (isset($row['FECHA_MODIFICACION']) && $row['FECHA_MODIFICACION'] instanceof DateTime) {
                $row['FECHA_MODIFICACION'] = $row['FECHA_MODIFICACION']->format('Y-m-d H:i:s');
            }
        }
        
        return $rows;
        
    } catch (Exception $e) {
        error_log("EXCEPCIÓN en listarGruposEquivalencias: " . $e->getMessage());
        return [];
    }
}

    /**
     * Buscar grupos de equivalencias con filtros
     * @param string $filtroArticulo
     * @param string $filtroGrupo
     * @param string $filtroEstado
     * @return array
     */
        public function buscarGruposEquivalencias($filtroArticulo, $filtroGrupo, $filtroRubro, $filtroEstado) {
        try {
            $condiciones = ["ae.ACTIVO = 1"];
            
            if (!empty($filtroArticulo)) {
                $filtroArticulo = $this->escapeSqlString($filtroArticulo);
                $condiciones[] = "ae.COD_ARTICULO LIKE '%" . $filtroArticulo . "%'";
            }
            
            if (!empty($filtroGrupo)) {
                $condiciones[] = "ae.COD_EQUIVALENCIA = " . intval($filtroGrupo);
            }
            
            if (!empty($filtroRubro)) {
                $filtroRubro = $this->escapeSqlString($filtroRubro);
                $condiciones[] = "ae.RUBRO = '" . $filtroRubro . "'";
            }
            
            if ($filtroEstado !== '') {
                $condiciones[] = "ae.ACTIVO = " . intval($filtroEstado);
            }
            
            $whereClause = "WHERE " . implode(" AND ", $condiciones);
            
            $sql = "SELECT 
                        ae.COD_EQUIVALENCIA,
                        MAX(ae.DESCRIPCION_GRUPO) AS DESCRIPCION_GRUPO,
                        MAX(ae.RUBRO) AS RUBRO,
                        COUNT(ae.COD_ARTICULO) AS TOTAL_ARTICULOS,
                        MAX(ae.FECHA_CREACION) AS FECHA_CREACION,
                        MAX(ae.FECHA_MODIFICACION) AS FECHA_MODIFICACION,
                        ae.ACTIVO AS ACTIVO
                    FROM RO_T_ARTICULOS_EQUIVALENTES ae
                    $whereClause
                    GROUP BY ae.COD_EQUIVALENCIA, ae.ACTIVO
                    ORDER BY ae.COD_EQUIVALENCIA DESC";

            return $this->retornarArray($sql);
            
        } catch (Exception $e) {
            error_log("Error en buscarGruposEquivalencias: " . $e->getMessage());
            throw $e;
        }
    }

     /**
     * Buscar artículos para Select2 - CON SQL CORREGIDO
     * @param string $termino
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function buscarArticulosSelect2($termino, $page = 1, $pageSize = 20)
    {
        try {
            error_log("=== buscarArticulosSelect2 === Término: '$termino', Page: $page");

            $offset = ($page - 1) * $pageSize;
            $terminoEscapado = $this->escapeSqlString($termino);

            // SQL con LEFT JOIN para verificar si el artículo ya está en un grupo
            $sql = "SELECT TOP " . ($pageSize + 1) . "
                        s.COD_ARTICU as id,
                        s.DESCRIPCIO as descripcion,
                        '' as color,
                        CONCAT(s.COD_ARTICU COLLATE Latin1_General_BIN, ' - ', s.DESCRIPCIO COLLATE Latin1_General_BIN) as text,
                        eq.COD_EQUIVALENCIA as en_grupo
                    FROM STA11 s
                    LEFT JOIN RO_T_ARTICULOS_EQUIVALENTES eq ON s.COD_ARTICU COLLATE Latin1_General_BIN = eq.COD_ARTICULO COLLATE Latin1_General_BIN AND eq.ACTIVO = 1
                    WHERE s.USA_ESC = 'B' 
                    AND s.COD_ARTICU LIKE '[XO]%' 
                    AND s.COD_ARTICU NOT LIKE 'OU%' 
                    AND s.PERFIL = 'A'
                    AND (s.COD_ARTICU LIKE '%$terminoEscapado%' OR s.DESCRIPCIO LIKE '%$terminoEscapado%')
                    ORDER BY s.COD_ARTICU";

            error_log("SQL generado: " . $sql);

            $resultados = $this->retornarArray($sql);
            $cantidadResultados = count($resultados);

            error_log("Resultados encontrados: " . $cantidadResultados);

            $hasMore = $cantidadResultados > $pageSize;

            if ($hasMore) {
                array_pop($resultados); // Remover el registro extra
            }

            // Log del primer resultado para debug
            if (count($resultados) > 0) {
                error_log("Primer resultado: " . json_encode($resultados[0]));
            }

            // Asegurar formato correcto
            foreach ($resultados as &$resultado) {
                // Asegurar que color existe (vacío para esta consulta)
                $resultado['color'] = '';

                // Asegurar que text existe
                if (empty($resultado['text'])) {
                    $resultado['text'] = $resultado['id'] . ' - ' . $resultado['descripcion'];
                }

                // Limpiar valores NULL
                if (is_null($resultado['descripcion'])) {
                    $resultado['descripcion'] = '';
                }

                // Asegurar que en_grupo existe y es null si no está definido
                if (!isset($resultado['en_grupo']) || is_null($resultado['en_grupo'])) {
                    $resultado['en_grupo'] = null;
                }
            }

            $respuesta = [
                'articulos' => $resultados,
                'hasMore' => $hasMore
            ];

            error_log("Respuesta final: " . json_encode($respuesta));

            return $respuesta;

        } catch (Exception $e) {
            error_log("Error en buscarArticulosSelect2: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // En caso de error, devolver array vacío
            return [
                'articulos' => [],
                'hasMore' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener grupo completo con sus artículos
     * @param int $codEquivalencia
     * @return array
     */
        public function obtenerGrupoCompleto($codEquivalencia) {
        try {
            // Obtener datos del grupo con RUBRO
            $sqlGrupo = "SELECT TOP 1 
                            COD_EQUIVALENCIA, 
                            DESCRIPCION_GRUPO,
                            RUBRO, 
                            ACTIVO,
                            FECHA_CREACION,
                            FECHA_MODIFICACION
                        FROM RO_T_ARTICULOS_EQUIVALENTES 
                        WHERE COD_EQUIVALENCIA = ? AND ACTIVO = 1";

            $stmt = sqlsrv_prepare($this->cid_central, $sqlGrupo, array($codEquivalencia));
            sqlsrv_execute($stmt);
            $grupo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            if (!$grupo) {
                throw new Exception("Grupo no encontrado");
            }

            // Obtener artículos del grupo
            $sqlArticulos = "SELECT 
                                ae.COD_ARTICULO as COD_ARTICU,
                                s.DESCRIPCIO,
                                ae.RUBRO
                            FROM RO_T_ARTICULOS_EQUIVALENTES ae
                            LEFT JOIN STA11 s ON ae.COD_ARTICULO COLLATE Latin1_General_BIN = s.COD_ARTICU COLLATE Latin1_General_BIN
                            WHERE ae.COD_EQUIVALENCIA = ? AND ae.ACTIVO = 1
                            ORDER BY ae.COD_ARTICULO";

            $stmt = sqlsrv_prepare($this->cid_central, $sqlArticulos, array($codEquivalencia));
            sqlsrv_execute($stmt);
            
            $articulos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $articulos[] = $row;
            }
            sqlsrv_free_stmt($stmt);

            $grupo['articulos'] = $articulos;
            return $grupo;
            
        } catch (Exception $e) {
            error_log("Error en obtenerGrupoCompleto: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear nuevo grupo de equivalencias
     */
    public function crearGrupoEquivalencias($descripcionGrupo, $rubro, $estado, $articulos) {
        try {
            // Obtener el siguiente código de equivalencia
            $sqlMaxCod = "SELECT COALESCE(MAX(COD_EQUIVALENCIA), 1000) + 1 as SIGUIENTE_COD FROM RO_T_ARTICULOS_EQUIVALENTES";
            $stmt = sqlsrv_query($this->cid_central, $sqlMaxCod);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $nuevoCodEquivalencia = $row['SIGUIENTE_COD'];
            sqlsrv_free_stmt($stmt);

            // Insertar artículos del grupo con RUBRO
            $sqlInsert = "INSERT INTO RO_T_ARTICULOS_EQUIVALENTES (
                            COD_EQUIVALENCIA, 
                            COD_ARTICULO, 
                            DESCRIPCION_GRUPO,
                            RUBRO, 
                            FECHA_CREACION, 
                            FECHA_MODIFICACION, 
                            ACTIVO
                        ) VALUES (?, ?, ?, ?, GETDATE(), GETDATE(), ?)";

            foreach ($articulos as $articulo) {
                $params = array(
                    $nuevoCodEquivalencia,
                    $articulo['COD_ARTICU'],
                    $descripcionGrupo,
                    $rubro,
                    $estado
                );

                $stmt = sqlsrv_prepare($this->cid_central, $sqlInsert, $params);
                if (!sqlsrv_execute($stmt)) {
                    throw new Exception("Error al insertar artículo: " . print_r(sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt($stmt);
            }

            return $nuevoCodEquivalencia;
            
        } catch (Exception $e) {
            error_log("Error en crearGrupoEquivalencias: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Editar grupo de equivalencias
     * @param int $codEquivalencia
     * @param string $descripcionGrupo
     * @param int $estado
     * @param array $articulos
     * @return bool
     */
        public function editarGrupoEquivalencias($codEquivalencia, $descripcionGrupo, $rubro, $estado, $articulos) {
        try {
            // Obtener la fecha de creación original del grupo
            $sqlFechaCreacion = "SELECT TOP 1 FECHA_CREACION 
                                FROM RO_T_ARTICULOS_EQUIVALENTES 
                                WHERE COD_EQUIVALENCIA = ?";
            
            $stmt = sqlsrv_prepare($this->cid_central, $sqlFechaCreacion, array($codEquivalencia));
            sqlsrv_execute($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            $fechaCreacionOriginal = $row['FECHA_CREACION'];
            sqlsrv_free_stmt($stmt);

            // Eliminar artículos existentes del grupo
            $sqlDelete = "DELETE FROM RO_T_ARTICULOS_EQUIVALENTES WHERE COD_EQUIVALENCIA = ?";
            $stmt = sqlsrv_prepare($this->cid_central, $sqlDelete, array($codEquivalencia));
            sqlsrv_execute($stmt);
            sqlsrv_free_stmt($stmt);

            // Insertar artículos actualizados con RUBRO
            if ($fechaCreacionOriginal) {
                $sqlInsert = "INSERT INTO RO_T_ARTICULOS_EQUIVALENTES (
                                COD_EQUIVALENCIA, 
                                COD_ARTICULO, 
                                DESCRIPCION_GRUPO,
                                RUBRO, 
                                FECHA_CREACION, 
                                FECHA_MODIFICACION, 
                                ACTIVO
                            ) VALUES (?, ?, ?, ?, ?, GETDATE(), ?)";
                
                foreach ($articulos as $articulo) {
                    $params = array(
                        $codEquivalencia,
                        $articulo['COD_ARTICU'],
                        $descripcionGrupo,
                        $rubro,
                        $fechaCreacionOriginal,
                        $estado
                    );

                    $stmt = sqlsrv_prepare($this->cid_central, $sqlInsert, $params);
                    if (!sqlsrv_execute($stmt)) {
                        throw new Exception("Error al insertar artículo: " . print_r(sqlsrv_errors(), true));
                    }
                    sqlsrv_free_stmt($stmt);
                }
            } else {
                // Si no hay fecha original, usar GETDATE() para ambas
                $sqlInsert = "INSERT INTO RO_T_ARTICULOS_EQUIVALENTES (
                                COD_EQUIVALENCIA, 
                                COD_ARTICULO, 
                                DESCRIPCION_GRUPO,
                                RUBRO, 
                                FECHA_CREACION, 
                                FECHA_MODIFICACION, 
                                ACTIVO
                            ) VALUES (?, ?, ?, ?, GETDATE(), GETDATE(), ?)";
                
                foreach ($articulos as $articulo) {
                    $params = array(
                        $codEquivalencia,
                        $articulo['COD_ARTICU'],
                        $descripcionGrupo,
                        $rubro,
                        $estado
                    );

                    $stmt = sqlsrv_prepare($this->cid_central, $sqlInsert, $params);
                    if (!sqlsrv_execute($stmt)) {
                        throw new Exception("Error al insertar artículo: " . print_r(sqlsrv_errors(), true));
                    }
                    sqlsrv_free_stmt($stmt);
                }
            }

            return true;
            
        } catch (Exception $e) {
            error_log("Error en editarGrupoEquivalencias: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Eliminar grupo de equivalencias (borrado lógico)
     * @param int $codEquivalencia
     * @return bool
     */
    public function eliminarGrupoEquivalencias($codEquivalencia) {
        try {
            $sql = "UPDATE RO_T_ARTICULOS_EQUIVALENTES 
                    SET ACTIVO = 0, FECHA_MODIFICACION = GETDATE() 
                    WHERE COD_EQUIVALENCIA = ?";
            
            $stmt = sqlsrv_prepare($this->cid_central, $sql, array($codEquivalencia));
            
            if (!sqlsrv_execute($stmt)) {
                throw new Exception("Error al eliminar grupo: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
            
        } catch (Exception $e) {
            error_log("Error en eliminarGrupoEquivalencias: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Método auxiliar para escapar strings SQL
     * @param string $str
     * @return string
     */
    private function escapeSqlString($str) {
        if (is_null($str)) return '';
        if (!is_string($str)) $str = (string)$str;
        return str_replace("'", "''", $str);
    }

    /**
     * Verificar si un artículo ya está en algún grupo activo
     * @param string $codigoArticulo
     * @return bool
     */
    public function articuloTieneEquivalencia($codigoArticulo) {
        try {
            $sql = "SELECT COUNT(*) as TOTAL 
                    FROM RO_T_ARTICULOS_EQUIVALENTES 
                    WHERE COD_ARTICULO = ? AND ACTIVO = 1";
            
            $stmt = sqlsrv_prepare($this->cid_central, $sql, array($codigoArticulo));
            sqlsrv_execute($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return intval($row['TOTAL']) > 0;
            
        } catch (Exception $e) {
            error_log("Error en articuloTieneEquivalencia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de equivalencias
     * @return array
     */
    public function obtenerEstadisticasEquivalencias() {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT COD_EQUIVALENCIA) as TOTAL_GRUPOS,
                        COUNT(COD_ARTICULO) as TOTAL_ARTICULOS,
                        COUNT(CASE WHEN ARTICULO_PRINCIPAL = 1 THEN 1 END) as TOTAL_PRINCIPALES,
                        COUNT(CASE WHEN ACTIVO = 1 THEN 1 END) as GRUPOS_ACTIVOS,
                        COUNT(CASE WHEN ACTIVO = 0 THEN 1 END) as GRUPOS_INACTIVOS
                    FROM RO_T_ARTICULOS_EQUIVALENTES";
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
            $estadisticas = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $estadisticas;
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasEquivalencias: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener rubros para filtro
     */
    public function obtenerRubrosEquivalencias() {
        try {
            $sql = "SELECT DISTINCT(RUBRO) RUBRO 
                    FROM SOF_RUBROS_TANGO 
                    WHERE RUBRO NOT LIKE '%OUTLET%' 
                    AND RUBRO NOT IN ('_KITS','LENTES','PACKAGING')
                    ORDER BY RUBRO";
            
            error_log("SQL rubros equivalencias: " . $sql);
            
            $resultados = $this->retornarArray($sql);
            
            error_log("Rubros encontrados: " . count($resultados));
            
            return $resultados;
            
        } catch (Exception $e) {
            error_log("Error en obtenerRubrosEquivalencias: " . $e->getMessage());
            return [];
        }
}

    /**
     * Obtener categorías para filtro
     */
    public function obtenerCategorias() {
        try {
            // Determinar base de datos según entorno
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $base = 'SUCURSALES_URUGUAY';
            } else {
                $base = 'LOCALES_LAKERS';
            }
            
            $sql = "SELECT DISTINCT(CATEGORIA) CATEGORIA 
                    FROM [LAKERBIS].$base.DBO.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                    WHERE CATEGORIA IS NOT NULL
                    AND CATEGORIA != ''
                    ORDER BY CATEGORIA";
            
            error_log("SQL categorías: " . $sql);
            
            $resultados = $this->retornarArray($sql);
            
            error_log("Categorías encontradas: " . count($resultados));
            
            return $resultados;
            
        } catch (Exception $e) {
            error_log("Error en obtenerCategorias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener categorías filtradas por rubro(s)
     */
    public function obtenerCategoriasPorRubro($rubros = []) {
        try {
            error_log("=== obtenerCategoriasPorRubro INICIADO ===");
            error_log("Rubros recibidos: " . json_encode($rubros));
            
            if (!$this->cid_central) {
                error_log("ERROR: Conexión cid_central es NULL");
                return [];
            }
            
            // Determinar base de datos según entorno
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $base = 'SUCURSALES_URUGUAY';
            } else {
                $base = 'LOCALES_LAKERS';
            }
            
            // Si no hay rubros específicos, devolver todas las categorías
            if (empty($rubros) || in_array('%', $rubros) || in_array('', $rubros)) {
                return $this->obtenerCategorias();
            }
            
            // Construir condición WHERE para rubros
            $rubroConditions = [];
            foreach ($rubros as $rubro) {
                if (!empty($rubro) && $rubro !== '%') {
                    $rubroEscapado = $this->escapeSqlString($rubro);
                    $rubroConditions[] = "RUBRO = '$rubroEscapado'";
                }
            }
            
            if (empty($rubroConditions)) {
                return $this->obtenerCategorias();
            }
            
            $rubroWhere = "(" . implode(" OR ", $rubroConditions) . ")";
            
            $sql = "SELECT DISTINCT(CATEGORIA) CATEGORIA 
                    FROM [LAKERBIS].$base.DBO.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                    WHERE CATEGORIA IS NOT NULL
                    AND CATEGORIA != ''
                    AND $rubroWhere
                    ORDER BY CATEGORIA";
            
            error_log("SQL categorías por rubro: " . $sql);
            
            $resultados = $this->retornarArray($sql);
            
            error_log("Categorías por rubro encontradas: " . count($resultados));
            
            return $resultados;
            
        } catch (Exception $e) {
            error_log("EXCEPCIÓN en obtenerCategoriasPorRubro: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Metodo optimizado para DataTables Server-Side
     */
    public function traerProductosPaginados($whereFilters, $globalSearch, $orderCol, $orderDir, $start, $length) {
        try {
            // 1. Definir base de datos
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $base = 'SUCURSALES_URUGUAY';
            } else {
                $base = 'LOCALES_LAKERS';
            }

            // 2. Búsqueda Global
            $searchCondition = "1=1";
            if (!empty($globalSearch)) {
                $str = $this->escapeSqlString($globalSearch);
                $searchCondition = "(ARTICULO LIKE '%$str%' OR DESCRIPCIO LIKE '%$str%' OR RUBRO LIKE '%$str%')";
            }

            // 3. Columnas de ordenamiento
            $columns = [
                0 => 'RUBRO', 1 => 'CATEGORIA', 2 => 'TEMPORADA', 3 => 'DESTINO',
                4 => 'LIQUIDACION', 5 => 'FECHA_INGRESO_SUC', 6 => 'COD_EQUIVALENCIA',
                7 => 'ARTICULO', 8 => 'DESCRIPCIO' // ... etc
            ];
            
            // FIX DE RENDIMIENTO: Si no especifican orden, usar ARTICULO (Primary Key) que es más rápido que ordenar por RUBRO
            $campoOrden = isset($columns[$orderCol]) ? $columns[$orderCol] : 'ARTICULO'; 
            $direccion = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';


            // --- PASO 1: Contar solo los registros (Consulta liviana con NOLOCK para evitar bloqueos) ---
            // Esta consulta NO trae datos, solo devuelve un número.
            
            $sqlCount = "SELECT COUNT_BIG(ARTICULO) as Total
                         FROM [LAKERBIS].$base.DBO.RO_ANALISIS_PRODUCTO_COMERCIAL WITH (NOLOCK)
                         WHERE $whereFilters AND $searchCondition";
                         
            $rowsCount = $this->retornarArray($sqlCount);
            $recordsFiltered = ($rowsCount && isset($rowsCount[0]['Total'])) ? intval($rowsCount[0]['Total']) : 0;

            
            // --- PASO 2: Traer los datos si hay resultados ---
            
            $data = [];
            if ($recordsFiltered > 0) {
                // Usamos solo las columnas necesarias con NOLOCK para mejorar rendimiento
                $sqlData = "SELECT TOP ($length) ARTICULO, DESCRIPCIO, COLOR, PRECIO, 
                            STOCK_CENTRAL, STOCK_CENTRAL_FISCAL, STOCK_LOCALES, 
                            STOCK_TOTAL, STOCK_EXCLUIDO, STOCK_RESERVA, 
                            CANT_VENTA, IMP_VENTA, 
                            CAST(FECHA_INGRESO_SUC AS DATE) as FECHA_INGRESO_SUC_DATE,
                            RUBRO, CATEGORIA, DESTINO, TEMPORADA, LIQUIDACION,
                            COD_EQUIVALENCIA
                        FROM (
                            SELECT *, ROW_NUMBER() OVER (ORDER BY $campoOrden $direccion) as RowNum
                            FROM [LAKERBIS].$base.DBO.RO_ANALISIS_PRODUCTO_COMERCIAL WITH (NOLOCK)
                            WHERE $whereFilters AND $searchCondition
                        ) AS SubQuery
                        WHERE RowNum > $start AND RowNum <= ($start + $length)
                        ORDER BY RowNum";

                $data = $this->retornarArray($sqlData);
            }

            return [
                'data' => $data,
                'recordsFiltered' => $recordsFiltered,
                'recordsTotal' => $recordsFiltered // Asumimos mismo total para velocidad
            ];

        } catch (Exception $e) {
            error_log("Error en traerProductosPaginados: " . $e->getMessage());
            // En caso de error, devolver vacío para no romper el JSON del frontend
            return [
                'data' => [],
                'recordsFiltered' => 0,
                'recordsTotal' => 0
            ];
        }
    }
    public function traerProductosExportacion($whereFilters) {
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $base = 'SUCURSALES_URUGUAY';
        } else {
            $base = 'LOCALES_LAKERS';
        }

        $sql = "SELECT RUBRO, CATEGORIA, TEMPORADA, DESTINO, LIQUIDACION,
                    CAST(FECHA_INGRESO_SUC AS DATE) as FECHA,
                    COD_EQUIVALENCIA, ARTICULO, DESCRIPCIO, COLOR, 
                    PRECIO, 
                    STOCK_LOCALES, 
                    STOCK_CENTRAL, 
                    STOCK_CENTRAL_FISCAL, -- Faltaba mostrar este
                    STOCK_TOTAL, 
                    STOCK_RESERVA,        -- Este es el STOCK ECOMMERCE según tu mapeo
                    STOCK_EXCLUIDO,       -- Faltaba este
                    CANT_VENTA, 
                    IMP_VENTA
                FROM [LAKERBIS].$base.DBO.RO_ANALISIS_PRODUCTO_COMERCIAL
                WHERE $whereFilters 
                ORDER BY RUBRO, CATEGORIA, ARTICULO";

        return $this->retornarArray($sql);
    }
    public function obtenerStatementExportacion($whereFilters) {
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $base = 'SUCURSALES_URUGUAY';
        } else {
            $base = 'LOCALES_LAKERS';
        }

        // SQL LIMPIO Y SIN ORDENAMIENTO
        $sql = "SELECT RUBRO, CATEGORIA, TEMPORADA, DESTINO, LIQUIDACION,
                    CAST(FECHA_INGRESO_SUC AS DATE) as FECHA,
                    COD_EQUIVALENCIA, ARTICULO, DESCRIPCIO, COLOR, 
                    PRECIO, 
                    STOCK_LOCALES, STOCK_CENTRAL, STOCK_CENTRAL_FISCAL, 
                    STOCK_TOTAL, STOCK_RESERVA, STOCK_EXCLUIDO,
                    CANT_VENTA, IMP_VENTA
                FROM [LAKERBIS].$base.DBO.RO_ANALISIS_PRODUCTO_COMERCIAL
                WHERE $whereFilters";
                // !!! BORRÉ EL ORDER BY A PROPÓSITO PARA VELOCIDAD !!!

        // Timeouts máximos
        $options = array(
            "QueryTimeout" => 3600, 
            "Scrollable" => SQLSRV_CURSOR_FORWARD
        );

        $stmt = sqlsrv_query($this->cid_central, $sql, array(), $options);

        if ($stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        return $stmt;
    }
}
    