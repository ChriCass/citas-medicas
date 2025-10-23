-- Script: create_1_n_calendario_citas.sql
-- Propósito: Crear relación 1:N donde un registro de `calendario` puede tener muchas `citas`.
-- Estrategia (idempotente):
-- 1) Eliminar FK inversa existente (ej. fk_calendario_cita) que haga referencia desde calendario->citas (si existe).
-- 2) Añadir columna `calendario_id` a `citas` si no existe (NULLABLE para seguridad).
-- 3) Crear índice no único sobre `citas.calendario_id` si no existe.
-- 4) Crear foreign key `fk_citas_calendario` en `citas` apuntando a `calendario(id)` con ON DELETE SET NULL.
-- 5) Mantener integridad y permitir migración de datos si es necesario.

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    DECLARE @schema_name SYSNAME = 'dbo'; -- Cambiar si tu esquema es distinto
    DECLARE @table_calendario SYSNAME = 'calendario';
    DECLARE @table_citas SYSNAME = 'citas';
    DECLARE @new_column SYSNAME = 'calendario_id';
    DECLARE @fk_name SYSNAME = 'fk_citas_calendario';
    DECLARE @idx_name SYSNAME = 'idx_citas_calendario_id';

    PRINT 'Inicio: establecer relación 1:N calendario->citas';

    -- 0) Comprobación básica: asegurar que ambas tablas existen
    IF OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_calendario)) IS NULL OR OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_citas)) IS NULL
    BEGIN
        PRINT 'ERROR: Una de las tablas especificadas no existe. Abortando.';
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- 1) Eliminar FK inversa si existe (calendario.cita_id -> citas.id)
    ;WITH fk_inversa AS (
        SELECT fk.name AS fk_name
        FROM sys.foreign_keys fk
        JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
        JOIN sys.columns c ON fkc.parent_object_id = c.object_id AND fkc.parent_column_id = c.column_id
        WHERE fk.parent_object_id = OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_calendario))
          AND OBJECT_NAME(fkc.referenced_object_id) = @table_citas
          AND c.name = 'cita_id'
    )
    SELECT * INTO #fk_inversa FROM fk_inversa;

    IF EXISTS (SELECT 1 FROM #fk_inversa)
    BEGIN
        DECLARE @fk_to_drop SYSNAME;
        SELECT TOP 1 @fk_to_drop = fk_name FROM #fk_inversa;
        DECLARE @sql_drop_fk NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_calendario) + N' DROP CONSTRAINT ' + QUOTENAME(@fk_to_drop) + N';';
        PRINT 'Eliminando FK inversa: ' + @fk_to_drop;
        EXEC sp_executesql @sql_drop_fk;
    END
    ELSE
    BEGIN
        PRINT 'No se encontró FK inversa (calendario.cita_id -> citas.id).';
    END

    DROP TABLE IF EXISTS #fk_inversa;

    -- 2) Añadir columna calendario_id en citas si no existe
    IF NOT EXISTS (
        SELECT 1 FROM sys.columns c JOIN sys.tables t ON c.object_id = t.object_id WHERE t.name = @table_citas AND c.name = @new_column
    )
    BEGIN
        DECLARE @sql_add_col NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_citas) + N' ADD ' + QUOTENAME(@new_column) + N' INT NULL;';
        PRINT 'Añadiendo columna: ' + @new_column + ' a ' + @table_citas;
        EXEC sp_executesql @sql_add_col;
    END
    ELSE
    BEGIN
        PRINT 'La columna ' + @new_column + ' ya existe en ' + @table_citas;
    END

    -- 3) Crear índice si no existe
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_citas)) AND name = @idx_name)
    BEGIN
        DECLARE @sql_create_idx NVARCHAR(MAX) = N'CREATE INDEX ' + QUOTENAME(@idx_name) + N' ON ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_citas) + N'(' + QUOTENAME(@new_column) + N');';
        PRINT 'Creando índice: ' + @idx_name;
        EXEC sp_executesql @sql_create_idx;
    END
    ELSE
    BEGIN
        PRINT 'Índice ' + @idx_name + ' ya existe.';
    END

    -- 4) Crear FK fk_citas_calendario si no existe
    IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE parent_object_id = OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_citas)) AND name = @fk_name)
    BEGIN
        DECLARE @sql_create_fk NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_citas) + N' WITH CHECK ADD CONSTRAINT ' + QUOTENAME(@fk_name) + N' FOREIGN KEY(' + QUOTENAME(@new_column) + N') REFERENCES ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_calendario) + N' (id) ON DELETE SET NULL;';
        PRINT 'Creando FK: ' + @fk_name;
        EXEC sp_executesql @sql_create_fk;

        -- Habilitar check
        DECLARE @sql_check_fk NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_citas) + N' CHECK CONSTRAINT ' + QUOTENAME(@fk_name) + N';';
        EXEC sp_executesql @sql_check_fk;
    END
    ELSE
    BEGIN
        PRINT 'FK ' + @fk_name + ' ya existe.';
    END

    COMMIT TRANSACTION;
    PRINT 'Relación 1:N creada/confirmada con éxito.';
END TRY
BEGIN CATCH
    DECLARE @err_msg NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @err_num INT = ERROR_NUMBER();
    PRINT 'ERROR: ' + CAST(@err_num AS NVARCHAR(20)) + ' - ' + @err_msg;
    ROLLBACK TRANSACTION;
    THROW;
END CATCH;
