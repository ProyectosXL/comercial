<?php

class PPP
{
    public function traerCuenta($cliente)
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';
        
        $cid = new Conexion();
        $cid_central = $cid->conectar('central');
        
        // Limpiamos headers para asegurar JSON
        header('Content-Type: application/json; charset=utf-8');

        if($cid_central === false){
             echo json_encode(["error" => "Error de conexión DB"]);
             exit;
        }
        
        // Evitar inyección SQL básica y limpiar comillas
        $clienteLimpio = str_replace("'", "", $cliente);
        $clienteLimpio = str_replace('"', '', $clienteLimpio);

        $sql = "SELECT * FROM RO_PPP_DETALLADO_MAYORISTAS WHERE COD_CLIENTE = '$clienteLimpio'";

        $stmt = sqlsrv_query($cid_central, $sql);
        
        if($stmt === false){
             echo json_encode(["error" => "Error SQL", "details" => sqlsrv_errors()]);
             exit;
        }

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // UTF-8 Encode si es necesario
            foreach ($v as $key => $value) {
                if (is_string($value)) {
                    $v[$key] = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                }
            }
            $rows[] = $v;
        }

        // JSON_NUMERIC_CHECK convierte strings numéricos "123" a números 123
        // Esto ayuda mucho al Javascript
        echo json_encode($rows, JSON_NUMERIC_CHECK);
    }
}

// Manejador de la petición GET
if(isset($_GET['cliente']))
{
    // Instanciamos y llamamos
    $p = new PPP();
    $p->traerCuenta($_GET['cliente']);
}
?>