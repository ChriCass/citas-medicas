<?php
declare(strict_types=1);
use App\Core\{Env,Router,Request,Response,SimpleDatabase,EloquentConfig,ModelAdapter,Database,Eloquent};
session_start(); 
date_default_timezone_set('America/Lima');
require __DIR__.'/../vendor/autoload.php';
Env::load(__DIR__.'/../.env');
const BASE_PATH = __DIR__.'/..';

// Inicializar conexiones a base de datos y Eloquent (manteniendo lógica de fallback existente)
$dbConfig = [
    'driver' => Env::get('DB_CONNECTION', 'mysql'),
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => (int)Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', ''),
    'username' => Env::get('DB_USERNAME', ''),
    'password' => Env::get('DB_PASSWORD', '')
];

// Si existe una clase Database personalizada, intentar inicializarla (no es obligatorio)
if (class_exists(\App\Core\Database::class)) {
    try {
        Database::init($dbConfig);
    } catch (Exception $e) {
        // No detener aquí; el resto de la inicialización intentará configurar Eloquent o SimpleDatabase
    }
}

// Intentar inicializar Eloquent. Soporta tanto una clase `Eloquent` nueva como la existente `EloquentConfig`.
$eloquentInitialized = false;
if (class_exists(\App\Core\Eloquent::class)) {
    try {
        Eloquent::init();
        ModelAdapter::setUseEloquent(true);
        $eloquentInitialized = true;
    } catch (Exception $e) {
        $eloquentInitialized = false;
    }
}

if (! $eloquentInitialized) {
    try {
        EloquentConfig::initialize();
        ModelAdapter::setUseEloquent(true);
        $eloquentInitialized = true;
    } catch (Exception $e) {
        // Fallback a SimpleDatabase
        ModelAdapter::setUseEloquent(false);
        try {
            SimpleDatabase::getInstance();
        } catch (Exception $e2) {
            die("Error de conexión a la base de datos: " . $e2->getMessage());
        }
    }
}

$router = new Router(); 
require BASE_PATH.'/routes/web.php'; 
require BASE_PATH.'/routes/api.php';
$request = Request::capture(); 
$response = new Response(); 
$router->dispatch($request, $response);