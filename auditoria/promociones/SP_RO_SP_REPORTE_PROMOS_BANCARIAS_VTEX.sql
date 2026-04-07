-- =============================================
-- SP: RO_SP_REPORTE_PROMOS_BANCARIAS_VTEX
-- Granularidad: una fila por FECHA + BANCO + PROMO
-- Parámetros:
--   @FECHA_DESDE DATE  Inicio del período
--   @FECHA_HASTA DATE  Fin del período
-- =============================================

CREATE OR ALTER PROCEDURE RO_SP_REPORTE_PROMOS_BANCARIAS_VTEX
    @FECHA_DESDE DATE,
    @FECHA_HASTA DATE
AS
BEGIN
    SET NOCOUNT ON;

    WITH

    -- =========================================================
    -- CTE 1: PEDIDOS
    -- Un registro por pedido (DISTINCT via window functions).
    -- =========================================================
    PEDIDOS AS (
        SELECT DISTINCT
            A.NRO_PEDIDO,
            A.TALON_PED,
            CAST(A.FECHA_PEDI AS DATE)                                          AS FECHA,
            CASE
                WHEN DATEPART(WEEKDAY, A.FECHA_PEDI) = 1 THEN 7
                ELSE DATEPART(WEEKDAY, A.FECHA_PEDI) - 1
            END                                                                 AS DIA_SEMANA,
            -- MAX en lugar de SUM: evita duplicación por múltiples líneas de la relación
            MAX(C.IMPORTE) OVER (PARTITION BY A.NRO_PEDIDO, A.TALON_PED)       AS IMPORTE_PEDIDO,
            COUNT(CASE WHEN B.COD_ARTICU LIKE '[XO]%' THEN 1 END)
                OVER (PARTITION BY A.NRO_PEDIDO, A.TALON_PED)                  AS CANT_ITEMS_VALIDOS,
            CASE
                WHEN COUNT(CASE WHEN B.COD_ARTICU LIKE '[XO]%' THEN 1 END)
                         OVER (PARTITION BY A.NRO_PEDIDO, A.TALON_PED) >= 2
                THEN 1 ELSE 0
            END                                                                 AS TIENE_2DO_PROD
        FROM GVA21 A
        INNER JOIN GVA03 B
            ON  A.NRO_PEDIDO = B.NRO_PEDIDO
            AND A.TALON_PED  = B.TALON_PED
        LEFT JOIN RO_V_RELACION_PEDIDOS_FACTURAS C
            ON  A.TALON_PED  = C.TALON_PED
            AND A.NRO_PEDIDO = C.NRO_PEDIDO
            AND A.TALON_PED  = '99'
        WHERE A.COD_CLIENT = '000000'
          AND A.ESTADO     <> '5'           -- Excluir pedidos cancelados
          AND CAST(A.FECHA_PEDI AS DATE) BETWEEN @FECHA_DESDE AND @FECHA_HASTA
    ),

    -- =========================================================
    -- CTE 2: BANCO_PEDIDO
    -- Mapea ISSUER → COD_BANCO. Usa NUMERO_PEDIDO_TANGO
    -- para vincular con GVA21.
    -- =========================================================
    BANCO_PEDIDO AS (
        SELECT DISTINCT
            G.NUMERO_PEDIDO_TANGO   AS NRO_PEDIDO,
            P.ISSUER,
            CASE UPPER(P.ISSUER)
                WHEN 'PROVINCIA'    THEN '014'
                WHEN 'SANTANDER'    THEN '025'
                WHEN 'GALICIA'      THEN '007'
                WHEN 'FRANCES'      THEN '017'
                WHEN 'ITAU'         THEN '259'
                WHEN 'ICBC'         THEN '015'
                WHEN 'HIPOTECARIO'  THEN '044'
                WHEN 'NACION'       THEN '011'
                WHEN 'MACRO'        THEN '314'
                WHEN 'HSBC'         THEN '150'
                WHEN 'CIUDAD'       THEN '029'
                WHEN 'PATAGONIA'    THEN '034'
                WHEN 'COMAFI'       THEN '299'
            END                         AS COD_BANCO
        FROM GC_ECOMMERCE_PAYMENT_PEDIDO PP
        INNER JOIN GC_ECOMMERCE_PAYMENT_DETAIL P
            ON PP.ID_GC_ECOMMERCE_PAYMENT_DETAIL = P.ID_GC_ECOMMERCE_PAYMENT_DETAIL
        INNER JOIN GC_ECOMMERCE_PEDIDO G
            ON PP.ID_GC_ECOMMERCE_PEDIDO = G.ID_GC_ECOMMERCE_PEDIDO
    ),

    -- =========================================================
    -- CTE 3: PEDIDOS_CON_PROMO
    -- Pedidos que calificaron para una promo bancaria el día
    -- de la compra (filtro por día de semana + sucursal 15).
    -- =========================================================
    PEDIDOS_CON_PROMO AS (
        SELECT
            PED.NRO_PEDIDO,
            PED.FECHA,
            PED.DIA_SEMANA,
            BP.COD_BANCO,
            PT.DESC_PROMOCION_TARJETA,
            PT.PORC_REINTEGRO
        FROM PEDIDOS PED
        INNER JOIN BANCO_PEDIDO BP
            ON PED.NRO_PEDIDO = BP.NRO_PEDIDO
        INNER JOIN SJ_BANCOS_RELACION SBR
            ON BP.COD_BANCO = SBR.COD_BANCO
        INNER JOIN BANCO BAN
            ON SBR.COD_BANCO = BAN.COD_BANCO
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
        INNER JOIN SUCURSAL S
            ON CPS.ID_SUCURSAL = S.ID_SUCURSAL
        WHERE S.NRO_SUCURSAL = 15
    ),

    -- =========================================================
    -- CTE 4: DIAS_CON_PROMO_POR_BANCO
    -- =========================================================
    DIAS_CON_PROMO_POR_BANCO AS (
        SELECT DISTINCT FECHA, COD_BANCO
        FROM PEDIDOS_CON_PROMO
    ),

    -- =========================================================
    -- CTE 5: UNIVERSO_DIA
    -- Universo total del sitio por cada día con promo activa.
    -- =========================================================
    UNIVERSO_DIA AS (
        SELECT
            DP.FECHA,
            DP.COD_BANCO,
            COUNT(DISTINCT PED.NRO_PEDIDO)                                  AS PEDIDOS_TOTAL_DIA,
            SUM(PED.IMPORTE_PEDIDO)                                         AS VTA_TOTAL_DIA,
            COUNT(DISTINCT CASE WHEN PCP.NRO_PEDIDO IS NULL
                THEN PED.NRO_PEDIDO END)                                    AS PEDIDOS_SIN_PROMO,
            AVG(CASE WHEN PCP.NRO_PEDIDO IS NULL
                THEN PED.IMPORTE_PEDIDO END)                                AS TKT_SIN_PROMO,
            AVG(CASE WHEN PCP.NRO_PEDIDO IS NULL
                THEN CAST(PED.TIENE_2DO_PROD AS FLOAT) END)                 AS PORC_2DO_SIN_PROMO
        FROM DIAS_CON_PROMO_POR_BANCO DP
        INNER JOIN PEDIDOS PED
            ON PED.FECHA = DP.FECHA
        LEFT JOIN PEDIDOS_CON_PROMO PCP
            ON  PED.NRO_PEDIDO = PCP.NRO_PEDIDO
            AND PCP.COD_BANCO  = DP.COD_BANCO
        GROUP BY DP.FECHA, DP.COD_BANCO
    )

    -- =========================================================
    -- SELECT FINAL — una fila por FECHA + BANCO + PROMO
    -- =========================================================
    SELECT
        PCP.FECHA                                                           AS FECHA,
        YEAR(PCP.FECHA)                                                     AS ANIO,
        MONTH(PCP.FECHA)                                                    AS NRO_MES,
        'VTEX'                                                              AS CANAL,
        UPPER(BP.ISSUER)                                                    AS BANCO,
        PCP.DESC_PROMOCION_TARJETA                                          AS DESC_PROMO,
        PCP.PORC_REINTEGRO,

        U.PEDIDOS_TOTAL_DIA,
        U.VTA_TOTAL_DIA,

        COUNT(DISTINCT PCP.NRO_PEDIDO)                                      AS PEDIDOS_CON_PROMO,
        SUM(PED.IMPORTE_PEDIDO)                                             AS VTA_PROMO,
        AVG(PED.IMPORTE_PEDIDO)                                             AS TKT_CON_PROMO,
        AVG(CAST(PED.TIENE_2DO_PROD AS FLOAT))                              AS PORC_2DO_CON_PROMO,

        U.PEDIDOS_SIN_PROMO,
        U.TKT_SIN_PROMO,
        U.PORC_2DO_SIN_PROMO,

        CAST(
            SUM(PED.IMPORTE_PEDIDO) * 1.0
            / NULLIF(U.VTA_TOTAL_DIA, 0)
        AS DECIMAL(5,4))                                                    AS PORC_VTA_PROMO,

        CAST(
            (AVG(PED.IMPORTE_PEDIDO) - U.TKT_SIN_PROMO)
            / NULLIF(U.TKT_SIN_PROMO, 0)
        AS DECIMAL(6,4))                                                    AS CREC_TKT,

        -- PROPORCION = Pedidos totales del día / Pedidos sin promo
        -- Indica cuántos pedidos totales hay por cada pedido sin promo
        CAST(
            U.PEDIDOS_TOTAL_DIA * 1.0
            / NULLIF(U.PEDIDOS_SIN_PROMO, 0)
        AS DECIMAL(8,2))                                                    AS PROPORCION

    FROM PEDIDOS_CON_PROMO PCP
    INNER JOIN PEDIDOS PED
        ON PCP.NRO_PEDIDO = PED.NRO_PEDIDO
    INNER JOIN BANCO_PEDIDO BP
        ON  PCP.NRO_PEDIDO = BP.NRO_PEDIDO
       AND  PCP.COD_BANCO  = BP.COD_BANCO
    INNER JOIN UNIVERSO_DIA U
        ON  PCP.FECHA     = U.FECHA
        AND PCP.COD_BANCO = U.COD_BANCO

    GROUP BY
        PCP.FECHA,
        YEAR(PCP.FECHA),
        MONTH(PCP.FECHA),
        'VTEX',
        UPPER(BP.ISSUER),
        PCP.DESC_PROMOCION_TARJETA,
        PCP.PORC_REINTEGRO,
        U.PEDIDOS_TOTAL_DIA,
        U.VTA_TOTAL_DIA,
        U.PEDIDOS_SIN_PROMO,
        U.TKT_SIN_PROMO,
        U.PORC_2DO_SIN_PROMO

    ORDER BY PCP.FECHA, UPPER(BP.ISSUER);

END;
GO
