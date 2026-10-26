<?php

// Controller/CategoriaController.php

$accion = $_GET['accion'];

switch ($accion) {
    case 'insertarNuevo':
        insertarNuevo();
        break;

    case 'actualizarDescCategoria':
        actualizarDescCategoria();
        break;
        
    case 'insertarNuevoRubro':
        insertarNuevoRubro();
        break;
        
    case 'obtenerSiglasDisponibles':
        obtenerSiglasDisponibles();
        break;
    
    case 'actualizarEstado':
    actualizarEstado();
    break;

    case 'validarImportacion':
        validarImportacion();
        break;

    case 'aplicarImportacion':
        aplicarImportacion();
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

function insertarNuevo() {
    require_once __DIR__.'/../Class/Categoria.php';

    $categoria = new Categoria();

    $codCategoria = $_POST['codCategoria'];
    $descCategoria = $_POST['descCategoria'];
    $descCategoriaPadre = $_POST['descCategoriaPadre'] ?? ''; // Nuevo campo
    $siglaRubro = $_POST['siglaRubro'];

    $result = $categoria->insertarNuevaCategoria($codCategoria, $descCategoria, $descCategoriaPadre, $siglaRubro);
    
    echo $result ? 1 : 0;
}

function actualizarDescCategoria() {
    require_once __DIR__.'/../Class/Categoria.php';
    $categoria = new Categoria();

    $nuevoValor = $_POST['nuevoValor'];
    $nuevoValorPadre = $_POST['nuevoValorPadre'] ?? ''; // Nuevo campo
    $rubro = $_POST['rubro'];
    $Categoria = $_POST['Categoria'];

    $result = $categoria->actualizarDescripcionCategoria($nuevoValor, $nuevoValorPadre, $rubro, $Categoria);

    echo $result ? 1 : 0;
}

function insertarNuevoRubro() {
    require_once __DIR__.'/../Class/Categoria.php';
    
    $categoria = new Categoria();
    
    $sigla = $_POST['sigla'];
    $descripcion = $_POST['descripcion'];
    
    $result = $categoria->insertarNuevoRubro($sigla, $descripcion);
    
    echo $result ? 1 : 0;
}

function obtenerSiglasDisponibles() {
    require_once __DIR__.'/../Class/Categoria.php';
    
    $categoria = new Categoria();
    
    $siglas = $categoria->obtenerSiglasDisponibles();
    
    echo json_encode($siglas);
}
// NUEVA FUNCIÓN: Actualizar estado de categoría
function actualizarEstado() {
    require_once __DIR__.'/../Class/Categoria.php';
    
    $categoria = new Categoria();
    
    $siglaRubro = $_POST['siglaRubro'];
    $codCategoria = $_POST['codCategoria'];
    $nuevoEstado = $_POST['nuevoEstado']; // 1 para activo, 0 para inactivo
    
    $result = $categoria->actualizarEstadoCategoria($siglaRubro, $codCategoria, $nuevoEstado);
    
    echo $result ? 1 : 0;
}

// Reasignación masiva de categorías por Excel: el body es JSON {filas: [{fila, COD_ARTICU, CATEGORIA, CATEGORIA_PADRE}]}
function leerFilasImportacion() {
    $body = json_decode(file_get_contents('php://input'), true);
    return (is_array($body) && isset($body['filas']) && is_array($body['filas'])) ? $body['filas'] : [];
}

function validarImportacion() {
    require_once __DIR__.'/../Class/Categoria.php';
    set_time_limit(300);
    header('Content-Type: application/json; charset=utf-8');

    $categoria = new Categoria();

    echo json_encode($categoria->validarImportacionCategorias(leerFilasImportacion()));
}

function aplicarImportacion() {
    require_once __DIR__.'/../Class/Categoria.php';
    set_time_limit(300);
    header('Content-Type: application/json; charset=utf-8');

    $categoria = new Categoria(); // abre la sesión (Conexion::conectar)
    // Mismas claves de sesión que usa Auth del hub (sistemas/hub/Class/Auth.php)
    $usuario = $_SESSION['nodo_usuario_activo']
        ?? $_SESSION['fp_auth_user']['username']
        ?? $_SESSION['username']
        ?? $_SESSION['usuario']
        ?? 'WEB_USER';

    echo json_encode($categoria->aplicarImportacionCategorias(leerFilasImportacion(), $usuario));
}
?>