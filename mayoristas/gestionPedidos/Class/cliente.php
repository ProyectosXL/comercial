

<?php

class Cliente
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


    public function traerClientes()
    {

        $sql = "SELECT DISTINCT(COD_CLIENT), RAZON_SOCI FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS ORDER BY RAZON_SOCI";
        
        $rows = $this->retornarArray($sql);

        return $rows;

    }

}