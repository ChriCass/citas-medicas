-- Script SQL Server: add anio column to horarios_medicos (type SMALLINT)
-- Safe: checks existence, adds column, populates values from creado_en when available,
-- and adds a default constraint for future inserts (current year).
-- Run in the target database (make a backup first).

SET NOCOUNT ON;
GO

BEGIN TRY
  BEGIN TRANSACTION;

  -- 1) Añadir columna si no existe (usar EXEC para evitar errores en la fase de parsing si el
  --    script se concatena con otros que referencian la columna)
  IF COL_LENGTH('dbo.horarios_medicos', 'anio') IS NULL
  BEGIN
    EXEC('ALTER TABLE dbo.horarios_medicos ADD anio SMALLINT NULL;');
  END

  -- 2) Poblar valores existentes usando creado_en cuando esté disponible,
  --    si no existe creado_en, usar el año actual. Ejecutar con sp_executesql para evitar
  --    validación de columnas en tiempo de parseo.
  IF COL_LENGTH('dbo.horarios_medicos', 'anio') IS NOT NULL
  BEGIN
    DECLARE @upd NVARCHAR(MAX) = N'
      UPDATE dbo.horarios_medicos
      SET anio = CASE
                   WHEN creado_en IS NOT NULL THEN DATEPART(year, creado_en)
                   ELSE DATEPART(year, GETDATE())
                 END
      WHERE anio IS NULL;';
    EXEC sp_executesql @upd;
  END

  -- 3) Añadir constraint DEFAULT para nuevos registros si no existe
  IF OBJECT_ID('DF_horarios_medicos_anio','D') IS NULL
  BEGIN
    EXEC('ALTER TABLE dbo.horarios_medicos ADD CONSTRAINT DF_horarios_medicos_anio DEFAULT (DATEPART(year, GETDATE())) FOR anio;');
  END

  COMMIT TRANSACTION;
END TRY
BEGIN CATCH
  IF XACT_STATE() <> 0
    ROLLBACK TRANSACTION;
  DECLARE @ErrMsg NVARCHAR(4000) = ERROR_MESSAGE();
  RAISERROR('Error al agregar la columna anio a horarios_medicos: %s', 16, 1, @ErrMsg);
END CATCH;
GO

-- Nota:
-- - Si prefieres que la columna sea NOT NULL, después de comprobar que todos los registros
--   tienen valor puedes ejecutar:
--     ALTER TABLE dbo.horarios_medicos ALTER COLUMN anio SMALLINT NOT NULL;
-- - Si quieres inferir anio a partir de la columna `mes` (nombre en español) con la regla
--   de diciembre -> año siguiente, puedo preparar un UPDATE más avanzado.
-- - Ejecuta este script en la misma base de datos que usa la aplicación.

-- Script para revertir (manual):
-- IF OBJECT_ID('DF_horarios_medicos_anio','D') IS NOT NULL
--   ALTER TABLE dbo.horarios_medicos DROP CONSTRAINT DF_horarios_medicos_anio;
-- IF COL_LENGTH('dbo.horarios_medicos', 'anio') IS NOT NULL
--   ALTER TABLE dbo.horarios_medicos DROP COLUMN anio;

GO
