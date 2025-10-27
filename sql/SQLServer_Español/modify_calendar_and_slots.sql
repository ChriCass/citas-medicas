USE [med_database_v5]
GO

SET XACT_ABORT ON;
BEGIN TRY
    BEGIN TRANSACTION;

    -- 1) Agregar columnas a calendario si no existen
    IF COL_LENGTH('dbo.calendario', 'horario_id') IS NULL
    BEGIN
        ALTER TABLE dbo.calendario
        ADD horario_id INT NULL;
    END

    IF COL_LENGTH('dbo.calendario', 'hora_inicio') IS NULL
    BEGIN
        ALTER TABLE dbo.calendario
        ADD hora_inicio TIME NULL;
    END

    IF COL_LENGTH('dbo.calendario', 'hora_fin') IS NULL
    BEGIN
        ALTER TABLE dbo.calendario
        ADD hora_fin TIME NULL;
    END

    IF COL_LENGTH('dbo.calendario', 'tipo') IS NULL
    BEGIN
        ALTER TABLE dbo.calendario
        ADD tipo NVARCHAR(20) NULL;
    END

    IF COL_LENGTH('dbo.calendario', 'motivo') IS NULL
    BEGIN
        ALTER TABLE dbo.calendario
        ADD motivo NVARCHAR(255) NULL;
    END

    IF COL_LENGTH('dbo.calendario', 'auto_generado') IS NULL
    BEGIN
        -- Se crea la columna con constraint DEFAULT
        ALTER TABLE dbo.calendario
        ADD auto_generado BIT CONSTRAINT DF_calendario_auto_generado DEFAULT (0) ;
    END

    -- 2) Agregar FK calendario->horarios_medicos si no existe
    -- Nota: usamos SQL dinámico para las sentencias que referencian directamente
    -- la columna `horario_id` porque el compilador de SQL Server valida nombres
    -- de columnas al parsear todo el batch y puede fallar si la columna fue
    -- creada en el mismo batch.
    IF COL_LENGTH('dbo.calendario', 'horario_id') IS NOT NULL
    BEGIN
        DECLARE @sql NVARCHAR(MAX) = N'';

        SET @sql = N'
            -- Limpiar valores huérfanos en calendario.horario_id (si existen)
            IF EXISTS (
                SELECT 1 FROM dbo.calendario c
                LEFT JOIN dbo.horarios_medicos h ON c.horario_id = h.id
                WHERE c.horario_id IS NOT NULL AND h.id IS NULL
            )
            BEGIN
                UPDATE c
                SET horario_id = NULL
                FROM dbo.calendario c
                LEFT JOIN dbo.horarios_medicos h ON c.horario_id = h.id
                WHERE c.horario_id IS NOT NULL AND h.id IS NULL;
            END;

            -- Crear la constraint solo si no existe
            IF NOT EXISTS (
                SELECT 1 FROM sys.foreign_keys fk WHERE fk.name = N''fk_calendario_horario''
            )
            BEGIN
                ALTER TABLE dbo.calendario
                ADD CONSTRAINT fk_calendario_horario FOREIGN KEY (horario_id)
                REFERENCES dbo.horarios_medicos(id)
                ON DELETE NO ACTION;
            END;
        ';

        EXEC sp_executesql @sql;
    END

    -- 3) Crear tabla slots_calendario si no existe
    IF OBJECT_ID('dbo.slots_calendario', 'U') IS NULL
    BEGIN
        CREATE TABLE dbo.slots_calendario (
            id INT IDENTITY(1,1) PRIMARY KEY,
            calendario_id INT NOT NULL,
            horario_id INT NULL,
            hora_inicio TIME NOT NULL,
            hora_fin TIME NOT NULL,
            disponible BIT DEFAULT 1,
            reservado_por_cita_id INT NULL,
            creado_en DATETIME DEFAULT GETDATE(),
            actualizado_en DATETIME NULL
        );

        -- Agregar las constraints de FK pero primero comprobar violaciones y dar mensajes claros
        DECLARE @checkSql NVARCHAR(MAX);
        DECLARE @violations INT;

        -- 1) fk_slot_calendario -> comprobar que todos los calendario_id existan
        SET @checkSql = N'SELECT @v = COUNT(1) FROM dbo.slots_calendario s LEFT JOIN dbo.calendario c ON s.calendario_id = c.id WHERE s.calendario_id IS NOT NULL AND c.id IS NULL;';
        EXEC sp_executesql @checkSql, N'@v INT OUTPUT', @v = @violations OUTPUT;
        IF @violations > 0
        BEGIN
            -- Obtener hasta 10 ejemplos para diagnóstico
            DECLARE @examples NVARCHAR(MAX) = N'';
            SELECT @examples = COALESCE(@examples + N', ', N'') + CAST(calendario_id AS NVARCHAR(50))
            FROM (SELECT DISTINCT TOP (10) calendario_id FROM dbo.slots_calendario s LEFT JOIN dbo.calendario c ON s.calendario_id = c.id WHERE s.calendario_id IS NOT NULL AND c.id IS NULL) t;
            RAISERROR('Cannot create fk_slot_calendario: %d violating rows found (example calendario_id: %s)', 16, 1, @violations, @examples);
        END
        ELSE
        BEGIN
            ALTER TABLE dbo.slots_calendario
            ADD CONSTRAINT fk_slot_calendario FOREIGN KEY (calendario_id)
            REFERENCES dbo.calendario(id)
            ON DELETE CASCADE;
        END

        -- 2) fk_slot_horario -> comprobar correspondencia entre horario_id
        SET @checkSql = N'SELECT @v = COUNT(1) FROM dbo.slots_calendario s LEFT JOIN dbo.horarios_medicos h ON s.horario_id = h.id WHERE s.horario_id IS NOT NULL AND h.id IS NULL;';
        EXEC sp_executesql @checkSql, N'@v INT OUTPUT', @v = @violations OUTPUT;
        IF @violations > 0
        BEGIN
            DECLARE @examples2 NVARCHAR(MAX) = N'';
            SELECT @examples2 = COALESCE(@examples2 + N', ', N'') + CAST(horario_id AS NVARCHAR(50))
            FROM (SELECT DISTINCT TOP (10) horario_id FROM dbo.slots_calendario s LEFT JOIN dbo.horarios_medicos h ON s.horario_id = h.id WHERE s.horario_id IS NOT NULL AND h.id IS NULL) t;
            RAISERROR('Cannot create fk_slot_horario: %d violating rows found (example horario_id: %s)', 16, 1, @violations, @examples2);
        END
        ELSE
        BEGIN
            ALTER TABLE dbo.slots_calendario
            ADD CONSTRAINT fk_slot_horario FOREIGN KEY (horario_id)
            REFERENCES dbo.horarios_medicos(id)
            ON DELETE SET NULL;
        END

        -- 3) fk_slot_cita -> comprobar correspondencia entre reservado_por_cita_id
        SET @checkSql = N'SELECT @v = COUNT(1) FROM dbo.slots_calendario s LEFT JOIN dbo.citas c ON s.reservado_por_cita_id = c.id WHERE s.reservado_por_cita_id IS NOT NULL AND c.id IS NULL;';
        EXEC sp_executesql @checkSql, N'@v INT OUTPUT', @v = @violations OUTPUT;
        IF @violations > 0
        BEGIN
            DECLARE @examples3 NVARCHAR(MAX) = N'';
            SELECT @examples3 = COALESCE(@examples3 + N', ', N'') + CAST(reservado_por_cita_id AS NVARCHAR(50))
            FROM (SELECT DISTINCT TOP (10) reservado_por_cita_id FROM dbo.slots_calendario s LEFT JOIN dbo.citas c ON s.reservado_por_cita_id = c.id WHERE s.reservado_por_cita_id IS NOT NULL AND c.id IS NULL) t;
            RAISERROR('Cannot create fk_slot_cita: %d violating rows found (example reservado_por_cita_id: %s)', 16, 1, @violations, @examples3);
        END
        ELSE
        BEGIN
            ALTER TABLE dbo.slots_calendario
            ADD CONSTRAINT fk_slot_cita FOREIGN KEY (reservado_por_cita_id)
            REFERENCES dbo.citas(id)
            ON DELETE SET NULL;
        END
    END

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF XACT_STATE() <> 0
    BEGIN
        ROLLBACK TRANSACTION;
    END
    -- Obtener información del error
    DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @ErrorNumber INT = ERROR_NUMBER();
    DECLARE @ErrorSeverity INT = ERROR_SEVERITY();
    DECLARE @ErrorState INT = ERROR_STATE();

    -- Detectar versión mayor del servidor SQL (ProductVersion) para saber si THROW está soportado
    DECLARE @productVersion VARCHAR(128) = CONVERT(VARCHAR(128), SERVERPROPERTY('ProductVersion'));
    DECLARE @major INT = 0;
    BEGIN TRY
        SET @major = CAST(PARSENAME(@productVersion, 4) AS INT);
    END TRY
    BEGIN CATCH
        SET @major = 0;
    END CATCH

    IF @major >= 11
    BEGIN
        -- SQL Server 2012+ soporta THROW; relanzar el error original
        THROW;
    END
    ELSE
    BEGIN
        -- Fallback para versiones antiguas: usar RAISERROR con severidad segura
        -- y estado fijo (no usamos ERROR_SEVERITY/STATE por si están fuera de rango)
        RAISERROR(@ErrorMessage, 16, 1);
    END
END CATCH
GO

-- Índices/Mejoras opcionales (descomentar según necesidad)
-- CREATE INDEX ix_slots_calendario_calendario_id ON dbo.slots_calendario(calendario_id);
-- CREATE INDEX ix_slots_calendario_horario_id ON dbo.slots_calendario(horario_id);
