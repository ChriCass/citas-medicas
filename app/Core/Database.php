<?php
namespace App\Core;
use PDO;
use PDOException;

class Database {
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function init(array $c): void {
        if (self::$pdo) return;
        $d = $c['driver'] ?? 'mysql';
        self::$driver = $d;
        
        try {
            if ($d === 'sqlsrv') {
                $host = $c['host'] ?? '127.0.0.1';
                $db = $c['database'] ?? '';
                $user = $c['username'] ?? '';
                $pass = $c['password'] ?? '';
                
                $dsn = "sqlsrv:Server={$host};Database={$db}";
                
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } else {
                $host = $c['host'] ?? '127.0.0.1';
                $port = (int)($c['port'] ?? 3306);
                $db = $c['database'] ?? '';
                $user = $c['username'] ?? '';
                $pass = $c['password'] ?? '';
                
                $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
                
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Error de conexión DB: ' . $e->getMessage());
        }
    }

    public static function pdo(): PDO {
        if (!self::$pdo) throw new \RuntimeException('DB no inicializada');
        return self::$pdo;
    }

    public static function driver(): string {
        return self::$driver;
    }

    public static function isSqlServer(): bool {
        return self::$driver === 'sqlsrv';
    }
}