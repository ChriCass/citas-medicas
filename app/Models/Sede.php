<?php
namespace App\Models;

use App\Core\Database;

class Sede
{
    public static function getAll(): array
    {
        return Database::pdo()->query('SELECT * FROM sedes ORDER BY nombre_sede ASC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM sedes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $nombreSede, ?string $direccion = null, ?string $telefono = null): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO sedes(nombre_sede, direccion, telefono) VALUES (:nombre_sede, :direccion, :telefono)');
        $stmt->execute(['nombre_sede' => $nombreSede, 'direccion' => $direccion, 'telefono' => $telefono]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = 'UPDATE sedes SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM sedes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function getDoctores(int $sedeId): array
    {
        $sql = 'SELECT d.*, u.nombre, u.apellido, u.email, e.nombre as especialidad_nombre
                FROM doctores d
                JOIN doctor_sede ds ON d.id = ds.doctor_id
                JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                WHERE ds.sede_id = :sede_id
                ORDER BY u.nombre ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['sede_id' => $sedeId]);
        return $stmt->fetchAll();
    }
}
