
<?php

class Articulo
{
    private $cid;
    private $cid_central;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';
        
        $this->cid = new Conexion();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = (isset($_SESSION['usuarioUy']) && $_SESSION['usuarioUy'] == 1) ? 'uy' : 'central';
        $this->cid_central = $this->cid->conectar($db);
    }

    /**
     * Ejecuta una consulta SQL y retorna los resultados como array
     * 
     * @param string $sqlEnviado Consulta SQL a ejecutar
     * @return array Array con los resultados
     * @throws Exception Si hay error en la consulta
     */
    private function retornarArray($sqlEnviado)
    {
        if (empty($sqlEnviado)) {
            throw new Exception('La consulta SQL no puede estar vacía');
        }

        $stmt = sqlsrv_query($this->cid_central, $sqlEnviado);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $errorMessage = 'Error en la consulta SQL';
            if (!empty($errors)) {
                $errorMessage .= ': ' . $errors[0]['message'];
            }
            throw new Exception($errorMessage);
        }

        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }

        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    /**
     * Consulta el stock de un artículo según el tipo especificado
     * 
     * @param string $codigoArticulo Código del artículo
     * @param string $tipo Tipo de consulta (Central, Locales, Franquicias, Todos)
     * @return array Resultados del stock
     * @throws Exception Si hay error en la consulta
     */
    public function consultarStockPorTipo($codigoArticulo, $tipo)
    {
        $cod = $this->escaparParametro($codigoArticulo);

        try {
            switch ($tipo) {
                case 'Central':
                    $sql    = $this->sqlCentral() . " ORDER BY A.COD_ARTICU, A.COD_DEPOSI";
                    $params = [$cod, $cod];
                    break;
                case 'Locales':
                    $sql    = $this->sqlLocales() . " ORDER BY COD_ARTICU, UBICACION, COD_DEPOSI";
                    $params = [$cod, $cod];
                    break;
                case 'Franquicias':
                    $sql    = $this->sqlFranquicias() . " ORDER BY COD_ARTICU, UBICACION, COD_DEPOSI";
                    $params = [$cod, $cod];
                    break;
                case 'Todos':
                default:
                    $sql = "SELECT * FROM (
                                " . $this->sqlCentral() . "
                                UNION ALL
                                " . $this->sqlLocales() . "
                                UNION ALL
                                " . $this->sqlFranquicias() . "
                            ) T ORDER BY LUGAR, COD_ARTICU, UBICACION, COD_DEPOSI";
                    $params = [$cod, $cod, $cod, $cod, $cod, $cod];
                    break;
            }

            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en consulta de stock: ' . print_r(sqlsrv_errors(), true));
            }
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = $row;
            }
            sqlsrv_free_stmt($stmt);
            return $resultados;

        } catch (Exception $e) {
            error_log("Error en consultarStockPorTipo - Tipo: $tipo, Código: $cod, Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function sqlCentral(): string
    {
        return "SELECT 1 AS LUGAR, 'CENTRAL' AS UBICACION,
                    A.COD_ARTICU, B.DESCRIPCIO,
                    A.COD_DEPOSI,
                    ISNULL(E.NOMBRE_SUC, A.COD_DEPOSI) AS DESC_DEPOSI,
                    CAST(A.CANT_STOCK AS INT) AS CANT_STOCK,
                    CAST(A.CANT_COMP  AS INT) AS CANT_COMP,
                    CAST((A.CANT_STOCK - A.CANT_COMP) AS INT) AS CANT_DISP,
                    C.PRECIO, D.DESTINO, D.FAMILIA, D.LIQUIDACION, D.TEMPORADA
                FROM STA19 A
                INNER JOIN STA11 B ON A.COD_ARTICU = B.COD_ARTICU
                LEFT JOIN GVA17 C ON A.COD_ARTICU = C.COD_ARTICU AND C.NRO_DE_LIS = 20
                LEFT JOIN MAESTRO_DESTINOS D ON A.COD_ARTICU = D.COD_ARTICU
                LEFT JOIN STA22 E ON A.COD_DEPOSI = E.COD_SUCURS
                WHERE A.CANT_STOCK > 0
                  AND (A.COD_ARTICU LIKE ? + '%' OR B.DESCRIPCIO LIKE '%' + ? + '%')";
    }

    private function sqlLocales(): string
    {
        return "SELECT 2 AS LUGAR, LK.DESC_SUCURSAL AS UBICACION,
                    A.SKU AS COD_ARTICU, B.DESCRIPCIO,
                    A.COD_CTA_DEPOSITO AS COD_DEPOSI,
                    ISNULL(E.NOMBRE_SUC, A.COD_CTA_DEPOSITO) AS DESC_DEPOSI,
                    CAST(A.CANTIDAD_STOCK AS INT) AS CANT_STOCK,
                    0 AS CANT_COMP,
                    CAST(A.CANTIDAD_STOCK AS INT) AS CANT_DISP,
                    C.PRECIO, D.DESTINO, D.FAMILIA, D.LIQUIDACION, D.TEMPORADA
                FROM SJ_STOCK_EN_LOCALES A
                INNER JOIN LAKERBIS.LOCALES_LAKERS.dbo.SUCURSALES_LAKERS LK
                    ON A.NRO_SUCURSAL = LK.NRO_SUCURSAL
                    AND LK.CANAL = 'PROPIOS'
                    AND LK.HABILITADO = 1
                    AND LK.NRO_SUC_MADRE IS NULL
                INNER JOIN STA11 B ON A.SKU = B.COD_ARTICU
                LEFT JOIN GVA17 C ON A.SKU = C.COD_ARTICU AND C.NRO_DE_LIS = 20
                LEFT JOIN MAESTRO_DESTINOS D ON A.SKU = D.COD_ARTICU
                LEFT JOIN STA22 E ON A.COD_CTA_DEPOSITO = E.COD_SUCURS
                WHERE A.CANTIDAD_STOCK > 0
                  AND (A.SKU LIKE ? + '%' OR B.DESCRIPCIO LIKE '%' + ? + '%')";
    }

    private function sqlFranquicias(): string
    {
        return "SELECT 3 AS LUGAR, LK.DESC_SUCURSAL AS UBICACION,
                    A.SKU AS COD_ARTICU, B.DESCRIPCIO,
                    A.COD_CTA_DEPOSITO AS COD_DEPOSI,
                    ISNULL(E.NOMBRE_SUC, A.COD_CTA_DEPOSITO) AS DESC_DEPOSI,
                    CAST(A.CANTIDAD_STOCK AS INT) AS CANT_STOCK,
                    0 AS CANT_COMP,
                    CAST(A.CANTIDAD_STOCK AS INT) AS CANT_DISP,
                    C.PRECIO, D.DESTINO, D.FAMILIA, D.LIQUIDACION, D.TEMPORADA
                FROM SJ_STOCK_EN_FRANQUICIAS A
                INNER JOIN LAKERBIS.LOCALES_LAKERS.dbo.SUCURSALES_LAKERS LK
                    ON A.NRO_SUCURSAL = LK.NRO_SUCURSAL
                    AND LK.CANAL = 'FRANQUICIAS'
                    AND LK.HABILITADO = 1
                    AND LK.NRO_SUC_MADRE IS NULL
                INNER JOIN STA11 B ON A.SKU = B.COD_ARTICU
                LEFT JOIN GVA17 C ON A.SKU = C.COD_ARTICU AND C.NRO_DE_LIS = 20
                LEFT JOIN MAESTRO_DESTINOS D ON A.SKU = D.COD_ARTICU
                LEFT JOIN STA22 E ON A.COD_CTA_DEPOSITO = E.COD_SUCURS
                WHERE A.CANTIDAD_STOCK > 0
                  AND (A.SKU LIKE ? + '%' OR B.DESCRIPCIO LIKE '%' + ? + '%')";
    }

    /**
     * Busca artículos por descripción parcial
     * 
     * @param string $descripcion Descripción parcial a buscar
     * @param int $limite Límite de resultados
     * @return array Array con los artículos encontrados
     */
    public function buscarArticulosPorDescripcion($descripcion, $limite = 50)
    {
        $descripcion = $this->escaparParametro($descripcion);
        $limite = (int)$limite;
        
        $sql = "SELECT TOP $limite COD_ARTICU, DESCRIPCIO 
                FROM ARTICULOS 
                WHERE DESCRIPCIO LIKE '%$descripcion%' 
                ORDER BY DESCRIPCIO";
        
        try {
            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error en buscarArticulosPorDescripcion: " . $e->getMessage());
            throw new Exception('Error al buscar artículos');
        }
    }

    /**
     * Obtiene información básica de un artículo
     * 
     * @param string $codigoArticulo Código del artículo
     * @return array|null Información del artículo o null si no existe
     */
    public function obtenerInfoArticulo($codigoArticulo)
    {
        $codigoArticulo = $this->escaparParametro($codigoArticulo);
        
        $sql = "SELECT COD_ARTICU, DESCRIPCIO, FAMILIA, TEMPORADA, PRECIO_BASE 
                FROM ARTICULOS 
                WHERE COD_ARTICU = '$codigoArticulo'";
        
        try {
            $resultados = $this->retornarArray($sql);
            return !empty($resultados) ? $resultados[0] : null;
        } catch (Exception $e) {
            error_log("Error en obtenerInfoArticulo: " . $e->getMessage());
            throw new Exception('Error al obtener información del artículo');
        }
    }

    /**
     * Obtiene los depósitos disponibles
     * 
     * @return array Array con los depósitos
     */
    public function obtenerDepositos()
    {
        $sql = "SELECT DISTINCT COD_DEPOSI, DESCRIPCIO 
                FROM DEPOSITOS 
                WHERE ACTIVO = 1 
                ORDER BY COD_DEPOSI";
        
        try {
            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error en obtenerDepositos: " . $e->getMessage());
            throw new Exception('Error al obtener depósitos');
        }
    }

    /**
     * Obtiene las ubicaciones disponibles
     * 
     * @return array Array con las ubicaciones
     */
    public function obtenerUbicaciones()
    {
        $sql = "SELECT DISTINCT UBICACION 
                FROM STOCK_DEPOSITOS 
                WHERE UBICACION IS NOT NULL 
                ORDER BY UBICACION";
        
        try {
            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error en obtenerUbicaciones: " . $e->getMessage());
            throw new Exception('Error al obtener ubicaciones');
        }
    }

    /**
     * Verifica si existe un artículo
     * 
     * @param string $codigoArticulo Código del artículo
     * @return bool True si existe
     */
    public function existeArticulo($codigoArticulo)
    {
        $codigoArticulo = $this->escaparParametro($codigoArticulo);
        
        $sql = "SELECT COUNT(*) as EXISTE 
                FROM ARTICULOS 
                WHERE COD_ARTICU = '$codigoArticulo'";
        
        try {
            $resultado = $this->retornarArray($sql);
            return $resultado[0]['EXISTE'] > 0;
        } catch (Exception $e) {
            error_log("Error en existeArticulo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Escapa un parámetro para prevenir inyección SQL
     * 
     * @param string $parametro Parámetro a escapar
     * @return string Parámetro escapado
     */
    private function escaparParametro($parametro)
    {
        // Remover caracteres peligrosos
        $parametro = str_replace(["'", '"', ';', '--', '/*', '*/'], '', $parametro);
        return trim($parametro);
    }

    /**
     * Obtiene estadísticas generales de stock
     * 
     * @return array Estadísticas del inventario
     */
    public function obtenerEstadisticasGenerales()
    {
        $sql = "SELECT 
                    COUNT(DISTINCT COD_ARTICU) as TOTAL_ARTICULOS,
                    COUNT(DISTINCT COD_DEPOSI) as TOTAL_DEPOSITOS,
                    SUM(CANT_STOCK) as STOCK_TOTAL,
                    SUM(CANT_DISP) as DISPONIBLE_TOTAL,
                    SUM(CANT_COMP) as COMPROMETIDO_TOTAL
                FROM STOCK_DEPOSITOS";
        
        try {
            $resultado = $this->retornarArray($sql);
            return $resultado[0] ?? [];
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasGenerales: " . $e->getMessage());
            throw new Exception('Error al obtener estadísticas generales');
        }
    }

    /**
     * Cierra la conexión
     */
    public function cerrarConexion()
    {
        if ($this->cid_central) {
            sqlsrv_close($this->cid_central);
        }
    }

    /**
     * Destructor de la clase
     */
    public function __destruct()
    {
        $this->cerrarConexion();
    }
}