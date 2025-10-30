-- Script robusto para crear la tabla `feriados` en SQL Server
-- Evita usar GO dentro de bloques IF y realiza comprobaciones seguras antes de cada ALTER
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;

-- Crear la tabla si no existe
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'feriados' AND schema_id = SCHEMA_ID('dbo'))
BEGIN
    PRINT 'Creando tabla dbo.feriados...';

    CREATE TABLE dbo.feriados (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        fecha DATE NOT NULL,
        nombre NVARCHAR(255) NOT NULL,
        tipo NVARCHAR(50) NOT NULL,
        activo BIT NULL,
        sede_id INT NULL,
        creado_en DATETIME NULL
    );

    PRINT 'Tabla dbo.feriados creada.';
END
ELSE
BEGIN
    PRINT 'La tabla dbo.feriados ya existe. Se omite creación.';
END

-- Añadir default para activo si no existe
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.feriados') AND name = 'activo')
AND NOT EXISTS (
    SELECT 1 FROM sys.default_constraints dc
    JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
    WHERE c.object_id = OBJECT_ID('dbo.feriados') AND c.name = 'activo'
)
BEGIN
    ALTER TABLE dbo.feriados ADD CONSTRAINT DF_feriados_activo DEFAULT ((1)) FOR activo;
    PRINT 'Default DF_feriados_activo añadido.';
END

-- Añadir default para creado_en si no existe
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.feriados') AND name = 'creado_en')
AND NOT EXISTS (
    SELECT 1 FROM sys.default_constraints dc
    JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
    WHERE c.object_id = OBJECT_ID('dbo.feriados') AND c.name = 'creado_en'
)
BEGIN
    ALTER TABLE dbo.feriados ADD CONSTRAINT DF_feriados_creado_en DEFAULT (GETDATE()) FOR creado_en;
    PRINT 'Default DF_feriados_creado_en añadido.';
END

-- Añadir CHECK constraint para tipo si no existe
IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE parent_object_id = OBJECT_ID('dbo.feriados') AND name = 'chk_feriados_tipo')
BEGIN
    ALTER TABLE dbo.feriados ADD CONSTRAINT chk_feriados_tipo CHECK (tipo IN ('nacional','local'));
    PRINT 'Constraint chk_feriados_tipo añadida.';
END

-- Crear índice único sobre (fecha, sede_id) si no existe
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('dbo.feriados') AND name = 'ux_feriados_fecha_sede'
)
BEGIN
    CREATE UNIQUE INDEX ux_feriados_fecha_sede ON dbo.feriados(fecha, sede_id);
    PRINT 'Índice ux_feriados_fecha_sede creado.';
END

-- Añadir FK a sedes si la tabla sedes existe y la FK no existe
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'sedes' AND schema_id = SCHEMA_ID('dbo'))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM sys.foreign_keys WHERE parent_object_id = OBJECT_ID('dbo.feriados') AND name = 'fk_feriados_sede'
    )
    BEGIN
        ALTER TABLE dbo.feriados WITH CHECK ADD CONSTRAINT fk_feriados_sede FOREIGN KEY (sede_id)
            REFERENCES dbo.sedes(id) ON DELETE SET NULL;
        ALTER TABLE dbo.feriados CHECK CONSTRAINT fk_feriados_sede;
        PRINT 'FK fk_feriados_sede creada.';
    END
END
ELSE
BEGIN
    PRINT 'Tabla dbo.sedes no encontrada: omitiendo creación de FK fk_feriados_sede.';
END

PRINT 'Script create_table_feriados.sql finalizado.';
