<?php
namespace App\Models;

use App\Core\Database;

class Especialidad
{
    public static function getAll(): array
    {
        return Database::pdo()->query('SELECT * FROM especialidades ORDER BY nombre ASC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM especialidades WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $nombre, ?string $descripcion = null): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO especialidades(nombre, descripcion) VALUES (:nombre, :descripcion)');
        $stmt->execute(['nombre' => $nombre, 'descripcion' => $descripcion]);
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
        
        $sql = 'UPDATE especialidades SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM especialidades WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
