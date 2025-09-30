<?php
namespace App\Models;

use App\Core\Database;

class DoctorSchedule
{
    /** Lista completa con joins (para el index) */
    public static function listAll(): array
    {
        $sql = "SELECT ds.*,
                       u.name AS doctor_name, u.email AS doctor_email,
                       l.name AS location_name
                FROM horarios_doctores ds
                JOIN usuarios u ON u.id = ds.doctor_id
                JOIN ubicaciones l ON l.id = ds.location_id
                ORDER BY ds.weekday ASC, ds.start_time ASC";
        return Database::pdo()->query($sql)->fetchAll();
    }

    /** Devuelve horarios activos de un doctor+sede+weekday (para generar slots) */
    public static function for(int $doctorId, int $locationId, int $weekday): array
    {
        $st = Database::pdo()->prepare(
            'SELECT * FROM horarios_doctores
             WHERE doctor_id=:d AND location_id=:l AND weekday=:w AND is_active=1
             ORDER BY start_time ASC'
        );
        $st->execute(['d'=>$doctorId,'l'=>$locationId,'w'=>$weekday]);
        return $st->fetchAll() ?: [];
    }

    /** Crea un horario */
    public static function create(int $doctorId, int $locationId, int $weekday, string $start, string $end, int $active=1): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO horarios_doctores(doctor_id,location_id,weekday,start_time,end_time,is_active)
             VALUES (:d,:l,:w,:s,:e,:a)'
        );
        $st->execute(['d'=>$doctorId,'l'=>$locationId,'w'=>$weekday,'s'=>$start,'e'=>$end,'a'=>$active]);
        return (int)Database::pdo()->lastInsertId();
    }

    /** Elimina un horario */
    public static function delete(int $id): bool
    {
        $st = Database::pdo()->prepare('DELETE FROM horarios_doctores WHERE id=:id');
        return $st->execute(['id'=>$id]);
    }

    /** Detecta solape entre horarios del mismo doctor+sede+día */
    public static function overlaps(int $doctorId, int $locationId, int $weekday, string $start, string $end): bool
    {
        $st = Database::pdo()->prepare(
            "SELECT 1 FROM horarios_doctores
             WHERE doctor_id=:d AND location_id=:l AND weekday=:w
               AND NOT (end_time <= :s OR start_time >= :e)
             LIMIT 1"
        );
        $st->execute(['d'=>$doctorId,'l'=>$locationId,'w'=>$weekday,'s'=>$start,'e'=>$end]);
        return (bool)$st->fetchColumn();
    }
}
