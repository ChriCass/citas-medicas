<?php
namespace App\Models;

use App\Core\Database;

class DoctorSchedule
{
    /** Lista completa con joins (para el index) */
    public static function listAll(): array
    {
        $sql = "SELECT h.*,
                       u.nombre AS doctor_name, u.apellido AS doctor_lastname, u.email AS doctor_email,
                       s.nombre_sede AS sede_nombre
                FROM horarios h
                JOIN doctores d ON d.id = h.doctor_id
                JOIN usuarios u ON u.id = d.usuario_id
                LEFT JOIN sedes s ON s.id = h.sede_id
                ORDER BY h.dia_semana ASC, h.hora_inicio ASC";
        return Database::pdo()->query($sql)->fetchAll();
    }

    /** Devuelve horarios activos de un doctor+sede+weekday (para generar slots) */
    public static function for(int $doctorId, int $locationId, int $weekday): array
    {
        $st = Database::pdo()->prepare(
            'SELECT * FROM horarios
             WHERE doctor_id = :d AND (sede_id = :s OR (:s = 0 AND sede_id IS NULL)) AND dia_semana = :w
             ORDER BY hora_inicio ASC'
        );
        $st->execute(['d'=>$doctorId,'s'=>$locationId,'w'=>$weekday]);
        return $st->fetchAll() ?: [];
    }

    /** Crea un horario */
    public static function create(int $doctorId, int $locationId, int $weekday, string $start, string $end, int $active=1): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO horarios(doctor_id, sede_id, dia_semana, hora_inicio, hora_fin)
             VALUES (:d, :s, :w, :start, :end)'
        );
        $st->execute(['d'=>$doctorId,'s'=>$locationId ?: null,'w'=>$weekday,'start'=>$start,'end'=>$end]);
        return (int)Database::pdo()->lastInsertId();
    }

    /** Elimina un horario */
    public static function delete(int $id): bool
    {
        $st = Database::pdo()->prepare('DELETE FROM horarios WHERE id=:id');
        return $st->execute(['id'=>$id]);
    }

    /** Detecta solape entre horarios del mismo doctor+sede+día */
    public static function overlaps(int $doctorId, int $locationId, int $weekday, string $start, string $end): bool
    {
        $st = Database::pdo()->prepare(
            "SELECT 1 FROM horarios
             WHERE doctor_id = :d AND (sede_id = :s OR (:s = 0 AND sede_id IS NULL)) AND dia_semana = :w
               AND NOT (hora_fin <= :start OR hora_inicio >= :end)"
        );
        $st->execute(['d'=>$doctorId,'s'=>$locationId,'w'=>$weekday,'start'=>$start,'end'=>$end]);
        return (bool)$st->fetchColumn();
    }
}
