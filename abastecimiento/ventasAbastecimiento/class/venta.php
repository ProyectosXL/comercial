
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
 
         $sql = "SELECT DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, SUM(CANT_VEND) CANT_VEND, RUBRO, CATEGORIA, CATEGORIA_PADRE, TEMPORADA, DESTINO, PRECIO FROM
         (
         SELECT FECHA_VENTA, DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, CANT_VEND, RUBRO, CATEGORIA, CATEGORIA_PADRE, TEMPORADA, DESTINO, PRECIO
         FROM dbo.RO_VENTAS_COMERCIAL WHERE FECHA_VENTA BETWEEN '$desde' AND '$hasta' AND DESTINO LIKE '$destino' 
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
                GROUP BY DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, RUBRO, CATEGORIA, CATEGORIA_PADRE, TEMPORADA, DESTINO, PRECIO
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
