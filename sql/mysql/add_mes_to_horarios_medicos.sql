-- Migration: Add `mes` column to `horarios_medicos` (MySQL)
-- Purpose: store month name in Spanish (e.g. 'enero', 'febrero')
-- Safe: adds column nullable, populates from `fecha` when available, and provides rollback.

START TRANSACTION;

-- 1) Add column (nullable, small varchar)
ALTER TABLE `horarios_medicos`
  ADD COLUMN `mes` VARCHAR(20) NULL AFTER `dia_semana`;

-- 2) Populate `mes` from existing `fecha` where available (use Spanish names)
-- Only update rows where fecha is not null and mes is empty
UPDATE `horarios_medicos`
SET `mes` = ELT(MONTH(`fecha`),
  'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'
)
WHERE `fecha` IS NOT NULL AND (`mes` IS NULL OR `mes` = '');

COMMIT;

-- Rollback (if needed):
-- ALTER TABLE `horarios_medicos` DROP COLUMN `mes`;
