
<?php

require_once 'class/Articulo.php';

class ArticuloController
{
    private $articulo;

    public function __construct()
    {
        $this->articulo = new Articulo();
    }

    /**
     * Consulta el stock de un artículo según el tipo especificado
     * 
     * @param string $codigoArticulo Código del artículo a consultar
     * @param string $tipo Tipo de consulta (Central, Locales, Franquicias, Todos)
     * @return array Array con los resultados del stock
     * @throws Exception Si hay errores en la consulta
     */
    public function consultarStock($codigoArticulo, $tipo = 'Todos')
    {
        // Validar entrada
        if (empty(trim($codigoArticulo))) {
            throw new Exception('El código de artículo es requerido');
        }

        // Limpiar y formatear código
        $codigoArticulo = strtoupper(trim($codigoArticulo));
        
        // Validar tipo de consulta
        $tiposValidos = ['Central', 'Locales', 'Franquicias', 'Todos'];
        if (!in_array($tipo, $tiposValidos)) {
            $tipo = 'Todos';
        }

        try {
            return $this->articulo->consultarStockPorTipo($codigoArticulo, $tipo);
        } catch (Exception $e) {
            // Log del error (si tienes sistema de logs)
            error_log("Error en consultarStock: " . $e->getMessage());
            throw new Exception('Error al consultar el stock del artículo');
        }
    }

    /**
     * Obtiene los tipos de consulta disponibles
     * 
     * @return array Array con los tipos disponibles
     */
    public function getTiposConsulta()
    {
        return [
            'Central' => [
                'label' => 'Central',
                'icon' => 'fas fa-building',
                'description' => 'Stock en depósito central'
            ],
            'Locales' => [
                'label' => 'Locales',
                'icon' => 'fas fa-store',
                'description' => 'Stock en locales propios'
            ],
            'Franquicias' => [
                'label' => 'Franquicias',
                'icon' => 'fas fa-handshake',
                'description' => 'Stock en franquicias'
            ],
            'Todos' => [
                'label' => 'Todos',
                'icon' => 'fas fa-globe',
                'description' => 'Stock en todas las ubicaciones'
            ]
        ];
    }

    /**
     * Valida el formato del código de artículo
     * 
     * @param string $codigo Código a validar
     * @return bool True si es válido
     */
    public function validarCodigoArticulo($codigo)
    {
        // Agregar validaciones específicas según el formato de códigos de tu sistema
        $codigo = trim($codigo);
        
        if (empty($codigo)) {
            return false;
        }

        // Ejemplo: código debe tener entre 3 y 20 caracteres alfanuméricos
        if (!preg_match('/^[A-Za-z0-9\-_]{3,20}$/', $codigo)) {
            return false;
        }

        return true;
    }

    /**
     * Formatea los resultados para mostrar en la vista
     * 
     * @param array $resultados Resultados raw de la base de datos
     * @return array Resultados formateados
     */
    public function formatearResultados($resultados)
    {
        if (empty($resultados)) {
            return [];
        }

        return array_map(function($item) {
            return [
                'UBICACION' => $item['UBICACION'] ?? '',
                'COD_ARTICU' => $item['COD_ARTICU'] ?? '',
                'DESCRIPCIO' => $item['DESCRIPCIO'] ?? '',
                'COD_DEPOSI' => $item['COD_DEPOSI'] ?? '',
                'DESC_DEPOSI' => $item['DESC_DEPOSI'] ?? $item['COD_DEPOSI'] ?? '',
                'CANT_STOCK' => (int)($item['CANT_STOCK'] ?? 0),
                'CANT_COMP' => (int)($item['CANT_COMP'] ?? 0),
                'CANT_DISP' => (int)($item['CANT_DISP'] ?? 0),
                'PRECIO' => (float)($item['PRECIO'] ?? 0),
                'DESTINO' => $item['DESTINO'] ?? '',
                'FAMILIA' => $item['FAMILIA'] ?? '',
                'TEMPORADA' => $item['TEMPORADA'] ?? ''
            ];
        }, $resultados);
    }

    /**
     * Obtiene estadísticas de los resultados
     * 
     * @param array $resultados Resultados del stock
     * @return array Estadísticas calculadas
     */
    public function obtenerEstadisticas($resultados)
    {
        if (empty($resultados)) {
            return [
                'total_registros' => 0,
                'total_stock' => 0,
                'total_disponible' => 0,
                'total_comprometido' => 0,
                'ubicaciones_unicas' => 0,
                'depositos_unicos' => 0
            ];
        }

        $totalStock = array_sum(array_column($resultados, 'CANT_STOCK'));
        $totalDisponible = array_sum(array_column($resultados, 'CANT_DISP'));
        $totalComprometido = array_sum(array_column($resultados, 'CANT_COMP'));
        
        $ubicacionesUnicas = count(array_unique(array_column($resultados, 'UBICACION')));
        $depositosUnicos = count(array_unique(array_column($resultados, 'COD_DEPOSI')));

        return [
            'total_registros' => count($resultados),
            'total_stock' => $totalStock,
            'total_disponible' => $totalDisponible,
            'total_comprometido' => $totalComprometido,
            'ubicaciones_unicas' => $ubicacionesUnicas,
            'depositos_unicos' => $depositosUnicos
        ];
    }
}