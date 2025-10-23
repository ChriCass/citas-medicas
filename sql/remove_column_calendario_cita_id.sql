-- Script: remove_column_calendario_cita_id.sql
-- Propósito: Eliminar la columna `cita_id` de la tabla `calendario` de forma segura en SQL Server.
-- Pasos que realiza (idempotente):
-- 1) Busca y elimina cualquier FOREIGN KEY que referencia calendario.cita_id
-- 2) Busca y elimina constraints UNIQUE que incluyan calendario.cita_id
-- 3) Busca y elimina índices únicos que incluyan calendario.cita_id
-- 4) Elimina la columna `cita_id` si existe
-- IMPORTANTE: Hacer backup antes de ejecutar. Probar en entornos de staging.

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    DECLARE @schema_name SYSNAME = 'dbo'; -- Cambia si usas otro esquema
    DECLARE @table_name SYSNAME = 'calendario';
    DECLARE @column_name SYSNAME = 'cita_id';

    PRINT 'Iniciando proceso para eliminar columna ' + @column_name + ' de ' + @schema_name + '.' + @table_name;

    -- 1) Eliminar FKs que referencien la columna como parent
    ;WITH fks AS (
        SELECT fk.object_id AS fk_object_id, fk.name AS fk_name, OBJECT_NAME(fkc.referenced_object_id) AS referenced_table,
               col.name AS parent_column
        FROM sys.foreign_keys fk
        JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
        JOIN sys.columns col ON fkc.parent_object_id = col.object_id AND fkc.parent_column_id = col.column_id
        WHERE OBJECT_NAME(fk.parent_object_id) = @table_name
          AND col.name = @column_name
    )
    SELECT * INTO #fks_to_drop FROM fks;

    IF EXISTS (SELECT 1 FROM #fks_to_drop)
    BEGIN
        DECLARE @fk_name SYSNAME;
        DECLARE fk_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT fk_name FROM #fks_to_drop;
        OPEN fk_cursor;
        FETCH NEXT FROM fk_cursor INTO @fk_name;
        WHILE @@FETCH_STATUS = 0
        BEGIN
            DECLARE @sql_fk NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' DROP CONSTRAINT ' + QUOTENAME(@fk_name) + N';';
            PRINT 'Eliminando FK: ' + @fk_name;
            EXEC sp_executesql @sql_fk;
            FETCH NEXT FROM fk_cursor INTO @fk_name;
        END
        CLOSE fk_cursor;
        DEALLOCATE fk_cursor;
    END
    ELSE
    BEGIN
        PRINT 'No se encontraron FKs que referencien ' + @column_name + ' como columna padre en ' + @table_name;
    END

    -- 2) Buscar constraints UNIQUE (key constraints)
    ;WITH uq AS (
        SELECT kc.name AS constraint_name
        FROM sys.key_constraints kc
        JOIN sys.index_columns ic ON kc.parent_object_id = ic.object_id AND kc.unique_index_id = ic.index_id
        JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
        WHERE kc.type = 'UQ'
          AND OBJECT_NAME(kc.parent_object_id) = @table_name
          AND c.name = @column_name
    )
    SELECT * INTO #unique_constraints FROM uq;

    IF EXISTS (SELECT 1 FROM #unique_constraints)
    BEGIN
        DECLARE @uq SYSNAME;
        DECLARE uq_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT constraint_name FROM #unique_constraints;
        OPEN uq_cursor;
        FETCH NEXT FROM uq_cursor INTO @uq;
        WHILE @@FETCH_STATUS = 0
        BEGIN
            DECLARE @sql_uq NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' DROP CONSTRAINT ' + QUOTENAME(@uq) + N';';
            PRINT 'Eliminando UNIQUE constraint: ' + @uq;
            EXEC sp_executesql @sql_uq;
            FETCH NEXT FROM uq_cursor INTO @uq;
        END
        CLOSE uq_cursor;
        DEALLOCATE uq_cursor;
    END
    ELSE
    BEGIN
        PRINT 'No se encontraron UNIQUE constraints en ' + @table_name + '(' + @column_name + ')';
    END

    -- 3) Buscar índices únicos (no constraint)
    ;WITH unique_indexes AS (
        SELECT i.name AS index_name
        FROM sys.indexes i
        JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
        JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
        WHERE i.is_unique = 1
          AND OBJECT_NAME(i.object_id) = @table_name
          AND c.name = @column_name
    )
    SELECT * INTO #unique_indexes FROM unique_indexes;

    IF EXISTS (SELECT 1 FROM #unique_indexes)
    BEGIN
        DECLARE @ix SYSNAME;
        DECLARE ix_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT index_name FROM #unique_indexes;
        OPEN ix_cursor;
        FETCH NEXT FROM ix_cursor INTO @ix;
        WHILE @@FETCH_STATUS = 0
        BEGIN
            DECLARE @sql_ix NVARCHAR(MAX) = N'DROP INDEX ' + QUOTENAME(@ix) + N' ON ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N';';
            PRINT 'Eliminando índice único: ' + @ix;
            EXEC sp_executesql @sql_ix;
            FETCH NEXT FROM ix_cursor INTO @ix;
        END
        CLOSE ix_cursor;
        DEALLOCATE ix_cursor;
    END
    ELSE
    BEGIN
        PRINT 'No se encontraron índices únicos sobre ' + @table_name + '(' + @column_name + ')';
    END

    -- 4) Eliminar la columna si existe
    IF EXISTS (
        SELECT 1 FROM sys.columns c
        JOIN sys.tables t ON c.object_id = t.object_id
        WHERE t.name = @table_name AND c.name = @column_name
    )
    BEGIN
        DECLARE @sql_drop NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' DROP COLUMN ' + QUOTENAME(@column_name) + N';';
        PRINT 'Eliminando columna: ' + @column_name;
        EXEC sp_executesql @sql_drop;
        PRINT 'Columna ' + @column_name + ' eliminada.';
    END
    ELSE
    BEGIN
        PRINT 'La columna ' + @column_name + ' no existe en ' + @table_name + '. Nada que hacer.';
    END

    -- Limpiar tablas temporales
    DROP TABLE IF EXISTS #fks_to_drop;
    DROP TABLE IF EXISTS #unique_constraints;
    DROP TABLE IF EXISTS #unique_indexes;

    COMMIT TRANSACTION;
    PRINT 'Transacción completada con éxito.';
END TRY
BEGIN CATCH
    DECLARE @err_msg NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @err_num INT = ERROR_NUMBER();
    PRINT 'ERROR: ' + CAST(@err_num AS NVARCHAR(20)) + ' - ' + @err_msg;
    ROLLBACK TRANSACTION;
    THROW;
END CATCH;
