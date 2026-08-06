<?php
// Acceso a datos y armado del dataset de la Matriz para Reposición UY.
// Combina tres fuentes independientes (SP base, categoría, ventas 7 días) y arma
// el dataset final en PHP, ya normalizado y separado en Panel / Packaging.

require_once __DIR__ . '/../config.php';
require_once REPO_CONEXION_PATH;

class Reposicion
{
    /**
     * Arma la matriz completa: dataset base + categoría + ventas 7 días,
     * mergeado, normalizado y separado en solapas Panel / Packaging.
     */
    public function obtenerMatriz()
    {
        set_time_limit(REPO_SCRIPT_TIME_LIMIT);

        $datasetBase = $this->obtenerDatasetBase();
        $categorias = $this->obtenerCategorias();
        $ventas7d = $this->obtenerVentas7Dias();

        $panel = [];
        $packaging = [];

        foreach ($datasetBase as $fila) {
            $normalizada = $this->normalizarFila($fila, $categorias, $ventas7d);

            if (strtoupper(trim($normalizada['RUBRO'])) === REPO_RUBRO_PACKAGING) {
                $packaging[] = $normalizada;
            } else {
                $panel[] = $normalizada;
            }
        }

        return [
            'generado' => date('d/m/Y H:i'),
            'data_panel' => $panel,
            'data_packaging' => $packaging,
            'filtros' => $this->calcularFiltros($panel),
        ];
    }

    /**
     * Fuente 1: dataset base. Ejecuta el SP que ya trae el universo correcto de
     * artículos (stock/venta por sucursal, depósito 82 ya filtrado por el SP).
     * El SP ejecuta internamente EXECs remotos, así que puede devolver uno o más
     * result sets vacíos antes del bueno: hay que buscar el que tenga metadata.
     */
    private function obtenerDatasetBase()
    {
        $cid = (new Conexion())->conectar('uy');

        if ($cid === false) {
            throw new Exception('No se pudo conectar a la base de datos (uy).');
        }

        $sql = 'SET NOCOUNT ON; EXEC ' . REPO_SP_DATASET_BASE . ';';

        $stmt = sqlsrv_query($cid, $sql, [], ['QueryTimeout' => REPO_SP_QUERY_TIMEOUT]);

        if ($stmt === false) {
            error_log('Reposicion::obtenerDatasetBase - error SP: ' . print_r(sqlsrv_errors(), true));
            throw new Exception('Error al ejecutar el stored procedure de reposición.');
        }

        // Buscar el result set que tenga columnas (metadata de campos)
        do {
            $numCampos = sqlsrv_num_fields($stmt);
            if ($numCampos !== false && $numCampos > 0) {
                break;
            }
        } while (sqlsrv_next_result($stmt) === true);

        if ($numCampos === false || $numCampos === 0) {
            throw new Exception('El stored procedure no devolvió resultados.');
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Fuente 2: categoría por artículo. Indexado por COD_ARTICU (trim) para el merge.
     */
    private function obtenerCategorias()
    {
        $cid = (new Conexion())->conectar('uy');

        if ($cid === false) {
            throw new Exception('No se pudo conectar a la base de datos (uy).');
        }

        $sql = 'SELECT COD_ARTICU, CATEGORIA FROM ' . REPO_TABLA_CATEGORIA . ' WITH (NOLOCK)';

        $stmt = sqlsrv_query($cid, $sql);

        if ($stmt === false) {
            error_log('Reposicion::obtenerCategorias - error: ' . print_r(sqlsrv_errors(), true));
            throw new Exception('Error al consultar las categorías de artículos.');
        }

        $categorias = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $cod = trim((string) $row['COD_ARTICU']);
            $categorias[$cod] = (string) ($row['CATEGORIA'] ?? '');
        }

        return $categorias;
    }

    /**
     * Fuente 3: venta de los últimos N días por sucursal (7 días cerrados, sin hoy).
     * Pivotea NUEVOCENTRO/MONTEVIDEO/TRES CRUCES a VTA_7D_NC/MV/3C, indexado por artículo.
     */
    private function obtenerVentas7Dias()
    {
        $cid = (new Conexion())->conectar('power_uy');

        if ($cid === false) {
            throw new Exception('No se pudo conectar a la base de datos (power_uy).');
        }

        $sucursales = array_keys(REPO_SUCURSALES);
        $listaSucursales = "'" . implode("','", $sucursales) . "'";

        $sql = "SELECT ARTICULO, DESC_SUCURSAL, SUM(CANT_VEND) AS CANT_VEND
                FROM " . REPO_TABLA_VENTAS . " WITH (NOLOCK)
                WHERE CAST(FECHA_VENTA AS DATE) >= CAST(DATEADD(DAY, -" . REPO_DIAS_VENTANA_VENTA . ", GETDATE()) AS DATE)
                  AND CAST(FECHA_VENTA AS DATE) <= CAST(DATEADD(DAY, -1, GETDATE()) AS DATE)
                  AND DESC_SUCURSAL IN (" . $listaSucursales . ")
                GROUP BY ARTICULO, DESC_SUCURSAL";

        $stmt = sqlsrv_query($cid, $sql);

        if ($stmt === false) {
            error_log('Reposicion::obtenerVentas7Dias - error: ' . print_r(sqlsrv_errors(), true));
            throw new Exception('Error al consultar las ventas de los últimos días.');
        }

        $ventas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $cod = trim((string) $row['ARTICULO']);
            $sucursal = trim((string) $row['DESC_SUCURSAL']);
            $sufijo = REPO_SUCURSALES[$sucursal] ?? null;

            if ($sufijo === null) {
                continue;
            }

            if (!isset($ventas[$cod])) {
                $ventas[$cod] = ['NC' => 0, 'MV' => 0, '3C' => 0];
            }

            $ventas[$cod][$sufijo] = (int) ($row['CANT_VEND'] ?? 0);
        }

        return $ventas;
    }

    /**
     * Normaliza una fila del dataset base al esquema final de 20 columnas.
     * El dataset base manda: nunca se agregan ni se pierden filas acá.
     */
    private function normalizarFila($fila, $categorias, $ventas7d)
    {
        $cod = trim((string) ($fila['COD_ARTICU'] ?? ''));
        $categoria = $categorias[$cod] ?? '';
        $v7 = $ventas7d[$cod] ?? ['NC' => 0, 'MV' => 0, '3C' => 0];

        return [
            'COD_ARTICU' => $cod,
            'CATEGORIA' => (string) $categoria,
            'DESCRIPCIO' => (string) ($fila['DESCRIPCIO'] ?? ''),
            'RUBRO' => (string) ($fila['RUBRO'] ?? ''),
            'DESTINO' => (string) ($fila['DESTINO'] ?? ''),
            'TEMPORADA' => (string) ($fila['TEMPORADA'] ?? ''),
            'PRECIO' => (float) ($fila['PRECIO'] ?? 0),
            'STOCK_CENTRAL' => (int) ($fila['STOCK_CENTRAL'] ?? 0),
            'STOCK_NC' => (int) ($fila['STOCK_NC'] ?? 0),
            'VENTA_NC' => (int) ($fila['VENTA_NC'] ?? 0),
            'REPO_NC' => '',
            'STOCK_3C' => (int) ($fila['STOCK_3C'] ?? 0),
            'VENTA_3C' => (int) ($fila['VENTA_3C'] ?? 0),
            'REPO_3C' => '',
            'STOCK_MV' => (int) ($fila['STOCK_MV'] ?? 0),
            'VENTA_MV' => (int) ($fila['VENTA_MV'] ?? 0),
            'REPO_MV' => '',
            'VTA_7D_NC' => (int) ($v7['NC'] ?? 0),
            'VTA_7D_MV' => (int) ($v7['MV'] ?? 0),
            'VTA_7D_3C' => (int) ($v7['3C'] ?? 0),
        ];
    }

    /**
     * Valores distintos ordenados alfabéticamente, sólo sobre la solapa Panel,
     * para alimentar los cuatro Select2 de filtro.
     */
    private function calcularFiltros($panel)
    {
        $campos = [
            'RUBRO' => 'rubros',
            'CATEGORIA' => 'categorias',
            'DESTINO' => 'destinos',
            'TEMPORADA' => 'temporadas',
        ];

        $filtros = [];

        foreach ($campos as $campo => $clave) {
            $valores = [];
            foreach ($panel as $fila) {
                $valor = trim((string) $fila[$campo]);
                $valores[$valor === '' ? REPO_ETIQUETA_EN_BLANCO : $valor] = true;
            }
            $lista = array_keys($valores);
            sort($lista, SORT_STRING | SORT_FLAG_CASE);
            $filtros[$clave] = $lista;
        }

        return $filtros;
    }
}
