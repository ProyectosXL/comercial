-- Script para habilitar la reapertura manual de periodos de gastos de supervision
-- Ejecutar en la base de datos central (LAKER_SA)
--
-- Este script es idempotente: se puede ejecutar mas de una vez sin efecto adicional.
--
-- La reapertura es COMPLEMENTARIA: habilita a una supervisora a cargar, por
-- unica vez, los conceptos que todavia no tienen gasto en ese periodo. No
-- modifica ni anula nada de lo ya cargado.
--
-- La restriccion unica UQ_GASTO_SUPERVISION sobre
-- (SEMANA, ANIO, ID_CONC, SUPERVISORA) es justamente lo que garantiza esa regla,
-- porque impide que un concepto tenga una segunda fila en el mismo periodo.
--
-- Los pasos 3 y 4 revierten una version anterior de este script, que planteaba
-- la reapertura como reemplazo con anulacion logica: esa version agregaba una
-- columna VIGENTE y cambiaba UQ_GASTO_SUPERVISION por un indice filtrado. El
-- paso 3 limpia la columna y sus indices, y el paso 4 vuelve a crear la
-- restriccion. Si nunca se ejecuto esa version, ninguno de los dos hace nada.

SET ANSI_NULLS ON
SET QUOTED_IDENTIFIER ON
GO

-- =====================================================================
-- 1. Tabla de reaperturas
-- =====================================================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='RO_T_REAPERTURAS_GASTOS_SUP' AND xtype='U')
BEGIN
    CREATE TABLE RO_T_REAPERTURAS_GASTOS_SUP (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        SEMANA INT NOT NULL,
        ANIO INT NOT NULL,
        SUPERVISORA VARCHAR(50) NOT NULL,
        MOTIVO VARCHAR(500) NOT NULL,
        USUARIO_HABILITA VARCHAR(100) NOT NULL,
        FECHA_HABILITACION DATETIME NOT NULL DEFAULT GETDATE(),
        ESTADO VARCHAR(20) NOT NULL DEFAULT 'ABIERTA', -- 'ABIERTA' | 'CONSUMIDA' | 'ANULADA'
        FECHA_USO DATETIME NULL,
        USUARIO_USO VARCHAR(100) NULL,
        USUARIO_ANULA VARCHAR(100) NULL,
        FECHA_ANULACION DATETIME NULL,
        CONSTRAINT CK_REAPERTURAS_GASTOS_ESTADO
            CHECK (ESTADO IN ('ABIERTA', 'CONSUMIDA', 'ANULADA'))
    )

    CREATE INDEX IX_REAPERTURAS_GASTOS_PERIODO
        ON RO_T_REAPERTURAS_GASTOS_SUP (SEMANA, ANIO, SUPERVISORA)

    PRINT 'Tabla RO_T_REAPERTURAS_GASTOS_SUP creada exitosamente'
END
ELSE
BEGIN
    PRINT 'La tabla RO_T_REAPERTURAS_GASTOS_SUP ya existe'
END
GO

-- =====================================================================
-- 2. Una sola reapertura ABIERTA por periodo y supervisora
-- =====================================================================
-- Es lo que hace seguro el consumo con compare-and-swap: dos pestañas compiten
-- por la misma fila y solo una obtiene rows_affected = 1.
IF NOT EXISTS (SELECT * FROM sys.indexes
               WHERE name = 'UQ_REAPERTURAS_GASTOS_ABIERTA'
                 AND object_id = OBJECT_ID('RO_T_REAPERTURAS_GASTOS_SUP'))
BEGIN
    CREATE UNIQUE INDEX UQ_REAPERTURAS_GASTOS_ABIERTA
        ON RO_T_REAPERTURAS_GASTOS_SUP (SEMANA, ANIO, SUPERVISORA)
        WHERE ESTADO = 'ABIERTA'

    PRINT 'Indice filtrado UQ_REAPERTURAS_GASTOS_ABIERTA creado'
END
ELSE
BEGIN
    PRINT 'El indice UQ_REAPERTURAS_GASTOS_ABIERTA ya existe'
END
GO

-- =====================================================================
-- 3. Limpieza de la columna VIGENTE (version anterior de este script)
-- =====================================================================
-- Con la reapertura complementaria nada se anula nunca, asi que VIGENTE
-- quedaria siempre en 1: una columna que no discrimina nada y un segundo
-- indice unico redundante sobre las mismas columnas que UQ_GASTO_SUPERVISION.
--
-- Solo se limpia si no hay filas anuladas. Si aparece alguna con VIGENTE = 0
-- significa que se llego a usar la version de reemplazo, y borrar la columna
-- perderia la unica marca de que esas filas estaban anuladas: en ese caso el
-- script avisa y no toca nada.
--
-- Todo lo que nombra la columna VIGENTE va por sp_executesql. SQL Server compila
-- el batch completo antes de ejecutarlo, asi que una referencia directa a VIGENTE
-- falla con "Invalid column name" en cualquier base donde la columna no exista
-- (o sea, en una instalacion limpia), aunque el IF que la protege sea falso.
-- Dentro de una cadena no se resuelve hasta que se ejecuta.
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_NAME = 'RO_T_GASTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    PRINT 'La columna VIGENTE no existe: nada que limpiar'
END
ELSE
BEGIN
    DECLARE @anuladas INT

    EXEC sp_executesql
        N'SELECT @cant = COUNT(*) FROM RO_T_GASTOS_SUPERVISION WHERE VIGENTE = 0',
        N'@cant INT OUTPUT',
        @cant = @anuladas OUTPUT

    IF @anuladas > 0
    BEGIN
        PRINT 'AVISO: hay ' + CAST(@anuladas AS VARCHAR(10)) + ' filas con VIGENTE = 0.'
        PRINT '       No se limpia la columna: revisarlas a mano antes de continuar.'
    END
    ELSE
    BEGIN
        -- Los indices primero: IX_GASTO_SUPERVISION_PERIODO incluye VIGENTE, asi que
        -- la columna no se puede borrar mientras exista.
        IF EXISTS (SELECT * FROM sys.indexes
                   WHERE name = 'UQ_GASTO_SUPERVISION_VIGENTE'
                     AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
        BEGIN
            DROP INDEX UQ_GASTO_SUPERVISION_VIGENTE ON RO_T_GASTOS_SUPERVISION
            PRINT 'Indice UQ_GASTO_SUPERVISION_VIGENTE eliminado (redundante)'
        END

        IF EXISTS (SELECT * FROM sys.indexes
                   WHERE name = 'IX_GASTO_SUPERVISION_PERIODO'
                     AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
        BEGIN
            DROP INDEX IX_GASTO_SUPERVISION_PERIODO ON RO_T_GASTOS_SUPERVISION
            PRINT 'Indice IX_GASTO_SUPERVISION_PERIODO eliminado'
        END

        IF EXISTS (SELECT * FROM sys.default_constraints
                   WHERE name = 'DF_RO_T_GASTOS_SUPERVISION_VIGENTE')
        BEGIN
            ALTER TABLE RO_T_GASTOS_SUPERVISION DROP CONSTRAINT DF_RO_T_GASTOS_SUPERVISION_VIGENTE
        END

        EXEC sp_executesql N'ALTER TABLE RO_T_GASTOS_SUPERVISION DROP COLUMN VIGENTE'
        PRINT 'Columna VIGENTE eliminada de RO_T_GASTOS_SUPERVISION'

        IF EXISTS (SELECT * FROM sys.default_constraints
                   WHERE name = 'DF_FU_T_ADELANTOS_SUPERVISION_VIGENTE')
        BEGIN
            ALTER TABLE FU_T_ADELANTOS_SUPERVISION DROP CONSTRAINT DF_FU_T_ADELANTOS_SUPERVISION_VIGENTE
        END

        IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_NAME = 'FU_T_ADELANTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
        BEGIN
            EXEC sp_executesql N'ALTER TABLE FU_T_ADELANTOS_SUPERVISION DROP COLUMN VIGENTE'
            PRINT 'Columna VIGENTE eliminada de FU_T_ADELANTOS_SUPERVISION'
        END
    END
END
GO

-- =====================================================================
-- 4. Restaurar la restriccion unica UQ_GASTO_SUPERVISION
-- =====================================================================
-- Es lo que impide que un concepto reciba una segunda fila en el mismo periodo,
-- o sea la red de seguridad de la reapertura complementaria. La version anterior
-- de este script la eliminaba para reemplazarla por un indice filtrado por
-- VIGENTE; si eso ya se ejecuto, aca se vuelve a crear.
--
-- No se puede crear si hay conceptos repetidos, asi que primero se verifica y,
-- si los hay, se listan y no se toca nada: habria que resolverlos a mano.
IF NOT EXISTS (SELECT * FROM sys.indexes
               WHERE name = 'UQ_GASTO_SUPERVISION'
                 AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    IF EXISTS (SELECT 1 FROM RO_T_GASTOS_SUPERVISION
               GROUP BY SEMANA, ANIO, ID_CONC, SUPERVISORA
               HAVING COUNT(*) > 1)
    BEGIN
        PRINT 'ERROR: hay conceptos con mas de una fila en el mismo periodo.'
        PRINT '       No se puede restaurar UQ_GASTO_SUPERVISION hasta resolverlos.'
        PRINT '       Se listan a continuacion.'

        SELECT SEMANA, ANIO, SUPERVISORA, ID_CONC, COUNT(*) AS FILAS
        FROM RO_T_GASTOS_SUPERVISION
        GROUP BY SEMANA, ANIO, ID_CONC, SUPERVISORA
        HAVING COUNT(*) > 1
        ORDER BY ANIO DESC, SEMANA DESC, SUPERVISORA
    END
    ELSE
    BEGIN
        ALTER TABLE RO_T_GASTOS_SUPERVISION
            ADD CONSTRAINT UQ_GASTO_SUPERVISION
            UNIQUE (SEMANA, ANIO, ID_CONC, SUPERVISORA)

        PRINT 'Restriccion UQ_GASTO_SUPERVISION restaurada'
    END
END
ELSE
BEGIN
    PRINT 'La restriccion UQ_GASTO_SUPERVISION ya existe'
END
GO

-- =====================================================================
-- 5. Verificacion final
-- =====================================================================
-- Los pasos van en batches separados por GO: si uno falla los siguientes se
-- ejecutan igual, asi que sin esto el script podria terminar diciendo
-- "finalizada" con la migracion a medias.
DECLARE @errores INT = 0

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='RO_T_REAPERTURAS_GASTOS_SUP' AND xtype='U')
BEGIN
    PRINT 'ERROR: falta la tabla RO_T_REAPERTURAS_GASTOS_SUP.'
    SET @errores = @errores + 1
END

IF NOT EXISTS (SELECT * FROM sys.indexes
               WHERE name = 'UQ_REAPERTURAS_GASTOS_ABIERTA'
                 AND object_id = OBJECT_ID('RO_T_REAPERTURAS_GASTOS_SUP'))
BEGIN
    PRINT 'ERROR: falta el indice UQ_REAPERTURAS_GASTOS_ABIERTA. Sin el, dos pestañas'
    PRINT '       podrian consumir la misma reapertura.'
    SET @errores = @errores + 1
END

-- La reapertura complementaria depende de esta restriccion para impedir que un
-- concepto reciba una segunda fila en el mismo periodo.
IF NOT EXISTS (SELECT * FROM sys.indexes
               WHERE name = 'UQ_GASTO_SUPERVISION'
                 AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    PRINT 'ERROR: falta UQ_GASTO_SUPERVISION sobre RO_T_GASTOS_SUPERVISION.'
    PRINT '       Es lo que impide cargar dos veces el mismo concepto en un periodo.'
    SET @errores = @errores + 1
END

IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_NAME = 'RO_T_GASTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    PRINT 'AVISO: la columna VIGENTE sigue presente. El codigo ya no la usa.'
END

IF @errores > 0
BEGIN
    RAISERROR('La migracion quedo incompleta: revisar los ERROR de arriba y volver a ejecutar el script.', 16, 1)
END
ELSE
BEGIN
    PRINT '--- Migracion de reapertura de gastos finalizada y verificada ---'
END
GO
