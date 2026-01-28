<?php

class Articulo
{

    private function retornarArray($sqlEnviado)
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';

        $cid = new Conexion();
        $cid_central = $cid->conectar('central');
        $sql = $sqlEnviado;

        $stmt = sqlsrv_query($cid_central, $sql);

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt)) {
            $rows[] = $v;
        }


        return $rows;
    }


    public function traerArticulos()
    {
        // Traer artículos excluidos y la descripción real desde STA11
        $sql = "SELECT E.*, COALESCE(S.DESCRIPCIO, E.DESCRIPCION) AS DESCRIPCION
                FROM SJ_EXCLUIDOS E
                LEFT JOIN STA11 S ON E.COD_ARTICU = S.COD_ARTICU
                ORDER BY E.COD_ARTICU ASC";

        $rows = $this->retornarArray($sql);

        $myJSON = json_encode($rows);

        return $myJSON;
    }

    public function traerMaestro($codArticu){

        $sql = "SELECT A.COD_ARTICU, DESCRIPCIO, B.PRECIO, TEMPORADA FROM STA11 A
                LEFT JOIN (SELECT COD_ARTICU, PRECIO FROM GVA17 WHERE NRO_DE_LIS = '20') B ON A.COD_ARTICU = B.COD_ARTICU
                LEFT JOIN MAESTRO_DESTINOS C ON A.COD_ARTICU = C.COD_ARTICU
                INNER JOIN SOF_RUBROS_TANGO D ON A.COD_ARTICU = D.COD_ARTICU
                WHERE A.COD_ARTICU LIKE '[XO]%'
                AND A.COD_ARTICU = '$codArticu'
        ";

        $rows = $this->retornarArray($sql);

        return $rows;

    }

}