<?php

class Venta
{

    public function traerVentas($rubro, $destino, $temporada, $desde, $hasta){
        try {
            
            session_start();

            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
                $db = 'POWER_BI_CONTROL';
            }else{
                $db ='POWER_BI_CONTROL_URUGUAY';
            }

            $servidor_lakerbis = '192.168.0.143,1433';
            $conexion_locales = array( "Database"=>$db, "UID"=>"sa", "PWD"=>"Axoft1988", "CharacterSet" => "UTF-8");
            $cid_central = sqlsrv_connect($servidor_lakerbis, $conexion_locales);
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }
 
         $sql = "SELECT DESC_SUCURSAL, ARTICULO, DESCRIPCION, SUM(CANT_VEND) CANT_VEND, RUBRO, TEMPORADA, DESTINO, PRECIO FROM
         (
         SELECT FECHA_VENTA, DESC_SUCURSAL, ARTICULO, DESCRIPCION, CANT_VEND, RUBRO, TEMPORADA, DESTINO, PRECIO 
         FROM RO_VENTAS_COMERCIAL WHERE FECHA_VENTA BETWEEN '$desde' AND '$hasta'AND DESTINO LIKE '$destino' 
         AND TEMPORADA LIKE '%$temporada'";

        if($rubro != '' && $rubro != '%'){
            $sql .= " AND RUBRO IN ($rubro) ";
        }
        
        $sql .= "
                ) A
                GROUP BY DESC_SUCURSAL, ARTICULO, DESCRIPCION, RUBRO, TEMPORADA, DESTINO, PRECIO
        ";

        $stmt = sqlsrv_query( $cid_central , $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;
    }

}    

