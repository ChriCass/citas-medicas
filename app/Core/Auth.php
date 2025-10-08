<?php
namespace App\Core;
class Auth {
  public static function user(): ?array { return $_SESSION['user'] ?? null; }
  public static function hasRole(array $roles): bool { 
    $u = self::user();
    if (!$u) return false;
    $role = $u['rol'] ?? ($u['role'] ?? null);
    return in_array($role, $roles, true);
  }
  public static function abortUnless(\App\Core\Response $res, array $roles): void { if(!self::hasRole($roles)) $res->abort(403,'No tienes acceso a esta pantalla.'); }
}