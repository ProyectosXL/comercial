
<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';
$cid = new Conexion();
$cid_central = $cid->conectar('central');

if($cid_central === false){
    die("Error al conectar con la base de datos: ".print_r(sqlsrv_errors(), true));
}

$fechaModif= Date('Y-m-d');

date_default_timezone_set('Etc/GMT+3');
$Object = new DateTime();  
$horaModif = $Object->format("G:i");
// var_dump($_GET['datos']);
if (isset($_GET['datos'])) {
    //si el nro de pedido existe se procede a actualizar los campos. En caso de no existir el pedido en la tabla se realiza un insert.
    $datos = json_decode(stripslashes($_GET['datos']));
    
    foreach ($datos as $dato){
        $codigo = $dato->codigo;
        $tipoComp = $dato->tipoComp;
        $embalaje = $dato->embalaje;
        $despacho = $dato->despacho;
        // Convertir arreglo y prioridad a valores apropiados para SQL
        $arreglo = (empty($dato->arreglo) || $dato->arreglo === 'null' || $dato->arreglo === '') ? 'NULL' : $dato->arreglo;
        $prioridad = (empty($dato->prioridad) || $dato->prioridad === 'null' || $dato->prioridad === '') ? 'NULL' : $dato->prioridad;
        $fecha_despacho = $dato->fechaDespacho;
    if (existe($dato->codigo)) {
        {
            try {
                $sql = "UPDATE RO_PEDIDOS_MAYORISTA_ASIGNADOS SET TIPO_COMP = '$tipoComp', EMBALAJE = '$embalaje', DESPACHO = '$despacho', 
                        ARREGLO = $arreglo, PRIORIDAD = $prioridad, FECHA_DESPACHO = '$fecha_despacho', FECHA_MODIF = '$fechaModif', HORA_MODIF = '$horaModif' 
                        WHERE NRO_PEDIDO = '$codigo'
                        
                        UPDATE SJ_PICKING_ENCABEZADO SET TIPO_COMP = '$tipoComp', EMBALAJE = '$embalaje', DESPACHO = '$despacho', 
                        ARREGLO = $arreglo, PRIORIDAD = $prioridad, 
                        PROX_DESPACHO = (CASE WHEN (PROX_DESPACHO != '$fecha_despacho' OR PROX_DESPACHO IS NULL) THEN '$fecha_despacho' ELSE PROX_DESPACHO END),
                        RE_DESPACHO = (CASE WHEN (RE_DESPACHO != '$fecha_despacho' OR RE_DESPACHO IS NULL) THEN '$fecha_despacho' ELSE RE_DESPACHO END)
                        WHERE NRO_PEDIDO = '$codigo' AND ESTADO LIKE 'ABIERTO'

                        UPDATE SJ_PICKING_ENCABEZADO SET RUTA = UPPER(DATENAME(DW, RE_DESPACHO))
                        WHERE NRO_PEDIDO = '$codigo' AND (RUTA != UPPER(DATENAME(DW, RE_DESPACHO)) OR RUTA IS NULL) AND ESTADO LIKE 'ABIERTO'
                        ";
                
                $result = sqlsrv_query($cid_central, $sql);
                if($result === false){
                    die("Error en sqlsrv_query (UPDATE): ".print_r(sqlsrv_errors(), true));
                }
                echo $codigo.",";  
            } catch (Exception $e) {
                echo 'Se produjo un Error:' . $e->getMessage();
            }
        }
    } else {

                $codClient=$dato->cod_client;
                $cliente=str_replace("&","",( str_replace("'"," ",$dato->razon_soci)));
        /*  $cliente=$dato->razon_soci; */
                $localidad=$dato->localidad;
                $codVended=$dato->cod_vended;
                $vendedor=$dato->vendedor;
                $fecha=$dato->fecha;
                $hora=$dato->hora;
                $estado=$dato->estado;
                $talonario=$dato->talonario;
                $unidPedido=$dato->unidPedido;
                $unidPendiente=$dato->unidPendiente;
                $importePendiente=str_replace(".","",$dato->importePendiente);
                echo $codigo.",";
        try {
            $sql = "INSERT INTO DBO.RO_PEDIDOS_MAYORISTA_ASIGNADOS (FECHA, ESTADO, HORA_INGRESO, COD_CLIENT, RAZON_SOCI, LOCALIDAD, TALON_PED, NRO_PEDIDO, CANT_PEDIDO, CANT_PENDIENTE, IMP_PENDIENTE, 
                    COD_VENDED, VENDEDOR, TIPO_COMP, EMBALAJE, DESPACHO, ARREGLO, PRIORIDAD, FECHA_MODIF, HORA_MODIF, FECHA_DESPACHO)
                    VALUES ('$fecha', $estado,'$hora','$codClient','$cliente','$localidad', $talonario,'$codigo', $unidPedido, $unidPendiente, $importePendiente, '$codVended', '$vendedor','$tipoComp',
                    '$embalaje','$despacho', $arreglo, $prioridad, '$fechaModif', '$horaModif','$fecha_despacho')

                    UPDATE SJ_PICKING_ENCABEZADO SET TIPO_COMP = '$tipoComp', EMBALAJE = '$embalaje', DESPACHO = '$despacho', 
                    ARREGLO = $arreglo, PRIORIDAD = $prioridad, 
                    PROX_DESPACHO = (CASE WHEN (PROX_DESPACHO != '$fecha_despacho' OR PROX_DESPACHO IS NULL) THEN '$fecha_despacho' ELSE PROX_DESPACHO END),
                    RE_DESPACHO = (CASE WHEN (RE_DESPACHO != '$fecha_despacho' OR RE_DESPACHO IS NULL) THEN '$fecha_despacho' ELSE RE_DESPACHO END)
                    WHERE NRO_PEDIDO = '$codigo' AND ESTADO LIKE 'ABIERTO'
                    
                    UPDATE SJ_PICKING_ENCABEZADO SET RUTA = UPPER(DATENAME(DW, RE_DESPACHO))
                    WHERE NRO_PEDIDO = '$codigo' AND (RUTA != UPPER(DATENAME(DW, RE_DESPACHO)) OR RUTA IS NULL) AND ESTADO LIKE 'ABIERTO'
                    ";
            $stmt=sqlsrv_query($cid_central, $sql);
            if( $stmt === false ) {
                die( print_r( sqlsrv_errors(), true));
           }
           
        } catch (Exception $e) {
            echo 'Se produjo un Error:' . $e->getMessage();
        }
    }
   
}
}

function existe($pedido)
{
    global $cid_central;
    $sql = "select * from RO_PEDIDOS_MAYORISTA_ASIGNADOS where nro_pedido='$pedido'";
    $result = sqlsrv_query($cid_central, $sql);
    if($result === false){
        die("Error en sqlsrv_query (existe): ".print_r(sqlsrv_errors(), true));
    }
    return sqlsrv_has_rows($result);

}


?>