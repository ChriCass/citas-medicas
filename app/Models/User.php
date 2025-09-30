<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $sql = 'SELECT * FROM usuarios WHERE email = :email LIMIT 1';
        if (Database::isSqlServer()) {
            $sql = 'SELECT TOP 1 * FROM usuarios WHERE email = :email';
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $name, string $email, string $passwordHash, string $role = 'patient'): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO usuarios(name,email,password,role) VALUES (:name,:email,:password,:role)');
        $stmt->execute(['name' => $name, 'email' => $email, 'password' => $passwordHash, 'role' => $role]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id,name,email,role,created_at FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Pacientes para selector (role='patient') */
    public static function patients(?string $term = null, int $limit = 100): array
    {
        return self::byRole('patient', $term, $limit);
    }

    /** Doctores para selector (role='doctor') */
    public static function doctors(?string $term = null, int $limit = 100): array
    {
        return self::byRole('doctor', $term, $limit);
    }

    private static function byRole(string $role, ?string $term, int $limit): array
    {
        $params = ['role' => $role];
        $where  = "WHERE role = :role";
        if ($term !== null && $term !== '') {
            $where .= " AND (name LIKE :s OR email LIKE :s)";
            $params['s'] = '%' . $term . '%';
        }

        if (Database::driver() === 'sqlsrv') {
            $limit = max(1, (int)$limit);
            $sql = "SELECT TOP {$limit} id, name, email, role
                    FROM usuarios
                    {$where}
                    ORDER BY name ASC";
            $stmt = Database::pdo()->prepare($sql);
        } else {
            $sql = "SELECT id, name, email, role
                    FROM usuarios
                    {$where}
                    ORDER BY name ASC
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
