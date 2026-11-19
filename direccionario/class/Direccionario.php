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

    /**
     * Sincroniza nombre y estado activo en PuntosDeVenta (base apps / sistemas)
     * matcheando por idTango = NRO_SUCURSAL, replicando la lógica de Adminweb.
     * Retorna null si tuvo éxito o un mensaje de advertencia si no pudo sincronizar.
     */
    private function syncPuntoDeVenta($nroSucursal, $nombre, $activo) {
        if ($nroSucursal <= 0) {
            return null;
        }
        $cidApps = $this->cid->conectar('apps');
        if (!$cidApps) {
            return 'No se pudo conectar a la base apps para sincronizar PuntosDeVenta.';
        }

        $sistemaPlaceholder = '00000000-0000-0000-0000-000000000000';
        $nombre = trim((string)$nombre);
        $nuevoActivo = $activo ? 1 : 0;

        try {
            $sqlSel = "SELECT TOP 1 nombre, activo FROM PuntosDeVenta WHERE idTango = ?";
            $stmtSel = sqlsrv_query($cidApps, $sqlSel, [$nroSucursal]);
            if ($stmtSel === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
            $row = sqlsrv_fetch_array($stmtSel, SQLSRV_FETCH_ASSOC);

            if (!$row) {
                $uuid = sprintf(
                    '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $sqlIns = "INSERT INTO PuntosDeVenta
                    (id, numero, idTango, nombre, direccion, localidad, idProvincia, telefono,
                     fax, email, web, idTipoPuntoVenta, idZona, esShopping,
                     fechaCreacion, usuarioCreacion, fechaUltMod, usuarioUltMod,
                     activo, local_referente, Grupo_Benchmark, SupervisorAsignado)
                    VALUES (?, 0, ?, ?, '', '', 1, '', '', '', '', 1, 1, 0,
                            GETDATE(), ?, GETDATE(), ?, ?, NULL, 0, NULL)";
                $stmtIns = sqlsrv_query($cidApps, $sqlIns, [
                    $uuid, $nroSucursal, $nombre,
                    $sistemaPlaceholder, $sistemaPlaceholder, $nuevoActivo
                ]);
                if ($stmtIns === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }
            } else {
                $dbNombre = trim((string)($row['nombre'] ?? ''));
                $dbActivo = !empty($row['activo']) ? 1 : 0;
                if ($dbNombre !== $nombre || $dbActivo !== $nuevoActivo) {
                    $sqlUpd = "UPDATE PuntosDeVenta
                               SET nombre = ?, activo = ?, fechaUltMod = GETDATE(), usuarioUltMod = ?
                               WHERE idTango = ?";
                    $stmtUpd = sqlsrv_query($cidApps, $sqlUpd, [
                        $nombre, $nuevoActivo, $sistemaPlaceholder, $nroSucursal
                    ]);
                    if ($stmtUpd === false) {
                        throw new Exception(print_r(sqlsrv_errors(), true));
                    }
                }
            }
            return null;
        } catch (Exception $e) {
            error_log("Error en syncPuntoDeVenta (suc={$nroSucursal}): " . $e->getMessage());
            return 'La sucursal se guardó, pero no se pudo sincronizar con PuntosDeVenta.';
        }
    }

    private function normalizeMailGrupo($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return '';
        }
        $parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $joined = implode(';', array_values(array_unique($parts)));
        return mb_substr($joined, 0, 200);
    }

    private function strLimit($val, $max) {
        $val = trim((string)$val);
        if ($val === '') {
            return null;
        }
        return mb_substr($val, 0, $max);
    }

    private function normalizarSnapshotHistorial($row) {
        if (!$row || !is_array($row)) {
            return null;
        }

        $boolFields = [
            'HABILITADO', 'DASHBOARD_BI', 'TANGO', 'SUC_MADRE',
            'EMPRESA_FERRETERIA', 'FACTURA_ELECT', 'INTEGRA_VTEX', 'RETIRO_EXPRES',
            'HABILITADO_CONT', 'INTEGRACION_MERCADOPAGO_MADRE', 'INTEGRACION_MERCADOPAGO_HIJA',
            'LAPOS_INTEGRADO', 'LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'
        ];
        $intFields = ['NRO_SUCURSAL', 'NRO_SUC_MADRE'];
        $strFields = [
            'COD_CLIENT', 'DESC_SUCURSAL', 'DIRECCION', 'TELEFONO', 'LOCALIDAD',
            'PROVINCIA', 'CANAL', 'TIPO_LOCAL', 'COD_DEPOSI', 'MAIL', 'HORARIO',
            'FECHA_CIERRE', 'MAIL_GRUP_EMP',
            'LAPOS_MODELO', 'VERSION_SOFT_LAPOS', 'CABLE_LAPOS_INTEGRADO', 'LINK_PEDIDO', 'LINK_SALES',
            'BASE_NOMBRE', 'N_LLAVE_TANGO', 'CONEXION_DNS', 'CONEXION_DNS_ALT',
            'USUARIO_DNS', 'CLAVE_DNS',
            'CONEXION_TEAMVIEWER_MADRE', 'CONEXION_TEAMVIEWER_HIJA', 'CLAVE_TEAMVIEWER', 'USUARIO_PC_MADRE'
        ];

        $out = [];
        foreach ($intFields as $col) {
            $val = $row[$col] ?? null;
            $out[$col] = ($val === null || $val === '') ? null : (int)$val;
        }
        foreach ($strFields as $col) {
            $val = $row[$col] ?? null;
            if ($val instanceof DateTimeInterface) {
                $val = $val->format('Y-m-d');
            }
            $val = $val === null ? '' : trim((string)$val);
            $out[$col] = ($val === '') ? null : $val;
        }
        foreach ($boolFields as $col) {
            $out[$col] = !empty($row[$col]) ? 1 : 0;
        }
        return $out;
    }

    private function getClientIp() {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }
        $ip = trim((string)$ip);
        return $ip !== '' ? mb_substr($ip, 0, 50) : null;
    }

    /**
     * Registra un evento de auditoría en dbo.FP_DIRECCIONARIO_HISTORIAL (base apps / sistemas),
     * vinculado a dbo.FP_SOF_USUARIOS de Gestión de Usuarios.
     */
    private function registrarHistorial($nroSucursal, $accion, $datosAnterioresRaw, $datosNuevosRaw, $usuario = null, $descripcionExtra = null) {
        try {
            $cidApps = $this->cid->conectar('apps');
            if (!$cidApps) {
                return 'No se pudo conectar a la base apps para registrar el historial.';
            }

            if ($usuario === null && class_exists('Auth')) {
                $usuario = Auth::usuario();
            }

            $usuarioId    = (!empty($usuario['id']) && (int)$usuario['id'] > 0) ? (int)$usuario['id'] : null;
            $username     = !empty($usuario['username']) ? mb_substr(trim((string)$usuario['username']), 0, 100) : null;
            $nombreUsu    = !empty($usuario['nombre']) ? mb_substr(trim((string)$usuario['nombre']), 0, 150) : null;
            $rolId        = (!empty($usuario['rol_id']) && (int)$usuario['rol_id'] > 0) ? (int)$usuario['rol_id'] : null;
            $rolNombre    = !empty($usuario['rol_nombre']) ? mb_substr(trim((string)$usuario['rol_nombre']), 0, 100) : null;
            $sectorId     = (!empty($usuario['sector_id']) && (int)$usuario['sector_id'] > 0) ? (int)$usuario['sector_id'] : null;
            $sectorNombre = !empty($usuario['sector_nombre']) ? mb_substr(trim((string)$usuario['sector_nombre']), 0, 100) : null;

            // Verificar que usuario_id exista en FP_SOF_USUARIOS antes de usar la FK
            if ($usuarioId !== null) {
                $chkU = sqlsrv_query($cidApps, "SELECT TOP 1 1 FROM FP_SOF_USUARIOS WHERE id = ?", [$usuarioId]);
                if (!$chkU || !sqlsrv_fetch_array($chkU, SQLSRV_FETCH_NUMERIC)) {
                    $usuarioId = null;
                }
            }

            $ipAddress = $this->getClientIp();
            $userAgent = !empty($_SERVER['HTTP_USER_AGENT']) ? mb_substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

            $snapAnt = $this->normalizarSnapshotHistorial($datosAnterioresRaw);
            $snapNue = $this->normalizarSnapshotHistorial($datosNuevosRaw);

            $camposModificados = [];
            if ($snapAnt !== null && $snapNue !== null) {
                foreach ($snapNue as $campo => $nuevoVal) {
                    $antVal = $snapAnt[$campo] ?? null;
                    if ($antVal !== $nuevoVal) {
                        $camposModificados[$campo] = [
                            'anterior' => $antVal,
                            'nuevo'    => $nuevoVal,
                        ];
                    }
                }
            }

            $nombreSuc = $snapNue['DESC_SUCURSAL'] ?? $snapAnt['DESC_SUCURSAL'] ?? '';
            if ($accion === 'ALTA') {
                $descripcion = "Alta de sucursal #{$nroSucursal}" . ($nombreSuc ? " - {$nombreSuc}" : '');
            } elseif ($accion === 'CLONACION') {
                $descripcion = "Alta por clonación de sucursal #{$nroSucursal}" . ($nombreSuc ? " - {$nombreSuc}" : '');
                if ($descripcionExtra) {
                    $descripcion .= " ({$descripcionExtra})";
                }
            } elseif ($accion === 'DESHABILITACION') {
                $descripcion = "Deshabilitación de sucursal #{$nroSucursal}" . ($nombreSuc ? " - {$nombreSuc}" : '');
            } else {
                $cant = count($camposModificados);
                if ($cant > 0) {
                    $descripcion = "Edición de {$cant} campo(s): " . implode(', ', array_keys($camposModificados));
                } else {
                    $descripcion = "Edición guardada sin cambios en los valores";
                }
            }
            $descripcion = mb_substr($descripcion, 0, 500);

            $jsonCampos = !empty($camposModificados) ? json_encode($camposModificados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            $jsonAnt    = $snapAnt !== null ? json_encode($snapAnt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            $jsonNue    = $snapNue !== null ? json_encode($snapNue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

            $sql = "INSERT INTO dbo.FP_DIRECCIONARIO_HISTORIAL (
                        nro_sucursal, accion, usuario_id, username, nombre_usuario,
                        rol_id, rol_nombre, sector_id, sector_nombre,
                        ip_address, user_agent, campos_modificados,
                        datos_anteriores, datos_nuevos, descripcion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                (int)$nroSucursal,
                $accion,
                $usuarioId,
                $username,
                $nombreUsu,
                $rolId,
                $rolNombre,
                $sectorId,
                $sectorNombre,
                $ipAddress,
                $userAgent,
                $jsonCampos,
                $jsonAnt,
                $jsonNue,
                $descripcion
            ];
            $stmt = sqlsrv_query($cidApps, $sql, $params);
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
            return null;
        } catch (Exception $e) {
            error_log("Error en registrarHistorial (suc={$nroSucursal}): " . $e->getMessage());
            return 'La sucursal se guardó, pero no se pudo registrar en FP_DIRECCIONARIO_HISTORIAL.';
        }
    }

    /**
     * Obtiene el historial de ediciones de una sucursal (o general) desde FP_DIRECCIONARIO_HISTORIAL
     * cruzado con FP_SOF_USUARIOS de Gestión de Usuarios.
     */
    public function getHistorialSucursal($nroSucursal = null, $limit = 50, $incluirTecnicos = false) {
        try {
            $cidApps = $this->cid->conectar('apps');
            if (!$cidApps) {
                return [];
            }

            $limit = max(1, min(200, (int)$limit));
            $params = [];
            $where = '';
            if ($nroSucursal !== null && (int)$nroSucursal > 0) {
                $where = "WHERE h.nro_sucursal = ?";
                $params[] = (int)$nroSucursal;
            }

            $sql = "SELECT TOP {$limit}
                           h.id, h.nro_sucursal, h.accion, h.usuario_id,
                           COALESCE(u.username, h.username) AS username,
                           COALESCE(u.nombre_completo, h.nombre_usuario) AS nombre_usuario,
                           COALESCE(r.nombre, h.rol_nombre) AS rol_nombre,
                           COALESCE(s.nombre, h.sector_nombre) AS sector_nombre,
                           h.ip_address, h.campos_modificados, h.descripcion, h.fecha_hora
                    FROM dbo.FP_DIRECCIONARIO_HISTORIAL h
                    LEFT JOIN dbo.FP_SOF_USUARIOS u ON u.id = h.usuario_id
                    LEFT JOIN dbo.FP_ROLES_PERMISOS_MAP r ON r.id = COALESCE(u.rol_id, h.rol_id)
                    LEFT JOIN dbo.FP_SECTORES s ON s.id = COALESCE(u.sector_id, h.sector_id)
                    {$where}
                    ORDER BY h.fecha_hora DESC, h.id DESC";

            $stmt = empty($params)
                ? sqlsrv_query($cidApps, $sql)
                : sqlsrv_query($cidApps, $sql, $params);
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $camposSensibles = ['USUARIO_DNS', 'CLAVE_DNS', 'CLAVE_TEAMVIEWER', 'USUARIO_PC_MADRE'];
            $rows = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fecha = '';
                if (!empty($row['fecha_hora']) && $row['fecha_hora'] instanceof DateTimeInterface) {
                    $fecha = $row['fecha_hora']->format('d/m/Y H:i:s');
                } elseif (!empty($row['fecha_hora'])) {
                    $fecha = (string)$row['fecha_hora'];
                }

                $cambios = [];
                if (!empty($row['campos_modificados'])) {
                    $decoded = json_decode((string)$row['campos_modificados'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $campo => $vals) {
                            if (!$incluirTecnicos && in_array($campo, $camposSensibles, true)) {
                                $cambios[$campo] = ['anterior' => '***', 'nuevo' => '***'];
                            } else {
                                $cambios[$campo] = $vals;
                            }
                        }
                    }
                }

                $rows[] = [
                    'id'                 => (int)$row['id'],
                    'nro_sucursal'       => (int)$row['nro_sucursal'],
                    'accion'             => trim((string)$row['accion']),
                    'usuario_id'         => $row['usuario_id'] !== null ? (int)$row['usuario_id'] : null,
                    'username'           => trim((string)($row['username'] ?? '')),
                    'nombre_usuario'     => trim((string)($row['nombre_usuario'] ?? '')),
                    'rol_nombre'         => trim((string)($row['rol_nombre'] ?? '')),
                    'sector_nombre'      => trim((string)($row['sector_nombre'] ?? '')),
                    'ip_address'         => trim((string)($row['ip_address'] ?? '')),
                    'campos_modificados' => $cambios,
                    'descripcion'        => trim((string)($row['descripcion'] ?? '')),
                    'fecha_hora'         => $fecha,
                ];
            }
            return $rows;
        } catch (Exception $e) {
            error_log("Error en getHistorialSucursal: " . $e->getMessage());
            return [];
        }
    }

    public function getSucursalById($nro) {
        try {
            $query = "SELECT TOP 1
                             s.ID, s.NRO_SUCURSAL, s.COD_CLIENT, s.DESC_SUCURSAL, s.DIRECCION,
                             s.TELEFONO, s.LOCALIDAD, s.PROVINCIA, s.CANAL, s.TIPO_LOCAL,
                             s.COD_DEPOSI, s.MAIL, s.HORARIO, s.FECHA_CIERRE,
                             s.HABILITADO, s.DASHBOARD_BI, s.TANGO, s.SUC_MADRE, s.NRO_SUC_MADRE,
                             s.EMPRESA_FERRETERIA, s.FACTURA_ELECT, s.INTEGRA_VTEX, s.RETIRO_EXPRES,
                             s.HABILITADO_CONT,
                             s.INTEGRACION_MERCADOPAGO_MADRE, s.INTEGRACION_MERCADOPAGO_HIJA,
                             s.LAPOS_INTEGRADO, s.LAPOS_MODELO, s.VERSION_SOFT_LAPOS,
                             s.CABLE_LAPOS_INTEGRADO, s.LINK_PEDIDO, s.LINK_SALES,
                             s.BASE_NOMBRE, s.N_LLAVE_TANGO, s.CONEXION_DNS, s.CONEXION_DNS_ALT,
                             s.USUARIO_DNS, s.CLAVE_DNS,
                             s.CONEXION_TEAMVIEWER_MADRE, s.CONEXION_TEAMVIEWER_HIJA,
                             s.CLAVE_TEAMVIEWER, s.USUARIO_PC_MADRE,
                             s.MAIL_GRUP_EMP,
                             s.LUNES, s.MARTES, s.MIERCOLES, s.JUEVES, s.VIERNES, s.SABADO, s.DOMINGO,
                             d.GRUPO_EMPRESARIO, d.NRO_SUC_ANTERIOR,
                             CASE WHEN ISNULL(s.TANGO, 0) = 1 THEN 'SI' ELSE 'NO' END AS TANGO_TXT,
                             CASE WHEN ISNULL(s.HABILITADO, 0) = 1 THEN 'SI' ELSE 'NO' END AS TIENDA,
                             CASE WHEN ISNULL(s.INTEGRA_VTEX, 0) = 1 THEN 'SI' ELSE 'NO' END AS INTEGRA_VTEX_TXT,
                             CASE WHEN ISNULL(s.RETIRO_EXPRES, 0) = 1 THEN 'SI' ELSE 'NO' END AS RETIRO_EXPRES_TXT
                      FROM SUCURSALES_LAKERS s
                      LEFT JOIN DIRECCIONARIO d ON s.NRO_SUCURSAL = d.NRO_SUCURSAL
                      WHERE s.NRO_SUCURSAL = ?";
            $stmt = sqlsrv_query($this->cid_locales, $query, [$nro]);
            if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
                if (!empty($row['FECHA_CIERRE']) && $row['FECHA_CIERRE'] instanceof DateTimeInterface) {
                    $row['FECHA_CIERRE'] = $row['FECHA_CIERRE']->format('Y-m-d');
                }
                return $row;
            }
            return null;
        } catch (Exception $e) {
            error_log("Error en getSucursalById: " . $e->getMessage());
            return null;
        }
    }

    public function eliminarSucursal($nro, $usuario = null) {
        try {
            $actual = $this->getSucursalById($nro);
            $query = "UPDATE SUCURSALES_LAKERS SET HABILITADO = 0 WHERE NRO_SUCURSAL = ?";
            $stmt = sqlsrv_query($this->cid_locales, $query, [$nro]);
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
            if ($actual) {
                $this->syncPuntoDeVenta($nro, $actual['DESC_SUCURSAL'] ?? '', false);
            }
            $despues = $this->getSucursalById($nro);
            $this->registrarHistorial($nro, 'DESHABILITACION', $actual, $despues, $usuario);
            return true;
        } catch (Exception $e) {
            error_log("Error en eliminarSucursal: " . $e->getMessage());
            throw $e;
        }
    }

    public function guardarSucursal($d, $esEdicion = false, $esAdminSistemas = false, $meta = []) {
        try {
            $nro = (int)($d['nro_sucursal'] ?? 0);
            if ($nro <= 0) {
                throw new Exception("Número de sucursal inválido.");
            }

            $datosAnteriores = null;
            if ($esEdicion) {
                $datosAnteriores = $this->getSucursalById($nro);
            } else {
                $chkStmt = sqlsrv_query($this->cid_locales, "SELECT TOP 1 1 FROM SUCURSALES_LAKERS WHERE NRO_SUCURSAL = ?", [$nro]);
                if ($chkStmt && sqlsrv_fetch_array($chkStmt, SQLSRV_FETCH_NUMERIC)) {
                    throw new Exception("Ya existe una sucursal registrada con el número #{$nro}.");
                }
            }

            $codClient    = mb_substr(strtoupper(trim($d['cod_client'] ?? '')), 0, 8);
            $descSucursal = mb_substr(strtoupper(trim($d['desc_sucursal'] ?? '')), 0, 50);
            $canal        = mb_substr(trim($d['canal'] ?? ''), 0, 15);
            $tipoLocal    = mb_substr(trim($d['tipo_local'] ?? ''), 0, 30);
            $direccion    = mb_substr(strtoupper(trim($d['direccion'] ?? '')), 0, 80);
            $localidad    = mb_substr(strtoupper(trim($d['localidad'] ?? '')), 0, 50);
            $provincia    = mb_substr(trim($d['provincia'] ?? ''), 0, 50);
            $telefono     = mb_substr(trim($d['telefono'] ?? ''), 0, 50);
            $mail         = mb_substr(trim($d['mail'] ?? ''), 0, 50);
            $horario      = mb_substr(strtoupper(trim($d['horario'] ?? '')), 0, 50);
            $codDeposi    = mb_substr(trim($d['cod_deposi'] ?? ''), 0, 2);
            $mailGrupEmp  = $this->normalizeMailGrupo($d['mail_grupo_emp'] ?? '');

            if ($codClient === '' || $descSucursal === '' || $direccion === '' || $localidad === '' || $provincia === '' || $telefono === '') {
                throw new Exception("Complete todos los campos obligatorios marcados con *.");
            }

            $tango          = !empty($d['tango']) ? 1 : 0;
            $habilitado     = (!empty($d['habilitado']) || !empty($d['tienda'])) ? 1 : 0;
            $vtex           = !empty($d['integra_vtex']) ? 1 : 0;
            $retiroExp      = !empty($d['retiro_expres']) ? 1 : 0;
            $habilitadoCont = !empty($d['habilitado_cont']) ? 1 : 0;
            $dashboardBi    = !empty($d['dashboard_bi']) ? 1 : 0;
            $sucMadre       = !empty($d['suc_madre']) ? 1 : 0;
            $empFerreteria  = !empty($d['empresa_ferreteria']) ? 1 : 0;
            $facturaElect   = !empty($d['factura_elect']) ? 1 : 0;
            $nroSucMadre    = (isset($d['nro_suc_madre']) && trim((string)$d['nro_suc_madre']) !== '') ? (int)$d['nro_suc_madre'] : null;

            $fechaCierreRaw = trim((string)($d['fecha_cierre'] ?? ''));
            $fechaCierre    = null;
            if ($fechaCierreRaw !== '') {
                $dt = DateTime::createFromFormat('Y-m-d', $fechaCierreRaw);
                if (!$dt || $dt->format('Y-m-d') !== $fechaCierreRaw) {
                    throw new Exception("Formato de fecha de cierre inválido.");
                }
                $fechaCierre = $fechaCierreRaw;
            }

            // Días de apertura
            $lunes     = !empty($d['lunes']) ? 1 : 0;
            $martes    = !empty($d['martes']) ? 1 : 0;
            $miercoles = !empty($d['miercoles']) ? 1 : 0;
            $jueves    = !empty($d['jueves']) ? 1 : 0;
            $viernes   = !empty($d['viernes']) ? 1 : 0;
            $sabado    = !empty($d['sabado']) ? 1 : 0;
            $domingo   = !empty($d['domingo']) ? 1 : 0;

            // Campos avanzados (Admin/Sistemas)
            $mpMadre          = !empty($d['integracion_mercadopago_madre']) ? 1 : 0;
            $mpHija           = !empty($d['integracion_mercadopago_hija']) ? 1 : 0;
            $laposIntegrado   = !empty($d['lapos_integrado']) ? 1 : 0;
            $laposModelo      = $this->strLimit($d['lapos_modelo'] ?? '', 30);
            $versionSoftLapos = $this->strLimit($d['version_soft_lapos'] ?? '', 30);
            $cableLapos       = $this->strLimit($d['cable_lapos_integrado'] ?? '', 30);
            $linkPedido       = $this->strLimit($d['link_pedido'] ?? '', 50);
            $linkSales        = $this->strLimit($d['link_sales'] ?? '', 50);
            $baseNombre       = $this->strLimit($d['base_nombre'] ?? '', 50);
            $nLlaveTango      = $this->strLimit($d['n_llave_tango'] ?? '', 20);
            $conexionDns      = $this->strLimit($d['conexion_dns'] ?? '', 50);
            $conexionDnsAlt   = $this->strLimit($d['conexion_dns_alt'] ?? '', 100);
            $usuarioDns       = $this->strLimit($d['usuario_dns'] ?? '', 30);
            $claveDns         = $this->strLimit($d['clave_dns'] ?? '', 30);
            $tvMadre          = $this->strLimit($d['conexion_teamviewer_madre'] ?? '', 30);
            $tvHija           = $this->strLimit($d['conexion_teamviewer_hija'] ?? '', 30);
            $claveTv          = $this->strLimit($d['clave_teamviewer'] ?? '', 30);
            $usuarioPcMadre   = $this->strLimit($d['usuario_pc_madre'] ?? '', 30);

            if ($esEdicion) {
                if ($esAdminSistemas) {
                    $sql = "UPDATE SUCURSALES_LAKERS SET
                            COD_CLIENT = ?, DESC_SUCURSAL = ?, CANAL = ?, TIPO_LOCAL = ?,
                            DIRECCION = ?, LOCALIDAD = ?, PROVINCIA = ?, TELEFONO = ?, MAIL = ?, HORARIO = ?,
                            COD_DEPOSI = ?, MAIL_GRUP_EMP = ?,
                            HABILITADO = ?, TANGO = ?, INTEGRA_VTEX = ?, RETIRO_EXPRES = ?, HABILITADO_CONT = ?,
                            DASHBOARD_BI = ?, SUC_MADRE = ?, EMPRESA_FERRETERIA = ?, FACTURA_ELECT = ?,
                            NRO_SUC_MADRE = ?, FECHA_CIERRE = ?,
                            LUNES = ?, MARTES = ?, MIERCOLES = ?, JUEVES = ?, VIERNES = ?, SABADO = ?, DOMINGO = ?,
                            INTEGRACION_MERCADOPAGO_MADRE = ?, INTEGRACION_MERCADOPAGO_HIJA = ?,
                            LAPOS_INTEGRADO = ?, LAPOS_MODELO = ?, VERSION_SOFT_LAPOS = ?, CABLE_LAPOS_INTEGRADO = ?,
                            LINK_PEDIDO = ?, LINK_SALES = ?,
                            BASE_NOMBRE = ?, N_LLAVE_TANGO = ?, CONEXION_DNS = ?, CONEXION_DNS_ALT = ?,
                            USUARIO_DNS = ?, CLAVE_DNS = ?,
                            CONEXION_TEAMVIEWER_MADRE = ?, CONEXION_TEAMVIEWER_HIJA = ?, CLAVE_TEAMVIEWER = ?, USUARIO_PC_MADRE = ?
                            WHERE NRO_SUCURSAL = ?";
                    $params = [
                        $codClient, $descSucursal, $canal, $tipoLocal,
                        $direccion, $localidad, $provincia, $telefono, $mail, $horario,
                        $codDeposi, $mailGrupEmp,
                        $habilitado, $tango, $vtex, $retiroExp, $habilitadoCont,
                        $dashboardBi, $sucMadre, $empFerreteria, $facturaElect,
                        $nroSucMadre, $fechaCierre,
                        $lunes, $martes, $miercoles, $jueves, $viernes, $sabado, $domingo,
                        $mpMadre, $mpHija,
                        $laposIntegrado, $laposModelo, $versionSoftLapos, $cableLapos,
                        $linkPedido, $linkSales,
                        $baseNombre, $nLlaveTango, $conexionDns, $conexionDnsAlt,
                        $usuarioDns, $claveDns,
                        $tvMadre, $tvHija, $claveTv, $usuarioPcMadre,
                        $nro
                    ];
                } else {
                    // Edición estándar (no Admin/Sistemas): preserva configuraciones técnicas, LAPOS, MP y TeamViewer
                    $sql = "UPDATE SUCURSALES_LAKERS SET
                            COD_CLIENT = ?, DESC_SUCURSAL = ?, CANAL = ?, TIPO_LOCAL = ?,
                            DIRECCION = ?, LOCALIDAD = ?, PROVINCIA = ?, TELEFONO = ?, MAIL = ?, HORARIO = ?,
                            COD_DEPOSI = ?, MAIL_GRUP_EMP = ?,
                            HABILITADO = ?, TANGO = ?, INTEGRA_VTEX = ?, RETIRO_EXPRES = ?, HABILITADO_CONT = ?,
                            DASHBOARD_BI = ?, SUC_MADRE = ?, EMPRESA_FERRETERIA = ?,
                            NRO_SUC_MADRE = ?,
                            LUNES = ?, MARTES = ?, MIERCOLES = ?, JUEVES = ?, VIERNES = ?, SABADO = ?, DOMINGO = ?
                            WHERE NRO_SUCURSAL = ?";
                    $params = [
                        $codClient, $descSucursal, $canal, $tipoLocal,
                        $direccion, $localidad, $provincia, $telefono, $mail, $horario,
                        $codDeposi, $mailGrupEmp,
                        $habilitado, $tango, $vtex, $retiroExp, $habilitadoCont,
                        $dashboardBi, $sucMadre, $empFerreteria,
                        $nroSucMadre,
                        $lunes, $martes, $miercoles, $jueves, $viernes, $sabado, $domingo,
                        $nro
                    ];
                }
                $stmt = sqlsrv_query($this->cid_locales, $sql, $params);
                if ($stmt === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }
            } else {
                $sql = "INSERT INTO SUCURSALES_LAKERS (
                        NRO_SUCURSAL, COD_CLIENT, DESC_SUCURSAL, CANAL, TIPO_LOCAL,
                        DIRECCION, LOCALIDAD, PROVINCIA, TELEFONO, MAIL, HORARIO,
                        COD_DEPOSI, MAIL_GRUP_EMP,
                        HABILITADO, TANGO, INTEGRA_VTEX, RETIRO_EXPRES, HABILITADO_CONT,
                        DASHBOARD_BI, SUC_MADRE, EMPRESA_FERRETERIA, FACTURA_ELECT,
                        NRO_SUC_MADRE, FECHA_CIERRE,
                        LUNES, MARTES, MIERCOLES, JUEVES, VIERNES, SABADO, DOMINGO,
                        INTEGRACION_MERCADOPAGO_MADRE, INTEGRACION_MERCADOPAGO_HIJA,
                        LAPOS_INTEGRADO, LAPOS_MODELO, VERSION_SOFT_LAPOS, CABLE_LAPOS_INTEGRADO,
                        LINK_PEDIDO, LINK_SALES,
                        BASE_NOMBRE, N_LLAVE_TANGO, CONEXION_DNS, CONEXION_DNS_ALT,
                        USUARIO_DNS, CLAVE_DNS,
                        CONEXION_TEAMVIEWER_MADRE, CONEXION_TEAMVIEWER_HIJA, CLAVE_TEAMVIEWER, USUARIO_PC_MADRE
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?,
                        ?, ?, ?, ?,
                        ?, ?,
                        ?, ?, ?, ?,
                        ?, ?,
                        ?, ?, ?, ?
                    )";
                $params = [
                    $nro, $codClient, $descSucursal, $canal, $tipoLocal,
                    $direccion, $localidad, $provincia, $telefono, $mail, $horario,
                    $codDeposi, $mailGrupEmp,
                    $habilitado, $tango, $vtex, $retiroExp, $habilitadoCont,
                    $dashboardBi, $sucMadre, $empFerreteria, ($esAdminSistemas ? $facturaElect : 0),
                    $nroSucMadre, ($esAdminSistemas ? $fechaCierre : null),
                    $lunes, $martes, $miercoles, $jueves, $viernes, $sabado, $domingo,
                    ($esAdminSistemas ? $mpMadre : 0), ($esAdminSistemas ? $mpHija : 0),
                    ($esAdminSistemas ? $laposIntegrado : 0),
                    ($esAdminSistemas ? $laposModelo : null),
                    ($esAdminSistemas ? $versionSoftLapos : null),
                    ($esAdminSistemas ? $cableLapos : null),
                    ($esAdminSistemas ? $linkPedido : null),
                    ($esAdminSistemas ? $linkSales : null),
                    ($esAdminSistemas ? $baseNombre : null),
                    ($esAdminSistemas ? $nLlaveTango : null),
                    ($esAdminSistemas ? $conexionDns : null),
                    ($esAdminSistemas ? $conexionDnsAlt : null),
                    ($esAdminSistemas ? $usuarioDns : null),
                    ($esAdminSistemas ? $claveDns : null),
                    ($esAdminSistemas ? $tvMadre : null),
                    ($esAdminSistemas ? $tvHija : null),
                    ($esAdminSistemas ? $claveTv : null),
                    ($esAdminSistemas ? $usuarioPcMadre : null)
                ];
                $stmt = sqlsrv_query($this->cid_locales, $sql, $params);
                if ($stmt === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }
            }

            $syncWarning = $this->syncPuntoDeVenta($nro, $descSucursal, (bool)$habilitado);

            $datosNuevos = $this->getSucursalById($nro);
            $clonarDe = !empty($meta['clonar_de']) ? (int)$meta['clonar_de'] : 0;
            if ($esEdicion) {
                $accionHist = 'EDICION';
                $descExtra = null;
            } elseif ($clonarDe > 0) {
                $accionHist = 'CLONACION';
                $descExtra = "desde sucursal #{$clonarDe}";
            } else {
                $accionHist = 'ALTA';
                $descExtra = null;
            }
            $histWarning = $this->registrarHistorial(
                $nro,
                $accionHist,
                $datosAnteriores,
                $datosNuevos,
                $meta['usuario'] ?? null,
                $descExtra
            );

            $warnings = array_filter([$syncWarning, $histWarning]);
            return ['warning' => !empty($warnings) ? implode(' ', $warnings) : null];
        } catch (Exception $e) {
            error_log("Error en guardarSucursal: " . $e->getMessage());
            throw $e;
        }
    }
}
