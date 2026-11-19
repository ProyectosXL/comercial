-- Tabla de auditoría e historial de ediciones del Direccionario
-- Base destino: sistemas (XL-APPS / ConexionApps)
-- Vinculada a dbo.FP_SOF_USUARIOS (Gestión de Usuarios)

IF OBJECT_ID('dbo.FP_DIRECCIONARIO_HISTORIAL', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.FP_DIRECCIONARIO_HISTORIAL (
        id                 BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nro_sucursal       INT NOT NULL,
        accion             VARCHAR(30) NOT NULL,
        usuario_id         INT NULL,
        username           VARCHAR(100) NULL,
        nombre_usuario     VARCHAR(150) NULL,
        rol_id             INT NULL,
        rol_nombre         VARCHAR(100) NULL,
        sector_id          INT NULL,
        sector_nombre      VARCHAR(100) NULL,
        ip_address         VARCHAR(50) NULL,
        user_agent         VARCHAR(500) NULL,
        campos_modificados NVARCHAR(MAX) NULL,
        datos_anteriores   NVARCHAR(MAX) NULL,
        datos_nuevos       NVARCHAR(MAX) NULL,
        descripcion        NVARCHAR(500) NULL,
        fecha_hora         DATETIME2 NOT NULL CONSTRAINT DF_FP_DIR_HIST_FECHA DEFAULT (SYSDATETIME()),
        CONSTRAINT FK_FP_DIR_HIST_USUARIO FOREIGN KEY (usuario_id) REFERENCES dbo.FP_SOF_USUARIOS(id)
    );

    CREATE INDEX IX_FP_DIR_HIST_SUC_FECHA
        ON dbo.FP_DIRECCIONARIO_HISTORIAL (nro_sucursal, fecha_hora DESC);

    CREATE INDEX IX_FP_DIR_HIST_USUARIO_FECHA
        ON dbo.FP_DIRECCIONARIO_HISTORIAL (usuario_id, fecha_hora DESC);
END
GO
