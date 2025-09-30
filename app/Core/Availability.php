<?php
namespace App\Core;

use App\Models\{Appointment, DoctorSchedule};

class Availability
{
    /** Duración fija de cita: 15 minutos */
    public const SLOT_MINUTES = 15;

    /**
     * Genera slots de 15 min para un doctor en una sede y fecha dada.
     */
    public static function slotsForDate(\DateTimeImmutable $date, int $doctorId, int $locationId): array
    {
        $weekday = (int)$date->format('w'); // 0..6
        $schedules = DoctorSchedule::for($doctorId, $locationId, $weekday);
        if (!$schedules) return [];

        $slots = [];
        [$y,$m,$d] = [$date->format('Y'), $date->format('m'), $date->format('d')];
        foreach ($schedules as $sch) {
            $start = new \DateTimeImmutable("$y-$m-$d {$sch['start_time']}");
            $end   = new \DateTimeImmutable("$y-$m-$d {$sch['end_time']}");
            for ($t = $start; $t < $end; $t = $t->modify('+'.self::SLOT_MINUTES.' minutes')) {
                $tEnd = $t->modify('+'.self::SLOT_MINUTES.' minutes');
                if ($tEnd > $end) break;

                // No permitir reservar en pasado
                if ($t <= new \DateTimeImmutable()) continue;

                $s = $t->format('Y-m-d H:i:s');
                $e = $tEnd->format('Y-m-d H:i:s');
                if (!Appointment::overlapsWindow($s, $e, $doctorId, $locationId)) {
                    $slots[] = $t->format('H:i');
                }
            }
        }
        return array_values(array_unique($slots));
    }
}
