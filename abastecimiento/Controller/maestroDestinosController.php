<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$accion = $_GET['accion'];

switch ($accion) {
    case 'filtrar':
        filtrar();
        break;

    case 'filtrarDestinos':
        filtrarDestinos();
        break;

    case 'liquidar':
        liquidar();
        break;

    case 'cambiarDestino':
        cambiarDestino();
        break;

    case 'cambiarLiquidacion':
        cambiarLiquidacion();
        break;


    case 'comprobarArticulos':
        comprobarArticulos();
        break;

    case 'actualizarMaestro':
        actualizarMaestro();
        break;

    case 'traerDescripcion':
        traerDescripcion();
        break;

    case 'traerTemporadas':
        traerTemporadas();
        break;

    case 'excluirTemporada':
        excluirTemporada();
        break;

    case 'excluirArticulo':
        excluirArticulo();
        break;

    case 'excluirMasivo':
        excluirMasivo();
        break;


    case 'guardarTemporada':
        guardarTemporada();
        break;


    case 'cambiarEntorno':
        cambiarEntorno();
        break;

    default:
        # code...
        break;
}


function filtrar()
{

    require_once "../Class/Articulo.php";

    $maestroArticulos = new Articulo();

    $rubro = $_POST['rubro'];
    $temporada = $_POST['temporada'];

    $todosLosArticulos = $maestroArticulos->traerArticulos($rubro, $temporada);

    echo json_encode($todosLosArticulos);


}


function filtrarDestinos()
{

    require_once "../Class/Destino.php";

    $maestroArticulos = new Destino();

    $rubro = $_POST['rubro'];
    $temporada = $_POST['temporada'];
    $novedades = $_POST['novedades'];

    $todosLosArticulos = $maestroArticulos->traerArticulos($rubro, $temporada, $novedades);

    echo json_encode($todosLosArticulos);


}

function liquidar()
{

    require_once "../Class/Destino.php";

    $maestroArticulos = new Destino();


    $liquidacionSiString = $_POST['liquidacionSiString'];
    $liquidacionNoString = $_POST['liquidacionNoString'];


    $maestroArticulos->liquidar($liquidacionSiString, 'SI');

    $maestroArticulos->liquidar($liquidacionNoString, 'NO');

    echo true;
}

function cambiarDestino()
{

    require_once "../Class/Destino.php";

    $maestroArticulos = new Destino();


    $codArticu = $_POST['codArticu'];
    $destino = $_POST['destino'];

    $maestroArticulos->cambiarDestino($codArticu, $destino);

    echo true;
}

function cambiarLiquidacion()
{

    require_once "../Class/Destino.php";

    $maestroArticulos = new Destino();


    $codArticu = $_POST['codArticu'];
    $liquidacion = $_POST['liquidacion'];

    $maestroArticulos->cambiarLiquidacion($codArticu, $liquidacion);

    echo true;

}

function comprobarArticulos()
{
    require_once "../Class/Destino.php";
    $maestroArticulos = new Destino();
    $data = json_decode($_POST['data'], true);
    if (!$data) {
        echo json_encode([]);
        return;
    }

    $codes = array_unique(array_map('trim', array_column($data, 0)));
    $existenciaMap = $maestroArticulos->comprobarArticulosBulk($codes);

    $arrayResponse = [];
    foreach ($data as $articulo) {
        $cod = trim($articulo[0]);
        $existe = isset($existenciaMap[$cod]) ? 1 : 0;
        $arrayResponse[] = [$cod, $existe];
    }
    echo json_encode($arrayResponse);
}

function actualizarMaestro()
{
    require_once "../Class/Destino.php";
    $maestroDestinos = new Destino();
    $data = json_decode($_POST['data'], true);
    if (!$data) {
        echo false;
        return;
    }

    $res = $maestroDestinos->actualizarMaestroBulk($data);
    echo $res;
}

function traerDescripcion()
{
    require_once "../Class/Destino.php";
    $maestroDestinos = new Destino();
    $data = json_decode($_POST['data'], true);
    if (!$data) {
        echo json_encode([]);
        return;
    }

    $codes = array_unique(array_map('trim', array_column($data, 0)));
    $descMap = $maestroDestinos->traerDescripcionesBulk($codes);

    $response = [];
    foreach ($data as $key => $value) {
        if ($key == 0)
            continue;
        $cod = trim($value[0]);
        $descripcion = isset($descMap[$cod]) ? $descMap[$cod] : '';
        $response[] = [$cod, $descripcion];
    }
    echo json_encode($response);
}

function traerTemporadas()
{

    require_once "../Class/Destino.php";

    $maestroDestinos = new Destino();

    $temporadas = $maestroDestinos->traerTemporadas();

    echo json_encode($temporadas);
}

function excluirTemporada()
{

    require_once "../Class/Destino.php";

    $maestroDestinos = new Destino();

    $nombreTemp = $_POST['nombreTemp'];
    $val = $_POST['excluir'];

    $maestroDestinos->excluirTemporada($nombreTemp, $val);

    echo true;
}

function excluirArticulo()
{

    require_once "../Class/Destino.php";

    $maestroDestinos = new Destino();

    $valor = $_POST['valor'];
    $articulo = $_POST['codArticu'];


    $maestroDestinos->excluirArticulo($articulo, $valor);

    echo true;
}

function excluirMasivo()
{

    require_once "../Class/Destino.php";

    $maestroDestinos = new Destino();

    $stringParaSql = $_POST['stringParaSql'];
    $valor = $_POST['valor'];


    $maestroDestinos->excluirMasivo($stringParaSql, $valor);

    echo true;
}

function guardarTemporada()
{

    require_once "../Class/Destino.php";

    $maestroDestinos = new Destino();

    $stringParaSql = $_POST['stringParaSql'];

    $maestroDestinos->guardarTemporada($stringParaSql);

    echo true;

}
function cambiarEntorno()
{

    session_start();

    if ($_POST['entorno'] == 0) {

        $_SESSION['entorno'] = 'central';

    } else {

        $_SESSION['entorno'] = 'uy';

    }
    echo true;
}
?>