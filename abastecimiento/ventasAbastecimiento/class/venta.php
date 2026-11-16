
<?php

class Venta
{

    private function formatearParaIn($valor, $esTipoSucursal = false) {
        if (empty($valor) || $valor === '%') {
            return '';
        }
        
        if (is_array($valor)) {
            $items = $valor;
        } else {
            $valorLimpio = trim($valor);
            if ($valorLimpio === '' || $valorLimpio === '%') {
                return '';
            }
            $items = str_getcsv($valorLimpio, ',', "'");
        }
        
        $limpios = [];
        foreach ($items as $item) {
            $item = trim($item, " \t\n\r\0\x0B'\"");
            if ($item !== '' && $item !== '%') {
                if ($esTipoSucursal) {
                    $itemUpper = strtoupper($item);
                    if ($itemUpper === 'PROPIOS' || $itemUpper === 'LOCALES PROPIOS') {
                        $item = 'LOCALES PROPIOS';
                    } elseif ($itemUpper === 'FRANQUICIAS' || $itemUpper === 'FRANQUICIA') {
                        $item = 'FRANQUICIAS';
                    } elseif ($itemUpper === 'FRANQUICIAS_GA' || $itemUpper === 'FRANQUICIAS GA') {
                        $item = 'FRANQUICIAS GA';
                    }
                }
                $itemEscaped = str_replace("'", "''", $item);
                $limpios[] = "'$itemEscaped'";
            }
        }
        
        return empty($limpios) ? '' : implode(',', $limpios);
    }

    /**
     * Ejecuta la consulta de ventas, opcionalmente desagregada por día y filtrada por búsqueda.
     */
    private function ejecutarConsultaVentas($rubro, $destino, $temporada, $desde, $hasta, $tipoSucursal = '', $porDia = false, $search = '')
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $conexionPath = !empty($_SERVER['DOCUMENT_ROOT'])
            ? $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php'
            : dirname(__DIR__, 3) . '/Class/Conexion.php';
        require_once $conexionPath;

        $conexion = new Conexion();
        $esCentral = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central');
        $cid_central = $conexion->conectar($esCentral ? 'power' : 'power_uy');

        if ($cid_central === false) {
            throw new Exception("Error al conectar a la base de datos: " . print_r(sqlsrv_errors(), true));
        }

        $maestroTable = $esCentral
            ? '[XL-LAKERBIS].[LOCALES_LAKERS].[dbo].[MAESTRO_DESTINOS]'
            : '[XL-LAKERBIS].[SUCURSALES_URUGUAY].[dbo].[MAESTRO_DESTINOS]';

        $desdeEscaped = str_replace("'", "''", (string)$desde);
        $hastaEscaped = str_replace("'", "''", (string)$hasta);
        $temporadaEscaped = str_replace("'", "''", (string)$temporada);

        $selectFecha = $porDia ? 'CAST(FECHA_VENTA AS DATE) AS FECHA_VENTA, ' : '';
        $groupFecha = $porDia ? 'CAST(FECHA_VENTA AS DATE), ' : '';
        $orderBy = $porDia ? ' ORDER BY CAST(FECHA_VENTA AS DATE) ASC, DESC_SUCURSAL ASC, ARTICULO ASC' : '';

        $sql = "SELECT {$selectFecha}DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, SUM(CANT_VEND) CANT_VEND, RUBRO, CATEGORIA, CATEGORIA_PADRE, TEMPORADA, DESTINO, LIQUIDACION, PRECIO FROM
         (
         SELECT V.FECHA_VENTA, V.DESC_SUCURSAL, V.CANAL, V.ARTICULO, V.DESCRIPCION, V.CANT_VEND, V.RUBRO, V.CATEGORIA, V.CATEGORIA_PADRE, V.TEMPORADA, V.DESTINO, M.LIQUIDACION, V.PRECIO
         FROM dbo.RO_VENTAS_COMERCIAL V
         LEFT JOIN $maestroTable M ON V.ARTICULO COLLATE Latin1_General_BIN = M.COD_ARTICU COLLATE Latin1_General_BIN
         WHERE V.FECHA_VENTA BETWEEN '$desdeEscaped' AND '$hastaEscaped' 
         AND V.TEMPORADA LIKE '%$temporadaEscaped'";

        $destinoIn = $this->formatearParaIn($destino);
        if ($destinoIn !== '') {
            $sql .= " AND V.DESTINO IN ($destinoIn) ";
        }

        $rubroIn = $this->formatearParaIn($rubro);
        if ($rubroIn !== '') {
            $sql .= " AND V.RUBRO IN ($rubroIn) ";
        }

        // Filtro por tipo de sucursal basado en la columna CANAL
        $tipoSucursalIn = $this->formatearParaIn($tipoSucursal, true);
        if ($tipoSucursalIn !== '') {
            $sql .= " AND V.CANAL IN ($tipoSucursalIn) ";
        }

        $searchTrimmed = trim((string)$search);
        if ($searchTrimmed !== '') {
            $searchEscaped = str_replace("'", "''", $searchTrimmed);
            $sql .= " AND (
                V.DESC_SUCURSAL LIKE '%$searchEscaped%'
                OR V.CANAL LIKE '%$searchEscaped%'
                OR V.ARTICULO LIKE '%$searchEscaped%'
                OR V.DESCRIPCION LIKE '%$searchEscaped%'
                OR V.RUBRO LIKE '%$searchEscaped%'
                OR V.CATEGORIA_PADRE LIKE '%$searchEscaped%'
                OR V.CATEGORIA LIKE '%$searchEscaped%'
                OR V.TEMPORADA LIKE '%$searchEscaped%'
                OR V.DESTINO LIKE '%$searchEscaped%'
                OR M.LIQUIDACION LIKE '%$searchEscaped%'
            ) ";
        }

        $sql .= "
                ) A
                GROUP BY {$groupFecha}DESC_SUCURSAL, CANAL, ARTICULO, DESCRIPCION, RUBRO, CATEGORIA, CATEGORIA_PADRE, TEMPORADA, DESTINO, LIQUIDACION, PRECIO{$orderBy}
        ";

        $stmt = sqlsrv_query($cid_central, $sql);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log("SQL Error: " . print_r($errors, true));
            error_log("SQL Query: " . $sql);
            throw new Exception("Error en la consulta SQL: " . print_r($errors, true));
        }

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Renombrar CANAL a TIPO_SUCURSAL para compatibilidad con el frontend
            $v['TIPO_SUCURSAL'] = $v['CANAL'];
            $rows[] = $v;
        }

        sqlsrv_free_stmt($stmt);

        return $rows;
    }

    public function traerVentas($rubro, $destino, $temporada, $desde, $hasta, $tipoSucursal = '')
    {
        return $this->ejecutarConsultaVentas($rubro, $destino, $temporada, $desde, $hasta, $tipoSucursal, false, '');
    }

    /**
     * Trae las ventas agrupadas por día, sucursal y artículo para la exportación Excel Día x Día.
     */
    public function traerVentasPorDia($rubro, $destino, $temporada, $desde, $hasta, $tipoSucursal = '', $search = '')
    {
        return $this->ejecutarConsultaVentas($rubro, $destino, $temporada, $desde, $hasta, $tipoSucursal, true, $search);
    }

}    
