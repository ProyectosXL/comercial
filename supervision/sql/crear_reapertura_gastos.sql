-- Script para habilitar la reapertura manual de periodos de gastos de supervision
-- Ejecutar en la base de datos central (LAKER_SA)
--
-- Este script es idempotente: se puede ejecutar mas de una vez sin efecto adicional.
--
-- IMPORTANTE: el paso 3 REEMPLAZA el indice unico UQ_GASTO_SUPERVISION por uno
-- filtrado. Sin ese cambio la anulacion logica no sirve de nada, porque marcar
-- una fila como VIGENTE = 0 no libera la clave unica y el INSERT de la segunda
-- carga sigue fallando con error 2601.

SET ANSI_NULLS ON
SET QUOTED_IDENTIFIER ON
GO

-- =====================================================================
-- 1. Columna VIGENTE en RO_T_GASTOS_SUPERVISION
-- =====================================================================
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_NAME = 'RO_T_GASTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    ALTER TABLE RO_T_GASTOS_SUPERVISION
        ADD VIGENTE BIT NOT NULL
        CONSTRAINT DF_RO_T_GASTOS_SUPERVISION_VIGENTE DEFAULT 1

    PRINT 'Columna VIGENTE agregada a RO_T_GASTOS_SUPERVISION'
END
ELSE
BEGIN
    PRINT 'La columna VIGENTE ya existe en RO_T_GASTOS_SUPERVISION'
END
GO

-- El DEFAULT ya deja en 1 las filas existentes al agregar la columna,
-- pero lo dejamos explicito por si la columna se creo de otra forma.
UPDATE RO_T_GASTOS_SUPERVISION SET VIGENTE = 1 WHERE VIGENTE IS NULL
GO

-- =====================================================================
-- 2. Columna VIGENTE en FU_T_ADELANTOS_SUPERVISION
-- =====================================================================
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_NAME = 'FU_T_ADELANTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    ALTER TABLE FU_T_ADELANTOS_SUPERVISION
        ADD VIGENTE BIT NOT NULL
        CONSTRAINT DF_FU_T_ADELANTOS_SUPERVISION_VIGENTE DEFAULT 1

    PRINT 'Columna VIGENTE agregada a FU_T_ADELANTOS_SUPERVISION'
END
ELSE
BEGIN
    PRINT 'La columna VIGENTE ya existe en FU_T_ADELANTOS_SUPERVISION'
END
GO

UPDATE FU_T_ADELANTOS_SUPERVISION SET VIGENTE = 1 WHERE VIGENTE IS NULL
GO

-- =====================================================================
-- 3. Reemplazo del indice unico por uno filtrado por VIGENTE = 1
-- =====================================================================
-- UQ_GASTO_SUPERVISION es UNIQUE sobre (SEMANA, ANIO, ID_CONC, SUPERVISORA).
-- Al filtrarlo por VIGENTE = 1 las filas anuladas dejan de ocupar la clave,
-- y se sigue garantizando una unica fila vigente por concepto y periodo.
--
-- Ojo: esta implementado como RESTRICCION UNIQUE, no como indice suelto.
-- Aparece igual en sys.indexes, pero DROP INDEX falla con error 3723
-- ("It is being used for UNIQUE KEY constraint enforcement"): hay que sacarlo
-- con ALTER TABLE ... DROP CONSTRAINT. Contemplamos las dos formas por si en
-- algun entorno se creo como indice.
IF EXISTS (SELECT * FROM sys.key_constraints
           WHERE name = 'UQ_GASTO_SUPERVISION'
             AND parent_object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    ALTER TABLE RO_T_GASTOS_SUPERVISION DROP CONSTRAINT UQ_GASTO_SUPERVISION
    PRINT 'Restriccion UNIQUE UQ_GASTO_SUPERVISION eliminada'
END
ELSE IF EXISTS (SELECT * FROM sys.indexes
                WHERE name = 'UQ_GASTO_SUPERVISION'
                  AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    DROP INDEX UQ_GASTO_SUPERVISION ON RO_T_GASTOS_SUPERVISION
    PRINT 'Indice UQ_GASTO_SUPERVISION eliminado'
END
ELSE
BEGIN
    PRINT 'UQ_GASTO_SUPERVISION ya no existe'
END
GO

IF NOT EXISTS (SELECT * FROM sys.indexes
               WHERE name = 'UQ_GASTO_SUPERVISION_VIGENTE'
                 AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    CREATE UNIQUE INDEX UQ_GASTO_SUPERVISION_VIGENTE
        ON RO_T_GASTOS_SUPERVISION (SEMANA, ANIO, ID_CONC, SUPERVISORA)
        WHERE VIGENTE = 1

    PRINT 'Indice filtrado UQ_GASTO_SUPERVISION_VIGENTE creado'
END
ELSE
BEGIN
    PRINT 'El indice UQ_GASTO_SUPERVISION_VIGENTE ya existe'
END
GO

-- Indice de apoyo para la anulacion masiva por periodo
IF NOT EXISTS (SELECT * FROM sys.indexes
               WHERE name = 'IX_GASTO_SUPERVISION_PERIODO'
                 AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    CREATE INDEX IX_GASTO_SUPERVISION_PERIODO
        ON RO_T_GASTOS_SUPERVISION (SEMANA, ANIO, SUPERVISORA) INCLUDE (VIGENTE)

    PRINT 'Indice IX_GASTO_SUPERVISION_PERIODO creado'
END
ELSE
BEGIN
    PRINT 'El indice IX_GASTO_SUPERVISION_PERIODO ya existe'
END
GO

-- =====================================================================
-- 4. Tabla de reaperturas
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

-- Como maximo una reapertura ABIERTA por periodo y supervisora.
-- Es lo que hace que el consumo con compare-and-swap sea seguro: dos pestañas
-- compiten por la misma fila y solo una obtiene rows_affected = 1.
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
-- 5. Verificacion final
-- =====================================================================
-- Los pasos anteriores van en batches separados por GO, asi que si uno falla
-- los siguientes se ejecutan igual y el script termina diciendo "finalizada".
-- Este bloque revisa el estado real y falla ruidosamente si algo quedo a medias.
DECLARE @errores INT = 0

IF EXISTS (SELECT * FROM sys.indexes
           WHERE name = 'UQ_GASTO_SUPERVISION'
             AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    PRINT 'ERROR: UQ_GASTO_SUPERVISION sigue existiendo. La reapertura NO va a funcionar:'
    PRINT '       el INSERT de la segunda carga va a chocar contra las filas anuladas.'
    SET @errores = @errores + 1
END

IF NOT EXISTS (SELECT * FROM sys.indexes
               WHERE name = 'UQ_GASTO_SUPERVISION_VIGENTE'
                 AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION') AND has_filter = 1)
BEGIN
    PRINT 'ERROR: falta el indice filtrado UQ_GASTO_SUPERVISION_VIGENTE.'
    SET @errores = @errores + 1
END

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_NAME = 'RO_T_GASTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    PRINT 'ERROR: falta la columna VIGENTE en RO_T_GASTOS_SUPERVISION.'
    SET @errores = @errores + 1
END

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_NAME = 'FU_T_ADELANTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    PRINT 'ERROR: falta la columna VIGENTE en FU_T_ADELANTOS_SUPERVISION.'
    SET @errores = @errores + 1
END

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='RO_T_REAPERTURAS_GASTOS_SUP' AND xtype='U')
BEGIN
    PRINT 'ERROR: falta la tabla RO_T_REAPERTURAS_GASTOS_SUP.'
    SET @errores = @errores + 1
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
