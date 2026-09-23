<?php
/**
 * Clase AltaMuestras
 * Migración de la lógica de Alta de Muestras de Artículos de Python a PHP.
 * Conexión a LAKER_SA (central).
 */
class AltaMuestras {
    private $conexion;
    const PATRON_STOCK_INSUFICIENTE = 'La Cantidad en stock no es suficiente';

    public function __construct() {
        if (file_exists(__DIR__ . '/../../../Class/Conexion.php')) {
            require_once __DIR__ . '/../../../Class/Conexion.php';
        } elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        } else {
            require_once 'W:/comercial/Class/Conexion.php';
        }
        $this->conexion = new Conexion();
    }

    private function getConnCentral() {
        return $this->conexion->conectar('central');
    }

    public function truncarTablas() {
        $conn = $this->getConnCentral();
        if (!$conn) throw new Exception("No se pudo conectar a la base central");

        sqlsrv_query($conn, "TRUNCATE TABLE temAjusteRecodificacion");
        sqlsrv_query($conn, "TRUNCATE TABLE EB_ProcResults");
        return true;
    }

    public function insertarFilas($filas) {
        $conn = $this->getConnCentral();
        if (!$conn) throw new Exception("No se pudo conectar a la base central");

        $sql = "INSERT INTO temAjusteRecodificacion (CodigoUbicacion, CodigoArticulo, CantidadAjuste)
                VALUES (?, ?, ?)";

        foreach ($filas as $f) {
            $params = [$f[0], $f[1], (int)$f[2]];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if (!$stmt) {
                $err = sqlsrv_errors();
                throw new Exception("Error al insertar fila en temAjusteRecodificacion: " . print_r($err, true));
            }
        }
        return true;
    }

    private function obtenerFilasConError($conn) {
        $sql = "SELECT CodigoUbicacion, CodigoArticulo, CantidadAjuste, Resultado 
                FROM temAjusteRecodificacion 
                WHERE Resultado IS NOT NULL";
        $stmt = sqlsrv_query($conn, $sql);
        $filas = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $filas[] = [
                    'CodigoUbicacion' => trim($r['CodigoUbicacion'] ?? ''),
                    'CodigoArticulo'  => trim($r['CodigoArticulo'] ?? ''),
                    'CantidadAjuste'  => (int)($r['CantidadAjuste'] ?? 0),
                    'Resultado'       => trim($r['Resultado'] ?? '')
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $filas;
    }

    private function obtenerFilasPendientes($conn) {
        $sql = "SELECT CodigoUbicacion, CodigoArticulo, CantidadAjuste 
                FROM temAjusteRecodificacion";
        $stmt = sqlsrv_query($conn, $sql);
        $filas = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $filas[] = [
                    'CodigoUbicacion' => trim($r['CodigoUbicacion'] ?? ''),
                    'CodigoArticulo'  => trim($r['CodigoArticulo'] ?? ''),
                    'CantidadAjuste'  => (int)($r['CantidadAjuste'] ?? 0)
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $filas;
    }

    private function eliminarFilasStockInsuficiente($conn) {
        $sql = "DELETE FROM temAjusteRecodificacion WHERE Resultado LIKE ?";
        $param = ['%' . self::PATRON_STOCK_INSUFICIENTE . '%'];
        sqlsrv_query($conn, $sql, $param);
    }

    private function ejecutarSpUnaVez($conn) {
        $sql = "{CALL [EB_RecodificacionV1.2]}";
        $stmt = sqlsrv_query($conn, $sql);

        if (!$stmt) {
            $filasConError = $this->obtenerFilasConError($conn);
            if (empty($filasConError)) {
                $err = sqlsrv_errors();
                throw new Exception("Error al ejecutar [EB_RecodificacionV1.2]: " . print_r($err, true));
            }
            return [false, [], $filasConError];
        }

        // Consultar resultados del proceso
        $sqlProc = "SELECT * FROM EB_ProcResults";
        $stmtProc = sqlsrv_query($conn, $sqlProc);
        $mensajesProceso = [];
        if ($stmtProc) {
            while ($r = sqlsrv_fetch_array($stmtProc, SQLSRV_FETCH_ASSOC)) {
                $mensajesProceso[] = $r;
            }
            sqlsrv_free_stmt($stmtProc);
        }

        return [true, $mensajesProceso, []];
    }

    public function ejecutarAjuste() {
        $conn = $this->getConnCentral();
        if (!$conn) throw new Exception("No se pudo conectar a la base central");

        list($exito, $mensajesProceso, $filasError) = $this->ejecutarSpUnaVez($conn);

        if ($exito) {
            return [
                'ejecutado'        => true,
                'mensajes_proceso' => $mensajesProceso,
                'resultado_filas'  => [],
                'filas_procesadas' => $this->obtenerFilasPendientes($conn),
                'filas_omitidas'   => [],
            ];
        }

        $erroresBloqueantes = [];
        $filasStockInsuficiente = [];

        foreach ($filasError as $f) {
            if (strpos($f['Resultado'], self::PATRON_STOCK_INSUFICIENTE) !== false) {
                $filasStockInsuficiente[] = $f;
            } else {
                $erroresBloqueantes[] = $f;
            }
        }

        if (!empty($erroresBloqueantes)) {
            return [
                'ejecutado'        => false,
                'mensajes_proceso' => [],
                'resultado_filas'  => $filasError,
                'filas_procesadas' => [],
                'filas_omitidas'   => [],
            ];
        }

        // Reintento omitiendo filas con stock insuficiente
        $this->eliminarFilasStockInsuficiente($conn);
        $pendientes = $this->obtenerFilasPendientes($conn);

        if (empty($pendientes)) {
            return [
                'ejecutado'        => false,
                'mensajes_proceso' => [],
                'resultado_filas'  => [],
                'filas_procesadas' => [],
                'filas_omitidas'   => $filasStockInsuficiente,
            ];
        }

        list($exito2, $mensajesProceso2, $filasError2) = $this->ejecutarSpUnaVez($conn);

        if (!$exito2) {
            return [
                'ejecutado'        => false,
                'mensajes_proceso' => [],
                'resultado_filas'  => $filasError2,
                'filas_procesadas' => [],
                'filas_omitidas'   => [],
            ];
        }

        return [
            'ejecutado'        => true,
            'mensajes_proceso' => $mensajesProceso2,
            'resultado_filas'  => [],
            'filas_procesadas' => $this->obtenerFilasPendientes($conn),
            'filas_omitidas'   => $filasStockInsuficiente,
        ];
    }
}
