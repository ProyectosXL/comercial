-- Agrega columna TIENDAS_CENTRAL a dbo.SUCURSALES_LAKERS
-- Base destino: locales_lakers (XL-LAKERBIS)
-- Significado: Local habilitado para retiro en tienda con preparación en Central

IF COL_LENGTH('dbo.SUCURSALES_LAKERS', 'TIENDAS_CENTRAL') IS NULL
BEGIN
    ALTER TABLE dbo.SUCURSALES_LAKERS
        ADD TIENDAS_CENTRAL BIT NOT NULL
            CONSTRAINT DF_SUCURSALES_LAKERS_TIENDAS_CENTRAL DEFAULT (0);
END
GO
