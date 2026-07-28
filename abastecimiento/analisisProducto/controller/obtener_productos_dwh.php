<?php
header('Content-Type: application/json');
require_once '../../Class/Conexion.php';
require_once '../Class/Articulo.php';

// Helpers (sin cambios)
function escape_val($s)
{
    return str_replace("'", "''", (string) $s);
}
function buildCondition($values, $fieldName)
{
    if (!is_array($values) || empty($values) || in_array('%', $values))
        return "1=1";
    $reg = [];
    $null = false;
    foreach ($values as $v) {
        if ($v === 'NULL')
            $null = true;
        else
            $reg[] = escape_val($v);
    }
    $sql = [];
    if (!empty($reg))
        $sql[] = "$fieldName IN ('" . implode("','", $reg) . "')";
    if ($null)
        $sql[] = "($fieldName IS NULL OR $fieldName = '')";
    return empty($sql) ? "1=1" : "(" . implode(" OR ", $sql) . ")";
}

try {
    $rubros = isset($_POST['rubro']) ? $_POST['rubro'] : [];
    $categorias = isset($_POST['categoria']) ? $_POST['categoria'] : [];
    $temporadas = isset($_POST['temporada']) ? $_POST['temporada'] : [];
    $destinos = isset($_POST['destino']) ? $_POST['destino'] : [];

    $conds = [];
    $conds[] = buildCondition($rubros, 'A.Rubro');
    $conds[] = buildCondition($categorias, 'A.Categoria');
    $conds[] = buildCondition($temporadas, 'A.Temporada');
    $conds[] = buildCondition($destinos, 'A.Destino');
    $where = implode(" AND ", $conds);


    // Iniciar sesión si no está iniciada para leer la variable de entorno
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Determinar la base de datos según el entorno
    $base = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'SUCURSALES_URUGUAY' : 'LOCALES_LAKERS';

    // ==============================================================================
    // ¡AQUÍ ESTÁ LA CORRECCIÓN!
    // Se agregan alias (AS) para que los nombres de las columnas coincidan con el
    // formato que espera el JavaScript (todo en mayúsculas).
    // ==============================================================================
    $sql = "SELECT 
                A.Rubro AS RUBRO, 
                A.Categoria AS CATEGORIA, 
                B.CATEGORIA_PADRE AS CATEGORIA_PADRE,
                A.Temporada AS TEMPORADA, 
                A.Destino AS DESTINO, 
                A.Liquidacion AS LIQUIDACION, 
                CONVERT(varchar, A.FechaIngreso, 103) AS FECHA_INGRESO_SUC,
                A.CodEquivalencia AS COD_EQUIVALENCIA, 
                A.Articulo AS ARTICULO, 
                A.Descripcion AS DESCRIPCIO, 
                A.Color AS COLOR, 
                A.Precio AS PRECIO,
                A.StockLocales AS STOCK_LOCALES, 
                A.StockCentral AS STOCK_CENTRAL, 
                A.StockCentralFiscal AS STOCK_CENTRAL_FISCAL, 
                A.StockTotal AS STOCK_TOTAL,
                A.StockReservaEcommerce AS STOCK_RESERVA, 
                A.StockExcluido AS STOCK_EXCLUIDO, 
                A.CantVenta AS CANT_VENTA, 
                A.VentaTotal AS IMP_VENTA,
                A.CantIngresoCentral AS CANT_INGRESO_CENTRAL,
                A.VentaFranq AS VENTA_FRANQ,
                A.VentaMay AS VENTA_MAY,
                A.VentaEcomm AS VENTA_ECOMM,
                A.VentaPropios AS VENTA_PROPIOS,
                A.StockFqs AS STOCK_FQS
            FROM 
                [XL-LAKERBIS].[$base].[dbo].FP_ANALISIS_PRODUCTO_COMERCIAL A
            LEFT JOIN
                [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA B
            ON A.Articulo COLLATE Latin1_General_BIN = B.COD_ARTICU COLLATE Latin1_General_BIN
            WHERE 
                $where";


    $articulo = new Articulo();
    // Reutilizamos el método de Articulo.php, que ahora se conecta a XL-LAKERBIS
    $resultados = $articulo->retornarArray($sql);

    echo json_encode($resultados);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>