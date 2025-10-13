<?php
declare(strict_types=1);
use App\Core\{Env,Router,Request,Response,Eloquent};
session_start(); 
date_default_timezone_set('America/Lima');
require __DIR__.'/../vendor/autoload.php';
Env::load(__DIR__.'/../.env');
const BASE_PATH = __DIR__.'/..';

// Inicializar Eloquent ORM
Eloquent::init();

$router = new Router(); 
require BASE_PATH.'/routes/web.php'; 
require BASE_PATH.'/routes/api.php';
$request = Request::capture(); 
$response = new Response(); 
$router->dispatch($request, $response);