


/* =====================================================================================
   5) RO_Pedido_Editar (reemplaza la versión actual)

   @CAMBIOS: JSON array. Cada elemento:
     - Renglón existente: {"n_renglon":5,"cod_articu":"XT5SBI05Y0504","cantidad":N}
         N = cantidad FINAL del renglón. 0 = eliminar. Menor = reducir. Mayor = aumentar.
     - Artículo o kit a agregar: {"n_renglon":null,"cod_articu":"XC6SBA01C1601","cantidad":N}
         N = unidades A AGREGAR (> 0). Si el artículo ya está en el pedido, se suman a su renglón.

   Reglas nuevas respecto de la versión anterior:
     - Se permiten aumentos y artículos nuevos (simples o kits). Un artículo no se duplica.
     - Al aumentar, el renglón (y los componentes si es kit) toma el precio actual de la lista.
     - Artículos nuevos: precio de la lista del pedido (GVA21.N_LISTA). Sin precio = bloqueo.
     - Excepción: los artículos OU (outlet) sin precio en la lista no bloquean. El renglón
       mantiene su precio actual (los agregados van con precio 0) y se avisa en AVISO del resumen.
     - Stock: lo que se agrega no puede superar el disponible (RO_STOCK_DISPONIBLE) en el
       depósito del pedido. En kits se valida cada componente.
     - Crédito (solo AR, clientes F/M/L): (total nuevo - total anterior) x 1.25 <= disponible.
     - Si falla cualquier validación no se aplica nada (todo o nada).
     - PEN_FAC_RE se actualiza con la misma regla que CANT_A_DES / CANT_A_FAC.

   Result sets (iguales en test y en aplicación):
     1) Resumen   2) Renglones afectados   3) Stock
   ===================================================================================== */
CREATE OR ALTER PROCEDURE dbo.RO_Pedido_Editar
    @TALON_PED   INT,
    @NRO_PEDIDO  VARCHAR(14),
    @CAMBIOS     NVARCHAR(MAX),
    @USUARIO     VARCHAR(50) = NULL,
    @TEST        BIT         = 1         -- 1 = simulación (ROLLBACK) / 0 = aplica (COMMIT)
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    DECLARE @NRO VARCHAR(14), @ESTADO INT, @ID_GVA21 INT, @COD_CLIENT VARCHAR(20), @DEPO VARCHAR(10),
            @TOTAL_ANT DECIMAL(22,7), @TOTAL_CALC_ANT DECIMAL(22,7), @TOTAL_NUEVO DECIMAL(22,7),
            @OPER UNIQUEIDENTIFIER = NEWID(), @MSG NVARCHAR(2000), @LISTA NVARCHAR(1500), @AVISO NVARCHAR(2000) = NULL,
            @TOP_TOTAL INT, @TOP_ELIM INT, @TOP_NUEVOS INT, @MAXR INT,
            @ID_MED_V INT, @ID_MED_S INT,
            @CONTROLA BIT = 0, @CRED_DISP DECIMAL(22,2) = NULL, @CONSUMO DECIMAL(22,2) = 0,
            @HAY_AUMENTOS BIT = 0,
            @ID INT, @N INT, @NP INT, @COD VARCHAR(15), @CANT DECIMAL(22,7), @ESKIT BIT;

    DECLARE @J TABLE (N_RENGLON INT, COD_ARTICU VARCHAR(15) COLLATE Latin1_General_BIN, CANTIDAD DECIMAL(22,7));

    DECLARE @REQ TABLE (
        ID              INT IDENTITY(1,1) PRIMARY KEY,
        N_RENGLON       INT NULL,
        COD_ARTICU      VARCHAR(15) COLLATE Latin1_General_BIN,
        CANTIDAD        DECIMAL(22,7),     -- existente: cantidad final / nuevo: unidades a agregar
        ES_NUEVO        BIT,
        ACCION          VARCHAR(20),       -- ELIMINAR / MODIFICAR (reducir) / AUMENTAR / AGREGAR
        CANT_PEDID_ACT  DECIMAL(22,7),
        RENGL_PADR      INT,
        PROMOCION       INT,
        ES_KIT          BIT DEFAULT 0
    );

    DECLARE @AFECT TABLE (
        N_RENGLON       INT PRIMARY KEY,
        N_REQ           INT,               -- renglón principal del que depende (él mismo o su kit)
        ID_REQ          INT,
        RENGL_PADR      INT,
        COD_ARTICU      VARCHAR(15) COLLATE Latin1_General_BIN,
        COD_DEPOSI      VARCHAR(10) COLLATE Latin1_General_BIN,
        COD_ARTICU_KIT  VARCHAR(15) COLLATE Latin1_General_BIN,
        PROMOCION       INT,
        MUEVE_STOCK     BIT,
        ES_NUEVO        BIT,
        PRECIO_ANT      DECIMAL(22,7),
        PRECIO_NUEVO    DECIMAL(22,7),
        CANT_PEDID_ANT  DECIMAL(22,7),
        CANT_PEN_D_ANT  DECIMAL(22,7),
        CANT_NUEVA      DECIMAL(22,7),
        RENGLON_JSON    NVARCHAR(MAX)
    );

    DECLARE @STOCK TABLE (
        COD_ARTICU    VARCHAR(15) COLLATE Latin1_General_BIN,
        COD_DEPOSI    VARCHAR(10) COLLATE Latin1_General_BIN,
        DELTA         DECIMAL(22,7),
        DISPONIBLE    DECIMAL(22,7),
        COMP_ANTES    DECIMAL(22,7),
        COMP_DESPUES  DECIMAL(22,7),
        PRIMARY KEY (COD_ARTICU, COD_DEPOSI)
    );

    ----------------------------------------------------------------------
    -- Validación del lote recibido
    ----------------------------------------------------------------------
    IF ISJSON(@CAMBIOS) <> 1
        THROW 51020, 'El formato de los cambios es inválido.', 1;

    INSERT INTO @J (N_RENGLON, COD_ARTICU, CANTIDAD)
    SELECT NULLIF(n_renglon, 0), UPPER(LTRIM(RTRIM(cod_articu))), ISNULL(cantidad, 0)
    FROM OPENJSON(@CAMBIOS)
    WITH (n_renglon INT '$.n_renglon', cod_articu VARCHAR(15) '$.cod_articu', cantidad DECIMAL(22,7) '$.cantidad');

    IF NOT EXISTS (SELECT 1 FROM @J)
        THROW 51021, 'No se recibieron cambios.', 1;
    IF EXISTS (SELECT 1 FROM @J WHERE COD_ARTICU IS NULL OR COD_ARTICU = '')
        THROW 51022, 'Hay cambios sin artículo.', 1;
    IF EXISTS (SELECT COD_ARTICU FROM @J GROUP BY COD_ARTICU HAVING COUNT(*) > 1)
        THROW 51023, 'Hay artículos repetidos en los cambios.', 1;
    IF EXISTS (SELECT N_RENGLON FROM @J WHERE N_RENGLON IS NOT NULL GROUP BY N_RENGLON HAVING COUNT(*) > 1)
        THROW 51023, 'Hay renglones repetidos en los cambios.', 1;
    IF EXISTS (SELECT 1 FROM @J WHERE CANTIDAD < 0)
        THROW 51000, 'La cantidad no puede ser negativa.', 1;
    IF EXISTS (SELECT 1 FROM @J WHERE CANTIDAD <> FLOOR(CANTIDAD))
        THROW 51012, 'Solo se permiten cantidades enteras.', 1;
    IF EXISTS (SELECT 1 FROM @J WHERE N_RENGLON IS NULL AND CANTIDAD = 0)
        THROW 51035, 'La cantidad a agregar tiene que ser mayor a 0.', 1;

    INSERT INTO @REQ (N_RENGLON, COD_ARTICU, CANTIDAD, ES_NUEVO)
    SELECT N_RENGLON, COD_ARTICU, CANTIDAD, CASE WHEN N_RENGLON IS NULL THEN 1 ELSE 0 END
    FROM @J;

    ----------------------------------------------------------------------
    -- Crédito: se consulta ANTES de abrir la transacción y solo si hay aumentos
    ----------------------------------------------------------------------
    SELECT @NRO = NRO_PEDIDO, @COD_CLIENT = COD_CLIENT
    FROM GVA21
    WHERE TALON_PED = @TALON_PED AND LTRIM(NRO_PEDIDO) = LTRIM(@NRO_PEDIDO);

    IF @NRO IS NULL
        THROW 51001, 'El pedido no existe.', 1;

    IF EXISTS (SELECT 1 FROM @REQ WHERE ES_NUEVO = 1)
       OR EXISTS (SELECT 1 FROM @REQ R
                  JOIN GVA03 B ON B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO
                              AND B.N_RENGLON = R.N_RENGLON
                  WHERE R.ES_NUEVO = 0 AND R.CANTIDAD > B.CANT_PEDID)
        SET @HAY_AUMENTOS = 1;

    IF @HAY_AUMENTOS = 1
        EXEC dbo.RO_Pedido_Credito @COD_CLIENT, @CONTROLA OUTPUT, @CRED_DISP OUTPUT;

    BEGIN TRY
        BEGIN TRAN;

        ------------------------------------------------------------------
        -- Pedido
        ------------------------------------------------------------------
        SELECT @ESTADO = A.ESTADO, @ID_GVA21 = A.ID_GVA21, @DEPO = A.COD_SUCURS, @TOTAL_ANT = A.TOTAL_PEDI
        FROM GVA21 A WITH (UPDLOCK, HOLDLOCK)
        WHERE A.TALON_PED = @TALON_PED AND A.NRO_PEDIDO = @NRO;

        IF @ESTADO <> 2
            THROW 51002, 'Solo se pueden modificar pedidos pendientes (estado 2).', 1;
        IF EXISTS (SELECT 1 FROM GVA03 WITH (UPDLOCK)
                   WHERE TALON_PED = @TALON_PED AND NRO_PEDIDO = @NRO AND CANT_PEN_F <> CANT_PEDID)
            THROW 51003, 'El pedido tiene facturación. No se puede modificar.', 1;

        SELECT @TOTAL_CALC_ANT = ISNULL(SUM(CANT_PEDID * PRECIO), 0)
        FROM GVA03
        WHERE TALON_PED = @TALON_PED AND NRO_PEDIDO = @NRO AND PROMOCION = 0;

        SELECT @MAXR = ISNULL(MAX(N_RENGLON), 0)
        FROM GVA03 WHERE TALON_PED = @TALON_PED AND NRO_PEDIDO = @NRO;

        -- Unidades de medida: se copian de un renglón existente del pedido (fallback 7, como los SP de carga)
        SELECT TOP 1 @ID_MED_V = ID_MEDIDA_VENTAS, @ID_MED_S = ID_MEDIDA_STOCK
        FROM GVA03
        WHERE TALON_PED = @TALON_PED AND NRO_PEDIDO = @NRO AND RENGL_PADR = 0
        ORDER BY N_RENGLON;
        SET @ID_MED_V = ISNULL(@ID_MED_V, 7);
        SET @ID_MED_S = ISNULL(@ID_MED_S, 7);

        ------------------------------------------------------------------
        -- Artículos "nuevos" que ya están en el pedido: se suman a su renglón
        ------------------------------------------------------------------
        UPDATE R
        SET N_RENGLON = B.N_RENGLON,
            CANTIDAD  = B.CANT_PEDID + R.CANTIDAD,
            ES_NUEVO  = 0
        FROM @REQ R
        JOIN GVA03 B ON B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO
                    AND B.COD_ARTICU = R.COD_ARTICU AND B.RENGL_PADR = 0
        WHERE R.ES_NUEVO = 1;

        IF EXISTS (SELECT N_RENGLON FROM @REQ WHERE N_RENGLON IS NOT NULL GROUP BY N_RENGLON HAVING COUNT(*) > 1)
            THROW 51023, 'Hay renglones repetidos en los cambios.', 1;

        -- Artículo que solo está en el pedido como componente de un kit
        IF EXISTS (SELECT 1 FROM @REQ R WHERE R.ES_NUEVO = 1
                   AND EXISTS (SELECT 1 FROM GVA03 B
                               WHERE B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO
                                 AND B.COD_ARTICU = R.COD_ARTICU AND B.RENGL_PADR <> 0))
        BEGIN
            SET @LISTA = STUFF((SELECT ', ' + R.COD_ARTICU FROM @REQ R WHERE R.ES_NUEVO = 1
                                AND EXISTS (SELECT 1 FROM GVA03 B
                                            WHERE B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO
                                              AND B.COD_ARTICU = R.COD_ARTICU AND B.RENGL_PADR <> 0)
                                FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('Estos artículos ya están en el pedido como componentes de un kit: ', @LISTA, '.');
            THROW 51032, @MSG, 1;
        END

        ------------------------------------------------------------------
        -- Renglones existentes
        ------------------------------------------------------------------
        UPDATE R
        SET CANT_PEDID_ACT = B.CANT_PEDID, RENGL_PADR = B.RENGL_PADR, PROMOCION = B.PROMOCION,
            ES_KIT = CASE WHEN B.PROMOCION = 1 THEN 1 ELSE 0 END
        FROM @REQ R
        JOIN GVA03 B WITH (UPDLOCK)
          ON B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO
         AND B.N_RENGLON = R.N_RENGLON AND B.COD_ARTICU = R.COD_ARTICU
        WHERE R.ES_NUEVO = 0;

        IF EXISTS (SELECT 1 FROM @REQ WHERE ES_NUEVO = 0 AND CANT_PEDID_ACT IS NULL)
        BEGIN
            SET @LISTA = STUFF((SELECT ', ' + CAST(N_RENGLON AS VARCHAR(10)) FROM @REQ
                                WHERE ES_NUEVO = 0 AND CANT_PEDID_ACT IS NULL FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('El pedido cambió desde que se abrió. Renglones no encontrados: ', @LISTA, '. Volver a cargar el pedido.');
            THROW 51004, @MSG, 1;
        END

        IF EXISTS (SELECT 1 FROM @REQ WHERE ES_NUEVO = 0 AND RENGL_PADR <> 0)
        BEGIN
            SET @LISTA = STUFF((SELECT ', ' + CAST(N_RENGLON AS VARCHAR(10)) FROM @REQ
                                WHERE ES_NUEVO = 0 AND RENGL_PADR <> 0 FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('Los componentes de kit no se editan directamente. Renglones: ', @LISTA, '.');
            THROW 51006, @MSG, 1;
        END

        IF EXISTS (SELECT 1 FROM @REQ R WHERE R.ES_NUEVO = 0 AND R.PROMOCION = 1
                   AND NOT EXISTS (SELECT 1 FROM GVA03 H
                                   WHERE H.TALON_PED = @TALON_PED AND H.NRO_PEDIDO = @NRO
                                     AND H.RENGL_PADR = R.N_RENGLON))
            THROW 51007, 'Hay un kit sin componentes en el pedido. Revisar el pedido.', 1;

        IF EXISTS (SELECT 1 FROM @REQ WHERE ES_NUEVO = 0 AND CANTIDAD = CANT_PEDID_ACT)
        BEGIN
            SET @LISTA = STUFF((SELECT ', ' + CAST(N_RENGLON AS VARCHAR(10)) FROM @REQ
                                WHERE ES_NUEVO = 0 AND CANTIDAD = CANT_PEDID_ACT FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('La cantidad nueva es igual a la actual. Renglones: ', @LISTA, '.');
            THROW 51010, @MSG, 1;
        END

        UPDATE @REQ
        SET ACCION = CASE WHEN CANTIDAD = 0 THEN 'ELIMINAR'
                          WHEN CANTIDAD < CANT_PEDID_ACT THEN 'MODIFICAR'
                          ELSE 'AUMENTAR' END
        WHERE ES_NUEVO = 0;

        ------------------------------------------------------------------
        -- Artículos nuevos: tipo (kit o simple) y validez
        ------------------------------------------------------------------
        UPDATE R
        SET ACCION = 'AGREGAR',
            ES_KIT = CASE WHEN EXISTS (SELECT 1 FROM STA03 K WHERE K.COD_ARTICU = R.COD_ARTICU) THEN 1 ELSE 0 END
        FROM @REQ R
        WHERE R.ES_NUEVO = 1;

        IF EXISTS (SELECT 1 FROM @REQ R WHERE R.ES_NUEVO = 1 AND (
                     (R.ES_KIT = 0 AND NOT EXISTS (SELECT 1 FROM STA11 S
                                                   WHERE S.COD_ARTICU = R.COD_ARTICU AND S.COD_ARTICU LIKE '[XO]%'
                                                     AND S.USA_ESC <> 'B' AND S.TIPO_PROMO = ''))
                  OR (R.ES_KIT = 1 AND NOT EXISTS (SELECT 1 FROM STA11 S WHERE S.COD_ARTICU = R.COD_ARTICU))))
        BEGIN
            SET @LISTA = STUFF((SELECT ', ' + R.COD_ARTICU FROM @REQ R WHERE R.ES_NUEVO = 1 AND (
                                  (R.ES_KIT = 0 AND NOT EXISTS (SELECT 1 FROM STA11 S
                                                                WHERE S.COD_ARTICU = R.COD_ARTICU AND S.COD_ARTICU LIKE '[XO]%'
                                                                  AND S.USA_ESC <> 'B' AND S.TIPO_PROMO = ''))
                               OR (R.ES_KIT = 1 AND NOT EXISTS (SELECT 1 FROM STA11 S WHERE S.COD_ARTICU = R.COD_ARTICU)))
                                FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('Estos artículos no se pueden agregar al pedido: ', @LISTA, '.');
            THROW 51033, @MSG, 1;
        END

        ------------------------------------------------------------------
        -- Renglones afectados existentes (solicitados + componentes de kits)
        ------------------------------------------------------------------
        INSERT INTO @AFECT (N_RENGLON, N_REQ, ID_REQ, RENGL_PADR, COD_ARTICU, COD_DEPOSI, COD_ARTICU_KIT,
                            PROMOCION, MUEVE_STOCK, ES_NUEVO, PRECIO_ANT, CANT_PEDID_ANT, CANT_PEN_D_ANT)
        SELECT B.N_RENGLON, R.N_RENGLON, R.ID, B.RENGL_PADR, B.COD_ARTICU, B.COD_DEPOSI, B.COD_ARTICU_KIT,
               B.PROMOCION, CASE WHEN B.PROMOCION = 1 THEN 0 ELSE 1 END, 0,
               B.PRECIO, B.CANT_PEDID, B.CANT_PEN_D
        FROM GVA03 B WITH (UPDLOCK)
        JOIN @REQ R ON R.ES_NUEVO = 0
                   AND (B.N_RENGLON = R.N_RENGLON OR (R.PROMOCION = 1 AND B.RENGL_PADR = R.N_RENGLON))
        WHERE B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO;

        -- Remito: bloquea el renglón (o el kit si algún componente tiene remito)
        IF EXISTS (SELECT 1 FROM @AFECT WHERE CANT_PEN_D_ANT < CANT_PEDID_ANT)
        BEGIN
            SET @LISTA = STUFF((SELECT DISTINCT ', ' + CAST(N_REQ AS VARCHAR(10)) FROM @AFECT
                                WHERE CANT_PEN_D_ANT < CANT_PEDID_ANT FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('Hay renglones con remito (o kits con componentes con remito): ', @LISTA, '.');
            THROW 51008, @MSG, 1;
        END

        -- Cantidades: el solicitado toma la cantidad indicada; los componentes, proporcional
        UPDATE F
        SET CANT_NUEVA = CASE WHEN R.CANTIDAD = 0 THEN 0
                              WHEN F.N_RENGLON = R.N_RENGLON THEN R.CANTIDAD
                              ELSE CAST(F.CANT_PEDID_ANT * R.CANTIDAD / R.CANT_PEDID_ACT AS DECIMAL(22,7)) END
        FROM @AFECT F
        JOIN @REQ R ON R.ID = F.ID_REQ;

        -- Precio: al aumentar se toma el precio actual de la lista; en el resto se mantiene
        UPDATE F
        SET PRECIO_NUEVO = CASE WHEN R.ACCION = 'AUMENTAR' THEN P.PRECIO ELSE F.PRECIO_ANT END
        FROM @AFECT F
        JOIN @REQ R ON R.ID = F.ID_REQ
        LEFT JOIN GVA17 P ON P.COD_ARTICU = F.COD_ARTICU
                         AND P.NRO_DE_LIS = (SELECT N_LISTA FROM GVA21 WHERE ID_GVA21 = @ID_GVA21);

        ------------------------------------------------------------------
        -- Renglones nuevos (artículos simples y kits con sus componentes)
        ------------------------------------------------------------------
        SET @N = @MAXR;
        SELECT @ID = MIN(ID) FROM @REQ WHERE ES_NUEVO = 1;

        WHILE @ID IS NOT NULL
        BEGIN
            SELECT @COD = COD_ARTICU, @CANT = CANTIDAD, @ESKIT = ES_KIT FROM @REQ WHERE ID = @ID;

            SET @N = @N + 1;
            SET @NP = @N;

            INSERT INTO @AFECT (N_RENGLON, N_REQ, ID_REQ, RENGL_PADR, COD_ARTICU, COD_DEPOSI, COD_ARTICU_KIT,
                                PROMOCION, MUEVE_STOCK, ES_NUEVO, PRECIO_ANT, PRECIO_NUEVO,
                                CANT_PEDID_ANT, CANT_PEN_D_ANT, CANT_NUEVA)
            VALUES (@NP, @NP, @ID, 0, @COD, @DEPO, CASE WHEN @ESKIT = 1 THEN @COD END,
                    CASE WHEN @ESKIT = 1 THEN 1 ELSE 0 END, CASE WHEN @ESKIT = 1 THEN 0 ELSE 1 END, 1, NULL,
                    (SELECT P.PRECIO FROM GVA17 P
                     WHERE P.COD_ARTICU = @COD
                       AND P.NRO_DE_LIS = (SELECT N_LISTA FROM GVA21 WHERE ID_GVA21 = @ID_GVA21)),
                    0, 0, @CANT);

            IF @ESKIT = 1
            BEGIN
                INSERT INTO @AFECT (N_RENGLON, N_REQ, ID_REQ, RENGL_PADR, COD_ARTICU, COD_DEPOSI, COD_ARTICU_KIT,
                                    PROMOCION, MUEVE_STOCK, ES_NUEVO, PRECIO_ANT, PRECIO_NUEVO,
                                    CANT_PEDID_ANT, CANT_PEN_D_ANT, CANT_NUEVA)
                SELECT @NP + ROW_NUMBER() OVER (ORDER BY K.COD_INSUMO), @NP, @ID, @NP, K.COD_INSUMO, @DEPO, @COD,
                       0, 1, 1, NULL,
                       (SELECT P.PRECIO FROM GVA17 P
                        WHERE P.COD_ARTICU = K.COD_INSUMO
                          AND P.NRO_DE_LIS = (SELECT N_LISTA FROM GVA21 WHERE ID_GVA21 = @ID_GVA21)),
                       0, 0, CAST(K.CANTIDAD AS DECIMAL(22,7)) * @CANT
                FROM STA03 K
                WHERE K.COD_ARTICU = @COD;

                SET @N = @N + @@ROWCOUNT;
            END

            SELECT @ID = MIN(ID) FROM @REQ WHERE ES_NUEVO = 1 AND ID > @ID;
        END

        -- Artículos OU sin precio en la lista: no bloquean. Mantienen el precio del renglón (0 si es nuevo) y se avisa
        IF EXISTS (SELECT 1 FROM @AFECT WHERE PRECIO_NUEVO IS NULL AND COD_ARTICU LIKE 'OU%')
        BEGIN
            SET @LISTA = STUFF((SELECT DISTINCT ', ' + COD_ARTICU FROM @AFECT
                                WHERE PRECIO_NUEVO IS NULL AND COD_ARTICU LIKE 'OU%' FOR XML PATH('')), 1, 2, '');
            SET @AVISO = CONCAT('Estos artículos OU no tienen precio en la lista del pedido; se mantiene el precio del renglón (los agregados van con precio 0): ', @LISTA, '.');

            UPDATE @AFECT SET PRECIO_NUEVO = ISNULL(PRECIO_ANT, 0)
            WHERE PRECIO_NUEVO IS NULL AND COD_ARTICU LIKE 'OU%';
        END

        -- Sin precio en la lista del pedido (aumentos y artículos nuevos)
        IF EXISTS (SELECT 1 FROM @AFECT WHERE PRECIO_NUEVO IS NULL)
        BEGIN
            SET @LISTA = STUFF((SELECT DISTINCT ', ' + COD_ARTICU FROM @AFECT
                                WHERE PRECIO_NUEVO IS NULL FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('Estos artículos no tienen precio en la lista del pedido: ', @LISTA, '.');
            THROW 51031, @MSG, 1;
        END

        IF EXISTS (SELECT 1 FROM @AFECT WHERE CANT_NUEVA <> FLOOR(CANT_NUEVA))
        BEGIN
            SET @LISTA = STUFF((SELECT DISTINCT ', ' + CAST(N_REQ AS VARCHAR(10)) FROM @AFECT
                                WHERE CANT_NUEVA <> FLOOR(CANT_NUEVA) FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('La cantidad indicada deja componentes de kit con cantidades no enteras. Renglones: ', @LISTA, '.');
            THROW 51013, @MSG, 1;
        END

        -- No se puede dejar el pedido sin renglones
        SELECT @TOP_TOTAL = COUNT(*) FROM GVA03
        WHERE TALON_PED = @TALON_PED AND NRO_PEDIDO = @NRO AND RENGL_PADR = 0;
        SELECT @TOP_ELIM   = COUNT(*) FROM @REQ WHERE ACCION = 'ELIMINAR';
        SELECT @TOP_NUEVOS = COUNT(*) FROM @REQ WHERE ES_NUEVO = 1;

        IF @TOP_TOTAL - @TOP_ELIM + @TOP_NUEVOS < 1
            THROW 51009, 'No se pueden eliminar todos los renglones. Anular el pedido.', 1;

        ------------------------------------------------------------------
        -- Stock (comunes y componentes; los kits no mueven stock)
        ------------------------------------------------------------------
        INSERT INTO @STOCK (COD_ARTICU, COD_DEPOSI, DELTA)
        SELECT COD_ARTICU, COD_DEPOSI, SUM(CANT_NUEVA - CANT_PEN_D_ANT)
        FROM @AFECT WHERE MUEVE_STOCK = 1
        GROUP BY COD_ARTICU, COD_DEPOSI;

        DELETE FROM @STOCK WHERE DELTA = 0;

        UPDATE K SET COMP_ANTES = S.CANT_COMP
        FROM @STOCK K
        JOIN STA19 S WITH (UPDLOCK, HOLDLOCK) ON S.COD_ARTICU = K.COD_ARTICU AND S.COD_DEPOSI = K.COD_DEPOSI;

        -- Liberar: el comprometido tiene que existir y alcanzar
        IF EXISTS (SELECT 1 FROM @STOCK WHERE DELTA < 0 AND (COMP_ANTES IS NULL OR COMP_ANTES + DELTA < 0))
        BEGIN
            SET @LISTA = STUFF((SELECT ', ' + COD_ARTICU FROM @STOCK
                                WHERE DELTA < 0 AND (COMP_ANTES IS NULL OR COMP_ANTES + DELTA < 0)
                                FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('Comprometido en STA19 inexistente o insuficiente para: ', @LISTA, '.');
            THROW 51011, @MSG, 1;
        END

        -- Comprometer: lo agregado no puede superar el stock disponible
        UPDATE K SET DISPONIBLE = ISNULL(V.STOCK_DISPONIBLE, 0)
        FROM @STOCK K
        LEFT JOIN dbo.RO_STOCK_DISPONIBLE V ON V.COD_ARTICU = K.COD_ARTICU AND V.COD_DEPOSI = K.COD_DEPOSI
        WHERE K.DELTA > 0;

        IF EXISTS (SELECT 1 FROM @STOCK WHERE DELTA > 0 AND (DISPONIBLE < DELTA OR COMP_ANTES IS NULL))
        BEGIN
            SET @LISTA = STUFF((
                SELECT '; ' + K.COD_ARTICU
                       + ISNULL(' (kit ' + (SELECT MAX(F.COD_ARTICU_KIT) FROM @AFECT F
                                            WHERE F.COD_ARTICU = K.COD_ARTICU AND F.COD_ARTICU_KIT IS NOT NULL
                                              AND F.MUEVE_STOCK = 1) + ')', '')
                       + ': requerido ' + CAST(CAST(K.DELTA AS INT) AS VARCHAR(20))
                       + ', disponible ' + CAST(CAST(ISNULL(K.DISPONIBLE, 0) AS INT) AS VARCHAR(20))
                FROM @STOCK K
                WHERE K.DELTA > 0 AND (K.DISPONIBLE < K.DELTA OR K.COMP_ANTES IS NULL)
                FOR XML PATH('')), 1, 2, '');
            SET @MSG = CONCAT('Stock insuficiente en el depósito ', @DEPO, ' para: ', @LISTA, '.');
            THROW 51034, @MSG, 1;
        END

        ------------------------------------------------------------------
        -- Snapshot de renglones existentes
        ------------------------------------------------------------------
        UPDATE F
        SET RENGLON_JSON = (SELECT B.* FROM GVA03 B
                            WHERE B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO
                              AND B.N_RENGLON = F.N_RENGLON
                            FOR JSON PATH, WITHOUT_ARRAY_WRAPPER)
        FROM @AFECT F
        WHERE F.ES_NUEVO = 0;

        ------------------------------------------------------------------
        -- Aplicar cambios
        ------------------------------------------------------------------
        UPDATE S SET S.CANT_COMP = S.CANT_COMP + K.DELTA
        FROM STA19 S
        JOIN @STOCK K ON K.COD_ARTICU = S.COD_ARTICU AND K.COD_DEPOSI = S.COD_DEPOSI;

        UPDATE @STOCK SET COMP_DESPUES = COMP_ANTES + DELTA;

        -- Eliminar
        DELETE B
        FROM GVA03 B
        JOIN @AFECT F ON F.N_RENGLON = B.N_RENGLON
        WHERE B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO
          AND F.ES_NUEVO = 0 AND F.CANT_NUEVA = 0;

        -- Reducir o aumentar
        UPDATE B
        SET B.CANT_PEDID = F.CANT_NUEVA,
            B.CANT_PEN_D = F.CANT_NUEVA,
            B.CANT_PEN_F = F.CANT_NUEVA,
            B.CANT_A_DES = CASE WHEN B.CANT_A_DES = F.CANT_PEDID_ANT OR B.CANT_A_DES > F.CANT_NUEVA
                                THEN F.CANT_NUEVA ELSE B.CANT_A_DES END,
            B.CANT_A_FAC = CASE WHEN B.CANT_A_FAC = F.CANT_PEDID_ANT OR B.CANT_A_FAC > F.CANT_NUEVA
                                THEN F.CANT_NUEVA ELSE B.CANT_A_FAC END,
            B.PEN_FAC_RE = CASE WHEN B.PEN_FAC_RE = F.CANT_PEDID_ANT OR B.PEN_FAC_RE > F.CANT_NUEVA
                                THEN F.CANT_NUEVA ELSE B.PEN_FAC_RE END,
            B.PRECIO     = F.PRECIO_NUEVO
        FROM GVA03 B
        JOIN @AFECT F ON F.N_RENGLON = B.N_RENGLON
        WHERE B.TALON_PED = @TALON_PED AND B.NRO_PEDIDO = @NRO
          AND F.ES_NUEVO = 0 AND F.CANT_NUEVA > 0;

        -- Agregar (mismos valores que SJ_PEDIDOS_DETALLE_SIMPLE / _KIT_ENCABEZADO / _KIT)
        INSERT INTO GVA03
        (
            CAN_EQUI_V, CANT_A_DES, CANT_A_FAC, CANT_PEDID, CANT_PEN_D, CANT_PEN_F, COD_ARTICU, DESCUENTO,
            N_RENGLON, NRO_PEDIDO, PEN_REM_FC, PEN_FAC_RE, PRECIO, TALON_PED,
            CANT_A_DES_2, CANT_A_FAC_2, CANT_PEDID_2, CANT_PEN_D_2, CANT_PEN_F_2, PEN_REM_FC_2,
            ID_MEDIDA_VENTAS, ID_MEDIDA_STOCK, UNIDAD_MEDIDA_SELECCIONADA, RENGL_PADR,
            PROMOCION, PRECIO_ADICIONAL_KIT, KIT_COMPLETO, INSUMO_KIT_SEPARADO, PRECIO_LISTA, PRECIO_BONIF,
            DESCUENTO_PARAM, COD_ARTICU_KIT,
            IMPORTE_CON_IMPUESTOS, IMPORTE_PROPORCIONADO, IMPORTE_SIN_IMPUESTOS, NRO_ORDEN, ID_GVA21, COD_DEPOSI
        )
        SELECT
            1, F.CANT_NUEVA, F.CANT_NUEVA, F.CANT_NUEVA, F.CANT_NUEVA, F.CANT_NUEVA, F.COD_ARTICU, 0,
            F.N_RENGLON, @NRO, 0, CASE WHEN F.COD_ARTICU_KIT IS NOT NULL THEN F.CANT_NUEVA ELSE 0 END,
            F.PRECIO_NUEVO, @TALON_PED,
            0, 0, 0, 0, 0, 0,
            @ID_MED_V, @ID_MED_S, CASE WHEN F.RENGL_PADR <> 0 THEN 'P' ELSE 'V' END, F.RENGL_PADR,
            F.PROMOCION, 0, CASE WHEN F.COD_ARTICU_KIT IS NOT NULL THEN 1 ELSE 0 END, 0, 0, 0,
            0, F.COD_ARTICU_KIT,
            0, 0, 0, 0, @ID_GVA21, F.COD_DEPOSI
        FROM @AFECT F
        WHERE F.ES_NUEVO = 1
        ORDER BY F.N_RENGLON;

        -- Total del pedido
        SELECT @TOTAL_NUEVO = ISNULL(SUM(CANT_PEDID * PRECIO), 0)
        FROM GVA03
        WHERE TALON_PED = @TALON_PED AND NRO_PEDIDO = @NRO AND PROMOCION = 0;

        UPDATE GVA21 SET TOTAL_PEDI = @TOTAL_NUEVO
        WHERE TALON_PED = @TALON_PED AND NRO_PEDIDO = @NRO;

        ------------------------------------------------------------------
        -- Crédito: diferencia neta del pedido x 1.25 contra el disponible
        ------------------------------------------------------------------
        SET @CONSUMO = CAST((@TOTAL_NUEVO - @TOTAL_CALC_ANT) * 1.25 AS DECIMAL(22,2));

        IF @CONTROLA = 1 AND @CONSUMO > 0
        BEGIN
            IF @CRED_DISP IS NULL
                THROW 51040, 'No se pudo obtener el crédito disponible del cliente.', 1;

            IF @CONSUMO > @CRED_DISP
            BEGIN
                SET @MSG = CONCAT('Crédito insuficiente. Disponible: $ ', FORMAT(@CRED_DISP, 'N2', 'es-AR'),
                                  ' · Requerido: $ ', FORMAT(@CONSUMO, 'N2', 'es-AR'), '.');
                THROW 51041, @MSG, 1;
            END
        END

        ------------------------------------------------------------------
        -- Log (un registro por renglón afectado)
        ------------------------------------------------------------------
        INSERT INTO dbo.RO_LOG_EDICION_PEDIDO
            (OPERACION, USUARIO, ACCION, TALON_PED, NRO_PEDIDO, N_RENGLON, RENGL_PADR,
             COD_ARTICU, COD_DEPOSI, PROMOCION, CANT_PEDID_ANT, CANT_PEDID_NUEVA,
             DELTA_COMP, COMP_ANTES, COMP_DESPUES, TOTAL_PEDI_ANT, TOTAL_PEDI_NUEVO, RENGLON_JSON)
        SELECT @OPER, @USUARIO, R.ACCION, @TALON_PED, @NRO, F.N_RENGLON, F.RENGL_PADR,
               F.COD_ARTICU, F.COD_DEPOSI, F.PROMOCION, F.CANT_PEDID_ANT, F.CANT_NUEVA,
               CASE WHEN F.MUEVE_STOCK = 1 THEN F.CANT_NUEVA - F.CANT_PEN_D_ANT END,
               K.COMP_ANTES, K.COMP_DESPUES, @TOTAL_ANT, @TOTAL_NUEVO, F.RENGLON_JSON
        FROM @AFECT F
        JOIN @REQ R ON R.ID = F.ID_REQ
        LEFT JOIN @STOCK K ON F.MUEVE_STOCK = 1
                          AND K.COD_ARTICU = F.COD_ARTICU AND K.COD_DEPOSI = F.COD_DEPOSI;

        IF @TEST = 0 COMMIT;

        ------------------------------------------------------------------
        -- Resultados (iguales en test y en aplicación)
        ------------------------------------------------------------------
        -- 1) Resumen
        SELECT CASE WHEN @TEST = 1 THEN 'SIMULACION' ELSE 'OK' END AS RESULTADO,
               @OPER AS OPERACION, @TALON_PED AS TALON_PED, LTRIM(@NRO) AS NRO_PEDIDO,
               (SELECT COUNT(*) FROM @REQ WHERE ACCION = 'ELIMINAR')  AS ELIMINADOS,
               (SELECT COUNT(*) FROM @REQ WHERE ACCION = 'MODIFICAR') AS MODIFICADOS,
               (SELECT COUNT(*) FROM @REQ WHERE ACCION = 'AUMENTAR')  AS AUMENTADOS,
               (SELECT COUNT(*) FROM @REQ WHERE ACCION = 'AGREGAR')   AS AGREGADOS,
               (SELECT COUNT(*) FROM @AFECT) AS RENGLONES_AFECTADOS,
               @TOTAL_ANT AS TOTAL_PEDI_ANT, @TOTAL_NUEVO AS TOTAL_PEDI_NUEVO,
               @CONTROLA AS CONTROLA_CREDITO,
               CASE WHEN @CONTROLA = 1 THEN @CRED_DISP END AS CREDITO_DISPONIBLE_ANTES,
               CASE WHEN @CONTROLA = 1 THEN @CONSUMO END AS CONSUMO_CREDITO,
               CASE WHEN @CONTROLA = 1 THEN @CRED_DISP - @CONSUMO END AS CREDITO_DISPONIBLE_DESPUES,
               @AVISO AS AVISO;

        -- 2) Renglones afectados
        SELECT F.N_RENGLON, F.N_REQ, F.RENGL_PADR, F.COD_ARTICU, F.PROMOCION,
               ISNULL(F.PRECIO_NUEVO, F.PRECIO_ANT) AS PRECIO, F.PRECIO_ANT, F.PRECIO_NUEVO,
               F.CANT_PEDID_ANT, F.CANT_NUEVA, F.ES_NUEVO,
               CASE WHEN F.ES_NUEVO = 1 THEN 'AGREGADO'
                    WHEN F.CANT_NUEVA = 0 THEN 'ELIMINADO'
                    WHEN F.CANT_NUEVA > F.CANT_PEDID_ANT THEN 'AUMENTADO'
                    ELSE 'MODIFICADO' END AS ESTADO_RENGLON
        FROM @AFECT F
        ORDER BY F.N_REQ, F.RENGL_PADR, F.N_RENGLON;

        -- 3) Stock
        SELECT COD_ARTICU, COD_DEPOSI, DISPONIBLE AS STOCK_DISPONIBLE, COMP_ANTES, DELTA, COMP_DESPUES
        FROM @STOCK
        ORDER BY COD_ARTICU;

        IF @TEST = 1 ROLLBACK;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        THROW;
    END CATCH
END;
