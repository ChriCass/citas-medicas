<?php
namespace App\Middleware;
use App\Core\{Request,Response};
interface MiddlewareInterface{ public function handle(Request $request, callable $next, Response $response); }