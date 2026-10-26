-- Log de la reasignación masiva de categorías por Excel (Gestión de Categorías).
-- Ejecutar una sola vez en XL-TANGO / LAKER_SA antes de usar la importación.
-- Cada importación agrupa sus filas con el mismo LOTE.

IF OBJECT_ID('dbo.RO_LOG_CAMBIO_CATEGORIA_ARTICULO', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.RO_LOG_CAMBIO_CATEGORIA_ARTICULO (
        ID                    INT IDENTITY(1,1) PRIMARY KEY,
        LOTE                  UNIQUEIDENTIFIER NOT NULL,
        FECHA                 DATETIME NOT NULL DEFAULT GETDATE(),
        USUARIO               VARCHAR(100) NULL,
        ACCION                VARCHAR(10) NOT NULL,   -- INSERT (nueva corrección en la AUX) / UPDATE (corrección existente)
        COD_ARTICU            VARCHAR(30) NOT NULL,
        CATEGORIA_ANT         VARCHAR(100) NULL,
        CATEGORIA_PADRE_ANT   VARCHAR(100) NULL,
        CATEGORIA_NUEVA       VARCHAR(100) NULL,
        CATEGORIA_PADRE_NUEVA VARCHAR(100) NULL
    );

    CREATE INDEX IX_RO_LOG_CAMBIO_CATEGORIA_ARTICULO_COD
        ON dbo.RO_LOG_CAMBIO_CATEGORIA_ARTICULO (COD_ARTICU, FECHA);
END

-- Tablas creadas con la primera versión del script (sin ACCION)
IF COL_LENGTH('dbo.RO_LOG_CAMBIO_CATEGORIA_ARTICULO', 'ACCION') IS NULL
BEGIN
    ALTER TABLE dbo.RO_LOG_CAMBIO_CATEGORIA_ARTICULO
        ADD ACCION VARCHAR(10) NOT NULL
            CONSTRAINT DF_RO_LOG_CAMBIO_CATEGORIA_ARTICULO_ACCION DEFAULT 'UPDATE';
END
