-- Script: remove_hora_inicio_hora_fin_from_calendario.sql
-- Purpose: Remove the columns `hora_inicio` and `hora_fin` from table `dbo.calendario` (SQL Server)
-- Notes:
--  - The script checks whether each column exists before attempting to drop it.
--  - If a column has a DEFAULT constraint, the script will try to drop that constraint first.
--  - The operation runs inside a transaction and uses TRY/CATCH to rollback on error.
--  - Review dependencies (FKs, indexes, computed columns, views, stored procs) that might reference these columns before running in production.

SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRAN;

    -- Drop hora_inicio if it exists
    IF EXISTS (
        SELECT 1 FROM sys.columns
        WHERE object_id = OBJECT_ID(N'dbo.calendario') AND name = N'hora_inicio'
    )
    BEGIN
        DECLARE @dc_hora_inicio sysname;
        SELECT @dc_hora_inicio = dc.name
        FROM sys.default_constraints dc
        JOIN sys.columns c ON c.default_object_id = dc.object_id
        WHERE dc.parent_object_id = OBJECT_ID(N'dbo.calendario')
          AND c.name = N'hora_inicio';

            IF @dc_hora_inicio IS NOT NULL
            BEGIN
                DECLARE @sql nvarchar(max) = N'ALTER TABLE dbo.calendario DROP CONSTRAINT ' + QUOTENAME(@dc_hora_inicio) + N';';
                EXEC sp_executesql @sql;
                PRINT 'Dropped default constraint for hora_inicio: ' + @dc_hora_inicio;
            END

            ALTER TABLE dbo.calendario DROP COLUMN hora_inicio;
            PRINT 'Dropped column: hora_inicio';
    END
    ELSE
    BEGIN
        PRINT 'Column hora_inicio does not exist, skipping';
    END

    -- Drop hora_fin if it exists
    IF EXISTS (
        SELECT 1 FROM sys.columns
        WHERE object_id = OBJECT_ID(N'dbo.calendario') AND name = N'hora_fin'
    )
    BEGIN
        DECLARE @dc_hora_fin sysname;
        SELECT @dc_hora_fin = dc.name
        FROM sys.default_constraints dc
        JOIN sys.columns c ON c.default_object_id = dc.object_id
        WHERE dc.parent_object_id = OBJECT_ID(N'dbo.calendario')
          AND c.name = N'hora_fin';

        IF @dc_hora_fin IS NOT NULL
        BEGIN
            DECLARE @sql2 nvarchar(max) = N'ALTER TABLE dbo.calendario DROP CONSTRAINT ' + QUOTENAME(@dc_hora_fin) + N';';
            EXEC sp_executesql @sql2;
            PRINT 'Dropped default constraint for hora_fin: ' + @dc_hora_fin;
        END

        ALTER TABLE dbo.calendario DROP COLUMN hora_fin;
        PRINT 'Dropped column: hora_fin';
    END
    ELSE
    BEGIN
        PRINT 'Column hora_fin does not exist, skipping';
    END

    COMMIT TRAN;
    PRINT 'Completed: columns removed (if they existed)';
END TRY
BEGIN CATCH
    DECLARE @ErrMsg NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @ErrNum INT = ERROR_NUMBER();
    ROLLBACK TRAN;
    RAISERROR('Error removing columns from calendario (Error %d): %s', 16, 1, @ErrNum, @ErrMsg);
END CATCH;

-- End of script
