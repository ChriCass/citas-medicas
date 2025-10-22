-- ========================================
-- Migration: Añadir tablas medicamentos y recetas
-- - Crea tabla medicamentos
-- - Crea tabla recetas (relación 1-N con consultas)
-- - Opcional: receta referencia a medicamento (1-N) puede ser NULL
-- - Cambia la columna consultas.receta por consultas.id_receta (INT NULL FK)
-- Idempotente: puede ejecutarse varias veces sin errores
-- ========================================

USE med_database_v5;
GO

-- 1) Crear tabla medicamentos
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'medicamentos') AND type = N'U')
BEGIN
    CREATE TABLE medicamentos (
        id INT IDENTITY(1,1) PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        presentacion VARCHAR(100) NULL,
        observaciones VARCHAR(255) NULL
    );
END;
GO

-- 2) Crear tabla recetas
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'recetas') AND type = N'U')
BEGIN
    CREATE TABLE recetas (
        id INT IDENTITY(1,1) PRIMARY KEY,
        consulta_id INT NOT NULL,
        id_medicamento INT NULL,
        indicacion VARCHAR(255) NULL,
        duracion VARCHAR(255) NULL,
        CONSTRAINT fk_receta_consulta FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE,
        CONSTRAINT fk_receta_medicamento FOREIGN KEY (id_medicamento) REFERENCES medicamentos(id) ON DELETE SET NULL
    );
END;
GO

-- 3) Modificar tabla consultas: eliminar columna receta si existe
-- Nota: el diseño final es consultas (1) -> recetas (N), por lo que la FK se mantiene en recetas.consulta_id
-- Detectar si existe columna "receta" en consultas y eliminarla
IF EXISTS(
    SELECT 1 FROM sys.columns c
    JOIN sys.objects o ON c.object_id = o.object_id
    WHERE o.name = N'consultas' AND c.name = N'receta'
)
BEGIN
    DECLARE @sql_drop_fk NVARCHAR(MAX) = N'';

    -- Buscar constraints por defecto ligados a la columna
    SELECT @sql_drop_fk = @sql_drop_fk + N'ALTER TABLE consultas DROP CONSTRAINT ' + QUOTENAME(dc.name) + ';\n'
    FROM sys.default_constraints dc
    JOIN sys.columns col ON dc.parent_object_id = col.object_id AND dc.parent_column_id = col.column_id
    JOIN sys.objects o ON col.object_id = o.object_id
    WHERE o.name = N'consultas' AND col.name = N'receta';

    -- Buscar constraints (FKs, checks, etc.) que contengan la columna
    SELECT @sql_drop_fk = @sql_drop_fk + N'ALTER TABLE consultas DROP CONSTRAINT ' + QUOTENAME(k.name) + ';\n'
    FROM sys.check_constraints k
    JOIN sys.columns col2 ON k.parent_object_id = col2.object_id
    JOIN sys.objects o2 ON col2.object_id = o2.object_id
    WHERE o2.name = N'consultas' AND col2.name = N'receta';

    -- Ejecutar si hay constraints a eliminar
    IF LEN(@sql_drop_fk) > 0
    BEGIN
        EXEC sp_executesql @sql_drop_fk;
    END

    -- Eliminar la columna receta
    ALTER TABLE consultas DROP COLUMN receta;
END;
GO

-- Nota: Las relaciones solicitadas son:
--  - consultas 1 - N recetas (una consulta puede tener 0..1 receta, pero el esquema solicitado indica que consultas referencian recetas; si se desea que una consulta tenga varias recetas, habría que crear recetas.consulta_id en su lugar)
--  - medicamentos 1 - N recetas (opcional en recetas.id_medicamento)

-- Fin de migración
-- Insertar datos iniciales en medicamentos (si no existen)
IF NOT EXISTS (SELECT 1 FROM medicamentos)
BEGIN
    INSERT INTO medicamentos (nombre, presentacion, observaciones)
    VALUES
    ('Paracetamol', 'Tableta 500 mg', 'Analgesico y antipirético de uso común para dolor leve a moderado y fiebre.'),
    ('Amoxicilina', 'Cápsula 500 mg', 'Antibiótico betalactámico, indicado en infecciones respiratorias y urinarias.'),
    ('Ibuprofeno', 'Tableta 400 mg', 'Antiinflamatorio no esteroideo (AINE), indicado para dolor e inflamación.'),
    ('Omeprazol', 'Cápsula 20 mg', 'Inhibidor de la bomba de protones, usado para gastritis y úlcera péptica.'),
    ('Salbutamol', 'Inhalador 100 mcg/dosis', 'Broncodilatador de acción corta, indicado en crisis asmáticas.'),
    ('Dorzolamida', 'Solución oftálmica 2% x 5 mL', 'Antiglaucomatoso, reduce la presión intraocular en pacientes con glaucoma.'),
    ('Metformina', 'Tableta 850 mg', 'Antidiabético oral, primera línea para diabetes mellitus tipo 2.'),
    ('Losartán', 'Tableta 50 mg', 'Antihipertensivo, antagonista del receptor de angiotensina II.'),
    ('Ceftriaxona', 'Ampolla 1 g', 'Antibiótico de amplio espectro, uso hospitalario por vía parenteral.'),
    ('Ácido acetilsalicílico', 'Tableta 100 mg', 'Antiagregante plaquetario, indicado en prevención de eventos cardiovasculares.');
END;

PRINT 'Migration migration_add_recetas_medicamentos.sql executed.';
