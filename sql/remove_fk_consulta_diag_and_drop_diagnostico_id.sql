-- Script: remove_fk_consulta_diag_and_drop_diagnostico_id.sql
-- Propósito: Eliminar la FK `fk_consulta_diag` de la tabla `consultas` y borrar la columna `diagnostico_id`.
-- Idempotente y seguro: verifica existencia de objetos antes de intentar eliminarlos.

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    DECLARE @schema_name SYSNAME = 'dbo';
    DECLARE @table_name SYSNAME = 'consultas';
    DECLARE @col_name SYSNAME = 'diagnostico_id';
    DECLARE @fk_name SYSNAME = 'fk_consulta_diag';

    PRINT 'Inicio: eliminación de FK ' + @fk_name + ' y columna ' + @col_name + ' en ' + @schema_name + '.' + @table_name;

    -- 1) Comprobar existencia de la tabla
    IF OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_name)) IS NULL
    BEGIN
        PRINT 'ERROR: La tabla ' + @schema_name + '.' + @table_name + ' no existe.';
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- 2) Si existe la FK llamada @fk_name, eliminarla
    IF EXISTS (SELECT 1 FROM sys.foreign_keys fk WHERE fk.name = @fk_name AND fk.parent_object_id = OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_name)))
    BEGIN
        DECLARE @sql_drop_fk NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' DROP CONSTRAINT ' + QUOTENAME(@fk_name) + N';';
        PRINT 'Eliminando FK: ' + @fk_name;
        EXEC sp_executesql @sql_drop_fk;
    END
    ELSE
    BEGIN
        PRINT 'No existe la FK ' + @fk_name + '. Saltando.';
    END

    -- 3) Comprobar existencia de la columna
    IF NOT EXISTS (SELECT 1 FROM sys.columns c JOIN sys.tables t ON c.object_id = t.object_id WHERE t.name = @table_name AND c.name = @col_name)
    BEGIN
        PRINT 'La columna ' + @col_name + ' no existe en ' + @table_name + '. Nada que hacer.';
        COMMIT TRANSACTION;
        RETURN;
    END

    -- 4) Eliminar índices que incluyan la columna (si los hay). Si el índice respalda una UNIQUE CONSTRAINT, eliminar la constraint.
    ;WITH idxs AS (
        SELECT i.name AS index_name
        FROM sys.indexes i
        JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
        JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
        WHERE OBJECT_NAME(i.object_id) = @table_name AND c.name = @col_name
    )
    SELECT * INTO #idxs_to_drop FROM idxs;

    IF EXISTS (SELECT 1 FROM #idxs_to_drop)
    BEGIN
        DECLARE @idx_name SYSNAME;
        DECLARE idx_cur CURSOR LOCAL FAST_FORWARD FOR SELECT index_name FROM #idxs_to_drop;
        OPEN idx_cur;
        FETCH NEXT FROM idx_cur INTO @idx_name;
        WHILE @@FETCH_STATUS = 0
        BEGIN
            IF EXISTS (
                SELECT 1 FROM sys.key_constraints kc
                WHERE kc.name = @idx_name AND kc.parent_object_id = OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_name))
            )
            BEGIN
                DECLARE @sql_drop_constr NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' DROP CONSTRAINT ' + QUOTENAME(@idx_name) + N';';
                PRINT 'Eliminando UNIQUE CONSTRAINT (respaldada por índice): ' + @idx_name;
                EXEC sp_executesql @sql_drop_constr;
            END
            ELSE
            BEGIN
                DECLARE @sql_drop_idx NVARCHAR(MAX) = N'DROP INDEX ' + QUOTENAME(@idx_name) + N' ON ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N';';
                PRINT 'Eliminando índice: ' + @idx_name;
                EXEC sp_executesql @sql_drop_idx;
            END

            FETCH NEXT FROM idx_cur INTO @idx_name;
        END
        CLOSE idx_cur;
        DEALLOCATE idx_cur;
    END
    ELSE
    BEGIN
        PRINT 'No se encontraron índices que incluyan ' + @col_name;
    END

    DROP TABLE IF EXISTS #idxs_to_drop;

    -- 5) Eliminar default constraints sobre la columna
    ;WITH defs AS (
        SELECT dc.name AS def_name
        FROM sys.default_constraints dc
        JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
        JOIN sys.tables t ON c.object_id = t.object_id
        WHERE t.name = @table_name AND c.name = @col_name
    )
    SELECT * INTO #defs_to_drop FROM defs;

    IF EXISTS (SELECT 1 FROM #defs_to_drop)
    BEGIN
        DECLARE @def_name SYSNAME;
        DECLARE def_cur CURSOR LOCAL FAST_FORWARD FOR SELECT def_name FROM #defs_to_drop;
        OPEN def_cur;
        FETCH NEXT FROM def_cur INTO @def_name;
        WHILE @@FETCH_STATUS = 0
        BEGIN
            DECLARE @sql_drop_def NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' DROP CONSTRAINT ' + QUOTENAME(@def_name) + N';';
            PRINT 'Eliminando default constraint: ' + @def_name;
            EXEC sp_executesql @sql_drop_def;
            FETCH NEXT FROM def_cur INTO @def_name;
        END
        CLOSE def_cur;
        DEALLOCATE def_cur;
    END
    ELSE
    BEGIN
        PRINT 'No se encontraron default constraints en ' + @col_name;
    END

    DROP TABLE IF EXISTS #defs_to_drop;

    -- 6) Finalmente eliminar la columna
    DECLARE @sql_drop_col NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' DROP COLUMN ' + QUOTENAME(@col_name) + N';';
    PRINT 'Ejecutando: ' + @sql_drop_col;
    EXEC sp_executesql @sql_drop_col;

    COMMIT TRANSACTION;
    PRINT 'Columna ' + @col_name + ' eliminada correctamente.';
END TRY
BEGIN CATCH
    DECLARE @err_msg NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @err_num INT = ERROR_NUMBER();
    PRINT 'ERROR: ' + CAST(@err_num AS NVARCHAR(20)) + ' - ' + @err_msg;
    ROLLBACK TRANSACTION;
    THROW;
END CATCH;
