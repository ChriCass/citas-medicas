<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class EloquentConfig
{
    public static function initialize()
    {
        $capsule = new Capsule;
        
        // Configuración de base de datos
        $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
        
        // Si no hay archivo .env, usar SQLite por defecto
        if (!file_exists(BASE_PATH . '/.env')) {
            $driver = 'sqlite';
        }
        
        
        switch ($driver) {
            case 'sqlite':
                $capsule->addConnection([
                    'driver' => 'sqlite',
                    'database' => BASE_PATH . '/database.sqlite',
                    'prefix' => '',
                ]);
                break;
                
            case 'mysql':
                $capsule->addConnection([
                    'driver' => 'mysql',
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'database' => $_ENV['DB_DATABASE'] ?? 'citas_medicas',
                    'username' => $_ENV['DB_USERNAME'] ?? 'root',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ]);
                break;
                
            case 'sqlsrv':
                $capsule->addConnection([
                    'driver' => 'sqlsrv',
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'database' => $_ENV['DB_DATABASE'] ?? 'citas_medicas',
                    'username' => $_ENV['DB_USERNAME'] ?? 'sa',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                    'charset' => 'utf8',
                    'prefix' => '',
                ]);
                break;
        }
        
        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
    }
}
