<?php
namespace App\Core;
class Response {
  public function json($data,int $status=200): void{ http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
  public function redirect(string $to): void{ header('Location: '.$to); exit; }
  public function view(string $view,array $params=[],string $layout='layouts/main'){ echo View::render($view,$params,$layout); exit; }
  public function abort(int $code=403,string $message='Forbidden'): void{ http_response_code($code); echo $message; exit; }
}