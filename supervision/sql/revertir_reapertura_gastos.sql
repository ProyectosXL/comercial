-- Rollback de crear_reapertura_gastos.sql
-- Ejecutar en la base de datos central (LAKER_SA)
--
-- ATENCION: solo es seguro si NO se consumio ninguna reapertura todavia.
-- Si ya hay filas con VIGENTE = 0 en RO_T_GASTOS_SUPERVISION, volver al indice
-- unico sin filtro va a fallar, porque esas filas anuladas conviven con la vigente
-- del mismo concepto y periodo. El bloque 1 aborta el script en ese caso.
--
-- Para revertir igual habria que decidir primero que hacer con esas filas
-- (borrarlas o archivarlas). Este script no lo hace por su cuenta.

SET ANSI_NULLS ON
SET QUOTED_IDENTIFIER ON
GO

-- =====================================================================
-- 1. Control previo: no puede haber filas anuladas
-- =====================================================================
IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_NAME = 'RO_T_GASTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    IF EXISTS (SELECT * FROM RO_T_GASTOS_SUPERVISION WHERE VIGENTE = 0)
    BEGIN
        RAISERROR('Hay filas con VIGENTE = 0. Resolver esas filas antes de revertir.', 16, 1)
        SET NOEXEC ON
    END
END
GO

-- =====================================================================
-- 2. Tabla de reaperturas
-- =====================================================================
IF EXISTS (SELECT * FROM sysobjects WHERE name='RO_T_REAPERTURAS_GASTOS_SUP' AND xtype='U')
BEGIN
    DROP TABLE RO_T_REAPERTURAS_GASTOS_SUP
    PRINT 'Tabla RO_T_REAPERTURAS_GASTOS_SUP eliminada'
END
GO

-- =====================================================================
-- 3. Indices
-- =====================================================================
IF EXISTS (SELECT * FROM sys.indexes
           WHERE name = 'UQ_GASTO_SUPERVISION_VIGENTE'
             AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    DROP INDEX UQ_GASTO_SUPERVISION_VIGENTE ON RO_T_GASTOS_SUPERVISION
    PRINT 'Indice UQ_GASTO_SUPERVISION_VIGENTE eliminado'
END
GO

IF EXISTS (SELECT * FROM sys.indexes
           WHERE name = 'IX_GASTO_SUPERVISION_PERIODO'
             AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    DROP INDEX IX_GASTO_SUPERVISION_PERIODO ON RO_T_GASTOS_SUPERVISION
    PRINT 'Indice IX_GASTO_SUPERVISION_PERIODO eliminado'
END
GO

-- Se restaura como RESTRICCION UNIQUE, que es como estaba originalmente,
-- no como indice suelto.
IF NOT EXISTS (SELECT * FROM sys.indexes
               WHERE name = 'UQ_GASTO_SUPERVISION'
                 AND object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION'))
BEGIN
    ALTER TABLE RO_T_GASTOS_SUPERVISION
        ADD CONSTRAINT UQ_GASTO_SUPERVISION
        UNIQUE (SEMANA, ANIO, ID_CONC, SUPERVISORA)

    PRINT 'Restriccion UQ_GASTO_SUPERVISION restaurada'
END
GO

-- =====================================================================
-- 4. Columnas VIGENTE
-- =====================================================================
IF EXISTS (SELECT * FROM sys.default_constraints WHERE name = 'DF_RO_T_GASTOS_SUPERVISION_VIGENTE')
BEGIN
    ALTER TABLE RO_T_GASTOS_SUPERVISION DROP CONSTRAINT DF_RO_T_GASTOS_SUPERVISION_VIGENTE
END
GO

IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_NAME = 'RO_T_GASTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    ALTER TABLE RO_T_GASTOS_SUPERVISION DROP COLUMN VIGENTE
    PRINT 'Columna VIGENTE eliminada de RO_T_GASTOS_SUPERVISION'
END
GO

IF EXISTS (SELECT * FROM sys.default_constraints WHERE name = 'DF_FU_T_ADELANTOS_SUPERVISION_VIGENTE')
BEGIN
    ALTER TABLE FU_T_ADELANTOS_SUPERVISION DROP CONSTRAINT DF_FU_T_ADELANTOS_SUPERVISION_VIGENTE
END
GO

IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_NAME = 'FU_T_ADELANTOS_SUPERVISION' AND COLUMN_NAME = 'VIGENTE')
BEGIN
    ALTER TABLE FU_T_ADELANTOS_SUPERVISION DROP COLUMN VIGENTE
    PRINT 'Columna VIGENTE eliminada de FU_T_ADELANTOS_SUPERVISION'
END
GO

SET NOEXEC OFF
GO

PRINT '--- Rollback de reapertura de gastos finalizado ---'
GO
