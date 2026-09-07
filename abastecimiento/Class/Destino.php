<?php

require_once __DIR__ . '/../../class/conexion.php';

class Destino
{

    private $cid;
    private $cid_central;


    function __construct()
    {


        $this->cid = new Conexion();

        // Verificar si no hay una sesión activa antes de iniciarla
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $db = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
        $this->cid_central = $this->cid->conectar($db);

    }


    private function retornarArray($sqlEnviado)
    {

        $sql = $sqlEnviado;

        if (!$this->cid_central) {
            return [];
        }

        $stmt = sqlsrv_query($this->cid_central, $sql);

        if (!$stmt) {
            return [];
        }

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }

        return $rows;

    }

    public function traerArticulos($rubro, $temporada, $novedades)
    {

        $sql = " 
            SELECT A.*, C.CATEGORIA
            FROM MAESTRO_DESTINOS A
            LEFT JOIN SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA C ON A.COD_ARTICU = C.COD_ARTICU
            WHERE A.EXCLUIR IS NULL
            AND A.TEMPORADA LIKE '$temporada' AND A.FAMILIA LIKE '$rubro'
        ";

        if ($novedades == 1) {

            $sql .= " AND A.FECHA_MOD = (
                SELECT MAX(FECHA_MOD) AS FECHA_MOD
                FROM MAESTRO_DESTINOS)
            ";

        }


        $rows = $this->retornarArray($sql);

        return $rows;

    }

    public function liquidar($codArticuString, $liquidacion)
    {

        $sql = " UPDATE MAESTRO_DESTINOS SET LIQUIDACION = '$liquidacion', FECHA_MOD = GETDATE() WHERE COD_ARTICU IN ($codArticuString)";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        return $stmt;

    }

    public function cambiarDestino($codArticuString, $destino)
    {

        $sql = " UPDATE MAESTRO_DESTINOS SET DESTINO_ANT = DESTINO, DESTINO = '$destino', FECHA_MOD = GETDATE() WHERE COD_ARTICU = '$codArticuString'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        return true;

    }

    public function cambiarLiquidacion($codArticuString, $liquidacion)
    {

        if ($liquidacion == '') {

            $liquidacion = 'LIQUIDACION = null';

        } else {

            $liquidacion = "LIQUIDACION ='" . $liquidacion . "'";

        }

        $sql = " UPDATE MAESTRO_DESTINOS SET $liquidacion, FECHA_MOD = GETDATE() WHERE COD_ARTICU = '$codArticuString'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        return true;
        ;

    }

    public function actualizarMaestro($articulo, $descripcion, $destino, $liquidacion)
    {

        $sql = " UPDATE MAESTRO_DESTINOS  SET DESCRIPCION = '$descripcion', DESTINO = '$destino', LIQUIDACION = '$liquidacion' WHERE COD_ARTICU = '$articulo'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        return true;

    }
    public function excluirArticulo($articulo, $valor)
    {
        if ($valor == 0) {
            $valor = "null";
        }

        $sql = " UPDATE MAESTRO_DESTINOS  SET EXCLUIR = $valor, FECHA_EXCLU = GETDATE() WHERE COD_ARTICU = '$articulo'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        return true;

    }

    public function comprobarArticulo($articulo)
    {

        $sql = 'SELECT CASE WHEN EXISTS (SELECT 1 FROM STA11 WHERE COD_ARTICU = \'' . $articulo . '\') THEN 1 ELSE 0 END AS resultado';




        $stmt = sqlsrv_query($this->cid_central, $sql);

        // Verificar el resultado de la consulta
        if ($stmt !== false) {
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            return $result['resultado'];

        } else {
            // Error en la consulta
            return false;
        }
    }

    public function traerDescripcion($codArticulo)
    {

        $sql = "SELECT DESCRIPCION FROM MAESTRO_DESTINOS WHERE COD_ARTICU = '$codArticulo'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        $result = '';

        while ($v = sqlsrv_fetch_array($stmt)) {
            $result = $v['DESCRIPCION'];
        }

        return $result;
    }

    public function traerTemporadas()
    {

        $sql = 'SELECT NOMBRE_TEMP, COD_TEMP, EXCLUIR FROM MAESTRO_TEMPORADAS';

        $stmt = sqlsrv_query($this->cid_central, $sql);

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt)) {
            $rows[] = $v;
        }

        return $rows;

    }

    public function excluirTemporada($nombreTemp, $val)
    {


        $sql = "UPDATE MAESTRO_TEMPORADAS SET EXCLUIR = $val WHERE NOMBRE_TEMP = '$nombreTemp'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        return true;

    }

    public function excluirMasivo($stringParaSql, $val)
    {


        $sql = "UPDATE MAESTRO_DESTINOS SET EXCLUIR = $val , FECHA_EXCLU = GETDATE() WHERE COD_ARTICU IN $stringParaSql ";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        return true;

    }

    public function guardarTemporada($string)
    {

        $sql = "INSERT INTO MAESTRO_TEMPORADAS (NOMBRE_TEMP, COD_TEMP, EXCLUIR) VALUES $string";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        return true;

    }

    public function traerDestinos()
    {

        $sql = "SELECT DISTINCT(DESTINO) DESTINO FROM MAESTRO_DESTINOS

        ";

        $rows = $this->retornarArray($sql);

        return $rows;

    }
    public function traerDescripcionesBulk($articulos)
    {
        if (empty($articulos))
            return [];
        $chunks = array_chunk($articulos, 1000);
        $rows = [];
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_map(function ($a) {
                return "'$a'";
            }, $chunk));
            $sql = "SELECT COD_ARTICU, DESCRIPCION FROM MAESTRO_DESTINOS WHERE COD_ARTICU IN ($placeholders)";
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if ($stmt) {
                while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $rows[trim($v['COD_ARTICU'])] = $v['DESCRIPCION'];
                }
            }
        }
        return $rows;
    }

    public function comprobarArticulosBulk($articulos)
    {
        if (empty($articulos))
            return [];
        $chunks = array_chunk($articulos, 1000);
        $validos = [];
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_map(function ($a) {
                return "'$a'";
            }, $chunk));
            $sql = "SELECT DISTINCT COD_ARTICU FROM STA11 WHERE COD_ARTICU IN ($placeholders)";
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if ($stmt) {
                while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $validos[trim($v['COD_ARTICU'])] = 1;
                }
            }
        }
        return $validos;
    }

    public function actualizarMaestroBulk($articulos)
    {
        if (empty($articulos))
            return true;
        // Iniciar transacción para velocidad y seguridad
        sqlsrv_begin_transaction($this->cid_central);
        $sql = "UPDATE MAESTRO_DESTINOS SET DESCRIPCION = ?, DESTINO_ANT = DESTINO, DESTINO = ?, LIQUIDACION = ?, FECHA_MOD = GETDATE() WHERE COD_ARTICU = ?";

        foreach ($articulos as $art) {
            $params = [$art[1], $art[2], $art[3], $art[0]];
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            if (!$stmt || !sqlsrv_execute($stmt)) {
                sqlsrv_rollback($this->cid_central);
                return false;
            }
        }
        sqlsrv_commit($this->cid_central);
        return true;
    }
}
