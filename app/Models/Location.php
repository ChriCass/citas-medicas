<?php
namespace App\Models;
use App\Core\Database;

class Location {
  public static function allActive(): array {
    return Database::pdo()->query('SELECT * FROM ubicaciones WHERE is_active=1 ORDER BY name')->fetchAll();
  }

  public static function find(int $id): ?array {
    $st = Database::pdo()->prepare('SELECT * FROM ubicaciones WHERE id=:id AND is_active=1');
    $st->execute(['id'=>$id]);
    $r = $st->fetch(); return $r ?: null;
  }
}
