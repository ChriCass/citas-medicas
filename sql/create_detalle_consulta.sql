-- Script: create_detalle_consulta.sql
-- Propósito: Crear la tabla `detalle_consulta` con relaciones 1:N hacia `diagnosticos` y `consultas`.
-- Idempotente: la tabla sólo se crea si no existe.

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    DECLARE @schema_name SYSNAME = 'dbo';
    DECLARE @table_name SYSNAME = 'detalle_consulta';

    IF OBJECT_ID(QUOTENAME(@schema_name) + '.' + QUOTENAME(@table_name)) IS NULL
    BEGIN
        PRINT 'Creando tabla ' + @schema_name + '.' + @table_name;
        DECLARE @sql_create NVARCHAR(MAX) = N'CREATE TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' (
                id INT IDENTITY(1,1) PRIMARY KEY,
                id_diagnostico INT NULL,
                id_consulta INT NULL
            );';
        EXEC sp_executesql @sql_create;

        -- Añadir FKs
        PRINT 'Añadiendo constraints FK: fk_detalle_diag -> diagnosticos(id), fk_detalle_consulta -> consultas(id)';
        DECLARE @sql_fk1 NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' ADD CONSTRAINT fk_detalle_diag FOREIGN KEY (id_diagnostico) REFERENCES diagnosticos(id) ON DELETE SET NULL;';
        EXEC sp_executesql @sql_fk1;
        DECLARE @sql_fk2 NVARCHAR(MAX) = N'ALTER TABLE ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' ADD CONSTRAINT fk_detalle_consulta FOREIGN KEY (id_consulta) REFERENCES consultas(id) ON DELETE CASCADE;';
        EXEC sp_executesql @sql_fk2;

        -- Índices para consultas frecuentes
        DECLARE @sql_idx1 NVARCHAR(MAX) = N'CREATE INDEX idx_detalle_id_diagnostico ON ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' (id_diagnostico);';
        EXEC sp_executesql @sql_idx1;
        DECLARE @sql_idx2 NVARCHAR(MAX) = N'CREATE INDEX idx_detalle_id_consulta ON ' + QUOTENAME(@schema_name) + N'.' + QUOTENAME(@table_name) + N' (id_consulta);';
        EXEC sp_executesql @sql_idx2;
    END
    ELSE
    BEGIN
        PRINT 'La tabla ' + @schema_name + '.' + @table_name + ' ya existe. Saltando creación.';
    END

    COMMIT TRANSACTION;
    PRINT 'Operación completada.';
END TRY
BEGIN CATCH
    DECLARE @err_msg NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @err_num INT = ERROR_NUMBER();
    PRINT 'ERROR: ' + CAST(@err_num AS NVARCHAR(20)) + ' - ' + @err_msg;
    ROLLBACK TRANSACTION;
    THROW;
END CATCH;
