<?php
namespace App\Middleware;
use App\Core\{Request,Response};
class JsonMiddleware implements MiddlewareInterface {
  public function handle(Request $r, callable $next, Response $res){ header('Content-Type: application/json; charset=utf-8'); return $next(); }
}