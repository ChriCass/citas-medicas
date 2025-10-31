-- Agrega columna `anio` a la tabla `horarios_medicos` para registrar el año asociado
-- Ejecutar en caso de MySQL
ALTER TABLE horarios_medicos
  ADD COLUMN anio INT NULL DEFAULT NULL;

-- Nota: revisar permisos y respaldar la tabla antes de ejecutar en producción.
