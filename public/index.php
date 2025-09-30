<?php
declare(strict_types=1);
use App\Core\{Env,Router,Request,Response,Database};
session_start(); date_default_timezone_set('America/Lima');
require __DIR__.'/../vendor/autoload.php';
Env::load(__DIR__.'/../.env');
const BASE_PATH = __DIR__.'/..';
Database::init([
  'driver'=>Env::get('DB_CONNECTION','mysql'),
  'host'=>Env::get('DB_HOST','127.0.0.1'),
  'port'=>(int)Env::get('DB_PORT','3306'),
  'database'=>Env::get('DB_DATABASE',''),
  'username'=>Env::get('DB_USERNAME',''),
  'password'=>Env::get('DB_PASSWORD',''),
]);
$router=new Router(); require BASE_PATH.'/routes/web.php'; require BASE_PATH.'/routes/api.php';
$request=Request::capture(); $response=new Response(); $router->dispatch($request,$response);
