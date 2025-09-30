<?php
namespace App\Core;
class Env {
  public static function load(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
      if ($line===null) continue;
      $line = trim($line);
      if ($line==='' || str_starts_with($line, '#')) continue;
      if (($pos=strpos($line,'#'))!==false) { $line=trim(substr($line,0,$pos)); if($line==='') continue; }
      [$k,$v] = array_pad(explode('=',$line,2),2,'');
      $k=trim($k); $v=trim($v);
      if ($k==='') continue;
      if ((str_starts_with($v,'"') && str_ends_with($v,'"'))||(str_starts_with($v,"'")&&str_ends_with($v,"'"))) $v=substr($v,1,-1);
      $_ENV[$k]=$v; putenv("$k=$v");
    }
  }
  public static function get(string $k, ?string $d=null): ?string { return $_ENV[$k] ?? getenv($k) ?: $d; }
}