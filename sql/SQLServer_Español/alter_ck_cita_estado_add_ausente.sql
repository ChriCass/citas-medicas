-- Script: alter_ck_cita_estado_add_ausente.sql
-- Objetivo: modificar el CHECK constraint ck_cita_estado en la tabla dbo.citas
-- para permitir el valor 'ausente' en la columna estado.
--
-- Recomendación antes de ejecutar:
-- 1) Hacer backup de la base de datos o al menos de la tabla `citas`.
-- 2) Ejecutar en entorno de staging/pruebas primero.
-- 3) Revisar si existen filas con valores no permitidos ejecutando
--      SELECT estado, COUNT(*) AS cnt FROM dbo.citas GROUP BY estado;
--
-- El script realiza:
-- - Inicio de transacción
-- - Si existe el constraint, lo elimina
-- - Crea un nuevo constraint que incluye 'ausente'
-- - Confirma la transacción o hace rollback en caso de error

SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    -- Informar estados existentes (útil para inspección manual previa)
    PRINT 'Estados existentes en dbo.citas (estado, cuenta):';
    SELECT estado, COUNT(*) AS cnt FROM dbo.citas GROUP BY estado;

    -- Sólo eliminar si existe
    IF EXISTS (
        SELECT 1 FROM sys.check_constraints cc
        WHERE cc.name = 'ck_cita_estado' AND cc.parent_object_id = OBJECT_ID('dbo.citas')
    )
    BEGIN
        PRINT 'Eliminando constraint existente ck_cita_estado';
        ALTER TABLE dbo.citas DROP CONSTRAINT ck_cita_estado;
    END
    ELSE
    BEGIN
        PRINT 'Constraint ck_cita_estado no existe; se creará nuevo constraint';
    END

    -- Crear el nuevo CHECK que incluye 'ausente'
    ALTER TABLE dbo.citas WITH CHECK ADD CONSTRAINT ck_cita_estado CHECK (
        [estado] IN ('cancelado','atendido','confirmado','pendiente','ausente')
    );

    -- Activar el constraint (verifica filas existentes)
    ALTER TABLE dbo.citas CHECK CONSTRAINT ck_cita_estado;

    COMMIT TRANSACTION;
    PRINT 'Constraint ck_cita_estado actualizado correctamente (''ausente'' añadido).';
END TRY
BEGIN CATCH
    IF XACT_STATE() <> 0
    BEGIN
        ROLLBACK TRANSACTION;
    END
    DECLARE @ErrMsg nvarchar(4000) = ERROR_MESSAGE();
    PRINT 'Error actualizando constraint: ' + ISNULL(@ErrMsg,'(no message)');
    THROW; -- volver a lanzar el error para que el engine lo muestre
END CATCH
