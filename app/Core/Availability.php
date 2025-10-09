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
        $dateString = $date->format('Y-m-d');
        $schedules = DoctorSchedule::forDate($doctorId, $locationId, $dateString);
        if (!$schedules) return [];

        $slots = [];
        $now = new \DateTimeImmutable();
        
        foreach ($schedules as $sch) {
            // Limpiar los microsegundos de las horas obtenidas de SQL Server
            $startTimeClean = substr($sch['hora_inicio'], 0, 8); // "08:00:00.0000000" -> "08:00:00"
            $endTimeClean = substr($sch['hora_fin'], 0, 8);       // "12:00:00.0000000" -> "12:00:00"
            
            $start = new \DateTimeImmutable($dateString . ' ' . $startTimeClean);
            $end   = new \DateTimeImmutable($dateString . ' ' . $endTimeClean);
            
            for ($t = $start; $t < $end; $t = $t->modify('+'.self::SLOT_MINUTES.' minutes')) {
                $tEnd = $t->modify('+'.self::SLOT_MINUTES.' minutes');
                if ($tEnd > $end) break;

                // No permitir reservar en pasado (solo si es hoy y la hora ya pasó)
                if ($date->format('Y-m-d') === $now->format('Y-m-d') && $t <= $now) {
                    continue;
                }

                $startTimeOnly = $t->format('H:i:s');
                $endTimeOnly = $tEnd->format('H:i:s');
                if (!Appointment::overlapsWindow($dateString, $startTimeOnly, $endTimeOnly, $doctorId, $locationId)) {
                    $slots[] = $t->format('H:i');
                }
            }
        }
        return array_values(array_unique($slots));
    }
}
