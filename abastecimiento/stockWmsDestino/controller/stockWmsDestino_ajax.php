<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}
require_once __DIR__ . '/../Class/StockWmsDestino.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado. Inicie sesión.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action  = $_POST['action'] ?? $_GET['action'] ?? 'buscar';
    $stockObj = new StockWmsDestino();

    // Determinar acceso restringido: Logística o Administrador
    $esAdmin = Auth::esAdmin();
    $sectorCod = strtoupper(trim(Auth::sectorCodigo() ?? ''));
    $sectorNom = strtoupper(trim(Auth::sectorNombre() ?? ''));
    $rolCod    = strtoupper(trim(Auth::rol() ?? ''));
    $rolNom    = strtoupper(trim(Auth::rolNombre() ?? ''));

    $esLogistica = (
        $sectorCod === 'LOG' ||
        $sectorCod === 'LOGISTICA' ||
        stripos($sectorNom, 'LOGISTICA') !== false ||
        stripos($rolCod, 'LOGISTICA') !== false ||
        stripos($rolNom, 'LOGISTICA') !== false ||
        Auth::puede(['logistica', 'reportes_logistica_stock_wms_destino', 'stock_wms_admin'])
    );

    $accesoRestringido = !($esAdmin || $esLogistica);

    switch ($action) {
        case 'buscar':
            $filtros = [
                'deposito'       => $_POST['deposito'] ?? $_GET['deposito'] ?? '',
                'ubicacion'      => $_POST['ubicacion'] ?? $_GET['ubicacion'] ?? '',
                'cod_articulo'   => $_POST['cod_articulo'] ?? $_GET['cod_articulo'] ?? '',
                'tipo_ubicacion' => $_POST['tipo_ubicacion'] ?? $_GET['tipo_ubicacion'] ?? '',
                'destino'        => $_POST['destino'] ?? $_GET['destino'] ?? '',
                'rubro'          => $_POST['rubro'] ?? $_GET['rubro'] ?? '',
                'temporada'      => $_POST['temporada'] ?? $_GET['temporada'] ?? '',
                'busqueda'       => $_POST['busqueda'] ?? $_GET['busqueda'] ?? '',
            ];

            $datos = $stockObj->getStock($filtros, $accesoRestringido);
            echo json_encode([
                'success'           => true,
                'data'              => $datos,
                'total'             => count($datos),
                'accesoRestringido' => $accesoRestringido
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'getFiltros':
            $refresh = !empty($_POST['refresh']) || !empty($_GET['refresh']);
            $filtros = $stockObj->getFiltros($refresh);

            echo json_encode([
                'success'           => true,
                'filtros'           => $filtros,
                'accesoRestringido' => $accesoRestringido
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data'    => []
    ], JSON_UNESCAPED_UNICODE);
}
