<?php
declare(strict_types=1);
use App\Core\{Env,Router,Request,Response,SimpleDatabase,EloquentConfig,ModelAdapter};
session_start(); 
date_default_timezone_set('America/Lima');
require __DIR__.'/../vendor/autoload.php';
Env::load(__DIR__.'/../.env');
const BASE_PATH = __DIR__.'/..';

// Inicializar Eloquent
try {
    EloquentConfig::initialize();
    ModelAdapter::setUseEloquent(true);
} catch (Exception $e) {
    // Fallback a SimpleDatabase
    ModelAdapter::setUseEloquent(false);
    try {
        SimpleDatabase::getInstance();
    } catch (Exception $e2) {
        die("Error de conexión a la base de datos: " . $e2->getMessage());
    }
}

$router = new Router(); 
require BASE_PATH.'/routes/web.php'; 
$request = Request::capture(); 
$response = new Response(); 
$router->dispatch($request, $response);