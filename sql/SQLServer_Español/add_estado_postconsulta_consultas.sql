-- Script para agregar el campo estado_postconsulta a la tabla consultas

ALTER TABLE consultas
ADD estado_postconsulta NVARCHAR(50) NOT NULL
CONSTRAINT chk_estado_postconsulta CHECK (estado_postconsulta IN ('No problemático', 'Pasivo', 'Problemático'));
