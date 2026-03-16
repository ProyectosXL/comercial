
<?php

if (!extension_loaded('sqlsrv')) {
    die('SQLSRV extension not loaded');
}

class Articulo
{
    private $cid;
    private $cid_central;
    private $cidUy;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
    }
    
    /**
     * Obtiene los datos de precio de un artículo específico
     * 
     * @param string $codigoArticulo El código del artículo a consultar
     * @return array|null Los datos del artículo o null si no existe
     */
    public function obtenerDatosPrecio($codigoArticulo) 
    {
        $query = "SELECT A.COD_ARTICU,
				ISNULL(CASE WHEN D.RUBRO = 'CARTERAS DE VINILICO' THEN D.CATEGORIA
                     WHEN D.RUBRO = 'CHALINAS' AND D.CATEGORIA = 'PONCHO' THEN 'RUANA'
                     WHEN D.RUBRO = 'CHALINAS' THEN D.CATEGORIA
				     WHEN D.RUBRO LIKE 'BILLETERAS%' THEN 'BILLETERA'
                     WHEN D.RUBRO LIKE 'CINTOS%' THEN 'CINTO'
                     WHEN D.RUBRO LIKE 'CAMPERAS' THEN 'CAMPERA'
                     WHEN D.RUBRO LIKE 'RELOJES' THEN 'RELOJ'
                     WHEN D.RUBRO LIKE 'CALZADOS' THEN 'CALZADO'
                     WHEN D.RUBRO LIKE 'EQUIPAJES' THEN 'VALIJA'
                ELSE D.RUBRO END, A.DESCRIPCIO) PRODUCTO, 
                CAST((C.PRECIO/(1+(E.PORCENTAJE/100))) AS DECIMAL(10,2)) PRECIO_S_IVA, CAST(C.PRECIO AS FLOAT) PRECIO_FINAL
                FROM STA11 A
                LEFT JOIN (SELECT COD_ARTICU, PRECIO FROM GVA17 WHERE NRO_DE_LIS = 20) C ON A.COD_ARTICU = C.COD_ARTICU
				LEFT JOIN SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA D ON A.COD_ARTICU = D.COD_ARTICU
				LEFT JOIN GVA41 E ON A.ID_GVA41_COD_IVA = E.ID_GVA41
                WHERE A.COD_ARTICU = ?";
        
        $params = array($codigoArticulo);
        $stmt = sqlsrv_query($this->cid_central, $query, $params);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $errorMsg = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error desconocido';
            error_log("obtenerDatosPrecio SQL error para '$codigoArticulo': " . $errorMsg);
            throw new Exception('Error en consulta SQL: ' . $errorMsg);
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        return $row;
    }
    
    /**
     * Obtiene los datos de precio para múltiples artículos
     * 
     * @param array $codigosArticulos Array con los códigos de artículos a consultar
     * @return array Los datos de los artículos encontrados
     */
    public function obtenerMultiplesDatosPrecio($codigosArticulos) 
    {
        $resultados = array();
        
        foreach ($codigosArticulos as $codigo) {
            if (!empty($codigo)) {
                $resultado = $this->obtenerDatosPrecio($codigo);
                if ($resultado) {
                    $resultados[] = $resultado;
                }
            }
        }
        
        return $resultados;
    }
}