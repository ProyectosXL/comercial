<?php
class Direccionario {
    private $cid;
    private $cid_central;

    function __construct() {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
    }

    public function getConnection() {
        return $this->cid_central;
    }

    public function getSucursales($busqueda = '', $canal = '', $provincia = '') {
        try {
            $params = [];
            $where  = ['NRO_SUC_MADRE IS NULL'];

            if (!empty($busqueda)) {
                $where[]  = "(DESC_SUCURSAL LIKE ? OR LOCALIDAD LIKE ? OR DIRECCION LIKE ?)";
                $like     = '%' . $busqueda . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            if (!empty($canal)) {
                $where[]  = "CANAL = ?";
                $params[] = $canal;
            }

            if (!empty($provincia)) {
                $where[]  = "PROVINCIA = ?";
                $params[] = $provincia;
            }

            $query = "SELECT
                        NRO_SUCURSAL,
                        COD_CLIENT,
                        DESC_SUCURSAL,
                        DIRECCION,
                        LOCALIDAD,
                        PROVINCIA,
                        TELEFONO,
                        MAIL,
                        CANAL,
                        HORARIO
                      FROM [XL-LAKERBIS].LOCALES_LAKERS.DBO.DIRECCIONARIO";

            if (!empty($where)) {
                $query .= " WHERE " . implode(" AND ", $where);
            }

            $query .= " ORDER BY DESC_SUCURSAL";

            $stmt = empty($params)
                ? sqlsrv_query($this->cid_central, $query)
                : sqlsrv_query($this->cid_central, $query, $params);

            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $results = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $results[] = [
                    'nro_sucursal'  => trim($row['NRO_SUCURSAL'] ?? ''),
                    'cod_client'    => trim($row['COD_CLIENT']   ?? ''),
                    'desc_sucursal' => trim($row['DESC_SUCURSAL'] ?? ''),
                    'direccion'     => trim($row['DIRECCION']    ?? ''),
                    'localidad'     => trim($row['LOCALIDAD']    ?? ''),
                    'provincia'     => trim($row['PROVINCIA']    ?? ''),
                    'telefono'      => trim($row['TELEFONO']     ?? ''),
                    'mail'          => trim($row['MAIL']         ?? ''),
                    'canal'         => trim($row['CANAL']        ?? ''),
                    'horario'       => trim($row['HORARIO']      ?? ''),
                ];
            }

            return $results;

        } catch (Exception $e) {
            error_log("Error en getSucursales: " . $e->getMessage());
            return [];
        }
    }

    public function getCanales() {
        try {
            $query = "SELECT DISTINCT CANAL
                      FROM [XL-LAKERBIS].LOCALES_LAKERS.DBO.DIRECCIONARIO
                      WHERE NRO_SUC_MADRE IS NULL AND CANAL IS NOT NULL AND CANAL <> ''
                      ORDER BY CANAL";

            $stmt = sqlsrv_query($this->cid_central, $query);

            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $canales = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $canales[] = trim($row['CANAL']);
            }

            return $canales;

        } catch (Exception $e) {
            error_log("Error en getCanales: " . $e->getMessage());
            return [];
        }
    }

    public function getProvincias() {
        try {
            $query = "SELECT DISTINCT PROVINCIA
                      FROM [XL-LAKERBIS].LOCALES_LAKERS.DBO.DIRECCIONARIO
                      WHERE NRO_SUC_MADRE IS NULL AND PROVINCIA IS NOT NULL AND PROVINCIA <> ''
                      ORDER BY PROVINCIA";

            $stmt = sqlsrv_query($this->cid_central, $query);

            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $provincias = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $provincias[] = trim($row['PROVINCIA']);
            }

            return $provincias;

        } catch (Exception $e) {
            error_log("Error en getProvincias: " . $e->getMessage());
            return [];
        }
    }
}
