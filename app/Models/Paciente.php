<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Paciente
{
    public static function findByUsuarioId(int $usuarioId): ?array
    {
        $sql = 'SELECT p.*, u.nombre, u.apellido, u.email, u.telefono, u.dni
                FROM pacientes p
                JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.usuario_id = :usuario_id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $usuarioId, ?string $tipoSangre = null, ?string $alergias = null, ?string $condicionCronica = null, ?string $historialCirugias = null, ?string $historicoFamiliar = null, ?string $observaciones = null, ?string $contactoEmergenciaNombre = null, ?string $contactoEmergenciaTelefono = null, ?string $contactoEmergenciaRelacion = null): int
    {
        $stmt = Database::pdo()->prepare('
            INSERT INTO pacientes(usuario_id, tipo_sangre, alergias, condicion_cronica, historial_cirugias, historico_familiar, observaciones, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_relacion)
            VALUES (:usuario_id, :tipo_sangre, :alergias, :condicion_cronica, :historial_cirugias, :historico_familiar, :observaciones, :contacto_emergencia_nombre, :contacto_emergencia_telefono, :contacto_emergencia_relacion)
        ');
        
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'tipo_sangre' => $tipoSangre,
            'alergias' => $alergias,
            'condicion_cronica' => $condicionCronica,
            'historial_cirugias' => $historialCirugias,
            'historico_familiar' => $historicoFamiliar,
            'observaciones' => $observaciones,
            'contacto_emergencia_nombre' => $contactoEmergenciaNombre,
            'contacto_emergencia_telefono' => $contactoEmergenciaTelefono,
            'contacto_emergencia_relacion' => $contactoEmergenciaRelacion
        ]);
        
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $usuarioId, array $data): bool
    {
        $fields = [];
        $params = ['usuario_id' => $usuarioId];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = 'UPDATE pacientes SET ' . implode(', ', $fields) . ' WHERE usuario_id = :usuario_id';
        $stmt = Database::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function getAll(): array
    {
        $sql = 'SELECT p.*, u.nombre, u.apellido, u.email, u.telefono, u.dni
                FROM pacientes p
                JOIN usuarios u ON p.usuario_id = u.id
                ORDER BY u.nombre ASC';
        return Database::pdo()->query($sql)->fetchAll();
    }
}
