<?php
/**
 * Clase Direccionario para gestión y consulta de sucursales.
 * Conexión optimizada a la base de datos locales_lakers (XL-LAKERBIS).
 * SOLO OPERACIONES DE LECTURA (SELECT).
 */
class Direccionario {
    private $cid;
    private $cid_locales;

    public function __construct() {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_locales = $this->cid->conectar('locales');
    }

    public function getConnection() {
        return $this->cid_locales;
    }

    private function getSupervisorasMap() {
        $map = [];
        try {
            $query = "SELECT s.NRO_SUCURSAL, sv.NOMBRE
                      FROM SUCURSALES_LAKERS s
                      INNER JOIN RO_T_SUPERVISORAS_COMERCIAL sv ON s.ID_SUPERVISORA = sv.ID
                      WHERE s.ID_SUPERVISORA IS NOT NULL AND sv.ACTIVA = 1";
            $stmt = sqlsrv_query($this->cid_locales, $query);
            if ($stmt !== false) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $map[(int)$row['NRO_SUCURSAL']] = trim($row['NOMBRE']);
                }
            }
        } catch (Exception $e) {
            error_log("Error en getSupervisorasMap: " . $e->getMessage());
        }
        return $map;
    }

    private function getDiasAperturaMap() {
        $map = [];
        try {
            $query = "SELECT NRO_SUCURSAL, LUNES, MARTES, MIERCOLES, JUEVES, VIERNES, SABADO, DOMINGO 
                      FROM SUCURSALES_LAKERS";
            $stmt = sqlsrv_query($this->cid_locales, $query);
            if ($stmt !== false) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $nro = (int)$row['NRO_SUCURSAL'];
                    $map[$nro] = [
                        'lunes'     => (bool)$row['LUNES'],
                        'martes'    => (bool)$row['MARTES'],
                        'miercoles' => (bool)$row['MIERCOLES'],
                        'jueves'    => (bool)$row['JUEVES'],
                        'viernes'   => (bool)$row['VIERNES'],
                        'sabado'    => (bool)$row['SABADO'],
                        'domingo'   => (bool)$row['DOMINGO'],
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error en getDiasAperturaMap: " . $e->getMessage());
        }
        return $map;
    }

    private function formatDiasAbiertosTexto($dias) {
        $labels = [
            'lunes'     => 'Lun',
            'martes'    => 'Mar',
            'miercoles' => 'Mie',
            'jueves'    => 'Jue',
            'viernes'   => 'Vie',
            'sabado'    => 'Sab',
            'domingo'   => 'Dom'
        ];
        $abiertos = [];
        foreach ($labels as $key => $lbl) {
            if (!empty($dias[$key])) {
                $abiertos[] = $lbl;
            }
        }
        return !empty($abiertos) ? implode(', ', $abiertos) : 'Cerrado';
    }

    public function getSucursales($busqueda = '', $canal = '', $provincia = '', $tipo_local = '', $grupo_empresario = '', $mostrar_todos = false, $puede_ver_tecnico = false) {
        try {
            $params = [];
            $where  = [];

            if (!$mostrar_todos) {
                $where[] = "NRO_SUC_MADRE IS NULL";
            }

            if (!empty($busqueda)) {
                $where[]  = "(DESC_SUCURSAL LIKE ? OR COD_CLIENT LIKE ? OR CAST(NRO_SUCURSAL AS VARCHAR(20)) LIKE ? OR LOCALIDAD LIKE ? OR DIRECCION LIKE ?)";
                $like     = '%' . trim($busqueda) . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            if (!empty($canal)) {
                $where[]  = "CANAL = ?";
                $params[] = trim($canal);
            }

            if (!empty($provincia)) {
                $where[]  = "PROVINCIA = ?";
                $params[] = trim($provincia);
            }

            if (!empty($tipo_local)) {
                $where[]  = "TIPO_LOCAL = ?";
                $params[] = trim($tipo_local);
            }

            if (!empty($grupo_empresario)) {
                $where[]  = "GRUPO_EMPRESARIO = ?";
                $params[] = trim($grupo_empresario);
            }

            $query = "SELECT
                        NRO_SUCURSAL,
                        COD_CLIENT,
                        DESC_SUCURSAL,
                        CANAL,
                        TIPO_LOCAL,
                        GRUPO_EMPRESARIO,
                        DIRECCION,
                        LOCALIDAD,
                        PROVINCIA,
                        TELEFONO,
                        MAIL,
                        HORARIO,
                        TANGO,
                        BASE_NOMBRE,
                        CONEXION_DNS,
                        N_LLAVE_TANGO,
                        TIENDA,
                        INTEGRA_VTEX,
                        COD_DEPOSI,
                        RETIRO_EXPRES,
                        NRO_SUC_ANTERIOR,
                        NRO_SUC_MADRE,
                        MAIL_GRUP_EMP
                      FROM DIRECCIONARIO";

            if (!empty($where)) {
                $query .= " WHERE " . implode(" AND ", $where);
            }

            $query .= " ORDER BY NRO_SUCURSAL";

            $stmt = empty($params)
                ? sqlsrv_query($this->cid_locales, $query)
                : sqlsrv_query($this->cid_locales, $query, $params);

            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $supervisorasMap = $this->getSupervisorasMap();
            $diasMap = $this->getDiasAperturaMap();

            $results = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $nro = (int)$row['NRO_SUCURSAL'];
                $diasFlags = $diasMap[$nro] ?? [
                    'lunes'     => false,
                    'martes'    => false,
                    'miercoles' => false,
                    'jueves'    => false,
                    'viernes'   => false,
                    'sabado'    => false,
                    'domingo'   => false
                ];

                $item = [
                    'nro_sucursal'          => $nro,
                    'cod_client'            => trim($row['COD_CLIENT'] ?? ''),
                    'desc_sucursal'         => trim($row['DESC_SUCURSAL'] ?? ''),
                    'canal'                 => trim($row['CANAL'] ?? ''),
                    'tipo_local'            => trim($row['TIPO_LOCAL'] ?? ''),
                    'grupo_empresario'      => trim($row['GRUPO_EMPRESARIO'] ?? ''),
                    'direccion'             => trim($row['DIRECCION'] ?? ''),
                    'localidad'             => trim($row['LOCALIDAD'] ?? ''),
                    'provincia'             => trim($row['PROVINCIA'] ?? ''),
                    'telefono'              => trim($row['TELEFONO'] ?? ''),
                    'mail'                  => trim($row['MAIL'] ?? ''),
                    'horario'               => trim($row['HORARIO'] ?? ''),
                    'tango'                 => trim($row['TANGO'] ?? ''),
                    'tienda'                => trim($row['TIENDA'] ?? ''),
                    'integra_vtex'          => trim($row['INTEGRA_VTEX'] ?? ''),
                    'deposito'              => trim($row['COD_DEPOSI'] ?? ''),
                    'retiro_expres'         => trim($row['RETIRO_EXPRES'] ?? ''),
                    'nro_sucursal_madre'    => $row['NRO_SUC_MADRE'],
                    'nro_sucursal_anterior' => $row['NRO_SUC_ANTERIOR'],
                    'mail_grupo_emp'        => trim($row['MAIL_GRUP_EMP'] ?? ''),
                    'supervisora'           => $supervisorasMap[$nro] ?? '',
                    'lunes'                 => $diasFlags['lunes'],
                    'martes'                => $diasFlags['martes'],
                    'miercoles'             => $diasFlags['miercoles'],
                    'jueves'                => $diasFlags['jueves'],
                    'viernes'               => $diasFlags['viernes'],
                    'sabado'                => $diasFlags['sabado'],
                    'domingo'               => $diasFlags['domingo'],
                    'dias_abiertos'         => $this->formatDiasAbiertosTexto($diasFlags)
                ];

                if ($puede_ver_tecnico) {
                    $item['base_nombre']   = trim($row['BASE_NOMBRE'] ?? '');
                    $item['conexion_dns']  = trim($row['CONEXION_DNS'] ?? '');
                    $item['n_llave_tango'] = trim($row['N_LLAVE_TANGO'] ?? '');
                }

                $results[] = $item;
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
                      FROM DIRECCIONARIO
                      WHERE NRO_SUC_MADRE IS NULL AND CANAL IS NOT NULL AND CANAL <> ''
                      ORDER BY CANAL";

            $stmt = sqlsrv_query($this->cid_locales, $query);
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $canales = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $val = trim($row['CANAL'] ?? '');
                if ($val !== '') {
                    $canales[] = $val;
                }
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
                      FROM DIRECCIONARIO
                      WHERE NRO_SUC_MADRE IS NULL AND PROVINCIA IS NOT NULL AND PROVINCIA <> ''
                      ORDER BY PROVINCIA";

            $stmt = sqlsrv_query($this->cid_locales, $query);
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $provincias = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $val = trim($row['PROVINCIA'] ?? '');
                if ($val !== '') {
                    $provincias[] = $val;
                }
            }
            return $provincias;
        } catch (Exception $e) {
            error_log("Error en getProvincias: " . $e->getMessage());
            return [];
        }
    }

    public function getTiposLocal() {
        try {
            $query = "SELECT DISTINCT TIPO_LOCAL
                      FROM DIRECCIONARIO
                      WHERE NRO_SUC_MADRE IS NULL AND TIPO_LOCAL IS NOT NULL AND TIPO_LOCAL <> ''
                      ORDER BY TIPO_LOCAL";

            $stmt = sqlsrv_query($this->cid_locales, $query);
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $tipos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $val = trim($row['TIPO_LOCAL'] ?? '');
                if ($val !== '') {
                    $tipos[] = $val;
                }
            }
            return $tipos;
        } catch (Exception $e) {
            error_log("Error en getTiposLocal: " . $e->getMessage());
            return [];
        }
    }

    public function getGruposEmpresarios() {
        try {
            $query = "SELECT DISTINCT GRUPO_EMPRESARIO
                      FROM DIRECCIONARIO
                      WHERE NRO_SUC_MADRE IS NULL AND GRUPO_EMPRESARIO IS NOT NULL AND GRUPO_EMPRESARIO <> ''
                      ORDER BY GRUPO_EMPRESARIO";

            $stmt = sqlsrv_query($this->cid_locales, $query);
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $grupos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $val = trim($row['GRUPO_EMPRESARIO'] ?? '');
                if ($val !== '') {
                    $grupos[] = $val;
                }
            }
            return $grupos;
        } catch (Exception $e) {
            error_log("Error en getGruposEmpresarios: " . $e->getMessage());
            return [];
        }
    }

    public function getSucursalById($nro) {
        try {
            $query = "SELECT TOP 1 d.*, 
                             s.LUNES, s.MARTES, s.MIERCOLES, s.JUEVES, s.VIERNES, s.SABADO, s.DOMINGO
                      FROM DIRECCIONARIO d
                      LEFT JOIN SUCURSALES_LAKERS s ON d.NRO_SUCURSAL = s.NRO_SUCURSAL
                      WHERE d.NRO_SUCURSAL = ?";
            $stmt = sqlsrv_query($this->cid_locales, $query, [$nro]);
            if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
                return $row;
            }
            return null;
        } catch (Exception $e) {
            error_log("Error en getSucursalById: " . $e->getMessage());
            return null;
        }
    }

    public function eliminarSucursal($nro) {
        try {
            $query = "UPDATE SUCURSALES_LAKERS SET HABILITADO = 0 WHERE NRO_SUCURSAL = ?";
            $stmt = sqlsrv_query($this->cid_locales, $query, [$nro]);
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en eliminarSucursal: " . $e->getMessage());
            throw $e;
        }
    }

    public function guardarSucursal($d, $esEdicion = false) {
        try {
            $nro = (int)($d['nro_sucursal'] ?? 0);
            if ($nro <= 0) {
                throw new Exception("Número de sucursal inválido.");
            }

            $codClient = trim($d['cod_client'] ?? '');
            $descSucursal = trim($d['desc_sucursal'] ?? '');
            $canal = trim($d['canal'] ?? '');
            $tipoLocal = trim($d['tipo_local'] ?? '');
            $direccion = trim($d['direccion'] ?? '');
            $localidad = trim($d['localidad'] ?? '');
            $provincia = trim($d['provincia'] ?? '');
            $telefono = trim($d['telefono'] ?? '');
            $mail = trim($d['mail'] ?? '');
            $horario = trim($d['horario'] ?? '');
            $codDeposi = trim($d['cod_deposi'] ?? '');
            $mailGrupEmp = trim($d['mail_grupo_emp'] ?? '');
            $tango = !empty($d['tango']) ? 1 : 0;
            $vtex = !empty($d['integra_vtex']) ? 1 : 0;
            $retiroExp = !empty($d['retiro_expres']) ? 1 : 0;
            $nroSucMadre = !empty($d['nro_suc_madre']) ? (int)$d['nro_suc_madre'] : null;

            // Días de apertura
            $lunes = !empty($d['lunes']) ? 1 : 0;
            $martes = !empty($d['martes']) ? 1 : 0;
            $miercoles = !empty($d['miercoles']) ? 1 : 0;
            $jueves = !empty($d['jueves']) ? 1 : 0;
            $viernes = !empty($d['viernes']) ? 1 : 0;
            $sabado = !empty($d['sabado']) ? 1 : 0;
            $domingo = !empty($d['domingo']) ? 1 : 0;

            if ($esEdicion) {
                $sql = "UPDATE SUCURSALES_LAKERS SET
                        COD_CLIENT = ?, DESC_SUCURSAL = ?, CANAL = ?, TIPO_LOCAL = ?,
                        DIRECCION = ?, LOCALIDAD = ?, PROVINCIA = ?, TELEFONO = ?, MAIL = ?, HORARIO = ?,
                        COD_DEPOSI = ?, MAIL_GRUP_EMP = ?, TANGO = ?, INTEGRA_VTEX = ?, RETIRO_EXPRES = ?,
                        NRO_SUC_MADRE = ?, LUNES = ?, MARTES = ?, MIERCOLES = ?, JUEVES = ?, VIERNES = ?, SABADO = ?, DOMINGO = ?
                        WHERE NRO_SUCURSAL = ?";
                $params = [
                    $codClient, $descSucursal, $canal, $tipoLocal,
                    $direccion, $localidad, $provincia, $telefono, $mail, $horario,
                    $codDeposi, $mailGrupEmp, $tango, $vtex, $retiroExp,
                    $nroSucMadre, $lunes, $martes, $miercoles, $jueves, $viernes, $sabado, $domingo,
                    $nro
                ];
                $stmt = sqlsrv_query($this->cid_locales, $sql, $params);
                if ($stmt === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }
            } else {
                $sql = "INSERT INTO SUCURSALES_LAKERS (
                        NRO_SUCURSAL, COD_CLIENT, DESC_SUCURSAL, CANAL, TIPO_LOCAL,
                        DIRECCION, LOCALIDAD, PROVINCIA, TELEFONO, MAIL, HORARIO,
                        COD_DEPOSI, MAIL_GRUP_EMP, TANGO, INTEGRA_VTEX, RETIRO_EXPRES,
                        NRO_SUC_MADRE, HABILITADO, LUNES, MARTES, MIERCOLES, JUEVES, VIERNES, SABADO, DOMINGO
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $nro, $codClient, $descSucursal, $canal, $tipoLocal,
                    $direccion, $localidad, $provincia, $telefono, $mail, $horario,
                    $codDeposi, $mailGrupEmp, $tango, $vtex, $retiroExp,
                    $nroSucMadre, $lunes, $martes, $miercoles, $jueves, $viernes, $sabado, $domingo
                ];
                $stmt = sqlsrv_query($this->cid_locales, $sql, $params);
                if ($stmt === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error en guardarSucursal: " . $e->getMessage());
            throw $e;
        }
    }
}
