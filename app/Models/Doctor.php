<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Doctor
{
    public static function find(int $id): ?array
    {
        $sql = 'SELECT d.*, u.nombre, u.apellido, u.email, u.telefono, u.dni, e.nombre as especialidad_nombre
                FROM doctores d
                JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                WHERE d.id = :id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUsuarioId(int $usuarioId): ?array
    {
        $sql = 'SELECT d.*, u.nombre, u.apellido, u.email, u.telefono, u.dni, e.nombre as especialidad_nombre
                FROM doctores d
                JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                WHERE d.usuario_id = :usuario_id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $usuarioId, ?int $especialidadId = null, ?string $cmp = null, ?string $biografia = null): int
    {
        $stmt = Database::pdo()->prepare('
            INSERT INTO doctores(usuario_id, especialidad_id, cmp, biografia)
            VALUES (:usuario_id, :especialidad_id, :cmp, :biografia)
        ');
        
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'especialidad_id' => $especialidadId,
            'cmp' => $cmp,
            'biografia' => $biografia
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
        
        $sql = 'UPDATE doctores SET ' . implode(', ', $fields) . ' WHERE usuario_id = :usuario_id';
        $stmt = Database::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function getAll(): array
    {
        $sql = 'SELECT d.*, u.nombre, u.apellido, u.email, u.telefono, u.dni, e.nombre as especialidad_nombre
                FROM doctores d
                JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                ORDER BY u.nombre ASC';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function getByEspecialidad(int $especialidadId): array
    {
        $sql = 'SELECT d.*, u.nombre, u.apellido, u.email, u.telefono, e.nombre as especialidad_nombre
                FROM doctores d
                JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                WHERE d.especialidad_id = :especialidad_id
                ORDER BY u.nombre ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['especialidad_id' => $especialidadId]);
        return $stmt->fetchAll();
    }
}
