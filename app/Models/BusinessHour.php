<?php
namespace App\Models; use App\Core\Database;
class BusinessHour{
  public static function forWeekday(int $w): ?array{ $sql='SELECT * FROM horarios_atencion WHERE weekday=:w'; if(Database::isSqlServer()) $sql='SELECT TOP 1 * FROM horarios_atencion WHERE weekday=:w'; $st=Database::pdo()->prepare($sql); $st->execute(['w'=>$w]); $r=$st->fetch(); return $r?:null; }
}