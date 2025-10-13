<?php
namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Eloquent
{
    private static ?Capsule $capsule = null;

    public static function init(): void
    {
        if (self::$capsule) return;

        $capsule = new Capsule;

        // Configuración de la base de datos desde .env
        $driver = Env::get('DB_CONNECTION', 'mysql');
        
        if ($driver === 'mysql') {
            $config = [
                'driver' => 'mysql',
                'host' => Env::get('DB_HOST', '127.0.0.1'),
                'port' => (int)Env::get('DB_PORT', '3306'),
                'database' => Env::get('DB_DATABASE', ''),
                'username' => Env::get('DB_USERNAME', ''),
                'password' => Env::get('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
            ];
        } elseif ($driver === 'sqlsrv') {
            $config = [
                'driver' => 'sqlsrv',
                'host' => Env::get('DB_HOST', '127.0.0.1'),
                'database' => Env::get('DB_DATABASE', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'options' => [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ],
                'trust_server_certificate' => true,
            ];
            
            // No agregamos username/password para usar autenticación de Windows
        } else {
            throw new \Exception("Driver de base de datos no soportado: {$driver}");
        }

        $capsule->addConnection($config);

        // Set the event dispatcher used by Eloquent models
        $capsule->setEventDispatcher(new Dispatcher(new Container));

        // Make this Capsule instance available globally via static methods
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM
        $capsule->bootEloquent();

        self::$capsule = $capsule;
    }

    public static function capsule(): Capsule
    {
        if (!self::$capsule) {
            throw new \RuntimeException('Eloquent no inicializado');
        }
        return self::$capsule;
    }

    public static function connection()
    {
        return self::capsule()->getConnection();
    }
}