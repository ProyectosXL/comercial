<?php
/**
 * Clase ImportarArticulos
 * Migración de la herramienta de importación de artículos para etiquetas de Python a PHP.
 * Conexión a LAKER_SA (central).
 */
class ImportarArticulos {
    private $conexion;

    public function __construct() {
        if (file_exists(__DIR__ . '/../../Class/Conexion.php')) {
            require_once __DIR__ . '/../../Class/Conexion.php';
        } elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        } else {
            require_once 'W:/comercial/Class/Conexion.php';
        }
        $this->conexion = new Conexion();
    }

    private function getConn() {
        return $this->conexion->conectar('central');
    }

    public function validarArticulo($articulo) {
        $articulo = strtoupper(trim((string)$articulo));
        if ($articulo === '') return 'ERROR';

        $conn = $this->getConn();
        if (!$conn) return 'ERROR';

        // 1. Verificar en STA11 si es kit / promo
        $sqlSta11 = "SELECT PROMO_MENU, DESCRIPCIO FROM STA11 WITH (NOLOCK) WHERE COD_ARTICU LIKE ?";
        $stmtSta11 = sqlsrv_query($conn, $sqlSta11, [$articulo . '%']);
        if (!$stmtSta11) return 'ERROR';

        $rowSta11 = sqlsrv_fetch_array($stmtSta11, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtSta11);

        if (!$rowSta11) return 'ERROR';

        $esKit = trim($rowSta11['PROMO_MENU'] ?? '');
        $descSta11 = trim($rowSta11['DESCRIPCIO'] ?? '');

        // 2. Buscar en SJ_ETIQUETAS_FINAL
        if ($esKit === 'P') {
            $sqlEtiq = "SELECT COALESCE(
                            (SELECT TOP 1 DESCRIPCIO FROM SJ_ETIQUETAS_FINAL WITH (NOLOCK) WHERE COD_ARTICU = ?),
                            'ERROR'
                        ) AS RESULT";
            $stmtEtiq = sqlsrv_query($conn, $sqlEtiq, [$articulo]);
        } else {
            $prefix = substr($articulo, 0, 11);
            $sqlEtiq = "SELECT COALESCE(
                            (SELECT TOP 1 DESCRIPCIO FROM SJ_ETIQUETAS_FINAL WITH (NOLOCK) WHERE COD_ARTICU LIKE ?),
                            'ERROR'
                        ) AS RESULT";
            $stmtEtiq = sqlsrv_query($conn, $sqlEtiq, [$prefix . '%']);
        }

        if ($stmtEtiq) {
            $rowEtiq = sqlsrv_fetch_array($stmtEtiq, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmtEtiq);
            $res = trim($rowEtiq['RESULT'] ?? 'ERROR');
            if ($res !== 'ERROR') {
                return $res;
            }
        }

        // Fallback a descripción de STA11 si existe
        return ($descSta11 !== '') ? $descSta11 : 'ERROR';
    }

    public function limpiarTablaEtiquetas() {
        $conn = $this->getConn();
        if (!$conn) throw new Exception("No se pudo conectar a la base de datos");
        sqlsrv_query($conn, "DELETE FROM SJ_T_ETIQUETAS_FINAL");
        return true;
    }

    public function cargarArticulos($articulos) {
        $conn = $this->getConn();
        if (!$conn) throw new Exception("No se pudo conectar a la base de datos");

        $this->limpiarTablaEtiquetas();

        $sql = "INSERT INTO SJ_T_ETIQUETAS_FINAL (COD_ARTICU, DESCRIPCIO) VALUES (?, ?)";
        $insertados = 0;

        foreach ($articulos as $item) {
            $art  = strtoupper(trim((string)($item['articulo'] ?? $item[0] ?? '')));
            $desc = trim((string)($item['descripcion'] ?? $item[1] ?? ''));
            if ($art === '') continue;

            $stmt = sqlsrv_query($conn, $sql, [$art, $desc]);
            if ($stmt) {
                $insertados++;
            }
        }

        return $insertados;
    }

    public function getArticulosCargados() {
        $conn = $this->getConn();
        if (!$conn) return [];

        $sql = "SELECT COD_ARTICU, DESCRIPCIO FROM SJ_T_ETIQUETAS_FINAL WITH (NOLOCK) ORDER BY COD_ARTICU ASC";
        $stmt = sqlsrv_query($conn, $sql);
        $lista = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $lista[] = [
                    'articulo'    => trim($row['COD_ARTICU'] ?? ''),
                    'descripcion' => trim($row['DESCRIPCIO'] ?? '')
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $lista;
    }
}
