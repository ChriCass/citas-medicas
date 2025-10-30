-- Migration: Add [mes] column to [horarios_medicos] (SQL Server)
-- Purpose: store month name in Spanish (e.g. 'enero', 'febrero')
-- Safe: adds column nullable, populates from [fecha] when available, and provides rollback.

BEGIN TRANSACTION;

-- 1) Add column
ALTER TABLE dbo.horarios_medicos
  ADD mes NVARCHAR(20) NULL;

-- 2) Populate mes from fecha (map month number to Spanish name)
UPDATE dbo.horarios_medicos
SET mes = CASE DATEPART(month, fecha)
  WHEN 1 THEN N'enero'
  WHEN 2 THEN N'febrero'
  WHEN 3 THEN N'marzo'
  WHEN 4 THEN N'abril'
  WHEN 5 THEN N'mayo'
  WHEN 6 THEN N'junio'
  WHEN 7 THEN N'julio'
  WHEN 8 THEN N'agosto'
  WHEN 9 THEN N'septiembre'
  WHEN 10 THEN N'octubre'
  WHEN 11 THEN N'noviembre'
  WHEN 12 THEN N'diciembre'
  ELSE mes
END
WHERE fecha IS NOT NULL AND (mes IS NULL OR mes = '');

COMMIT;

-- Rollback (if needed):
-- ALTER TABLE dbo.horarios_medicos DROP COLUMN mes;
