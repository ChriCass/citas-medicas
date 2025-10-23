-- Script: add_dia_semana_to_horarios_medicos.sql
-- Propósito: Añadir la columna `dia_semana` VARCHAR(10) a la tabla `horarios_medicos` y poblarla desde `fecha`.
-- Idempotente: no hará nada si la columna ya existe.

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    DECLARE @schema_name SYSNAME = 'dbo';
    DECLARE @table_name SYSNAME = 'horarios_medicos';
    DECLARE @col_name SYSNAME = 'dia_semana';

    -- 1) Comprobar existencia de la tabla
    IF OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_name)) IS NULL
    BEGIN
        PRINT 'ERROR: La tabla ' + @schema_name + '.' + @table_name + ' no existe.';
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- 2) Añadir la columna si no existe
    IF NOT EXISTS (SELECT 1 FROM sys.columns c JOIN sys.tables t ON c.object_id = t.object_id WHERE t.name = @table_name AND c.name = @col_name)
    BEGIN
        DECLARE @sql_add_col NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' ADD ' + QUOTENAME(@col_name) + N' VARCHAR(10) NULL;';
        PRINT 'Añadiendo columna: ' + @col_name;
        EXEC sp_executesql @sql_add_col;
    END
    ELSE
    BEGIN
        PRINT 'La columna ' + @col_name + ' ya existe. Saltando creación.';
    END

    -- 3) Poblar dia_semana a partir de fecha (si existe la columna fecha)
    IF EXISTS (SELECT 1 FROM sys.columns c JOIN sys.tables t ON c.object_id = t.object_id WHERE t.name = @table_name AND c.name = 'fecha')
    BEGIN
        PRINT 'Poblando ' + @col_name + ' desde la columna fecha...';

        -- Intentamos primero usar FORMAT(fecha, 'dddd', 'es-ES') para obtener el nombre del día en español
        DECLARE @sql_update NVARCHAR(MAX) = N'UPDATE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) +
            N' SET ' + QUOTENAME(@col_name) + N' = FORMAT(fecha, ''dddd'', ''es-ES'')'
            + N' WHERE fecha IS NOT NULL AND (' + QUOTENAME(@col_name) + N' IS NULL OR ' + QUOTENAME(@col_name) + N' = '''');';

        BEGIN TRY
            EXEC sp_executesql @sql_update;
        END TRY
        BEGIN CATCH
            PRINT 'FORMAT not available or failed; usando DATEPART+CASE como fallback.';
            DECLARE @sql_update_fallback NVARCHAR(MAX) = N'UPDATE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) +
                N' SET ' + QUOTENAME(@col_name) + N' = CASE DATEPART(WEEKDAY, fecha) '
                + N'WHEN 1 THEN N''Domingo'' WHEN 2 THEN N''Lunes'' WHEN 3 THEN N''Martes'' WHEN 4 THEN N''Miércoles'' '
                + N'WHEN 5 THEN N''Jueves'' WHEN 6 THEN N''Viernes'' WHEN 7 THEN N''Sábado'' ELSE NULL END '
                + N' WHERE fecha IS NOT NULL AND (' + QUOTENAME(@col_name) + N' IS NULL OR ' + QUOTENAME(@col_name) + N' = '''');';
            EXEC sp_executesql @sql_update_fallback;
        END CATCH;
    END
    ELSE
    BEGIN
        PRINT 'No existe la columna fecha; la columna ' + @col_name + ' permanecerá vacía hasta que se provean fechas.';
    END

    COMMIT TRANSACTION;
    PRINT 'Script finalizado correctamente.';
END TRY
BEGIN CATCH
    DECLARE @err_msg NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @err_num INT = ERROR_NUMBER();
    PRINT 'ERROR: ' + CAST(@err_num AS NVARCHAR(20)) + ' - ' + @err_msg;
    ROLLBACK TRANSACTION;
    THROW;
END CATCH;
