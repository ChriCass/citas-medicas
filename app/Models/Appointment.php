<?php
namespace App\Models;
use App\Core\Database;
use PDO;

class Appointment
{
    public static function listAll(): array {
        $sql = "SELECT a.*,
                       s.name AS service_name,
                       u.name AS user_name, u.role AS user_role,
                       d.name AS doctor_name,
                       l.name AS location_name
                FROM citas a
                JOIN servicios s ON s.id = a.service_id
                JOIN usuarios u    ON u.id = a.user_id
                LEFT JOIN usuarios d ON d.id = a.doctor_id
                LEFT JOIN ubicaciones l ON l.id = a.location_id
                ORDER BY a.starts_at DESC";
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function usercitas(int $userId): array {
        $sql = "SELECT a.*, s.name AS service_name, d.name AS doctor_name, l.name AS location_name
                FROM citas a
                JOIN servicios s ON s.id = a.service_id
                LEFT JOIN usuarios d ON d.id = a.doctor_id
                LEFT JOIN ubicaciones l ON l.id = a.location_id
                WHERE a.user_id = :uid
                ORDER BY a.starts_at DESC";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['uid'=>$userId]);
        return $st->fetchAll();
    }

    public static function doctorcitas(int $doctorId): array {
        $sql = "SELECT a.*, s.name AS service_name, u.name AS user_name, l.name AS location_name
                FROM citas a
                JOIN servicios s ON s.id = a.service_id
                JOIN usuarios u    ON u.id = a.user_id
                LEFT JOIN ubicaciones l ON l.id = a.location_id
                WHERE a.doctor_id = :did
                ORDER BY a.starts_at DESC";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['did'=>$doctorId]);
        return $st->fetchAll();
    }

    public static function upcomingForUser(int $userId, int $limit = 5): array {
        if (Database::driver()==='sqlsrv') {
            $sql = "SELECT a.*, s.name AS service_name
                    FROM citas a
                    JOIN servicios s ON s.id = a.service_id
                    WHERE a.user_id = :uid
                      AND a.status <> 'cancelled'
                      AND a.starts_at >= GETDATE()
                    ORDER BY a.starts_at ASC
                    OFFSET 0 ROWS FETCH NEXT $limit ROWS ONLY";
            $st = Database::pdo()->prepare($sql);
            $st->bindValue(':uid',$userId,PDO::PARAM_INT);
            $st->execute(); return $st->fetchAll();
        } else {
            $sql = "SELECT a.*, s.name AS service_name
                    FROM citas a
                    JOIN servicios s ON s.id = a.service_id
                    WHERE a.user_id = :uid
                      AND a.status <> 'cancelled'
                      AND a.starts_at >= NOW()
                    ORDER BY a.starts_at ASC
                    LIMIT :l";
            $st = Database::pdo()->prepare($sql);
            $st->bindValue(':uid',$userId,PDO::PARAM_INT);
            $st->bindValue(':l',(int)$limit,PDO::PARAM_INT);
            $st->execute(); return $st->fetchAll();
        }
    }

    /** Chequea solape para doctor o sede (no se doble-reserva) */
    public static function overlapsWindow(string $start, string $end, int $doctorId, int $locationId): bool {
        $sqlMy = "SELECT 1 FROM citas
                  WHERE status<>'cancelled'
                    AND NOT (ends_at<=:start OR starts_at>=:end)
                    AND (doctor_id=:d OR location_id=:l)
                  LIMIT 1";
        $sqlMs = "SELECT TOP 1 1 FROM citas
                  WHERE status<>'cancelled'
                    AND NOT (ends_at<=:start OR starts_at>=:end)
                    AND (doctor_id=:d OR location_id=:l)";
        $sql = Database::isSqlServer() ? $sqlMs : $sqlMy;
        $st = Database::pdo()->prepare($sql);
        $st->execute(['start'=>$start,'end'=>$end,'d'=>$doctorId,'l'=>$locationId]);
        return (bool)$st->fetchColumn();
    }

    public static function create(
        int $userId,
        int $serviceId,
        int $doctorId,
        int $locationId,
        string $start,
        string $end,
        ?string $notes=''
    ): int {
        $st = Database::pdo()->prepare(
            "INSERT INTO citas(user_id, doctor_id, location_id, service_id, starts_at, ends_at, status, payment_status, notes)
             VALUES (:u, :d, :l, :s, :b, :e, 'pending', 'unpaid', :n)"
        );
        $st->execute([
          'u'=>$userId,'d'=>$doctorId,'l'=>$locationId,'s'=>$serviceId,
          'b'=>$start,'e'=>$end,'n'=>$notes
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    /** Paciente cancela si faltan >= 24h */
    public static function cancelByPatient(int $id, int $userId): bool {
        $st = Database::pdo()->prepare('SELECT starts_at FROM citas WHERE id=:id AND user_id=:u');
        $st->execute(['id'=>$id,'u'=>$userId]);
        $row = $st->fetch();
        if (!$row) return false;
        $starts = new \DateTimeImmutable($row['starts_at']);
        $now = new \DateTimeImmutable('now');
        if ($starts->getTimestamp() - $now->getTimestamp() < 24*3600) {
            return false; // no se puede cancelar a menos de 24h
        }
        $up = Database::pdo()->prepare("UPDATE citas SET status='cancelled' WHERE id=:id AND user_id=:u");
        return $up->execute(['id'=>$id,'u'=>$userId]);
    }

    public static function updateStatus(int $id, string $status): bool {
        $allowed = ['pending','confirmed','attended','cancelled'];
        if (!in_array($status,$allowed,true)) return false;
        $st = Database::pdo()->prepare('UPDATE citas SET status=:st WHERE id=:id');
        return $st->execute(['st'=>$status,'id'=>$id]);
    }

    public static function updatePayment(int $id, string $payment): bool {
        $allowed = ['unpaid','paid'];
        if (!in_array($payment,$allowed,true)) return false;
        // Solo permitir pago cuando está 'attended'
        $chk = Database::pdo()->prepare('SELECT status FROM citas WHERE id=:id');
        $chk->execute(['id'=>$id]);
        $stt = $chk->fetchColumn();
        if ($stt !== 'attended') return false;
        $st = Database::pdo()->prepare('UPDATE citas SET payment_status=:p WHERE id=:id');
        return $st->execute(['p'=>$payment,'id'=>$id]);
    }

    public static function belongsToDoctor(int $id, int $doctorId): bool {
        $st=Database::pdo()->prepare('SELECT 1 FROM citas WHERE id=:id AND doctor_id=:d');
        $st->execute(['id'=>$id,'d'=>$doctorId]);
        return (bool)$st->fetchColumn();
    }
}
