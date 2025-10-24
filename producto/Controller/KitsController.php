
<?php

// Controller/KitsController.php

header('Content-Type: application/json');

$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'search-kits':
        searchKits();
        break;

    case 'search-articulos':
        searchArticulos();
        break;

    case 'get-articulos-by-kit':
        getArticulosByKit();
        break;

    case 'verificar-kit':
        verificarKit();
        break;

    case 'add':
        addKits();
        break;

    case 'list':
        listKits();
        break;

    case 'toggle':
        toggleKit();
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

/**
 * Búsqueda de kits para Select2
 * GET /kits/search-kits?q=
 */
function searchKits() {
    require_once __DIR__.'/../Class/Kit.php';
    
    $q = $_GET['q'] ?? '';
    $kit = new Kit();
    
    try {
        $results = $kit->searchKits($q);
        
        // Format for Select2
        $formatted = array_map(function($item) {
            return [
                'id' => $item['COD_KIT'],
                'text' => $item['COD_KIT'],
                'descripcion' => $item['DESCRIP_KIT']
            ];
        }, $results);
        
        echo json_encode(['results' => $formatted]);
    } catch (Exception $e) {
        error_log("Error en searchKits: " . $e->getMessage());
        echo json_encode(['results' => []]);
    }
}

/**
 * Búsqueda de artículos para Select2
 * GET /kits/search-articulos?q=
 */
function searchArticulos() {
    require_once __DIR__.'/../Class/Kit.php';
    
    $q = $_GET['q'] ?? '';
    $kit = new Kit();
    
    try {
        $results = $kit->searchArticulos($q);
        
        // Format for Select2
        $formatted = array_map(function($item) {
            return [
                'id' => $item['COD_ARTICU'],
                'text' => $item['COD_ARTICU'] . ' — ' . $item['DESCRIP_ARTICU']
            ];
        }, $results);
        
        echo json_encode(['results' => $formatted]);
    } catch (Exception $e) {
        error_log("Error en searchArticulos: " . $e->getMessage());
        echo json_encode(['results' => []]);
    }
}

/**
 * Obtener artículos de un kit específico
 * GET /kits/get-articulos-by-kit?codKit=
 */
function getArticulosByKit() {
    require_once __DIR__.'/../Class/Kit.php';
    
    $codKit = $_GET['codKit'] ?? '';
    
    if (empty($codKit)) {
        echo json_encode(['results' => []]);
        return;
    }
    
    $kit = new Kit();
    
    try {
        $results = $kit->getArticulosByKit($codKit);
        
        // Devolver datos completos (no formato Select2)
        echo json_encode(['results' => $results]);
    } catch (Exception $e) {
        error_log("Error en getArticulosByKit: " . $e->getMessage());
        echo json_encode(['results' => []]);
    }
}

/**
 * Verificar si un kit tiene artículos registrados
 * GET /kits/verificar-kit?codKit=
 */
function verificarKit() {
    require_once __DIR__.'/../Class/Kit.php';
    
    $codKit = $_GET['codKit'] ?? '';
    
    if (empty($codKit)) {
        echo json_encode(['existe' => false]);
        return;
    }
    
    $kit = new Kit();
    
    try {
        $existe = $kit->kitTieneArticulos($codKit);
        echo json_encode(['existe' => $existe]);
    } catch (Exception $e) {
        error_log("Error en verificarKit: " . $e->getMessage());
        echo json_encode(['existe' => false]);
    }
}

/**
 * Alta de kits
 * POST /kits/add
 * Body: { codKit, items: [{ codArticu, multiplo }] }
 */
function addKits() {
    require_once __DIR__.'/../Class/Kit.php';
    
    // Obtener datos del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['codKit']) || !isset($data['items'])) {
        echo json_encode([
            'ok' => false,
            'message' => 'Datos inválidos'
        ]);
        return;
    }
    
    $codKit = $data['codKit'];
    $items = $data['items'];
    
    if (empty($items)) {
        echo json_encode([
            'ok' => false,
            'message' => 'Debe agregar al menos un artículo'
        ]);
        return;
    }
    
    $kit = new Kit();
    
    try {
        $result = $kit->insertBatch($codKit, $items);
        echo json_encode($result);
    } catch (Exception $e) {
        error_log("Error en addKits: " . $e->getMessage());
        echo json_encode([
            'ok' => false,
            'message' => 'Error al insertar: ' . $e->getMessage()
        ]);
    }
}

/**
 * Listado de kits (DataTables server-side)
 * GET /kits/list
 */
function listKits() {
    require_once __DIR__.'/../Class/Kit.php';
    
    // Parámetros de DataTables
    $draw = $_GET['draw'] ?? 1;
    $start = $_GET['start'] ?? 0;
    $length = $_GET['length'] ?? 10;
    $searchValue = $_GET['search']['value'] ?? '';
    $orderColumnIndex = $_GET['order'][0]['column'] ?? 0;
    $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
    
    // Filtros personalizados
    $filtroKit = $_GET['filtroKit'] ?? '';
    $filtroEstado = $_GET['filtroEstado'] ?? '';
    
    // Log para debug
    error_log("listKits - Params: draw=$draw, start=$start, length=$length, search=$searchValue");
    error_log("listKits - Filters: codKit=$filtroKit, estado=$filtroEstado");
    
    $kit = new Kit();
    
    try {
        $dtParams = [
            'start' => (int)$start,
            'length' => (int)$length,
            'search' => $searchValue,
            'orderColumn' => (int)$orderColumnIndex,
            'orderDir' => $orderDir
        ];
        
        $filters = [
            'codKit' => $filtroKit,
            'estado' => $filtroEstado
        ];
        
        $result = $kit->list($dtParams, $filters);
        
        error_log("listKits - Result: total={$result['total']}, filtered={$result['filtered']}, data_count=" . count($result['data']));
        
        // Formato para DataTables
        $response = [
            'draw' => intval($draw),
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $result['data']
        ];
        
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("Error en listKits: " . $e->getMessage());
        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Habilitar/Inhabilitar kit
 * POST /kits/toggle
 * Body: { id, inhabilitar: boolean }
 */
function toggleKit() {
    require_once __DIR__.'/../Class/Kit.php';
    
    // Obtener datos del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['id']) || !isset($data['inhabilitar'])) {
        echo json_encode([
            'ok' => false,
            'message' => 'Datos inválidos'
        ]);
        return;
    }
    
    $id = $data['id'];
    $inhabilitar = (bool)$data['inhabilitar'];
    
    $kit = new Kit();
    
    try {
        $result = $kit->toggle($id, $inhabilitar);
        
        if ($result) {
            echo json_encode([
                'ok' => true,
                'message' => $inhabilitar ? 'Kit inhabilitado correctamente' : 'Kit habilitado correctamente'
            ]);
        } else {
            echo json_encode([
                'ok' => false,
                'message' => 'No se pudo actualizar el estado'
            ]);
        }
    } catch (Exception $e) {
        error_log("Error en toggleKit: " . $e->getMessage());
        echo json_encode([
            'ok' => false,
            'message' => 'Error al actualizar: ' . $e->getMessage()
        ]);
    }
}

?>