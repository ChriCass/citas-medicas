<?php
namespace App\Models;

use App\Core\Database;

class DoctorSchedule
{
    /** Lista completa con joins (para el index) */
    public static function listAll(): array
    {
        $sql = "SELECT hm.*,
                       u.nombre AS doctor_name, u.apellido AS doctor_lastname, u.email AS doctor_email,
                       s.nombre_sede AS sede_nombre,
                       DATENAME(WEEKDAY, hm.fecha) AS dia_nombre
                FROM horarios_medicos hm
                JOIN doctores d ON d.id = hm.doctor_id
                JOIN usuarios u ON u.id = d.usuario_id
                LEFT JOIN sedes s ON s.id = hm.sede_id
                WHERE hm.activo = 1
                ORDER BY hm.fecha ASC, hm.hora_inicio ASC";
        return Database::pdo()->query($sql)->fetchAll();
    }

    /** Devuelve horarios activos de un doctor+sede+fecha específica (para generar slots) */
    public static function forDate(int $doctorId, int $locationId, string $date): array
    {
        if ($locationId > 0) {
            $st = Database::pdo()->prepare(
                'SELECT * FROM horarios_medicos
                 WHERE doctor_id = :d AND sede_id = :s 
                   AND fecha = :date AND activo = 1
                 ORDER BY hora_inicio ASC'
            );
            $st->execute(['d'=>$doctorId,'s'=>$locationId,'date'=>$date]);
        } else {
            $st = Database::pdo()->prepare(
                'SELECT * FROM horarios_medicos
                 WHERE doctor_id = :d AND sede_id IS NULL 
                   AND fecha = :date AND activo = 1
                 ORDER BY hora_inicio ASC'
            );
            $st->execute(['d'=>$doctorId,'date'=>$date]);
        }
        return $st->fetchAll() ?: [];
    }

    /** Devuelve horarios de un doctor en un rango de fechas */
    public static function forDateRange(int $doctorId, string $startDate, string $endDate): array
    {
        $st = Database::pdo()->prepare(
            'SELECT * FROM horarios_medicos
             WHERE doctor_id = :d AND fecha BETWEEN :start AND :end AND activo = 1
             ORDER BY fecha ASC, hora_inicio ASC'
        );
        $st->execute(['d'=>$doctorId,'start'=>$startDate,'end'=>$endDate]);
        return $st->fetchAll() ?: [];
    }

    /** Crea un horario para fecha específica */
    public static function create(int $doctorId, int $locationId, string $date, string $start, string $end, string $observaciones = null): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO horarios_medicos(doctor_id, sede_id, fecha, hora_inicio, hora_fin, observaciones, activo)
             VALUES (:d, :s, :date, :start, :end, :obs, 1)'
        );
        $st->execute([
            'd'=>$doctorId,
            's'=>$locationId ?: null,
            'date'=>$date,
            'start'=>$start,
            'end'=>$end,
            'obs'=>$observaciones
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    /** Elimina un horario (marca como inactivo) */
    public static function delete(int $id): bool
    {
        $st = Database::pdo()->prepare('UPDATE horarios_medicos SET activo = 0 WHERE id=:id');
        return $st->execute(['id'=>$id]);
    }

    /** Elimina físicamente un horario */
    public static function hardDelete(int $id): bool
    {
        $st = Database::pdo()->prepare('DELETE FROM horarios_medicos WHERE id=:id');
        return $st->execute(['id'=>$id]);
    }

    /** Detecta solape entre horarios del mismo doctor+sede+fecha */
    public static function overlaps(int $doctorId, int $locationId, string $date, string $start, string $end, int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM horarios_medicos
                WHERE doctor_id = :d AND (sede_id = :s OR (:s = 0 AND sede_id IS NULL)) 
                  AND fecha = :date AND activo = 1
                  AND NOT (hora_fin <= :start OR hora_inicio >= :end)";
        
        $params = ['d'=>$doctorId,'s'=>$locationId,'date'=>$date,'start'=>$start,'end'=>$end];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude";
            $params['exclude'] = $excludeId;
        }
        
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    /** Método de compatibilidad para el método anterior `for` - OBSOLETO, usar forDate */
    public static function for(int $doctorId, int $locationId, int $weekday): array
    {
        // Este método es obsoleto y se mantiene solo para compatibilidad
        // Se recomienda usar forDate() con una fecha específica
        return [];
    }
}
