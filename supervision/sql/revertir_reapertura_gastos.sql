-- Rollback de crear_reapertura_gastos.sql
-- Ejecutar en la base de datos central (LAKER_SA)
--
-- Elimina la tabla de reaperturas. Como la reapertura es complementaria y nunca
-- modifica ni anula gastos, no hay nada mas que revertir: RO_T_GASTOS_SUPERVISION
-- y FU_T_ADELANTOS_SUPERVISION quedan como estaban.
--
-- Los gastos que se hayan cargado usando una reapertura se conservan: son filas
-- normales, indistinguibles de cualquier otra carga. Lo unico que se pierde es
-- el registro de quien habilito cada reapertura y cuando, asi que conviene
-- guardarse una copia antes si ese historial interesa:
--
--   SELECT * FROM RO_T_REAPERTURAS_GASTOS_SUP ORDER BY FECHA_HABILITACION;

SET ANSI_NULLS ON
SET QUOTED_IDENTIFIER ON
GO

IF EXISTS (SELECT * FROM sysobjects WHERE name='RO_T_REAPERTURAS_GASTOS_SUP' AND xtype='U')
BEGIN
    DECLARE @filas INT
    SELECT @filas = COUNT(*) FROM RO_T_REAPERTURAS_GASTOS_SUP

    IF @filas > 0
    BEGIN
        PRINT 'AVISO: se eliminan ' + CAST(@filas AS VARCHAR(10)) + ' reaperturas registradas.'
    END

    DROP TABLE RO_T_REAPERTURAS_GASTOS_SUP
    PRINT 'Tabla RO_T_REAPERTURAS_GASTOS_SUP eliminada'
END
ELSE
BEGIN
    PRINT 'La tabla RO_T_REAPERTURAS_GASTOS_SUP no existe'
END
GO

PRINT '--- Rollback de reapertura de gastos finalizado ---'
GO
