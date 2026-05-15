<?php
require_once('../../Class/Conexion.php');

$action = $_POST['action'] ?? '';
$env = $_POST['env'] ?? 'ARG';

$conexion = new Conexion();
// Determine which server to use based on env
// ARG usually uses 'central' (LAKER_SA)
// URY usually uses 'uy' (DATABASE_UY)
$server = ($env === 'ARG') ? 'central' : 'uy';
$cid = $conexion->conectar($server);

if (!$cid) {
    echo json_encode(['error' => 'Error de conexión con la base de datos']);
    exit;
}

switch ($action) {
    case 'get_articles_info':
        $codesJson = $_POST['codes'] ?? '[]';
        $codes = json_decode($codesJson, true);
        
        if (empty($codes)) {
            echo json_encode(['prices' => [], 'descriptions' => []]);
            break;
        }

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        
        // 1. Get Prices
        $sqlPrices = "SELECT RTRIM(COD_ARTICU) as COD_ARTICU, PRECIO FROM GVA17 WHERE NRO_DE_LIS = 20 AND COD_ARTICU IN ($placeholders)";
        $stmtPrices = sqlsrv_query($cid, $sqlPrices, $codes);
        $prices = [];
        if ($stmtPrices) {
            while ($row = sqlsrv_fetch_array($stmtPrices, SQLSRV_FETCH_ASSOC)) {
                $prices[strtoupper(trim($row['COD_ARTICU']))] = $row;
            }
        }

        // 2. Get Descriptions - Very direct approach
        $sqlDesc = "SELECT RTRIM(S.COD_ARTICU) as COD_ARTICU, 
                           RTRIM(S.DESCRIPCIO) as DESCRIPCIO_BASE,
                           RTRIM(S.BASE) as BASE,
                           RTRIM(A.DESCRIPCION) as DESCRIPCION_VIEW,
                           ISNULL(A.DESCRIP_01, '') as DESCRIP_01,
                           ISNULL(A.SINONIMO, '') as SINONIMO,
                           ISNULL(E.PORCENTAJE, 21) as PORCENTAJE,
                           ISNULL(S12.PRECIO_U_L, 0) as COSTO_LIMITE
                    FROM STA11 S
                    LEFT JOIN MAESTRO_ARTICULOS_CODIGOS_ACTIVOS A ON RTRIM(S.COD_ARTICU) = RTRIM(A.COD_ARTICU)
                    LEFT JOIN GVA41 E ON A.ID_GVA41_COD_IVA = E.ID_GVA41
                    LEFT JOIN STA12 S12 ON RTRIM(S.COD_ARTICU) = RTRIM(S12.COD_ARTICU)
                    WHERE S.COD_ARTICU IN ($placeholders)";
        
        $stmtDesc = sqlsrv_query($cid, $sqlDesc, $codes);
        $descriptions = [];
        if ($stmtDesc) {
            while ($row = sqlsrv_fetch_array($stmtDesc, SQLSRV_FETCH_ASSOC)) {
                $row['DESCRIPCIO'] = $row['DESCRIPCION_VIEW'] ?: $row['DESCRIPCIO_BASE'];
                $descriptions[strtoupper(trim($row['COD_ARTICU']))] = $row;
            }
        }

        // Fallback to STA11 only for those NOT found in the view
        $foundCodes = array_keys($descriptions);
        $missingCodes = array_diff(array_map('strtoupper', $codes), $foundCodes);
        
        if (!empty($missingCodes)) {
            $missingPlaceholders = implode(',', array_fill(0, count($missingCodes), '?'));
            $sqlFallback = "SELECT RTRIM(S.COD_ARTICU) as COD_ARTICU, RTRIM(S.DESCRIPCIO) as DESCRIPCIO, 
                                   RTRIM(S.BASE) as BASE,
                                   '' as DESCRIP_01, '' as SINONIMO, 21 as PORCENTAJE,
                                   ISNULL(S12.PRECIO_U_L, 0) as COSTO_LIMITE
                            FROM STA11 S
                            LEFT JOIN STA12 S12 ON RTRIM(S.COD_ARTICU) = RTRIM(S12.COD_ARTICU)
                            WHERE S.COD_ARTICU IN ($missingPlaceholders)";
            $stmtFallback = sqlsrv_query($cid, $sqlFallback, array_values($missingCodes));
            if ($stmtFallback) {
                while ($row = sqlsrv_fetch_array($stmtFallback, SQLSRV_FETCH_ASSOC)) {
                    $descriptions[strtoupper(trim($row['COD_ARTICU']))] = $row;
                }
            }
        }

        echo json_encode([
            'prices' => $prices,
            'descriptions' => $descriptions
        ]);
        break;

    case 'get_articles_by_base':
        $prefixesJson = $_POST['codes'] ?? '[]';
        $prefixes = json_decode($prefixesJson, true);
        
        if (empty($prefixes)) {
            echo json_encode([]);
            break;
        }

        $placeholders = implode(',', array_fill(0, count($prefixes), '?'));
        
        // Use STA11 directly to avoid any filtering from views
        // We fetch the IVA percentage from GVA41 joining by the ID in STA11
        $sql = "SELECT RTRIM(S.COD_ARTICU) as COD_ARTICU, RTRIM(S.DESCRIPCIO) as DESCRIPCIO, 
                       RTRIM(S.BASE) as BASE,
                       '' as DESCRIP_01, '' as SINONIMO, 
                       ISNULL(E.PORCENTAJE, 21) as PORCENTAJE, 
                       ISNULL(G17.PRECIO, 0) as PRECIO,
                       ISNULL(S12.PRECIO_U_L, 0) as COSTO_LIMITE
                FROM STA11 S
                LEFT JOIN GVA17 G17 ON RTRIM(S.COD_ARTICU) = RTRIM(G17.COD_ARTICU) AND G17.NRO_DE_LIS = 20
                LEFT JOIN GVA41 E ON S.ID_GVA41_COD_IVA = E.ID_GVA41
                LEFT JOIN STA12 S12 ON RTRIM(S.COD_ARTICU) = RTRIM(S12.COD_ARTICU)
                WHERE RTRIM(S.BASE) IN ($placeholders)";
        
        $stmt = sqlsrv_query($cid, $sql, $prefixes);
        $results = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $results[] = $row;
            }
        }

        echo json_encode($results);
        break;

    case 'get_all_prices':
        // For Manual Mode: Get all articles with price in Lista 20
        $sql = "SELECT G17.COD_ARTICU, G17.PRECIO, M.DESCRIPCION as DESCRIPCIO, M.DESCRIP_01, M.SINONIMO, E.PORCENTAJE
                FROM GVA17 G17
                LEFT JOIN MAESTRO_ARTICULOS_CODIGOS_ACTIVOS M ON RTRIM(G17.COD_ARTICU) = RTRIM(M.COD_ARTICU)
                LEFT JOIN GVA41 E ON M.ID_GVA41_COD_IVA = E.ID_GVA41
                WHERE G17.NRO_DE_LIS = 20";
        
        $stmt = sqlsrv_query($cid, $sql);
        $data = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['COD_ARTICU'] = trim($row['COD_ARTICU']);
                $data[] = $row;
            }
        }
        echo json_encode($data);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

sqlsrv_close($cid);
?>
