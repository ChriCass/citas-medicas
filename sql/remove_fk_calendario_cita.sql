-- Script: remove_fk_calendario_cita.sql
-- Propósito: Localizar y eliminar la constraint FOREIGN KEY que relaciona calendario.cita_id -> citas(id)
-- Uso: Hacer backup antes de ejecutar. Ejecutar en el contexto de la base de datos objetivo (USE <db>).

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    DECLARE @schema_name SYSNAME = 'dbo'; -- Cambiar si usas otro esquema
    DECLARE @table_name SYSNAME = 'calendario';
    DECLARE @referenced_table SYSNAME = 'citas';
    DECLARE @column_name SYSNAME = 'cita_id';

    -- Buscar la FK por comprobación de columnas y tablas
    SELECT fk.name AS fk_name,
           OBJECT_SCHEMA_NAME(fk.parent_object_id) AS schema_name,
           OBJECT_NAME(fk.parent_object_id) AS table_name,
           col.name AS column_name,
           OBJECT_NAME(fk.referenced_object_id) AS referenced_table_name
    INTO #found_fk
    FROM sys.foreign_keys fk
    JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
    JOIN sys.columns col ON fkc.parent_object_id = col.object_id AND fkc.parent_column_id = col.column_id
    WHERE OBJECT_NAME(fk.parent_object_id) = @table_name
      AND OBJECT_NAME(fk.referenced_object_id) = @referenced_table
      AND col.name = @column_name;

    IF EXISTS (SELECT 1 FROM #found_fk)
    BEGIN
        DECLARE @fk_to_drop SYSNAME;
        SELECT TOP 1 @fk_to_drop = fk_name FROM #found_fk;
        PRINT 'FK encontrada: ' + @fk_to_drop;

        DECLARE @sql NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' DROP CONSTRAINT ' + QUOTENAME(@fk_to_drop) + N';';
        PRINT 'Ejecutando: ' + @sql;
        EXEC sp_executesql @sql;

        PRINT 'FK ' + @fk_to_drop + ' eliminada correctamente.';
    END
    ELSE
    BEGIN
        PRINT 'No se encontró ninguna FK que relacione ' + @schema_name + '.' + @table_name + '(' + @column_name + ') con ' + @referenced_table + '.';
    END

    DROP TABLE IF EXISTS #found_fk;

    COMMIT TRANSACTION;
    PRINT 'Transacción completada.';
END TRY
BEGIN CATCH
    DECLARE @err_msg NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @err_num INT = ERROR_NUMBER();
    PRINT 'ERROR: ' + CAST(@err_num AS NVARCHAR(20)) + ' - ' + @err_msg;
    ROLLBACK TRANSACTION;
    THROW;
END CATCH;
