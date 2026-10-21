/* =============================================================================
   Ajuste de Stock por Depósito (Tango, sin WMS / sin ubicación)
   Base: LAKER_SA (central)

   - EB_AjusteStockLote : cabecera del lote (IdLote GUID, estado, leyenda)
   - EB_AjusteStockDet  : detalle (depósito, artículo, cantidad con signo, partida)
   - EB_AjusteStockComp : comprobantes AJU del lote (uno por depósito y sentido)
   - EB_AjusteStock_Procesar : ejecuta UN BLOQUE de artículos pendientes del lote.

   La validación (stock, partidas, duplicados) la hace la aplicación PHP
   (Class/AjusteStockDeposito.php -> validarLote) y deja el resultado por fila en
   EB_AjusteStockDet.Estado / Resultado. El SP vuelve a controlar stock y partidas
   con bloqueo dentro de la transacción antes de mover stock.

   Ejecución por bloques (para no frenar a los demás procesos de Tango):
   - cada llamada procesa como máximo @MaxArticulos artículos pendientes en una
     transacción corta y la confirma; la aplicación llama al SP hasta terminar;
   - LOCK_TIMEOUT corto: si Tango está ocupado el bloque se cancela sin cambios
     y la aplicación reintenta; DEADLOCK_PRIORITY LOW: ante un deadlock cede el ajuste;
   - cada fila queda marcada (Ejecutada) en la misma transacción: si se corta,
     se retoma sin duplicar;
   - los bloques agregan renglones al mismo comprobante del lote (uno por depósito
     y sentido), así el ajuste completo queda en pocos comprobantes.

   Derivado de EB_RecodificacionV1.2 / EB_InsertardetallecomprobanteAjuste2:
   - encabezado: se reutiliza EB_InsertarEncMovimiento2 (AJU, talonario 850, TCOMP_IN_S 'AJ');
   - renglones STA20, STA19 y partidas STA10 con las mismas columnas que el SP original,
     pero respetando la partida informada y sin ningún movimiento en el WMS.
   ============================================================================= */

IF OBJECT_ID('dbo.EB_AjusteStockLote', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.EB_AjusteStockLote (
        IdLote           UNIQUEIDENTIFIER NOT NULL PRIMARY KEY,
        Usuario          VARCHAR(50)      NOT NULL,
        Host             VARCHAR(50)      NOT NULL,
        Leyenda          VARCHAR(120)     NOT NULL,
        -- CARGADO | CON_ERRORES | VALIDADO | EN_PROCESO | EJECUTADO
        Estado           VARCHAR(12)      NOT NULL CONSTRAINT DF_EB_AjusteStockLote_Estado DEFAULT ('CARGADO'),
        Filas            INT              NOT NULL CONSTRAINT DF_EB_AjusteStockLote_Filas DEFAULT (0),
        FechaAlta        DATETIME         NOT NULL CONSTRAINT DF_EB_AjusteStockLote_FechaAlta DEFAULT (GETDATE()),
        FechaValidacion  DATETIME         NULL,
        FechaEjecucion   DATETIME         NULL,
        NroComprobantes  VARCHAR(200)     NULL,
        Mensaje          NVARCHAR(1000)   NULL
    );
END
GO

IF OBJECT_ID('dbo.EB_AjusteStockDet', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.EB_AjusteStockDet (
        IdLote           UNIQUEIDENTIFIER NOT NULL,
        Fila             INT              NOT NULL,
        CodDeposito      VARCHAR(2)       NOT NULL,
        CodArticulo      VARCHAR(15)      NOT NULL,
        Cantidad         INT              NOT NULL,        -- negativa = baja, positiva = alta
        Partida          VARCHAR(25)      NULL,
        -- OK | ADVERTENCIA | ERROR (NULL = sin validar)
        Estado           VARCHAR(12)      NULL,
        StockActual      DECIMAL(18, 2)   NULL,
        StockResultante  DECIMAL(18, 2)   NULL,
        Disponible       DECIMAL(18, 2)   NULL,            -- máximo que se puede bajar en esta fila
        Resultado        NVARCHAR(500)    NULL,
        Sugerencia       NVARCHAR(MAX)    NULL,            -- JSON: desglose FIFO de partidas
        CONSTRAINT PK_EB_AjusteStockDet PRIMARY KEY (IdLote, Fila),
        CONSTRAINT FK_EB_AjusteStockDet_Lote FOREIGN KEY (IdLote)
            REFERENCES dbo.EB_AjusteStockLote (IdLote) ON DELETE CASCADE
    );
    CREATE INDEX IX_EB_AjusteStockDet_Art ON dbo.EB_AjusteStockDet (IdLote, CodArticulo, CodDeposito);
END
GO

-- Ejecución por bloques: marca por fila
IF COL_LENGTH('dbo.EB_AjusteStockDet', 'Ejecutada') IS NULL
    ALTER TABLE dbo.EB_AjusteStockDet ADD
        Ejecutada        BIT          NOT NULL CONSTRAINT DF_EB_AjusteStockDet_Ejecutada DEFAULT (0),
        NroComprobante   VARCHAR(20)  NULL,
        FechaEjecucion   DATETIME     NULL;
GO

-- Lotes ejecutados con la versión anterior (una sola transacción): todas sus filas ya se aplicaron
UPDATE d SET d.Ejecutada = 1, d.FechaEjecucion = l.FechaEjecucion
FROM dbo.EB_AjusteStockDet d
JOIN dbo.EB_AjusteStockLote l ON l.IdLote = d.IdLote
WHERE l.Estado = 'EJECUTADO' AND d.Ejecutada = 0;
GO

IF OBJECT_ID('dbo.EB_AjusteStockComp', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.EB_AjusteStockComp (
        IdLote          UNIQUEIDENTIFIER NOT NULL,
        CodDeposito     VARCHAR(2)       NOT NULL,
        TipoMov         CHAR(1)          NOT NULL,          -- E = alta, S = baja
        NroComprobante  VARCHAR(20)      NOT NULL,          -- STA14.N_COMP
        NcompInS        VARCHAR(20)      NOT NULL,          -- STA14.NCOMP_IN_S
        UltRenglon      INT              NOT NULL CONSTRAINT DF_EB_AjusteStockComp_Ult DEFAULT (0),
        FechaAlta       DATETIME         NOT NULL CONSTRAINT DF_EB_AjusteStockComp_Fecha DEFAULT (GETDATE()),
        CONSTRAINT PK_EB_AjusteStockComp PRIMARY KEY (IdLote, CodDeposito, TipoMov),
        CONSTRAINT FK_EB_AjusteStockComp_Lote FOREIGN KEY (IdLote)
            REFERENCES dbo.EB_AjusteStockLote (IdLote) ON DELETE CASCADE
    );
END
GO

/* Intercalación: las columnas que se cruzan con Tango tienen que usar la misma que Tango
   (Latin1_General_BIN), no la de la base (Modern_Spanish_CI_AI). Se toma de STA19.COD_ARTICU. */
DECLARE @Collation SYSNAME = (SELECT collation_name FROM sys.columns
                              WHERE object_id = OBJECT_ID('dbo.STA19') AND name = 'COD_ARTICU');

IF @Collation IS NOT NULL AND EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('dbo.EB_AjusteStockDet')
      AND name IN ('CodDeposito', 'CodArticulo', 'Partida')
      AND collation_name <> @Collation)
BEGIN
    IF EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('dbo.EB_AjusteStockDet') AND name = 'IX_EB_AjusteStockDet_Art')
        DROP INDEX IX_EB_AjusteStockDet_Art ON dbo.EB_AjusteStockDet;

    EXEC ('ALTER TABLE dbo.EB_AjusteStockDet ALTER COLUMN CodDeposito VARCHAR(2)  COLLATE ' + @Collation + ' NOT NULL');
    EXEC ('ALTER TABLE dbo.EB_AjusteStockDet ALTER COLUMN CodArticulo VARCHAR(15) COLLATE ' + @Collation + ' NOT NULL');
    EXEC ('ALTER TABLE dbo.EB_AjusteStockDet ALTER COLUMN Partida     VARCHAR(25) COLLATE ' + @Collation + ' NULL');

    CREATE INDEX IX_EB_AjusteStockDet_Art ON dbo.EB_AjusteStockDet (IdLote, CodArticulo, CodDeposito);
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('dbo.EB_AjusteStockDet') AND name = 'IX_EB_AjusteStockDet_Pend')
    CREATE INDEX IX_EB_AjusteStockDet_Pend ON dbo.EB_AjusteStockDet (IdLote, Ejecutada, CodArticulo);
GO

/* -----------------------------------------------------------------------------
   EB_AjusteStock_Procesar: ejecuta un bloque de hasta @MaxArticulos artículos
   pendientes (todas sus filas, de todos los depósitos, van juntas en el bloque).

   Result set 1: filas con error (Fila, CodDeposito, CodArticulo, Cantidad, Partida, Resultado)
   Result set 2: resumen (Ok, FilasProcesadas, FilasPendientes, Terminado, NroComprobantes, Mensaje)

   Partidas (artículos con USA_PARTID = 1), sobre STA10:
     - fila con partida   -> mueve esa partida (si es un alta y la partida no está en el
                             depósito, se crea copiando fecha / vencimiento);
     - alta sin partida   -> igual que el SP original: última partida del depósito
                             o, si no hay, la última partida del artículo (sof_partidas);
     - baja sin partida   -> FIFO por fecha de partida sobre el saldo libre.
   ----------------------------------------------------------------------------- */
CREATE OR ALTER PROCEDURE dbo.EB_AjusteStock_Procesar
    @IdLote        UNIQUEIDENTIFIER,
    @Usuario       VARCHAR(50),
    @Host          VARCHAR(50),
    @MaxArticulos  INT = 50
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    -- No esperar más de 5 s por un bloqueo de otro proceso: se cancela el bloque y se reintenta.
    SET LOCK_TIMEOUT 5000;
    -- Ante un deadlock cede el ajuste, nunca la operación de otro sector.
    SET DEADLOCK_PRIORITY LOW;

    DECLARE @Estado VARCHAR(12), @Leyenda VARCHAR(120), @Filas INT, @Pendientes INT;
    DECLARE @Fecha DATETIME = GETDATE();
    DECLARE @NroComprobantes VARCHAR(200) = '';
    DECLARE @ErrorMessage NVARCHAR(4000), @ErrorLine INT, @ErrorNumber INT, @UserMessage NVARCHAR(2000);
    DECLARE @CurrentOperation NVARCHAR(200) = 'Inicio';

    IF @MaxArticulos IS NULL OR @MaxArticulos < 1 SET @MaxArticulos = 50;

    DECLARE @Errores TABLE (Fila INT, CodDeposito VARCHAR(2), CodArticulo VARCHAR(15),
                            Cantidad INT, Partida VARCHAR(25), Resultado NVARCHAR(500));

    -- Las temporales se crean con SELECT INTO desde EB_AjusteStockDet para heredar la
    -- intercalación de Tango (si se declaran a mano toman la de tempdb y chocan en los JOIN).
    SELECT Fila, CodDeposito, CodArticulo, Cantidad, Partida,
           CAST(0 AS BIT) AS UsaPartida,
           CAST('E' AS CHAR(1)) AS TipoMov          -- E = entrada (alta), S = salida (baja)
    INTO #Det
    FROM dbo.EB_AjusteStockDet WHERE 1 = 0;
    CREATE UNIQUE CLUSTERED INDEX IX_Det ON #Det (Fila);

    SELECT CodArticulo, CodDeposito, Partida, Cantidad AS Delta
    INTO #Part
    FROM dbo.EB_AjusteStockDet WHERE 1 = 0;

    SELECT CodArticulo, CodDeposito, Partida, Cantidad AS Delta, CAST(NULL AS INT) AS IdSta10
    INTO #PartDestino
    FROM dbo.EB_AjusteStockDet WHERE 1 = 0;

    BEGIN TRY
        BEGIN TRAN;

        -- Bloquea la cabecera del lote (solo esta fila): dos ejecuciones simultáneas se serializan.
        SELECT @Estado = Estado, @Leyenda = Leyenda
        FROM dbo.EB_AjusteStockLote WITH (UPDLOCK, HOLDLOCK)
        WHERE IdLote = @IdLote;

        IF @Estado IS NULL OR @Estado NOT IN ('VALIDADO', 'EN_PROCESO')
        BEGIN
            ROLLBACK;
            SELECT * FROM @Errores;
            SELECT CAST(0 AS BIT) AS Ok, 0 AS FilasProcesadas, 0 AS FilasPendientes,
                   CAST(CASE WHEN @Estado = 'EJECUTADO' THEN 1 ELSE 0 END AS BIT) AS Terminado,
                   CAST(NULL AS VARCHAR(200)) AS NroComprobantes,
                   CASE WHEN @Estado IS NULL THEN N'El lote no existe.'
                        WHEN @Estado = 'EJECUTADO' THEN N'El lote ya fue ejecutado. No se puede volver a aplicar.'
                        ELSE N'El lote no está validado (estado ' + @Estado + N'). Revalidá antes de ejecutar.' END AS Mensaje;
            RETURN;
        END

        /* ---------------------------------------------------------------------
           BLOQUE: próximos @MaxArticulos artículos pendientes (con todas sus filas)
           --------------------------------------------------------------------- */
        SET @CurrentOperation = 'Selección del bloque';
        INSERT INTO #Det (Fila, CodDeposito, CodArticulo, Cantidad, Partida, UsaPartida, TipoMov)
        SELECT d.Fila, d.CodDeposito, UPPER(LTRIM(RTRIM(d.CodArticulo))), d.Cantidad,
               NULLIF(UPPER(LTRIM(RTRIM(d.Partida))), ''),
               ISNULL(a.usa_partid, 0),
               CASE WHEN d.Cantidad < 0 THEN 'S' ELSE 'E' END
        FROM dbo.EB_AjusteStockDet d
        OUTER APPLY (SELECT TOP (1) usa_partid FROM dbo.SJ_VIEW_STA11 WHERE cod_articu = d.CodArticulo) a
        WHERE d.IdLote = @IdLote
          AND d.Ejecutada = 0
          AND d.CodArticulo IN (SELECT TOP (@MaxArticulos) x.CodArticulo
                                FROM dbo.EB_AjusteStockDet x
                                WHERE x.IdLote = @IdLote AND x.Ejecutada = 0
                                GROUP BY x.CodArticulo
                                ORDER BY x.CodArticulo);

        SET @Filas = @@ROWCOUNT;

        IF @Filas = 0
        BEGIN
            -- No quedan filas pendientes: se cierra el lote.
            UPDATE dbo.EB_AjusteStockLote
            SET Estado = 'EJECUTADO', FechaEjecucion = ISNULL(FechaEjecucion, GETDATE()),
                Mensaje = N'Ejecutado por ' + @Usuario + N' desde ' + @Host
            WHERE IdLote = @IdLote;
            COMMIT;

            SELECT * FROM @Errores;
            SELECT CAST(1 AS BIT) AS Ok, 0 AS FilasProcesadas, 0 AS FilasPendientes, CAST(1 AS BIT) AS Terminado,
                   (SELECT NroComprobantes FROM dbo.EB_AjusteStockLote WHERE IdLote = @IdLote) AS NroComprobantes,
                   N'Ajuste aplicado correctamente.' AS Mensaje;
            RETURN;
        END

        /* ---------------------------------------------------------------------
           RE-CONTROL (el stock pudo cambiar desde la validación)
           --------------------------------------------------------------------- */
        SET @CurrentOperation = 'Re-control de stock y partidas';

        -- Stock del depósito: suma de bajas por artículo/depósito
        ;WITH Bajas AS (
            SELECT CodArticulo, CodDeposito, SUM(-Cantidad) AS CantBaja
            FROM #Det WHERE Cantidad < 0
            GROUP BY CodArticulo, CodDeposito
        )
        INSERT INTO @Errores (Fila, CodDeposito, CodArticulo, Cantidad, Partida, Resultado)
        SELECT d.Fila, d.CodDeposito, d.CodArticulo, d.Cantidad, d.Partida,
               N'Stock insuficiente al ejecutar: stock ' + CAST(CAST(ISNULL(s.cant_stock, 0) AS INT) AS NVARCHAR(20))
               + N', baja total ' + CAST(b.CantBaja AS NVARCHAR(20))
        FROM Bajas b
        JOIN #Det d ON d.CodArticulo = b.CodArticulo AND d.CodDeposito = b.CodDeposito AND d.Cantidad < 0
        LEFT JOIN dbo.sta19 s WITH (UPDLOCK, HOLDLOCK)
               ON s.cod_articu = b.CodArticulo AND s.cod_deposi = b.CodDeposito
        WHERE ISNULL(s.cant_stock, 0) < b.CantBaja;

        -- Partida informada en artículo sin partidas
        INSERT INTO @Errores (Fila, CodDeposito, CodArticulo, Cantidad, Partida, Resultado)
        SELECT Fila, CodDeposito, CodArticulo, Cantidad, Partida, N'El artículo no lleva partidas'
        FROM #Det WHERE UsaPartida = 0 AND Partida IS NOT NULL;

        -- Bajas con partida: saldo de esa partida en el depósito
        ;WITH BajasPart AS (
            SELECT CodArticulo, CodDeposito, Partida, SUM(-Cantidad) AS CantBaja
            FROM #Det WHERE UsaPartida = 1 AND Partida IS NOT NULL AND Cantidad < 0
            GROUP BY CodArticulo, CodDeposito, Partida
        ), Saldo AS (
            SELECT b.CodArticulo, b.CodDeposito, b.Partida, b.CantBaja, ISNULL(SUM(p.cantidad), 0) AS Saldo
            FROM BajasPart b
            LEFT JOIN dbo.sta10 p WITH (UPDLOCK, HOLDLOCK)
                   ON p.cod_articu = b.CodArticulo AND p.cod_deposi = b.CodDeposito AND p.n_partida COLLATE DATABASE_DEFAULT = b.Partida COLLATE DATABASE_DEFAULT
            GROUP BY b.CodArticulo, b.CodDeposito, b.Partida, b.CantBaja
        )
        INSERT INTO @Errores (Fila, CodDeposito, CodArticulo, Cantidad, Partida, Resultado)
        SELECT d.Fila, d.CodDeposito, d.CodArticulo, d.Cantidad, d.Partida,
               N'Saldo insuficiente en la partida ' + d.Partida + N' al ejecutar: saldo '
               + CAST(CAST(s.Saldo AS INT) AS NVARCHAR(20)) + N', baja ' + CAST(s.CantBaja AS NVARCHAR(20))
        FROM Saldo s
        JOIN #Det d ON d.CodArticulo = s.CodArticulo AND d.CodDeposito = s.CodDeposito
                   AND d.Partida = s.Partida AND d.Cantidad < 0
        WHERE s.Saldo < s.CantBaja;

        -- Bajas de artículos con partida: saldo total en partidas del depósito
        ;WITH BajasArt AS (
            SELECT CodArticulo, CodDeposito, SUM(-Cantidad) AS CantBaja
            FROM #Det WHERE UsaPartida = 1 AND Cantidad < 0
            GROUP BY CodArticulo, CodDeposito
        ), Saldo AS (
            SELECT b.CodArticulo, b.CodDeposito, b.CantBaja,
                   ISNULL(SUM(CASE WHEN p.cantidad > 0 THEN p.cantidad ELSE 0 END), 0) AS Saldo
            FROM BajasArt b
            LEFT JOIN dbo.sta10 p WITH (UPDLOCK, HOLDLOCK)
                   ON p.cod_articu = b.CodArticulo AND p.cod_deposi = b.CodDeposito
            GROUP BY b.CodArticulo, b.CodDeposito, b.CantBaja
        )
        INSERT INTO @Errores (Fila, CodDeposito, CodArticulo, Cantidad, Partida, Resultado)
        SELECT d.Fila, d.CodDeposito, d.CodArticulo, d.Cantidad, d.Partida,
               N'Saldo insuficiente en partidas del depósito al ejecutar: saldo '
               + CAST(CAST(s.Saldo AS INT) AS NVARCHAR(20)) + N', baja ' + CAST(s.CantBaja AS NVARCHAR(20))
        FROM Saldo s
        JOIN #Det d ON d.CodArticulo = s.CodArticulo AND d.CodDeposito = s.CodDeposito
                   AND d.Cantidad < 0 AND d.Partida IS NULL
        WHERE s.Saldo < s.CantBaja;

        -- Altas con partida: la partida tiene que existir para el artículo
        INSERT INTO @Errores (Fila, CodDeposito, CodArticulo, Cantidad, Partida, Resultado)
        SELECT d.Fila, d.CodDeposito, d.CodArticulo, d.Cantidad, d.Partida,
               N'La partida ' + d.Partida + N' no existe para el artículo'
        FROM #Det d
        WHERE d.UsaPartida = 1 AND d.Partida IS NOT NULL AND d.Cantidad > 0
          AND NOT EXISTS (SELECT 1 FROM dbo.sta10 p WHERE p.cod_articu = d.CodArticulo AND p.n_partida COLLATE DATABASE_DEFAULT = d.Partida COLLATE DATABASE_DEFAULT);

        /* ---------------------------------------------------------------------
           MOVIMIENTOS DE PARTIDAS (#Part: artículo, depósito, partida, delta)
           --------------------------------------------------------------------- */
        SET @CurrentOperation = 'Cálculo de movimientos de partidas';

        -- 1) Filas con partida informada
        INSERT INTO #Part (CodArticulo, CodDeposito, Partida, Delta)
        SELECT CodArticulo, CodDeposito, Partida, SUM(Cantidad)
        FROM #Det WHERE UsaPartida = 1 AND Partida IS NOT NULL
        GROUP BY CodArticulo, CodDeposito, Partida;

        -- 2) Altas sin partida: última partida del depósito, o última partida del artículo
        ;WITH Altas AS (
            SELECT CodArticulo, CodDeposito, SUM(Cantidad) AS Cant
            FROM #Det WHERE UsaPartida = 1 AND Partida IS NULL AND Cantidad > 0
            GROUP BY CodArticulo, CodDeposito
        )
        INSERT INTO #Part (CodArticulo, CodDeposito, Partida, Delta)
        SELECT a.CodArticulo, a.CodDeposito,
               COALESCE(
                   (SELECT TOP (1) p.n_partida COLLATE DATABASE_DEFAULT FROM dbo.sta10 p
                    WHERE p.cod_articu = a.CodArticulo AND p.cod_deposi = a.CodDeposito
                    ORDER BY p.id_sta10 DESC),
                   -- sof_partidas puede no usar la intercalación de Tango: comparación explícita
                   (SELECT MAX(sp.ULTIMA_PARTIDA) COLLATE DATABASE_DEFAULT FROM dbo.sof_partidas sp
                    WHERE sp.cod_articu = a.CodArticulo COLLATE DATABASE_DEFAULT)
               ),
               a.Cant
        FROM Altas a;

        INSERT INTO @Errores (Fila, CodDeposito, CodArticulo, Cantidad, Partida, Resultado)
        SELECT d.Fila, d.CodDeposito, d.CodArticulo, d.Cantidad, d.Partida,
               N'El artículo usa partidas pero no se encontró ninguna partida existente'
        FROM #Part x
        JOIN #Det d ON d.CodArticulo = x.CodArticulo AND d.CodDeposito = x.CodDeposito
                   AND d.UsaPartida = 1 AND d.Partida IS NULL AND d.Cantidad > 0
        WHERE x.Partida IS NULL
           OR NOT EXISTS (SELECT 1 FROM dbo.sta10 p WHERE p.cod_articu = x.CodArticulo AND p.n_partida COLLATE DATABASE_DEFAULT = x.Partida COLLATE DATABASE_DEFAULT);

        -- 3) Bajas sin partida: FIFO (fecha de partida, id) sobre el saldo que no usan las filas con partida
        ;WITH Necesidad AS (
            SELECT CodArticulo, CodDeposito, SUM(-Cantidad) AS Necesita
            FROM #Det WHERE UsaPartida = 1 AND Partida IS NULL AND Cantidad < 0
            GROUP BY CodArticulo, CodDeposito
        ), Saldos AS (
            SELECT p.cod_articu AS CodArticulo, p.cod_deposi AS CodDeposito, UPPER(p.n_partida) COLLATE DATABASE_DEFAULT AS Partida,
                   SUM(p.cantidad) AS Saldo, MIN(p.fecha) AS FechaPart, MIN(p.id_sta10) AS IdPart
            FROM dbo.sta10 p
            JOIN Necesidad n ON n.CodArticulo = p.cod_articu AND n.CodDeposito = p.cod_deposi
            GROUP BY p.cod_articu, p.cod_deposi, UPPER(p.n_partida)
        ), Usado AS (
            SELECT CodArticulo, CodDeposito, Partida, SUM(-Cantidad) AS Usado
            FROM #Det WHERE UsaPartida = 1 AND Partida IS NOT NULL AND Cantidad < 0
            GROUP BY CodArticulo, CodDeposito, Partida
        ), Libre AS (
            SELECT s.CodArticulo, s.CodDeposito, s.Partida, s.FechaPart, s.IdPart,
                   CAST(FLOOR(s.Saldo - ISNULL(u.Usado, 0)) AS INT) AS Libre
            FROM Saldos s
            LEFT JOIN Usado u ON u.CodArticulo = s.CodArticulo AND u.CodDeposito = s.CodDeposito AND u.Partida COLLATE DATABASE_DEFAULT = s.Partida
            WHERE s.Saldo - ISNULL(u.Usado, 0) >= 1
        ), Acum AS (
            SELECT l.*, n.Necesita,
                   SUM(l.Libre) OVER (PARTITION BY l.CodArticulo, l.CodDeposito
                                      ORDER BY l.FechaPart, l.IdPart ROWS UNBOUNDED PRECEDING) - l.Libre AS Antes
            FROM Libre l
            JOIN Necesidad n ON n.CodArticulo = l.CodArticulo AND n.CodDeposito = l.CodDeposito
        )
        INSERT INTO #Part (CodArticulo, CodDeposito, Partida, Delta)
        SELECT CodArticulo, CodDeposito, Partida,
               -CASE WHEN Necesita - Antes < Libre THEN Necesita - Antes ELSE Libre END
        FROM Acum
        WHERE Antes < Necesita;

        IF EXISTS (SELECT 1 FROM @Errores)
        BEGIN
            -- No se mueve nada de este bloque. Las filas quedan marcadas para corregir;
            -- los bloques anteriores ya confirmados quedan aplicados.
            ROLLBACK;
            BEGIN TRAN;
            UPDATE d SET d.Estado = 'ERROR', d.Resultado = LEFT(e.Resultado, 500)
            FROM dbo.EB_AjusteStockDet d
            JOIN (SELECT Fila, MIN(Resultado) AS Resultado FROM @Errores GROUP BY Fila) e ON e.Fila = d.Fila
            WHERE d.IdLote = @IdLote;

            UPDATE dbo.EB_AjusteStockLote SET Estado = 'CON_ERRORES',
                   Mensaje = N'El stock cambió desde la validación. Corregí las filas marcadas y continuá la ejecución.'
            WHERE IdLote = @IdLote;
            COMMIT;

            SELECT @Pendientes = COUNT(*) FROM dbo.EB_AjusteStockDet WHERE IdLote = @IdLote AND Ejecutada = 0;

            SELECT * FROM @Errores ORDER BY Fila;
            SELECT CAST(0 AS BIT) AS Ok, 0 AS FilasProcesadas, @Pendientes AS FilasPendientes, CAST(0 AS BIT) AS Terminado,
                   CAST(NULL AS VARCHAR(200)) AS NroComprobantes,
                   N'El stock cambió desde la validación. Este bloque no se aplicó.' AS Mensaje;
            RETURN;
        END

        /* ---------------------------------------------------------------------
           APLICAR PARTIDAS (STA10)
           --------------------------------------------------------------------- */
        SET @CurrentOperation = 'Actualización de partidas (STA10)';

        INSERT INTO #PartDestino (CodArticulo, CodDeposito, Partida, Delta, IdSta10)
        SELECT x.CodArticulo, x.CodDeposito, x.Partida, SUM(x.Delta),
               (SELECT MAX(p.id_sta10) FROM dbo.sta10 p
                WHERE p.cod_articu = x.CodArticulo AND p.cod_deposi = x.CodDeposito AND p.n_partida COLLATE DATABASE_DEFAULT = x.Partida COLLATE DATABASE_DEFAULT)
        FROM #Part x
        GROUP BY x.CodArticulo, x.CodDeposito, x.Partida
        HAVING SUM(x.Delta) <> 0;

        UPDATE p SET p.cantidad = p.cantidad + t.Delta
        FROM dbo.sta10 p
        JOIN #PartDestino t ON t.IdSta10 = p.id_sta10;

        -- Alta en una partida que todavía no está en el depósito: se crea copiando fecha y vencimiento
        INSERT INTO dbo.sta10
            (filler, cant_despa, cantidad, cod_articu, cod_deposi, costo_bloq, costo_ex, costo_lo,
             n_partida, saldo_ant, exp_saldo, fecha, fecha_vto, cantidad_2, saldo_ant_stock_2, cant_despa_2)
        SELECT '', 0, t.Delta, t.CodArticulo, t.CodDeposito, 0, 0, 0,
               t.Partida, 0, 0, o.fecha, o.fecha_vto, 0, 0, 0
        FROM #PartDestino t
        CROSS APPLY (SELECT TOP (1) p.fecha, p.fecha_vto FROM dbo.sta10 p
                     WHERE p.cod_articu = t.CodArticulo AND p.n_partida COLLATE DATABASE_DEFAULT = t.Partida COLLATE DATABASE_DEFAULT
                     ORDER BY p.id_sta10 DESC) o
        WHERE t.IdSta10 IS NULL AND t.Delta > 0;

        /* ---------------------------------------------------------------------
           APLICAR STOCK (STA19)
           --------------------------------------------------------------------- */
        SET @CurrentOperation = 'Actualización de stock (STA19)';

        ;WITH Neto AS (
            SELECT CodArticulo, CodDeposito, SUM(Cantidad) AS Delta
            FROM #Det GROUP BY CodArticulo, CodDeposito
        )
        UPDATE s SET s.cant_stock = s.cant_stock + n.Delta
        FROM dbo.sta19 s
        JOIN Neto n ON s.cod_articu = n.CodArticulo AND s.cod_deposi = n.CodDeposito;

        ;WITH Neto AS (
            SELECT CodArticulo, CodDeposito, SUM(Cantidad) AS Delta
            FROM #Det GROUP BY CodArticulo, CodDeposito
        )
        INSERT INTO dbo.sta19
            (filler, cant_comp, cant_pend, cant_stock, cod_articu, cod_deposi, fecha_ant, lote, saldo_ant,
             exp_saldo, cod_ubic1, cod_ubic2, cod_ubic3, ubic_txt, cant_comp_2, cant_pend_2, cant_stock_2, saldo_ant_stock_2)
        SELECT '', 0, 0, n.Delta, n.CodArticulo, n.CodDeposito, '18000101', 0, 0,
               0, '', '', '', '', 0, 0, 0, 0
        FROM Neto n
        WHERE n.Delta > 0
          AND NOT EXISTS (SELECT 1 FROM dbo.sta19 s WHERE s.cod_articu = n.CodArticulo AND s.cod_deposi = n.CodDeposito);

        /* ---------------------------------------------------------------------
           COMPROBANTES (STA14 + STA20): uno por depósito y sentido para todo el lote.
           El primer bloque crea el encabezado; los siguientes agregan renglones.
           --------------------------------------------------------------------- */
        SET @CurrentOperation = 'Generación de comprobantes (STA14 / STA20)';

        DECLARE @Enc TABLE (PROXIMO VARCHAR(100), PROXINTERNO VARCHAR(500));
        DECLARE @Dep VARCHAR(2), @TipoMov CHAR(1), @Proximo VARCHAR(100), @ProxInterno VARCHAR(500), @UltRenglon INT;
        -- Va a LEYENDA1 del comprobante: identifica el lote
        DECLARE @IdUnico VARCHAR(50) = LEFT('AJDEP ' + LEFT(CONVERT(VARCHAR(36), @IdLote), 8) + ' ' + @Leyenda, 50);

        DECLARE cur CURSOR LOCAL FAST_FORWARD FOR
            SELECT DISTINCT CodDeposito, TipoMov FROM #Det ORDER BY TipoMov DESC, CodDeposito;  -- primero salidas
        OPEN cur;
        FETCH NEXT FROM cur INTO @Dep, @TipoMov;
        WHILE @@FETCH_STATUS = 0
        BEGIN
            SELECT @Proximo = NULL, @ProxInterno = NULL, @UltRenglon = NULL;

            SELECT @Proximo = NroComprobante, @ProxInterno = NcompInS, @UltRenglon = UltRenglon
            FROM dbo.EB_AjusteStockComp
            WHERE IdLote = @IdLote AND CodDeposito = @Dep AND TipoMov = @TipoMov;

            IF @ProxInterno IS NULL
            BEGIN
                DELETE FROM @Enc;
                INSERT INTO @Enc EXEC dbo.EB_InsertarEncMovimiento2 @Usuario, 1, @Host, @IdUnico;
                SELECT @Proximo = PROXIMO, @ProxInterno = PROXINTERNO FROM @Enc;

                IF @ProxInterno IS NULL
                    RAISERROR(N'No se pudo generar el encabezado del comprobante de ajuste.', 16, 1);

                SET @UltRenglon = 0;
                INSERT INTO dbo.EB_AjusteStockComp (IdLote, CodDeposito, TipoMov, NroComprobante, NcompInS, UltRenglon)
                VALUES (@IdLote, @Dep, @TipoMov, LTRIM(RTRIM(@Proximo)), @ProxInterno, 0);
            END

            INSERT INTO dbo.sta20
                (can_equi_v, cant_dev, cant_oc, cant_pend, cant_scrap, cantidad, cod_articu, cod_deposi, deposi_dde,
                 equivalenc, fecha_mov, n_rengl_oc, n_rengl_s, ncomp_in_s, plista_rem, ppp_ex, ppp_lo, precio,
                 precio_rem, tcomp_in_s, tipo_mov, cant_factu, dcto_factu, cant_dev_2, cant_pend_2, cantidad_2,
                 cant_factu_2, id_medida_stock, unidad_medida_seleccionada, precio_remito_ventas, cant_oc_2,
                 rengl_padr, promocion, precio_adicional_kit, talonario_oc, COD_ARTICU_KIT)
            SELECT 1, 0, 0, 0, 0, ABS(d.Cantidad), d.CodArticulo, d.CodDeposito, '',
                   1, @Fecha, 0, @UltRenglon + ROW_NUMBER() OVER (ORDER BY d.Fila), @ProxInterno, 0, 0, 0, 0,
                   0, 'AJ', d.TipoMov, 0, 0, 0, 0, 0,
                   0, 7, 'P', 0, 0,
                   0, 0, 0, 0, ''
            FROM #Det d
            WHERE d.CodDeposito = @Dep AND d.TipoMov = @TipoMov;

            UPDATE dbo.EB_AjusteStockComp
            SET UltRenglon = UltRenglon + @@ROWCOUNT
            WHERE IdLote = @IdLote AND CodDeposito = @Dep AND TipoMov = @TipoMov;

            UPDATE d SET d.NroComprobante = LTRIM(RTRIM(@Proximo))
            FROM dbo.EB_AjusteStockDet d
            JOIN #Det t ON t.Fila = d.Fila
            WHERE d.IdLote = @IdLote AND t.CodDeposito = @Dep AND t.TipoMov = @TipoMov;

            FETCH NEXT FROM cur INTO @Dep, @TipoMov;
        END
        CLOSE cur;
        DEALLOCATE cur;

        /* ---------------------------------------------------------------------
           MARCAR EL BLOQUE COMO EJECUTADO
           --------------------------------------------------------------------- */
        UPDATE d SET d.Ejecutada = 1, d.FechaEjecucion = GETDATE()
        FROM dbo.EB_AjusteStockDet d
        JOIN #Det t ON t.Fila = d.Fila
        WHERE d.IdLote = @IdLote;

        SELECT @Pendientes = COUNT(*) FROM dbo.EB_AjusteStockDet WHERE IdLote = @IdLote AND Ejecutada = 0;

        SELECT @NroComprobantes = LEFT(STUFF((
            SELECT ' | ' + CASE WHEN c.TipoMov = 'S' THEN 'Baja ' ELSE 'Alta ' END + c.CodDeposito + ': ' + c.NroComprobante
            FROM dbo.EB_AjusteStockComp c
            WHERE c.IdLote = @IdLote
            ORDER BY c.TipoMov DESC, c.CodDeposito
            FOR XML PATH('')), 1, 3, ''), 200);

        UPDATE dbo.EB_AjusteStockLote
        SET Estado = CASE WHEN @Pendientes = 0 THEN 'EJECUTADO' ELSE 'EN_PROCESO' END,
            FechaEjecucion = CASE WHEN @Pendientes = 0 THEN GETDATE() ELSE FechaEjecucion END,
            NroComprobantes = @NroComprobantes,
            Mensaje = N'Ejecutado por ' + @Usuario + N' desde ' + @Host
        WHERE IdLote = @IdLote;

        COMMIT;

        EXEC dbo.EB_LogOperation
            @ProcedureName = 'EB_AjusteStock_Procesar',
            @Action = 'BLOQUE',
            @Parameters = @NroComprobantes;

        SELECT * FROM @Errores;
        SELECT CAST(1 AS BIT) AS Ok, @Filas AS FilasProcesadas, @Pendientes AS FilasPendientes,
               CAST(CASE WHEN @Pendientes = 0 THEN 1 ELSE 0 END AS BIT) AS Terminado,
               @NroComprobantes AS NroComprobantes,
               CASE WHEN @Pendientes = 0 THEN N'Ajuste aplicado correctamente.'
                    ELSE N'Bloque aplicado.' END AS Mensaje;
    END TRY
    BEGIN CATCH
        IF XACT_STATE() <> 0 ROLLBACK;

        SELECT @ErrorMessage = ERROR_MESSAGE(), @ErrorLine = ERROR_LINE(), @ErrorNumber = ERROR_NUMBER();

        -- 1222 = espera de bloqueo vencida, 1205 = elegido como víctima de deadlock:
        -- Tango está ocupado; el bloque no se aplicó y la aplicación lo reintenta.
        IF @ErrorNumber IN (1222, 1205)
        BEGIN
            SET @UserMessage = N'TANGO_OCUPADO: otro proceso está usando las tablas de stock. El bloque no se aplicó.';
            RAISERROR(@UserMessage, 16, 1);
            RETURN;
        END

        EXEC dbo.EB_LogError
            @ErrorProcedure = 'EB_AjusteStock_Procesar',
            @ErrorLine = @ErrorLine,
            @ErrorMessage = @ErrorMessage,
            @RootCause = @CurrentOperation;

        SET @UserMessage = CONCAT(
            N'Error en la operación: ', @CurrentOperation, N'. ', @ErrorMessage, N' (línea ', @ErrorLine, N')');
        RAISERROR(@UserMessage, 16, 1);
    END CATCH
END
GO
