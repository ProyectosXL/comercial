
<?php

class PPP
{

    private function retornarArray($sqlEnviado)
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';
        /* echo $sqlEnviado; */
        $cid = new Conexion();
        $cid_central = $cid->conectar('central');
        
        if($cid_central === false){
            die("Error al conectar con la base de datos: ".print_r(sqlsrv_errors(), true));
        }
        
        $sql = $sqlEnviado;

        $stmt = sqlsrv_query($cid_central, $sql);
        
        if($stmt === false){
            die("Error en sqlsrv_query: ".print_r(sqlsrv_errors(), true));
        }

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }


        return $rows;
    }


    public function traerCuenta($cliente)
    {
        $sql = "SELECT * FROM RO_PPP_DETALLADO_MAYORISTAS WHERE COD_CLIENTE = '$cliente'";
        $sql=str_replace('"','',$sql);
        
        require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';
        
        $cid = new Conexion();
        $cid_central = $cid->conectar('central');
        
        if($cid_central === false){
            die("Error al conectar con la base de datos: ".print_r(sqlsrv_errors(), true));
        }

        $stmt = sqlsrv_query($cid_central, $sql);
        
        if($stmt === false){
            die("Error en sqlsrv_query: ".print_r(sqlsrv_errors(), true));
        }

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }

        // Asegurar que retorna JSON válido con header correcto
        header('Content-Type: application/json');
        echo json_encode($rows, JSON_NUMERIC_CHECK);

    }

}

$p = new PPP();
if(isset($_GET['cliente']))
{
    $p->traerCuenta($_GET['cliente']);
}