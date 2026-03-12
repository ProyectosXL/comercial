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
    $conds[] = buildCondition($rubros, 'Rubro');
    $conds[] = buildCondition($categorias, 'Categoria');
    $conds[] = buildCondition($temporadas, 'Temporada');
    $conds[] = buildCondition($destinos, 'Destino');
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
                Rubro AS RUBRO, 
                Categoria AS CATEGORIA, 
                Temporada AS TEMPORADA, 
                Destino AS DESTINO, 
                Liquidacion AS LIQUIDACION, 
                CONVERT(varchar, FechaIngreso, 103) AS FECHA_INGRESO_SUC,
                CodEquivalencia AS COD_EQUIVALENCIA, 
                Articulo AS ARTICULO, 
                Descripcion AS DESCRIPCIO, 
                Color AS COLOR, 
                Precio AS PRECIO,
                StockLocales AS STOCK_LOCALES, 
                StockCentral AS STOCK_CENTRAL, 
                StockCentralFiscal AS STOCK_CENTRAL_FISCAL, 
                StockTotal AS STOCK_TOTAL,
                StockReservaEcommerce AS STOCK_RESERVA, 
                StockExcluido AS STOCK_EXCLUIDO, 
                CantVenta AS CANT_VENTA, 
                VentaTotal AS IMP_VENTA,
                CantIngresoCentral AS CANT_INGRESO_CENTRAL,
                VentaFranq AS VENTA_FRANQ,
                VentaMay AS VENTA_MAY,
                VentaEcomm AS VENTA_ECOMM,
                VentaPropios AS VENTA_PROPIOS
            FROM 
                [XL-LAKERBIS].[$base].[dbo].FP_ANALISIS_PRODUCTO_COMERCIAL
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