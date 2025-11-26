<?php

class Venta
{

    public function traerVentas($rubro, $destino, $temporada, $desde, $hasta, $tipoSucursal = ''){
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
            
            if($cid_central === false) {
                throw new Exception("Error al conectar a POWER_BI_CONTROL: " . print_r(sqlsrv_errors(), true));
            }
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }
 
         $sql = "SELECT DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, SUM(CANT_VEND) CANT_VEND, RUBRO, TEMPORADA, DESTINO, PRECIO FROM
         (
         SELECT FECHA_VENTA, DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, CANT_VEND, RUBRO, TEMPORADA, DESTINO, PRECIO
         FROM RO_VENTAS_COMERCIAL WHERE FECHA_VENTA BETWEEN '$desde' AND '$hasta' AND DESTINO LIKE '$destino' 
         AND TEMPORADA LIKE '%$temporada'";

        if($rubro != '' && $rubro != '%'){
            $sql .= " AND RUBRO IN ($rubro) ";
        }
        
        // Filtro por tipo de sucursal basado en la columna CANAL
        if($tipoSucursal == 'propios'){
            $sql .= " AND CANAL = 'LOCALES PROPIOS' ";
        } elseif($tipoSucursal == 'franquicias'){
            $sql .= " AND CANAL = 'FRANQUICIAS' ";
        }
        
        $sql .= "
                ) A
                GROUP BY DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, RUBRO, TEMPORADA, DESTINO, PRECIO
        ";

        $stmt = sqlsrv_query( $cid_central , $sql );
        
        if( $stmt === false ) {
            $errors = sqlsrv_errors();
            error_log("SQL Error: " . print_r($errors, true));
            error_log("SQL Query: " . $sql);
            throw new Exception("Error en la consulta SQL: " . print_r($errors, true));
        }

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            // Renombrar CANAL a TIPO_SUCURSAL para compatibilidad con el frontend
            $v['TIPO_SUCURSAL'] = $v['CANAL'];
            $rows[] = $v;
        }

        return $rows;
    }

}    

