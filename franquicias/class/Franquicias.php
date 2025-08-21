<?php
/**
 * Clase Franquicias - Gestión de franquicias y su stock
 * 
 * Esta clase maneja todas las operaciones relacionadas con franquicias,
 * incluyendo la gestión de stock, reportes y validaciones.
 */

require_once 'Database.php';

class Franquicias {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Inicializar tablas si no existen
        $this->db->initializeTables();
    }
    
    /**
     * Obtener todas las franquicias activas
     */
    public function obtenerFranquiciasActivas() {
        $query = "SELECT * FROM franquicias WHERE activa = 1 ORDER BY nombre";
        return $this->db->select($query);
    }
    
    /**
     * Obtener franquicia por ID
     */
    public function obtenerFranquiciaPorId($id) {
        $query = "SELECT * FROM franquicias WHERE id = :id AND activa = 1";
        return $this->db->selectOne($query, [':id' => $id]);
    }
    
    /**
     * Obtener franquicia por usuario
     */
    public function obtenerFranquiciaPorUsuario($usuarioId) {
        $query = "SELECT * FROM franquicias WHERE usuario_id = :usuario_id AND activa = 1";
        return $this->db->selectOne($query, [':usuario_id' => $usuarioId]);
    }
    
    /**
     * Crear nueva franquicia
     */
    public function crearFranquicia($datos) {
        $query = "INSERT INTO franquicias (nombre, ciudad, usuario_id, activa) 
                  VALUES (:nombre, :ciudad, :usuario_id, :activa)";
        
        $params = [
            ':nombre' => $datos['nombre'],
            ':ciudad' => $datos['ciudad'] ?? null,
            ':usuario_id' => $datos['usuario_id'] ?? null,
            ':activa' => $datos['activa'] ?? 1
        ];
        
        return $this->db->insert($query, $params);
    }
    
    /**
     * Actualizar franquicia
     */
    public function actualizarFranquicia($id, $datos) {
        $query = "UPDATE franquicias 
                  SET nombre = :nombre, ciudad = :ciudad, usuario_id = :usuario_id 
                  WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':nombre' => $datos['nombre'],
            ':ciudad' => $datos['ciudad'] ?? null,
            ':usuario_id' => $datos['usuario_id'] ?? null
        ];
        
        return $this->db->update($query, $params);
    }
    
    /**
     * Desactivar franquicia
     */
    public function desactivarFranquicia($id) {
        $query = "UPDATE franquicias SET activa = 0 WHERE id = :id";
        return $this->db->update($query, [':id' => $id]);
    }
    
    /**
     * Obtener rubros disponibles desde la tabla maestra
     */
    public function obtenerRubros() {
        $query = "SELECT DISTINCT RUBRO 
                  FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                  WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                  ORDER BY RUBRO";
        
        return $this->db->select($query);
    }
    
    /**
     * Obtener categorías por rubro
     */
    public function obtenerCategoriasPorRubro($rubro) {
        $query = "SELECT DISTINCT CATEGORIA_PADRE 
                  FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                  WHERE RUBRO = :rubro 
                  AND CATEGORIA_PADRE != '' 
                  AND RUBRO NOT LIKE '%OUTLET'
                  ORDER BY CATEGORIA_PADRE";
        
        return $this->db->select($query, [':rubro' => $rubro]);
    }
    
    /**
     * Guardar stock de franquicia
     */
    public function guardarStock($franquiciaId, $periodo, $stockData) {
        try {
            $this->db->beginTransaction();
            
            // Eliminar registros existentes del período y franquicia
            $deleteQuery = "DELETE FROM franquicia_stock 
                           WHERE franquicia_id = :franquicia_id AND periodo = :periodo";
            $this->db->delete($deleteQuery, [
                ':franquicia_id' => $franquiciaId,
                ':periodo' => $periodo
            ]);
            
            // Insertar nuevos registros
            $insertQuery = "INSERT INTO franquicia_stock 
                           (franquicia_id, rubro, categoria, cantidad, periodo) 
                           VALUES (:franquicia_id, :rubro, :categoria, :cantidad, :periodo)";
            
            foreach ($stockData as $item) {
                $this->db->insert($insertQuery, [
                    ':franquicia_id' => $franquiciaId,
                    ':rubro' => $item['rubro'],
                    ':categoria' => $item['categoria'],
                    ':cantidad' => $item['cantidad'],
                    ':periodo' => $periodo
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error al guardar stock: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener stock por franquicia y período
     */
    public function obtenerStockPorPeriodo($franquiciaId, $periodo) {
        $query = "SELECT * FROM franquicia_stock 
                  WHERE franquicia_id = :franquicia_id AND periodo = :periodo 
                  ORDER BY rubro, categoria";
        
        return $this->db->select($query, [
            ':franquicia_id' => $franquiciaId,
            ':periodo' => $periodo
        ]);
    }
    
    /**
     * Obtener historial de stock de una franquicia
     */
    public function obtenerHistorialStock($franquiciaId, $limite = 12) {
        $query = "SELECT periodo, COUNT(*) as total_registros, 
                         MAX(fecha_actualizacion) as ultima_actualizacion,
                         SUM(cantidad) as total_stock
                  FROM franquicia_stock 
                  WHERE franquicia_id = :franquicia_id 
                  GROUP BY periodo 
                  ORDER BY periodo DESC 
                  LIMIT :limite";
        
        return $this->db->select($query, [
            ':franquicia_id' => $franquiciaId,
            ':limite' => $limite
        ]);
    }
    
    /**
     * Obtener resumen de cumplimiento por período
     */
    public function obtenerResumenCumplimiento($periodo) {
        $query = "SELECT 
                    f.id as franquicia_id,
                    f.nombre as franquicia_nombre,
                    f.ciudad,
                    CASE 
                        WHEN COUNT(fs.id) > 0 THEN 'INFORMÓ'
                        ELSE 'NO INFORMÓ'
                    END as estado,
                    COUNT(fs.id) as total_registros,
                    MAX(fs.fecha_actualizacion) as ultima_actualizacion
                  FROM franquicias f
                  LEFT JOIN franquicia_stock fs ON f.id = fs.franquicia_id 
                                                 AND fs.periodo = :periodo
                  WHERE f.activa = 1
                  GROUP BY f.id, f.nombre, f.ciudad
                  ORDER BY f.nombre";
        
        return $this->db->select($query, [':periodo' => $periodo]);
    }
    
    /**
     * Obtener estadísticas generales de un período
     */
    public function obtenerEstadisticas($periodo) {
        $query = "SELECT 
                    COUNT(DISTINCT f.id) as total_franquicias,
                    COUNT(DISTINCT CASE WHEN fs.id IS NOT NULL THEN f.id END) as franquicias_informaron,
                    COUNT(DISTINCT CASE WHEN fs.id IS NULL THEN f.id END) as franquicias_no_informaron,
                    ROUND((COUNT(DISTINCT CASE WHEN fs.id IS NOT NULL THEN f.id END) * 100.0 / COUNT(DISTINCT f.id)), 2) as porcentaje_cumplimiento
                  FROM franquicias f
                  LEFT JOIN franquicia_stock fs ON f.id = fs.franquicia_id 
                                                 AND fs.periodo = :periodo
                  WHERE f.activa = 1";
        
        return $this->db->selectOne($query, [':periodo' => $periodo]);
    }
    
    /**
     * Obtener stock consolidado por rubro y categoría
     */
    public function obtenerStockConsolidado($periodo) {
        $query = "SELECT 
                    rubro,
                    categoria,
                    COUNT(DISTINCT franquicia_id) as franquicias_reportan,
                    SUM(cantidad) as cantidad_total,
                    AVG(cantidad) as promedio_cantidad,
                    MIN(cantidad) as cantidad_minima,
                    MAX(cantidad) as cantidad_maxima
                  FROM franquicia_stock 
                  WHERE periodo = :periodo
                  GROUP BY rubro, categoria
                  ORDER BY rubro, categoria";
        
        return $this->db->select($query, [':periodo' => $periodo]);
    }
    
    /**
     * Obtener períodos disponibles
     */
    public function obtenerPeriodosDisponibles($limite = 12) {
        $query = "SELECT DISTINCT periodo 
                  FROM franquicia_stock 
                  ORDER BY periodo DESC 
                  LIMIT :limite";
        
        $result = $this->db->select($query, [':limite' => $limite]);
        return array_column($result, 'periodo');
    }
    
    /**
     * Verificar si un período puede ser editado (solo mes actual)
     */
    public function puedeEditarPeriodo($periodo) {
        $periodoActual = date('m-Y');
        return $periodo === $periodoActual;
    }
    
    /**
     * Obtener detalle completo de stock por franquicia
     */
    public function obtenerDetalleStockFranquicia($franquiciaId, $periodo) {
        $query = "SELECT 
                    fs.*,
                    f.nombre as franquicia_nombre,
                    f.ciudad as franquicia_ciudad
                  FROM franquicia_stock fs
                  INNER JOIN franquicias f ON fs.franquicia_id = f.id
                  WHERE fs.franquicia_id = :franquicia_id AND fs.periodo = :periodo
                  ORDER BY fs.rubro, fs.categoria";
        
        return $this->db->select($query, [
            ':franquicia_id' => $franquiciaId,
            ':periodo' => $periodo
        ]);
    }
    
    /**
     * Validar datos de stock antes de guardar
     */
    public function validarDatosStock($stockData) {
        $errores = [];
        
        foreach ($stockData as $index => $item) {
            // Validar que exista rubro
            if (empty($item['rubro'])) {
                $errores[] = "Rubro requerido en ítem $index";
            }
            
            // Validar que exista categoría
            if (empty($item['categoria'])) {
                $errores[] = "Categoría requerida en ítem $index";
            }
            
            // Validar cantidad
            if (!isset($item['cantidad']) || !is_numeric($item['cantidad']) || $item['cantidad'] < 0) {
                $errores[] = "Cantidad inválida en ítem $index";
            }
            
            // Validar que la categoría pertenezca al rubro
            if (!empty($item['rubro']) && !empty($item['categoria'])) {
                $categorias = $this->obtenerCategoriasPorRubro($item['rubro']);
                $categoriasValidas = array_column($categorias, 'CATEGORIA_PADRE');
                
                if (!in_array($item['categoria'], $categoriasValidas)) {
                    $errores[] = "La categoría '{$item['categoria']}' no pertenece al rubro '{$item['rubro']}'";
                }
            }
        }
        
        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
    
    /**
     * Generar reporte de cumplimiento para exportar
     */
    public function generarReporteCumplimiento($periodo) {
        $datos = $this->obtenerResumenCumplimiento($periodo);
        $estadisticas = $this->obtenerEstadisticas($periodo);
        
        return [
            'datos' => $datos,
            'estadisticas' => $estadisticas,
            'periodo' => $periodo,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generar reporte de stock consolidado para exportar
     */
    public function generarReporteStockConsolidado($periodo) {
        $datos = $this->obtenerStockConsolidado($periodo);
        
        return [
            'datos' => $datos,
            'periodo' => $periodo,
            'total_rubros' => count(array_unique(array_column($datos, 'rubro'))),
            'total_categorias' => count($datos),
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Limpiar datos antiguos (opcional - configurar según política de retención)
     */
    public function limpiarDatosAntiguos($mesesAntiguedad = 24) {
        try {
            // Calcular fecha límite
            $fechaLimite = date('m-Y', strtotime("-$mesesAntiguedad months"));
            
            $query = "DELETE FROM franquicia_stock WHERE periodo < :fecha_limite";
            $registrosEliminados = $this->db->delete($query, [':fecha_limite' => $fechaLimite]);
            
            return [
                'success' => true,
                'registros_eliminados' => $registrosEliminados,
                'fecha_limite' => $fechaLimite
            ];
            
        } catch (Exception $e) {
            error_log("Error al limpiar datos antiguos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener resumen de actividad por franquicia
     */
    public function obtenerResumenActividad($franquiciaId, $ultimosMeses = 6) {
        $query = "SELECT 
                    periodo,
                    COUNT(*) as categorias_cargadas,
                    COUNT(DISTINCT rubro) as rubros_cargados,
                    SUM(cantidad) as total_stock,
                    fecha_actualizacion
                  FROM franquicia_stock 
                  WHERE franquicia_id = :franquicia_id 
                  AND periodo >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL :meses MONTH), '%m-%Y')
                  GROUP BY periodo 
                  ORDER BY periodo DESC";
        
        return $this->db->select($query, [
            ':franquicia_id' => $franquiciaId,
            ':meses' => $ultimosMeses
        ]);
    }
    
    /**
     * Verificar integridad de datos
     */
    public function verificarIntegridad() {
        $problemas = [];
        
        try {
            // Verificar franquicias sin usuario asignado
            $franquiciasSinUsuario = $this->db->select(
                "SELECT id, nombre FROM franquicias WHERE usuario_id IS NULL AND activa = 1"
            );
            
            if (!empty($franquiciasSinUsuario)) {
                $problemas[] = [
                    'tipo' => 'franquicias_sin_usuario',
                    'cantidad' => count($franquiciasSinUsuario),
                    'datos' => $franquiciasSinUsuario
                ];
            }
            
            // Verificar stock con cantidades negativas
            $stockNegativo = $this->db->select(
                "SELECT fs.*, f.nombre as franquicia_nombre 
                 FROM franquicia_stock fs 
                 JOIN franquicias f ON fs.franquicia_id = f.id 
                 WHERE fs.cantidad < 0"
            );
            
            if (!empty($stockNegativo)) {
                $problemas[] = [
                    'tipo' => 'stock_negativo',
                    'cantidad' => count($stockNegativo),
                    'datos' => $stockNegativo
                ];
            }
            
            // Verificar registros huérfanos
            $stockHuerfano = $this->db->select(
                "SELECT fs.* FROM franquicia_stock fs 
                 LEFT JOIN franquicias f ON fs.franquicia_id = f.id 
                 WHERE f.id IS NULL"
            );
            
            if (!empty($stockHuerfano)) {
                $problemas[] = [
                    'tipo' => 'stock_huerfano',
                    'cantidad' => count($stockHuerfano),
                    'datos' => $stockHuerfano
                ];
            }
            
            return [
                'integridad_ok' => empty($problemas),
                'problemas' => $problemas,
                'fecha_verificacion' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Error en verificación de integridad: " . $e->getMessage());
            return [
                'integridad_ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas de uso del sistema
     */
    public function obtenerEstadisticasUso() {
        try {
            $stats = [];
            
            // Total de franquicias
            $stats['total_franquicias'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM franquicias WHERE activa = 1"
            )['total'];
            
            // Total de registros de stock
            $stats['total_registros_stock'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM franquicia_stock"
            )['total'];
            
            // Períodos con datos
            $stats['periodos_con_datos'] = $this->db->selectOne(
                "SELECT COUNT(DISTINCT periodo) as total FROM franquicia_stock"
            )['total'];
            
            // Franquicia más activa
            $franquiciaMasActiva = $this->db->selectOne(
                "SELECT f.nombre, COUNT(*) as registros 
                 FROM franquicia_stock fs 
                 JOIN franquicias f ON fs.franquicia_id = f.id 
                 GROUP BY fs.franquicia_id 
                 ORDER BY registros DESC 
                 LIMIT 1"
            );
            $stats['franquicia_mas_activa'] = $franquiciaMasActiva;
            
            // Rubro con más movimiento
            $rubroMasActivo = $this->db->selectOne(
                "SELECT rubro, COUNT(*) as registros 
                 FROM franquicia_stock 
                 GROUP BY rubro 
                 ORDER BY registros DESC 
                 LIMIT 1"
            );
            $stats['rubro_mas_activo'] = $rubroMasActivo;
            
            // Último registro
            $ultimoRegistro = $this->db->selectOne(
                "SELECT MAX(fecha_actualizacion) as ultima_actualizacion 
                 FROM franquicia_stock"
            );
            $stats['ultima_actualizacion'] = $ultimoRegistro['ultima_actualizacion'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas de uso: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Backup de datos de stock
     */
    public function generarBackupStock($periodo = null) {
        try {
            $whereClause = $periodo ? "WHERE periodo = :periodo" : "";
            $params = $periodo ? [':periodo' => $periodo] : [];
            
            $query = "SELECT 
                        fs.*,
                        f.nombre as franquicia_nombre,
                        f.ciudad as franquicia_ciudad
                      FROM franquicia_stock fs
                      JOIN franquicias f ON fs.franquicia_id = f.id
                      $whereClause
                      ORDER BY fs.periodo DESC, f.nombre, fs.rubro, fs.categoria";
            
            $datos = $this->db->select($query, $params);
            
            return [
                'success' => true,
                'datos' => $datos,
                'total_registros' => count($datos),
                'periodo' => $periodo,
                'fecha_backup' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Error al generar backup: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Importar datos desde backup
     */
    public function importarBackupStock($datosBackup) {
        try {
            $this->db->beginTransaction();
            
            $insertQuery = "INSERT INTO franquicia_stock 
                           (franquicia_id, rubro, categoria, cantidad, periodo, fecha_actualizacion) 
                           VALUES (:franquicia_id, :rubro, :categoria, :cantidad, :periodo, :fecha_actualizacion)
                           ON DUPLICATE KEY UPDATE 
                           cantidad = VALUES(cantidad), 
                           fecha_actualizacion = VALUES(fecha_actualizacion)";
            
            $registrosImportados = 0;
            
            foreach ($datosBackup as $registro) {
                $this->db->execute($insertQuery, [
                    ':franquicia_id' => $registro['franquicia_id'],
                    ':rubro' => $registro['rubro'],
                    ':categoria' => $registro['categoria'],
                    ':cantidad' => $registro['cantidad'],
                    ':periodo' => $registro['periodo'],
                    ':fecha_actualizacion' => $registro['fecha_actualizacion']
                ]);
                $registrosImportados++;
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'registros_importados' => $registrosImportados
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error al importar backup: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener log de actividades recientes
     */
    public function obtenerLogActividades($limite = 50) {
        $query = "SELECT 
                    fs.periodo,
                    fs.fecha_actualizacion,
                    f.nombre as franquicia_nombre,
                    COUNT(*) as categorias_actualizadas,
                    SUM(fs.cantidad) as total_stock
                  FROM franquicia_stock fs
                  JOIN franquicias f ON fs.franquicia_id = f.id
                  WHERE fs.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY fs.franquicia_id, fs.periodo, DATE(fs.fecha_actualizacion)
                  ORDER BY fs.fecha_actualizacion DESC
                  LIMIT :limite";
        
        return $this->db->select($query, [':limite' => $limite]);
    }
}