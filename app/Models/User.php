<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $sql = 'SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN tiene_roles tr ON u.id = tr.usuario_id 
                LEFT JOIN roles r ON tr.rol_id = r.id 
                WHERE u.email = :email 
                LIMIT 1';
        if (Database::isSqlServer()) {
            $sql = 'SELECT TOP 1 u.*, r.nombre as rol_nombre 
                    FROM usuarios u 
                    LEFT JOIN tiene_roles tr ON u.id = tr.usuario_id 
                    LEFT JOIN roles r ON tr.rol_id = r.id 
                    WHERE u.email = :email';
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $nombre, string $apellido, string $email, string $passwordHash, string $rol = 'paciente', ?string $dni = null, ?string $telefono = null): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        
        try {
            // Crear usuario
            $stmt = $pdo->prepare('INSERT INTO usuarios(nombre, apellido, email, contrasenia, dni, telefono) VALUES (:nombre, :apellido, :email, :contrasenia, :dni, :telefono)');
            $stmt->execute([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'contrasenia' => $passwordHash,
                'dni' => $dni,
                'telefono' => $telefono
            ]);
            $userId = (int)$pdo->lastInsertId();
            
            // Obtener ID del rol
            $rolId = self::getRolId($rol);
            if (!$rolId) {
                throw new \Exception("Rol no encontrado: $rol");
            }
            
            // Asignar rol
            $stmt = $pdo->prepare('INSERT INTO tiene_roles(usuario_id, rol_id) VALUES (:usuario_id, :rol_id)');
            $stmt->execute(['usuario_id' => $userId, 'rol_id' => $rolId]);
            
            $pdo->commit();
            return $userId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function findById(int $id): ?array
    {
        $sql = 'SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN tiene_roles tr ON u.id = tr.usuario_id 
                LEFT JOIN roles r ON tr.rol_id = r.id 
                WHERE u.id = :id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function getRolId(string $rol): ?int
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM roles WHERE nombre = :rol');
        $stmt->execute(['rol' => $rol]);
        $result = $stmt->fetch();
        return $result ? (int)$result['id'] : null;
    }

    public static function getRoles(int $userId): array
    {
        $sql = 'SELECT r.nombre 
                FROM roles r 
                JOIN tiene_roles tr ON r.id = tr.rol_id 
                WHERE tr.usuario_id = :usuario_id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['usuario_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function hasRole(int $userId, string $rol): bool
    {
        $roles = self::getRoles($userId);
        return in_array($rol, $roles, true);
    }

    /** Pacientes para selector */
    public static function patients(?string $term = null, int $limit = 100): array
    {
        return self::getUsersByRole('paciente', $term, $limit);
    }

    /** Doctores para selector */
    public static function doctors(?string $term = null, int $limit = 100): array
    {
        return self::getUsersByRole('doctor', $term, $limit);
    }

    private static function getUsersByRole(string $rol, ?string $term, int $limit): array
    {
        $params = ['rol' => $rol];
        $where = "WHERE r.nombre = :rol";
        
        if ($term !== null && $term !== '') {
            $where .= " AND (u.nombre LIKE :s OR u.apellido LIKE :s OR u.email LIKE :s)";
            $params['s'] = '%' . $term . '%';
        }

        if (Database::driver() === 'sqlsrv') {
            $limit = max(1, (int)$limit);
            $sql = "SELECT TOP {$limit} u.id, u.nombre, u.apellido, u.email, u.telefono
                    FROM usuarios u
                    JOIN tiene_roles tr ON u.id = tr.usuario_id
                    JOIN roles r ON tr.rol_id = r.id
                    {$where}
                    ORDER BY u.nombre ASC";
            $stmt = Database::pdo()->prepare($sql);
        } else {
            $sql = "SELECT u.id, u.nombre, u.apellido, u.email, u.telefono
                    FROM usuarios u
                    JOIN tiene_roles tr ON u.id = tr.usuario_id
                    JOIN roles r ON tr.rol_id = r.id
                    {$where}
                    ORDER BY u.nombre ASC
                    LIMIT :lim";
            $stmt = Database::pdo()->prepare($sql);
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        }

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
