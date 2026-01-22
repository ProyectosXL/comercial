<?php

class Dashboard
{
    private $cid;
    private $cid_central;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
    } 

    private function retornarArray($sqlEnviado){
        $sql = $sqlEnviado;
        $stmt = sqlsrv_query($this->cid_central, $sql);
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;  
    }

    /**
     * Obtiene datos del dashboard con nuevos filtros de fecha relativa
     * 
     * @param string $periodo Período a consultar ('mes_actual', 'mes_anterior', '4_semanas', 'anio_actual', 'anio_anterior', 'custom')
     * @param string $supervisora Filtro por supervisora (opcional)
     * @param string $concepto Filtro por concepto (opcional)
     * @param string $fechaDesde Fecha desde (para período custom)
     * @param string $fechaHasta Fecha hasta (para período custom)
     * @return array Datos de gastos para el dashboard
     */
    public function obtenerDatosDashboard($periodo = 'mes_actual', $supervisora = '', $concepto = '', $fechaDesde = '', $fechaHasta = '') {
        // Determinar el rango de fechas según el período seleccionado
        if ($periodo !== 'custom') {
            $fechaActual = new DateTime();
            
            switch ($periodo) {
                case 'mes_actual':
                    $fechaDesde = $fechaActual->format('Y-m-01'); // Primer día del mes actual
                    $fechaHasta = $fechaActual->format('Y-m-t');  // Último día del mes actual
                    break;
                    
                case 'mes_anterior':
                    $fechaActual->modify('first day of last month');
                    $fechaDesde = $fechaActual->format('Y-m-01'); // Primer día del mes anterior
                    $fechaHasta = $fechaActual->format('Y-m-t');  // Último día del mes anterior
                    break;
                    
                case '4_semanas':
                    $fechaHasta = (new DateTime())->format('Y-m-d');
                    $fechaInicio = new DateTime();
                    $fechaInicio->modify('-4 weeks');
                    $fechaDesde = $fechaInicio->format('Y-m-d');
                    break;
                    
                case 'anio_actual':
                    $fechaDesde = $fechaActual->format('Y-01-01'); // 1 de enero del año actual
                    $fechaHasta = $fechaActual->format('Y-12-31'); // 31 de diciembre del año actual
                    break;
                    
                case 'anio_anterior':
                    $anioAnterior = $fechaActual->format('Y') - 1;
                    $fechaDesde = $anioAnterior . '-01-01'; // 1 de enero del año anterior
                    $fechaHasta = $anioAnterior . '-12-31'; // 31 de diciembre del año anterior
                    break;
                    
                default:
                    // Fallback a mes actual si el período no es reconocido
                    $fechaDesde = $fechaActual->format('Y-m-01');
                    $fechaHasta = $fechaActual->format('Y-m-t');
                    break;
            }
        } else {
            // Para período personalizado, validar las fechas proporcionadas
            if (empty($fechaDesde) || empty($fechaHasta)) {
                throw new Exception("Para período personalizado se requieren fechaDesde y fechaHasta");
            }
            
            // Validar formato de fechas
            $fechaDesdeObj = DateTime::createFromFormat('Y-m-d', $fechaDesde);
            $fechaHastaObj = DateTime::createFromFormat('Y-m-d', $fechaHasta);
            
            if (!$fechaDesdeObj || !$fechaHastaObj) {
                throw new Exception("Formato de fecha inválido. Use YYYY-MM-DD");
            }
            
            // Verificar que fecha desde sea menor que fecha hasta
            if ($fechaDesdeObj > $fechaHastaObj) {
                throw new Exception("La fecha desde debe ser menor o igual a la fecha hasta");
            }
        }
        
       
        // Construir la consulta SQL base usando FECHA_MODIF
        $sql = "SELECT g.ID_CONC, g.SEMANA, g.ANIO, g.SUPERVISORA, c.CONCEPTO, 
                ROUND(g.IMPORTE, 0) as IMPORTE, ROUND(g.IMPORTE_TARJETA, 0) as IMPORTE_TARJETA,
                g.FECHA_MODIF,
                MONTH(g.FECHA_MODIF) as MES,
                YEAR(g.FECHA_MODIF) as ANIO_FECHA
                FROM RO_T_GASTOS_SUPERVISION g
                JOIN RO_T_CONCEPTOS_GASTOS_SUP c ON g.ID_CONC = c.ID_CONC
                WHERE g.ESTADO = 1";
        
        $params = [];
        
        // Filtro por rango de fechas usando FECHA_MODIF
        $sql .= " AND CAST(g.FECHA_MODIF AS DATE) >= ?";
        $params[] = $fechaDesde;
        
        $sql .= " AND CAST(g.FECHA_MODIF AS DATE) <= ?";
        $params[] = $fechaHasta;
        
        // Filtro por supervisora
        if (!empty($supervisora)) {
            $sql .= " AND g.SUPERVISORA = ?";
            $params[] = $supervisora;
        }
        
        // Filtro por concepto
        if (!empty($concepto)) {
            $sql .= " AND c.CONCEPTO = ?";
            $params[] = $concepto;
        }
        
        // Ordenar por fecha de modificación
        $sql .= " ORDER BY g.FECHA_MODIF DESC";
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            $error = print_r(sqlsrv_errors(), true);
            error_log("Error SQL en obtenerDatosDashboard: " . $error);
            throw new Exception("Error al obtener datos para el dashboard: " . $error);
        }
        
        $datos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear la fecha si es un objeto DateTime
            if ($row['FECHA_MODIF'] instanceof DateTime) {
                $row['FECHA_MODIF'] = $row['FECHA_MODIF']->format('Y-m-d H:i:s');
            }
            $datos[] = $row;
        }
        
        return $datos;
    }
    
    public function obtenerListaSupervisoras() {
        $sql = "SELECT DISTINCT SUPERVISORA FROM RO_T_GASTOS_SUPERVISION WHERE ESTADO = 1 ORDER BY SUPERVISORA";
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener lista de supervisoras: " . print_r(sqlsrv_errors(), true));
        }
        
        $supervisoras = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $supervisoras[] = $row['SUPERVISORA'];
        }
        
        return $supervisoras;
    }
    
    public function obtenerListaConceptos() {
        $sql = "SELECT DISTINCT CONCEPTO FROM RO_T_CONCEPTOS_GASTOS_SUP ORDER BY CONCEPTO";
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener lista de conceptos: " . print_r(sqlsrv_errors(), true));
        }
        
        $conceptos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $conceptos[] = $row['CONCEPTO'];
        }
        
        return $conceptos;
    }

    /**
     * Método auxiliar para obtener datos por rango de fechas usando FECHA_MODIF
     * Actualización del método obtenerDatosPorRangoSemanas para usar fechas
     * 
     * @param string $fechaDesde Fecha desde
     * @param string $fechaHasta Fecha hasta  
     * @param string $supervisora Filtro por supervisora (opcional)
     * @param string $concepto Filtro por concepto (opcional)
     * @return array Datos de gastos para el rango especificado
     */
    public function obtenerDatosPorRangoFechas($fechaDesde, $fechaHasta, $supervisora = '', $concepto = '') {
        // Construir la consulta SQL base usando FECHA_MODIF
        $sql = "SELECT g.ID_CONC, g.SEMANA, g.ANIO, g.SUPERVISORA, c.CONCEPTO, 
                ROUND(g.IMPORTE, 0) as IMPORTE, ROUND(g.IMPORTE_TARJETA, 0) as IMPORTE_TARJETA,
                g.FECHA_MODIF,
                MONTH(g.FECHA_MODIF) as MES,
                YEAR(g.FECHA_MODIF) as ANIO_FECHA
                FROM RO_T_GASTOS_SUPERVISION g
                JOIN RO_T_CONCEPTOS_GASTOS_SUP c ON g.ID_CONC = c.ID_CONC
                WHERE g.ESTADO = 1";
        
        $params = [];
        
        // Filtro por rango de fechas
        $sql .= " AND CAST(g.FECHA_MODIF AS DATE) >= ?";
        $params[] = $fechaDesde;
        
        $sql .= " AND CAST(g.FECHA_MODIF AS DATE) <= ?";
        $params[] = $fechaHasta;
        
        // Filtro por supervisora
        if ($supervisora) {
            $sql .= " AND g.SUPERVISORA = ?";
            $params[] = $supervisora;
        }
        
        // Filtro por concepto
        if ($concepto) {
            $sql .= " AND c.CONCEPTO = ?";
            $params[] = $concepto;
        }
        
        // Ordenar por fecha de modificación
        $sql .= " ORDER BY g.FECHA_MODIF DESC";
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener datos por rango de fechas: " . print_r(sqlsrv_errors(), true));
        }
        
        $datos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear la fecha si es un objeto DateTime
            if ($row['FECHA_MODIF'] instanceof DateTime) {
                $row['FECHA_MODIF'] = $row['FECHA_MODIF']->format('Y-m-d H:i:s');
            }
            $datos[] = $row;
        }
        
        return $datos;
    }

    /**
     * Obtiene datos de presupuesto vs gastos usando criterio de fechas consistente
     * Este método complementa la vista para usar el mismo criterio de fechas que el dashboard de gastos
     * 
     * @param string $fechaDesde Fecha desde
     * @param string $fechaHasta Fecha hasta
     * @param string $supervisora Filtro por supervisora (opcional)
     * @param string $concepto Filtro por concepto (opcional)
     * @return array Datos de presupuesto vs gastos
     */
    public function obtenerDatosPresupuestoConFechas($fechaDesde, $fechaHasta, $supervisora = '', $concepto = '') {
        // Obtener mes y año del rango de fechas
        $fechaDesdeObj = new DateTime($fechaDesde);
        $fechaHastaObj = new DateTime($fechaHasta);
        
        $mesDesde = $fechaDesdeObj->format('n');
        $anioDesde = $fechaDesdeObj->format('Y');
        $mesHasta = $fechaHastaObj->format('n');
        $anioHasta = $fechaHastaObj->format('Y');
        
        // Query que combina la vista de presupuesto con filtro por FECHA_MODIF en gastos
        $sql = "WITH GASTOS_FILTRADOS AS (
                    SELECT 
                        MONTH(FECHA_MODIF) AS MES,
                        YEAR(FECHA_MODIF) AS ANIO,
                        SUPERVISORA,
                        ID_CONC,
                        ISNULL(SUM(IMPORTE), 0) + ISNULL(SUM(IMPORTE_TARJETA), 0) AS IMPORTE_GASTO
                    FROM RO_T_GASTOS_SUPERVISION
                    WHERE ESTADO = 1
                    AND CAST(FECHA_MODIF AS DATE) >= ?
                    AND CAST(FECHA_MODIF AS DATE) <= ?";
        
        $params = [$fechaDesde, $fechaHasta];
        
        // Aplicar filtros adicionales en la subconsulta de gastos
        if ($supervisora) {
            $sql .= " AND SUPERVISORA = ?";
            $params[] = $supervisora;
        }
        
        $sql .= " GROUP BY MONTH(FECHA_MODIF), YEAR(FECHA_MODIF), SUPERVISORA, ID_CONC
                ),
                PRESUPUESTOS AS (
                    SELECT 
                        A.MES,
                        A.ANIO,
                        B.NOMBRE AS SUPERVISORA,
                        A.ID_CONC,
                        CAST(A.IMP_PRESUPUESTO AS FLOAT) AS IMP_PRESUPUESTO
                    FROM RO_T_PRESUPUESTOS_SUPERVISION A
                    INNER JOIN XL-LAKERBIS.LOCALES_LAKERS.DBO.RO_T_SUPERVISORAS_COMERCIAL B 
                        ON A.ID_SUPERVISORA = B.ID
                    WHERE ((A.ANIO = ? AND A.MES >= ?) OR (A.ANIO > ?))
                    AND ((A.ANIO = ? AND A.MES <= ?) OR (A.ANIO < ?))";
        
        // Agregar parámetros para el filtro de presupuesto por fecha
        $params[] = $anioDesde;
        $params[] = $mesDesde;
        $params[] = $anioDesde;
        $params[] = $anioHasta;
        $params[] = $mesHasta;
        $params[] = $anioHasta;
        
        // Filtro por supervisora en presupuesto
        if ($supervisora) {
            $sql .= " AND B.NOMBRE = ?";
            $params[] = $supervisora;
        }
        
        $sql .= ")
                SELECT 
                    P.MES,
                    P.ANIO,
                    P.SUPERVISORA,
                    C.CONCEPTO,
                    ISNULL(G.IMPORTE_GASTO, 0) AS IMPORTE_GASTO,
                    P.IMP_PRESUPUESTO,
                    (P.IMP_PRESUPUESTO - ISNULL(G.IMPORTE_GASTO, 0)) AS SALDO_PRESPUESTO
                FROM PRESUPUESTOS P
                LEFT JOIN GASTOS_FILTRADOS G 
                    ON P.MES = G.MES
                    AND P.ANIO = G.ANIO
                    AND P.SUPERVISORA = G.SUPERVISORA
                    AND P.ID_CONC = G.ID_CONC
                INNER JOIN RO_T_CONCEPTOS_GASTOS_SUP C 
                    ON P.ID_CONC = C.ID_CONC";
        
        // Filtro por concepto
        if ($concepto) {
            $sql .= " WHERE C.CONCEPTO = ?";
            $params[] = $concepto;
        }
        
        $sql .= " ORDER BY P.ANIO DESC, P.MES DESC, P.SUPERVISORA, C.CONCEPTO";
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error al obtener datos de presupuesto con fechas: " . print_r(sqlsrv_errors(), true));
        }
        
        $datos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }
        
        return $datos;
    }

}