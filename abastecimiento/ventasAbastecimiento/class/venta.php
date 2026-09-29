
<?php

class Venta
{

    public function traerVentas($rubro, $destino, $temporada, $desde, $hasta, $tipoSucursal = ''){
        try {
            
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            // Incluir la clase Conexion
            require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
            
            // Crear instancia de Conexion
            $conexion = new Conexion();
            
            // Conectar a XL-APPS (POWER_BI_CONTROL o POWER_BI_CONTROL_URUGUAY)
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
                $cid_central = $conexion->conectar('power');
            }else{
                $cid_central = $conexion->conectar('power_uy');
            }
            
            if($cid_central === false) {
                throw new Exception("Error al conectar a la base de datos: " . print_r(sqlsrv_errors(), true));
            }
             
         } catch (Exception $e){
                 echo $e->getMessage();
         }
 
         $maestroTable = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central') ? '[XL-LAKERBIS].[LOCALES_LAKERS].[dbo].[MAESTRO_DESTINOS]' : '[XL-LAKERBIS].[SUCURSALES_URUGUAY].[dbo].[MAESTRO_DESTINOS]';

         $sql = "SELECT DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, SUM(CANT_VEND) CANT_VEND, RUBRO, CATEGORIA, CATEGORIA_PADRE, TEMPORADA, DESTINO, LIQUIDACION, PRECIO FROM
         (
         SELECT V.FECHA_VENTA, V.DESC_SUCURSAL, V.CANAL, V.ARTICULO, V.DESCRIPCION, V.CANT_VEND, V.RUBRO, V.CATEGORIA, V.CATEGORIA_PADRE, V.TEMPORADA, V.DESTINO, M.LIQUIDACION, V.PRECIO
         FROM dbo.RO_VENTAS_COMERCIAL V
         LEFT JOIN $maestroTable M ON V.ARTICULO COLLATE Latin1_General_BIN = M.COD_ARTICU COLLATE Latin1_General_BIN
         WHERE V.FECHA_VENTA BETWEEN '$desde' AND '$hasta' 
         AND V.TEMPORADA LIKE '%$temporada'";

        if($destino != '' && $destino != '%'){
            $sql .= " AND V.DESTINO IN ($destino) ";
        }

        if($rubro != '' && $rubro != '%'){
            $sql .= " AND V.RUBRO IN ($rubro) ";
        }
        
        // Filtro por tipo de sucursal basado en la columna CANAL
        if($tipoSucursal != '' && $tipoSucursal != '%'){
            $sql .= " AND V.CANAL IN ($tipoSucursal) ";
        }
        
        $sql .= "
                ) A
                GROUP BY DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, RUBRO, CATEGORIA, CATEGORIA_PADRE, TEMPORADA, DESTINO, LIQUIDACION, PRECIO
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
