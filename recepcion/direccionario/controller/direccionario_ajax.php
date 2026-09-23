<?php
require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
require_once __DIR__ . '/../class/Direccionario.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado. Debe iniciar sesión.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $direccionario = new Direccionario();
    $action = $_POST['action'] ?? $_GET['action'] ?? 'buscar';

    $user = Auth::usuario();
    $username = Auth::username();
    $esAdmin = Auth::esAdmin();
    $rol = $_SESSION['rol'] ?? ($user['rol'] ?? '');
    $rolNom = strtoupper(trim($user['rol_nombre'] ?? $rol ?? ''));
    $sectorCod = strtoupper($user['sector_codigo'] ?? '');
    $sectorNom = strtoupper($user['sector_nombre'] ?? '');
    $subsectorNom = strtoupper($user['subsector_nombre'] ?? '');

    // Solo Sistemas, IT y Administrador Total tienen acceso a datos de infraestructura y ver sucursales no principales
    $es_admin_sistemas = $esAdmin 
                      || in_array(strtolower($rol), ['admin', 'sistemas', 'gerencia_adm', 'superadmin', 'controltotal']) 
                      || in_array(strtolower($username ?? ''), ['admin', 'sistemas', 'francopertus', 'franco.pertus'])
                      || in_array($sectorCod, ['SISTEMAS', 'IT']);

    // Rol Recepcionista (Comercial y Ventas -> Analistas -> Recepcionista)
    $es_recepcionista = ($rolNom === 'RECEPCIONISTA' || ($user['rol_id'] ?? 0) == 18)
                     || ($sectorNom === 'COMERCIAL Y VENTAS' && $subsectorNom === 'ANALISTAS' && $rolNom === 'RECEPCIONISTA');

    $puede_ver_todo    = $es_admin_sistemas;
    $puede_ver_tecnico = $es_admin_sistemas;
    $puede_crear       = $es_admin_sistemas;
    $puede_editar      = $es_admin_sistemas || $es_recepcionista;
    $puede_eliminar    = $es_admin_sistemas || $es_recepcionista;

    switch ($action) {
        case 'buscar':
            $busqueda         = $_POST['busqueda'] ?? $_GET['busqueda'] ?? '';
            $canal            = $_POST['canal'] ?? $_GET['canal'] ?? '';
            $provincia        = $_POST['provincia'] ?? $_GET['provincia'] ?? '';
            $tipo_local       = $_POST['tipo_local'] ?? $_GET['tipo_local'] ?? '';
            $grupo_empresario = $_POST['grupo_empresario'] ?? $_GET['grupo_empresario'] ?? '';
            $mostrar_todos    = (($_POST['mostrar_todos'] ?? $_GET['mostrar_todos'] ?? 'false') === 'true') && $puede_ver_todo;

            $sucursales = $direccionario->getSucursales(
                $busqueda,
                $canal,
                $provincia,
                $tipo_local,
                $grupo_empresario,
                $mostrar_todos,
                $puede_ver_tecnico
            );

            echo json_encode([
                'success'    => true,
                'sucursales' => $sucursales,
                'total'      => count($sucursales)
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'getCanales':
            $canales = $direccionario->getCanales();
            echo json_encode(['success' => true, 'canales' => $canales], JSON_UNESCAPED_UNICODE);
            break;

        case 'getProvincias':
            $provincias = $direccionario->getProvincias();
            echo json_encode(['success' => true, 'provincias' => $provincias], JSON_UNESCAPED_UNICODE);
            break;

        case 'getTiposLocal':
            $tipos = $direccionario->getTiposLocal();
            echo json_encode(['success' => true, 'tipos' => $tipos], JSON_UNESCAPED_UNICODE);
            break;

        case 'getGruposEmpresarios':
            $grupos = $direccionario->getGruposEmpresarios();
            echo json_encode(['success' => true, 'grupos' => $grupos], JSON_UNESCAPED_UNICODE);
            break;

        case 'eliminar':
            if (!$puede_eliminar) {
                throw new Exception('No posee permisos para eliminar sucursales.');
            }
            $nro = (int)($_POST['nro_sucursal'] ?? 0);
            if ($nro <= 0) {
                throw new Exception('Número de sucursal inválido.');
            }
            $direccionario->eliminarSucursal($nro);
            echo json_encode(['success' => true, 'message' => "Sucursal #$nro eliminada con éxito."], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception('Acción no válida: ' . $action);
    }

} catch (Exception $e) {
    error_log("Error en direccionario_ajax: " . $e->getMessage());
    echo json_encode([
        'success'    => false,
        'message'    => $e->getMessage(),
        'sucursales' => [],
        'total'      => 0
    ], JSON_UNESCAPED_UNICODE);
}
