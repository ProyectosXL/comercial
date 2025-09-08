<?php
/**
 * Clase FranquiciasSQLServer - Gestión de franquicias con SQL Server
 * 
 * Esta clase maneja todas las operaciones relacionadas con franquicias,
 * usando la nueva base de datos SQL Server en lugar de SQLite.
 */

require_once 'DatabaseSQLServer.php';

class FranquiciasSQLServer {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseSQLServer::getInstance();
    }
    
    /**
     * Obtener todas las franquicias activas
     */
    public function obtenerFranquiciasActivas() {
        $sql = "SELECT * FROM franquicias_sistema WHERE estado = 'activo' ORDER BY nombre";
        return $this->db->obtenerFilas($sql);
    }
    
    /**
     * Obtener franquicia por ID
     */
    public function obtenerFranquiciaPorId($id) {
        $sql = "SELECT * FROM franquicias_sistema WHERE id = ? AND estado = 'activo'";
        return $this->db->obtenerFila($sql, [$id]);
    }
    
    /**
     * Obtener franquicia por código
     */
    public function obtenerFranquiciaPorCodigo($codigo) {
        $sql = "SELECT * FROM franquicias_sistema WHERE codigo = ? AND estado = 'activo'";
        return $this->db->obtenerFila($sql, [$codigo]);
    }
    
    /**
     * Crear nueva franquicia
     */
    public function crearFranquicia($datos) {
        $sql = "INSERT INTO franquicias_sistema (codigo, nombre, direccion, telefono, email, responsable, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'activo')";
        
        $params = [
            $datos['codigo'],
            $datos['nombre'],
            $datos['direccion'] ?? '',
            $datos['telefono'] ?? '',
            $datos['email'] ?? '',
            $datos['responsable'] ?? ''
        ];
        
        return $this->db->ejecutarConsulta($sql, $params);
    }
    
    /**
     * Actualizar franquicia
     */
    public function actualizarFranquicia($id, $datos) {
        $sql = "UPDATE franquicias_sistema 
                SET nombre = ?, direccion = ?, telefono = ?, email = ?, responsable = ?, fecha_modificacion = GETDATE()
                WHERE id = ?";
        
        $params = [
            $datos['nombre'],
            $datos['direccion'] ?? '',
            $datos['telefono'] ?? '',
            $datos['email'] ?? '',
            $datos['responsable'] ?? '',
            $id
        ];
        
        return $this->db->ejecutarConsulta($sql, $params);
    }
    
    /**
     * Desactivar franquicia
     */
    public function desactivarFranquicia($id) {
        $sql = "UPDATE franquicias_sistema SET estado = 'inactivo', fecha_modificacion = GETDATE() WHERE id = ?";
        return $this->db->ejecutarConsulta($sql, [$id]);
    }
    
    // === MÉTODOS DE STOCK ===
    
    /**
     * Obtener stock actual de un artículo en una franquicia
     */
    public function obtenerStockArticulo($franquiciaId, $codigoArticulo) {
        return $this->db->obtenerStockActual($franquiciaId, $codigoArticulo);
    }
    
    /**
     * Registrar movimiento de stock
     */
    public function registrarMovimientoStock($datos) {
        // Validar datos requeridos
        $camposRequeridos = ['franquicia_id', 'codigo_articulo', 'movimiento', 'tipo_movimiento'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo])) {
                throw new Exception("Campo requerido faltante: $campo");
            }
        }
        
        // Obtener stock anterior
        $stockAnterior = $this->obtenerStockArticulo($datos['franquicia_id'], $datos['codigo_articulo']);
        
        // Calcular stock actual
        $stockActual = $stockAnterior + $datos['movimiento'];
        
        // Validar que no quede stock negativo (excepto para ajustes)
        if ($stockActual < 0 && $datos['tipo_movimiento'] !== 'AJUSTE') {
            throw new Exception("No hay suficiente stock. Stock actual: $stockAnterior, Movimiento: {$datos['movimiento']}");
        }
        
        // Preparar datos para insertar
        $datosMovimiento = [
            'franquicia_id' => $datos['franquicia_id'],
            'codigo_articulo' => $datos['codigo_articulo'],
            'descripcion_articulo' => $datos['descripcion_articulo'] ?? '',
            'stock_anterior' => $stockAnterior,
            'movimiento' => $datos['movimiento'],
            'stock_actual' => $stockActual,
            'tipo_movimiento' => $datos['tipo_movimiento'],
            'observaciones' => $datos['observaciones'] ?? '',
            'usuario' => $datos['usuario'] ?? 'sistema'
        ];
        
        return $this->db->registrarMovimientoStock($datosMovimiento);
    }
    
    /**
     * Obtener historial de movimientos de una franquicia
     */
    public function obtenerHistorialMovimientos($franquiciaId, $fechaInicio = null, $fechaFin = null, $limite = 100) {
        $sql = "SELECT";
        
        if ($limite) {
            $sql .= " TOP $limite";
        }
        
        $sql .= " hs.*, f.nombre as franquicia_nombre 
                FROM franquicia_historial_stock hs 
                INNER JOIN franquicias_sistema f ON hs.franquicia_id = f.id 
                WHERE hs.franquicia_id = ?";
        
        $params = [$franquiciaId];
        
        if ($fechaInicio) {
            $sql .= " AND hs.fecha_movimiento >= ?";
            $params[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $sql .= " AND hs.fecha_movimiento <= ?";
            $params[] = $fechaFin;
        }
        
        $sql .= " ORDER BY hs.fecha_movimiento DESC";
        
        return $this->db->obtenerFilas($sql, $params);
    }
    
    /**
     * Obtener resumen de stock por franquicia
     */
    public function obtenerResumenStock($franquiciaId, $periodo = null) {
        if ($periodo) {
            // Convertir periodo MM-YYYY a filtro de fecha  
            list($mes, $año) = explode('-', $periodo);
            
            $sql = "SELECT 
                        rubro,
                        categoria,
                        stock_actual,
                        fecha_movimiento as ultima_actualizacion
                    FROM franquicia_historial_stock hs1
                    WHERE hs1.franquicia_id = ?
                    AND MONTH(hs1.fecha_movimiento) = ?
                    AND YEAR(hs1.fecha_movimiento) = ?
                    AND hs1.fecha_movimiento = (
                        SELECT MAX(hs2.fecha_movimiento) 
                        FROM franquicia_historial_stock hs2 
                        WHERE hs2.franquicia_id = hs1.franquicia_id 
                        AND hs2.rubro = hs1.rubro 
                        AND hs2.categoria = hs1.categoria
                        AND MONTH(hs2.fecha_movimiento) = ?
                        AND YEAR(hs2.fecha_movimiento) = ?
                    )
                    ORDER BY rubro, categoria";
                    
            $resultados = $this->db->obtenerFilas($sql, [$franquiciaId, $mes, $año, $mes, $año]);
        } else {
            // Sin filtro de período - tomar toda la historia
            $sql = "SELECT 
                        rubro,
                        categoria,
                        stock_actual,
                        fecha_movimiento as ultima_actualizacion
                    FROM franquicia_historial_stock hs1
                    WHERE hs1.franquicia_id = ?
                    AND hs1.fecha_movimiento = (
                        SELECT MAX(hs2.fecha_movimiento) 
                        FROM franquicia_historial_stock hs2 
                        WHERE hs2.franquicia_id = hs1.franquicia_id 
                        AND hs2.rubro = hs1.rubro 
                        AND hs2.categoria = hs1.categoria
                    )
                    ORDER BY rubro, categoria";
                    
            $resultados = $this->db->obtenerFilas($sql, [$franquiciaId]);
        }
        
        // Transformar para agregar campos de compatibilidad
        $resultadosTransformados = [];
        foreach ($resultados as $item) {
            $item['codigo_articulo'] = $item['rubro'] . '_' . $item['categoria'];
            $item['descripcion_articulo'] = $item['categoria'] . ' - ' . $item['rubro'];
            $resultadosTransformados[] = $item;
        }
        
        return $resultadosTransformados;
    }
    
    /**
     * Obtener artículos con stock bajo (menos de un mínimo)
     */
    public function obtenerStockBajo($franquiciaId, $stockMinimo = 5) {
        $resumenStock = $this->obtenerResumenStock($franquiciaId);
        
        return array_filter($resumenStock, function($item) use ($stockMinimo) {
            return $item['stock_actual'] < $stockMinimo;
        });
    }
    
    /**
     * Obtener estadísticas de movimientos por período
     */
    public function obtenerEstadisticasMovimientos($franquiciaId, $fechaInicio, $fechaFin) {
        $sql = "SELECT 
                    tipo_movimiento,
                    COUNT(*) as total_movimientos,
                    SUM(CASE WHEN movimiento > 0 THEN movimiento ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN movimiento < 0 THEN ABS(movimiento) ELSE 0 END) as total_salidas
                FROM franquicia_historial_stock 
                WHERE franquicia_id = ? 
                AND fecha_movimiento BETWEEN ? AND ?
                GROUP BY tipo_movimiento
                ORDER BY total_movimientos DESC";
        
        return $this->db->obtenerFilas($sql, [$franquiciaId, $fechaInicio, $fechaFin]);
    }
    
    /**
     * Validar existencia de franquicia
     */
    public function validarFranquicia($franquiciaId) {
        $franquicia = $this->obtenerFranquiciaPorId($franquiciaId);
        return $franquicia !== false;
    }
    
    /**
     * Obtener franquicia por sesión (para compatibilidad)
     */
    public function obtenerFranquiciaPorSesion() {
        if (!isset($_SESSION['franquicia_id'])) {
            throw new Exception("No hay franquicia en sesión");
        }
        
        return $this->obtenerFranquiciaPorId($_SESSION['franquicia_id']);
    }
}
?>
