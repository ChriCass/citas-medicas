-- Script para eliminar la columna tipo de la tabla calendario
IF EXISTS (
    SELECT 1 
    FROM sys.columns 
    WHERE Name = 'tipo'
    AND Object_ID = Object_ID('calendario')
)
BEGIN
    ALTER TABLE calendario
    DROP COLUMN tipo;
    PRINT 'Columna tipo eliminada de la tabla calendario';
END
ELSE
BEGIN
    PRINT 'La columna tipo no existe en la tabla calendario';
END