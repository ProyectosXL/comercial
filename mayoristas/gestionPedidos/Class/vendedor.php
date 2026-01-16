
<?php

class Vendedor
{

    private function retornarArray($sqlEnviado)
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';

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


    public function traerVendedores()
    {

        $sql = "SELECT DISTINCT(COD_VENDED), VENDEDOR FROM RO_PEDIDOS_PENDIENTE_MAYORISTAS";
        
        $rows = $this->retornarArray($sql);

        return $rows;

    }

}