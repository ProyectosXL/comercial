<?php

require_once __DIR__ . '/../../../Class/Conexion.php';
require_once __DIR__ . '/../../../Class/classEnv.php';

class Bancarias
{
    private $conn;

    /**
     * @param string $entorno  'central' (Argentina) o 'uy' (Uruguay)
     */
    public function __construct(string $entorno = 'central')
    {
        $entornosValidos = ['central', 'uy'];
        if (!in_array($entorno, $entornosValidos, true)) {
            $entorno = 'central';
        }

        $conexion   = new Conexion();
        $this->conn = $conexion->conectar($entorno);

        if (!$this->conn) {
            throw new RuntimeException('No se pudo establecer conexión con la base de datos.');
        }
    }

    /**
     * Ejecuta el SP de promociones bancarias VTEX y retorna los datos.
     *
     * @param  string $fechaDesde  Fecha de inicio en formato Y-m-d
     * @param  string $fechaHasta  Fecha de fin en formato Y-m-d
     * @return array               Arreglo de filas asociativas
     * @throws RuntimeException    Si el SP falla
     */
    public function getBancarias(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "SELECT
                    FECHA, ANIO, NRO_MES, NRO_DIA,
                    CANAL, BANCO, DESC_PROMO, PORC_REINTEGRO,
                    PEDIDOS_TOTAL_DIA, VTA_TOTAL_DIA,
                    PEDIDOS_CON_PROMO, VTA_PROMO, TKT_CON_PROMO, PORC_2DO_CON_PROMO,
                    PEDIDOS_SIN_PROMO, TKT_SIN_PROMO, PORC_2DO_SIN_PROMO,
                    PORC_VTA_PROMO, CREC_TKT, PROPORCION
                FROM RO_T_PROMOS_BANCARIAS_VTEX
                WHERE FECHA BETWEEN ? AND ?
                ORDER BY FECHA, BANCO";

        $params = [
            [$fechaDesde, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE],
            [$fechaHasta,  SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE],
        ];

        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            $errors  = sqlsrv_errors(SQLSRV_ERR_ALL);
            $mensaje = !empty($errors[0]['message'])
                ? $errors[0]['message']
                : 'Error desconocido al consultar los datos.';
            throw new RuntimeException($mensaje);
        }

        $resultado = [];

        while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $fecha = $fila['FECHA'];
            if ($fecha instanceof DateTime) {
                $fecha = $fecha->format('Y-m-d');
            }

            $resultado[] = [
                'FECHA'              => $fecha,
                'ANIO'               => (int)   $fila['ANIO'],
                'NRO_MES'            => (int)   $fila['NRO_MES'],
                'NRO_DIA'            => (int)   $fila['NRO_DIA'],
                'CANAL'              =>          $fila['CANAL'],
                'BANCO'              =>          $fila['BANCO'],
                'DESC_PROMO'         =>          $fila['DESC_PROMO'],
                'PORC_REINTEGRO'     => (float)  $fila['PORC_REINTEGRO'],
                'PEDIDOS_TOTAL_DIA'  => (int)    $fila['PEDIDOS_TOTAL_DIA'],
                'VTA_TOTAL_DIA'      => (float)  $fila['VTA_TOTAL_DIA'],
                'PEDIDOS_CON_PROMO'  => (int)    $fila['PEDIDOS_CON_PROMO'],
                'VTA_PROMO'          => (float)  $fila['VTA_PROMO'],
                'TKT_CON_PROMO'      => (float)  $fila['TKT_CON_PROMO'],
                'PORC_2DO_CON_PROMO' => (float) ($fila['PORC_2DO_CON_PROMO'] ?? 0),
                'PEDIDOS_SIN_PROMO'  => (int)    $fila['PEDIDOS_SIN_PROMO'],
                'TKT_SIN_PROMO'      => (float) ($fila['TKT_SIN_PROMO'] ?? 0),
                'PORC_2DO_SIN_PROMO' => (float) ($fila['PORC_2DO_SIN_PROMO'] ?? 0),
                'PORC_VTA_PROMO'     => (float) ($fila['PORC_VTA_PROMO'] ?? 0),
                'CREC_TKT'           => (float) ($fila['CREC_TKT'] ?? 0),
                'PROPORCION'         => (float) ($fila['PROPORCION'] ?? 0),
            ];
        }

        sqlsrv_free_stmt($stmt);

        return $resultado;
    }

    /**
     * Retorna el detalle de pedidos con promo para una fecha y banco específicos.
     * Usa los mismos CTEs que el SP pero filtrado a un único día.
     *
     * @param  string $fecha  Fecha exacta en formato Y-m-d
     * @param  string $banco  Nombre del banco en mayúsculas (UPPER(ISSUER))
     * @return array
     * @throws RuntimeException
     */
    public function getDetallePedidos(string $fecha, string $banco): array
    {
        $sql = "
            WITH
            PEDIDOS AS (
                SELECT DISTINCT
                    A.NRO_PEDIDO,
                    A.TALON_PED,
                    CAST(A.FECHA_PEDI AS DATE)                                                  AS FECHA,
                    CASE WHEN DATEPART(WEEKDAY, A.FECHA_PEDI) = 1 THEN 7
                         ELSE DATEPART(WEEKDAY, A.FECHA_PEDI) - 1 END                          AS DIA_SEMANA,
                    MAX(C.IMPORTE) OVER (PARTITION BY A.NRO_PEDIDO, A.TALON_PED)               AS IMPORTE_PEDIDO,
                    COUNT(CASE WHEN B.COD_ARTICU LIKE '[XO]%' THEN 1 END)
                        OVER (PARTITION BY A.NRO_PEDIDO, A.TALON_PED)                          AS CANT_ITEMS_VALIDOS,
                    CASE
                        WHEN COUNT(CASE WHEN B.COD_ARTICU LIKE '[XO]%' THEN 1 END)
                                 OVER (PARTITION BY A.NRO_PEDIDO, A.TALON_PED) >= 2 THEN 1
                        ELSE 0
                    END                                                                         AS TIENE_2DO_PROD
                FROM GVA21 A
                INNER JOIN GVA03 B ON A.NRO_PEDIDO = B.NRO_PEDIDO AND A.TALON_PED = B.TALON_PED
                LEFT JOIN RO_V_RELACION_PEDIDOS_FACTURAS C
                    ON A.TALON_PED = C.TALON_PED AND A.NRO_PEDIDO = C.NRO_PEDIDO AND A.TALON_PED = '99'
                WHERE A.COD_CLIENT = '000000'
                  AND A.ESTADO <> '5'
                  AND CAST(A.FECHA_PEDI AS DATE) = ?
            ),
            BANCO_PEDIDO AS (
                SELECT DISTINCT G.NUMERO_PEDIDO_TANGO NRO_PEDIDO, P.ISSUER,
                    CASE UPPER(P.ISSUER)
                        WHEN 'PROVINCIA'   THEN '014'
                        WHEN 'SANTANDER'   THEN '025'
                        WHEN 'GALICIA'     THEN '007'
                        WHEN 'FRANCES'     THEN '017'
                        WHEN 'ITAU'        THEN '259'
                        WHEN 'ICBC'        THEN '015'
                        WHEN 'HIPOTECARIO' THEN '044'
                        WHEN 'NACION'      THEN '011'
                        WHEN 'MACRO'       THEN '314'
                        WHEN 'HSBC'        THEN '150'
                        WHEN 'CIUDAD'      THEN '029'
                        WHEN 'PATAGONIA'   THEN '034'
                        WHEN 'COMAFI'      THEN '299'
                    END AS COD_BANCO
                FROM GC_ECOMMERCE_PAYMENT_PEDIDO PP
                INNER JOIN GC_ECOMMERCE_PAYMENT_DETAIL P
                    ON PP.ID_GC_ECOMMERCE_PAYMENT_DETAIL = P.ID_GC_ECOMMERCE_PAYMENT_DETAIL
                INNER JOIN GC_ECOMMERCE_PEDIDO G
                    ON PP.ID_GC_ECOMMERCE_PEDIDO = G.ID_GC_ECOMMERCE_PEDIDO
            ),
            PEDIDOS_CON_PROMO AS (
                SELECT PED.NRO_PEDIDO, PED.FECHA, PED.DIA_SEMANA,
                       BP.COD_BANCO, PT.DESC_PROMOCION_TARJETA, PT.PORC_REINTEGRO
                FROM PEDIDOS PED
                INNER JOIN BANCO_PEDIDO BP ON PED.NRO_PEDIDO = BP.NRO_PEDIDO
                INNER JOIN SJ_BANCOS_RELACION SBR ON BP.COD_BANCO = SBR.COD_BANCO
                INNER JOIN BANCO BAN ON SBR.COD_BANCO = BAN.COD_BANCO
                INNER JOIN PROMOCION_TARJETA PT
                    ON  BAN.ID_BANCO = PT.ID_BANCO
                    AND PED.FECHA BETWEEN CAST(PT.DESFE_VIGENCIA AS DATE)
                                      AND CAST(PT.HASFE_VIGENCIA AS DATE)
                    AND (
                        (PED.DIA_SEMANA = 1 AND PT.APLICA_LUNES     = 'S') OR
                        (PED.DIA_SEMANA = 2 AND PT.APLICA_MARTES    = 'S') OR
                        (PED.DIA_SEMANA = 3 AND PT.APLICA_MIERCOLES = 'S') OR
                        (PED.DIA_SEMANA = 4 AND PT.APLICA_JUEVES    = 'S') OR
                        (PED.DIA_SEMANA = 5 AND PT.APLICA_VIERNES   = 'S') OR
                        (PED.DIA_SEMANA = 6 AND PT.APLICA_SABADO    = 'S') OR
                        (PED.DIA_SEMANA = 7 AND PT.APLICA_DOMINGO   = 'S')
                    )
                INNER JOIN CTA_PROMOCION_TARJETA_POR_SUCURSAL CPS
                    ON PT.ID_PROMOCION_TARJETA = CPS.ID_PROMOCION_TARJETA
                INNER JOIN SUCURSAL S ON CPS.ID_SUCURSAL = S.ID_SUCURSAL
                WHERE S.NRO_SUCURSAL = 15
            )
            SELECT
                PCP.NRO_PEDIDO,
                PED.IMPORTE_PEDIDO,
                PED.TIENE_2DO_PROD,
                PED.CANT_ITEMS_VALIDOS,
                UPPER(BP.ISSUER)               AS BANCO,
                PCP.DESC_PROMOCION_TARJETA     AS DESC_PROMO,
                PCP.PORC_REINTEGRO
            FROM PEDIDOS_CON_PROMO PCP
            INNER JOIN PEDIDOS PED
                ON PCP.NRO_PEDIDO = PED.NRO_PEDIDO
            INNER JOIN BANCO_PEDIDO BP
                ON  PCP.NRO_PEDIDO = BP.NRO_PEDIDO
                AND PCP.COD_BANCO  = BP.COD_BANCO
            WHERE UPPER(BP.ISSUER) = ?
            ORDER BY PED.IMPORTE_PEDIDO DESC
        ";

        $params = [
            [$fecha, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE],
            [$banco, SQLSRV_PARAM_IN],
        ];

        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            $errors  = sqlsrv_errors(SQLSRV_ERR_ALL);
            $mensaje = !empty($errors[0]['message'])
                ? $errors[0]['message']
                : 'Error al obtener el detalle de pedidos.';
            throw new RuntimeException($mensaje);
        }

        $resultado = [];

        while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultado[] = [
                'NRO_PEDIDO'       =>          $fila['NRO_PEDIDO'],
                'IMPORTE_PEDIDO'   => (float)  $fila['IMPORTE_PEDIDO'],
                'TIENE_2DO_PROD'   => (int)    $fila['TIENE_2DO_PROD'],
                'CANT_ITEMS_VALIDOS'=> (int)   $fila['CANT_ITEMS_VALIDOS'],
                'BANCO'            =>          $fila['BANCO'],
                'DESC_PROMO'       =>          $fila['DESC_PROMO'],
                'PORC_REINTEGRO'   => (float)  $fila['PORC_REINTEGRO'],
            ];
        }

        sqlsrv_free_stmt($stmt);

        return $resultado;
    }
}
