<?php
namespace App\Middleware;
use App\Core\{Request,Response};
class GuestMiddleware implements MiddlewareInterface {
  public function handle(Request $r, callable $next, Response $res){ if(!empty($_SESSION['user'])) return $res->redirect('/dashboard'); return $next(); }
}