<?php
namespace App\Core;
class Auth {
  public static function user(): ?array { return $_SESSION['user'] ?? null; }
  public static function hasRole(array $roles): bool { $u=self::user(); return $u && in_array($u['role']??null,$roles,true); }
  public static function abortUnless(\App\Core\Response $res, array $roles): void { if(!self::hasRole($roles)) $res->abort(403,'No tienes acceso a esta pantalla.'); }
}