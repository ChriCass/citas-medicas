<?php
namespace App\Core;
class Request {
  public string $method; public string $uri; public array $query; public array $body; public array $files; public array $headers; public array $params=[];
  public static function capture(): self {
    $r=new self(); $r->method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
    $uri=$_SERVER['REQUEST_URI']??'/'; $r->uri=parse_url($uri, PHP_URL_PATH)?:'/';
    $r->query=$_GET??[]; $r->body=$_POST??[]; $r->files=$_FILES??[]; $r->headers=function_exists('getallheaders')?(getallheaders()?:[]):[];
    return $r;
}}