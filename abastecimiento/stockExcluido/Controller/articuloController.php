<?php
require_once '../../class/conexion.php';



switch ($_GET['action']) {
  case 'insert':
    insertarArticulo();
    break;

  case 'update':

    updateArticulo();
    break;
    
  default:

    break;
};

function insertarArticulo(){
  try {
    
    $articulos = json_decode($_POST['articulos']);

    $fecha = Date('Y-m-d');
    date_default_timezone_set('Etc/GMT+3');
    $Object = new DateTime();
    $hora = $Object->format("G:i");
    $cid = new Conexion();
    $cid_central = $cid->conectar('central');

  } catch (Exception $e) {
    echo 'Ocurrió un error'+$e->getMessage();
  }
    foreach ($articulos as $art) {
      $sql = "INSERT INTO SJ_EXCLUIDOS (FECHA, HORA_CARGA, COD_ARTICU, DESCRIPCION, CANT, OBSERVACIONES)
    VALUES ('$fecha','$hora','$art[0]','$art[3]',$art[1],'$art[2]')
  ";
      $stmt = sqlsrv_query($cid_central, $sql);

      sqlsrv_execute($stmt);
    }
}

function updateArticulo() {

  $cid = new Conexion();
  $cid_central = $cid->conectar('central');
  $articulo = $_POST['articulo'];
  $cantidad = $_POST['cantidad'];
  $observaciones = $_POST['observaciones'];

  $fecha = Date('Y-m-d');
  date_default_timezone_set('Etc/GMT+3');
  $Object = new DateTime();
  $hora = $Object->format("G:i");

  try {

    $sql = "UPDATE SJ_EXCLUIDOS SET FECHA = '$fecha', HORA_CARGA = '$hora', CANT = $cantidad, OBSERVACIONES = '$observaciones' WHERE COD_ARTICU = '$articulo'";
    $stmt = sqlsrv_query($cid_central, $sql);
    return true;

  } catch (\Throwable $th) {
    //throw $th;
  }


  
}
/* session_start(); */

/* function insertarArticulo($codArticu, $descripcion, $cantidad, $observaciones)
{
  $fecha = Date('Y-m-d');
  date_default_timezone_set('Etc/GMT+3');
  $Object = new DateTime();
  $hora = $Object->format("G:i");

  $cid = new Conexion();
  $cid_central = $cid->conectar();

  $sql = "INSERT INTO SJ_EXCLUIDOS (FECHA, HORA_CARGA, COD_ARTICU, DESCRIPCION, CANT, OBSERVACIONES)
          VALUES ('$fecha','$hora','$codArticu','$descripcion',$cantidad,'$observaciones')

  ";
  $stmt = sqlsrv_query($cid_central, $sql);

  sqlsrv_execute($stmt);
}

if (isset($_GET['save'])) {
  $articulo_nuevo->insertarArticulo($_GET['articulo'], $_GET['descrip'], $_GET['cantidad'], $_GET['observaciones']);
} */
