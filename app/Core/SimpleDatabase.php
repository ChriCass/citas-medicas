<?php

namespace App\Core;

use PDO;
use PDOException;

class SimpleDatabase
{
    private static $instance = null;
    private $pdo = null;

    private function __construct()
    {
        $this->connect();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect()
    {
        try {
            // Leer configuración del .env
            $envFile = __DIR__ . '/../../.env';
            $config = [];
            
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                        list($key, $value) = explode('=', $line, 2);
                        $config[trim($key)] = trim($value);
                    }
                }
            }

            $driver = $config['DB_CONNECTION'] ?? 'sqlsrv';
            $host = $config['DB_HOST'] ?? 'localhost';
            $database = $config['DB_DATABASE'] ?? 'med_database_v5';
            $username = $config['DB_USERNAME'] ?? 'sa';
            $password = $config['DB_PASSWORD'] ?? '';

            if ($driver === 'sqlsrv') {
                // Intentar usar SQL Server por defecto
                try {
                    $dsn = "sqlsrv:Server={$host};Database={$database}";
                    $this->pdo = new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    return;
                } catch (PDOException $e) {
                    // Si falla SQL Server, usar SQLite como fallback
                    echo "Warning: SQL Server no disponible, usando SQLite como fallback\n";
                    $dbFile = __DIR__ . '/../../database.sqlite';
                    $this->pdo = new PDO('sqlite:' . $dbFile);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $this->createSchemaIfNeeded();
                    return;
                }
            }

            // Fallback a SQLite si se especifica
            if ($driver === 'sqlite') {
                $dbFile = __DIR__ . '/../../database.sqlite';
                $this->pdo = new PDO('sqlite:' . $dbFile);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->createSchemaIfNeeded();
                return;
            }

        } catch (PDOException $e) {
            throw new \Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    private function createSchemaIfNeeded()
    {
        // Verificar si las tablas existen (solo para SQLite)
        $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetchAll();
        
        if (empty($tables)) {
            // Crear schema desde el archivo SQLite
            $schemaFile = __DIR__ . '/../../sql/sqlite/schema.sql';
            if (file_exists($schemaFile)) {
                $schema = file_get_contents($schemaFile);
                $this->pdo->exec($schema);
            }
        }
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function getConnectionType()
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlsrv') {
            return 'sqlsrv';
        } elseif ($driver === 'sqlite') {
            return 'sqlite';
        } elseif ($driver === 'mysql') {
            return 'mysql';
        }
        
        return 'unknown';
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Error en consulta: " . $e->getMessage());
        }
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($table, $data)
    {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        // Agregar parámetros de WHERE al final
        $params = array_merge($params, $whereParams);
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        return $result && $stmt->rowCount() > 0;
    }

    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        return $result && $stmt->rowCount() > 0;
    }
}
