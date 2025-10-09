<?php
namespace App\Models;
use App\Core\Database;
use PDO;

class Appointment
{
    public static function listAll(): array {
        $sql = "SELECT c.*,
                       p.usuario_id as paciente_usuario_id,
                       u_p.nombre as paciente_nombre, u_p.apellido as paciente_apellido,
                       d.usuario_id as doctor_usuario_id,
                       u_d.nombre as doctor_nombre, u_d.apellido as doctor_apellido,
                       s.nombre_sede as sede_nombre,
                       e.nombre as especialidad_nombre
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN usuarios u_p ON p.usuario_id = u_p.id
                JOIN doctores d ON c.doctor_id = d.id
                JOIN usuarios u_d ON d.usuario_id = u_d.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function usercitas(int $userId): array {
        $sql = "SELECT c.*,
                       u_d.nombre as doctor_nombre, u_d.apellido as doctor_apellido,
                       s.nombre_sede as sede_nombre,
                       e.nombre as especialidad_nombre
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN doctores d ON c.doctor_id = d.id
                JOIN usuarios u_d ON d.usuario_id = u_d.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                WHERE p.usuario_id = :usuario_id
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['usuario_id'=>$userId]);
        return $st->fetchAll();
    }

    public static function doctorcitas(int $doctorId): array {
        $sql = "SELECT c.*,
                       u_p.nombre as paciente_nombre, u_p.apellido as paciente_apellido,
                       s.nombre_sede as sede_nombre,
                       e.nombre as especialidad_nombre
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN usuarios u_p ON p.usuario_id = u_p.id
                JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                WHERE d.usuario_id = :doctor_id
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['doctor_id'=>$doctorId]);
        return $st->fetchAll();
    }

    public static function upcomingForUser(int $userId, int $limit = 5): array {
        if (Database::driver()==='sqlsrv') {
            $sql = "SELECT c.*, e.nombre as especialidad_nombre
                    FROM citas c
                    JOIN pacientes p ON c.paciente_id = p.id
                    JOIN doctores d ON c.doctor_id = d.id
                    LEFT JOIN especialidades e ON d.especialidad_id = e.id
                    WHERE p.usuario_id = :usuario_id
                      AND c.estado <> 'cancelado'
                      AND (c.fecha > CAST(GETDATE() AS DATE) OR (c.fecha = CAST(GETDATE() AS DATE) AND c.hora_inicio > CAST(GETDATE() AS TIME)))
                    ORDER BY c.fecha ASC, c.hora_inicio ASC
                    OFFSET 0 ROWS FETCH NEXT $limit ROWS ONLY";
            $st = Database::pdo()->prepare($sql);
            $st->bindValue(':usuario_id',$userId,PDO::PARAM_INT);
            $st->execute(); return $st->fetchAll();
        } else {
            $sql = "SELECT c.*, e.nombre as especialidad_nombre
                    FROM citas c
                    JOIN pacientes p ON c.paciente_id = p.id
                    JOIN doctores d ON c.doctor_id = d.id
                    LEFT JOIN especialidades e ON d.especialidad_id = e.id
                    WHERE p.usuario_id = :usuario_id
                      AND c.estado <> 'cancelado'
                      AND (c.fecha > CURDATE() OR (c.fecha = CURDATE() AND c.hora_inicio > CURTIME()))
                    ORDER BY c.fecha ASC, c.hora_inicio ASC
                    LIMIT :l";
            $st = Database::pdo()->prepare($sql);
            $st->bindValue(':usuario_id',$userId,PDO::PARAM_INT);
            $st->bindValue(':l',(int)$limit,PDO::PARAM_INT);
            $st->execute(); return $st->fetchAll();
        }
    }

    /** Chequea solape para doctor o sede (no se doble-reserva) */
    public static function overlapsWindow(string $fecha, string $horaInicio, string $horaFin, int $doctorId, int $sedeId): bool {
        $sqlMy = "SELECT 1 FROM citas
                  WHERE estado<>'cancelado'
                    AND fecha = :fecha
                    AND NOT (hora_fin <= :hora_inicio OR hora_inicio >= :hora_fin)
                    AND (doctor_id = :doctor_id OR sede_id = :sede_id)
                  LIMIT 1";
        $sqlMs = "SELECT TOP 1 1 FROM citas
                  WHERE estado<>'cancelado'
                    AND fecha = :fecha
                    AND NOT (hora_fin <= :hora_inicio OR hora_inicio >= :hora_fin)
                    AND (doctor_id = :doctor_id OR sede_id = :sede_id)";
        $sql = Database::isSqlServer() ? $sqlMs : $sqlMy;
        $st = Database::pdo()->prepare($sql);
        $st->execute([
            'fecha'=>$fecha,
            'hora_inicio'=>$horaInicio,
            'hora_fin'=>$horaFin,
            'doctor_id'=>$doctorId,
            'sede_id'=>$sedeId
        ]);
        return (bool)$st->fetchColumn();
    }

    public static function create(
        int $pacienteId,
        int $doctorId,
        ?int $sedeId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?string $razon = ''
    ): int {
        $st = Database::pdo()->prepare(
            "INSERT INTO citas(paciente_id, doctor_id, sede_id, fecha, hora_inicio, hora_fin, razon, estado)
             VALUES (:paciente_id, :doctor_id, :sede_id, :fecha, :hora_inicio, :hora_fin, :razon, 'pendiente')"
        );
        $st->execute([
          'paciente_id'=>$pacienteId,
          'doctor_id'=>$doctorId,
          'sede_id'=>$sedeId,
          'fecha'=>$fecha,
          'hora_inicio'=>$horaInicio,
          'hora_fin'=>$horaFin,
          'razon'=>$razon
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    /** Paciente cancela si faltan >= 24h */
    public static function cancelByPatient(int $id, int $userId): bool {
        $st = Database::pdo()->prepare('
            SELECT c.fecha, c.hora_inicio 
            FROM citas c
            JOIN pacientes p ON c.paciente_id = p.id
            WHERE c.id = :id AND p.usuario_id = :usuario_id
        ');
        $st->execute(['id'=>$id,'usuario_id'=>$userId]);
        $row = $st->fetch();
        if (!$row) return false;
        
        $citaDateTime = new \DateTimeImmutable($row['fecha'] . ' ' . $row['hora_inicio']);
        $now = new \DateTimeImmutable('now');
        if ($citaDateTime->getTimestamp() - $now->getTimestamp() < 24*3600) {
            return false; // no se puede cancelar a menos de 24h
        }
        
        $up = Database::pdo()->prepare("UPDATE citas SET estado='cancelado' WHERE id=:id");
        return $up->execute(['id'=>$id]);
    }

    public static function updateStatus(int $id, string $estado): bool {
        $allowed = ['pendiente','confirmado','atendido','cancelado'];
        if (!in_array($estado,$allowed,true)) return false;
        $st = Database::pdo()->prepare('UPDATE citas SET estado=:estado WHERE id=:id');
        return $st->execute(['estado'=>$estado,'id'=>$id]);
    }

    public static function updatePayment(int $id, string $paymentStatus): bool
    {
        $allowed = ['pendiente','pagado','rechazado'];
        if (!in_array($paymentStatus,$allowed,true)) return false;
        
        // Solo se puede cambiar el pago si la cita está atendida
        $st = Database::pdo()->prepare('UPDATE citas SET pago=:pago WHERE id=:id AND estado=\'atendido\'');
        return $st->execute(['pago'=>$paymentStatus,'id'=>$id]);
    }

    public static function belongsToDoctor(int $id, int $doctorId): bool {
        $st=Database::pdo()->prepare('SELECT 1 FROM citas WHERE id=:id AND doctor_id=:doctor_id');
        $st->execute(['id'=>$id,'doctor_id'=>$doctorId]);
        return (bool)$st->fetchColumn();
    }

    public static function belongsToPatient(int $id, int $userId): bool {
        $st=Database::pdo()->prepare('
            SELECT 1 FROM citas c
            JOIN pacientes p ON c.paciente_id = p.id
            WHERE c.id = :id AND p.usuario_id = :usuario_id
        ');
        $st->execute(['id'=>$id,'usuario_id'=>$userId]);
        return (bool)$st->fetchColumn();
    }
}
