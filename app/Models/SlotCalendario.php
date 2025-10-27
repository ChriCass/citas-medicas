<?php
namespace App\Models;

use App\Models\BaseModel;

class SlotCalendario extends BaseModel
{
    protected $table = 'slots_calendario';

    protected $fillable = [
        'calendario_id', 'horario_id', 'hora_inicio', 'hora_fin', 'disponible', 'reservado_por_cita_id', 'creado_en', 'actualizado_en'
    ];

    public $timestamps = false;

    /**
     * Crear bloques de tamaño $minutes entre $start y $end para un calendario dado.
     * Devuelve el número de slots creados.
     */
    public static function createSlots(int $calendarioId, ?int $horarioId, string $start, string $end, int $minutes = 15): int
    {
        $startDt = \DateTime::createFromFormat('H:i:s', strlen($start) === 5 ? $start . ':00' : $start);
        $endDt = \DateTime::createFromFormat('H:i:s', strlen($end) === 5 ? $end . ':00' : $end);
        if (!$startDt || !$endDt) return 0;

        $created = 0;
        $intervalSpec = 'PT' . (int)$minutes . 'M';
        $interval = new \DateInterval($intervalSpec);

        while ($startDt < $endDt) {
            $slotEnd = (clone $startDt)->add($interval);
            if ($slotEnd > $endDt) break;

            $slot = new static();
            $slot->calendario_id = $calendarioId;
            $slot->horario_id = $horarioId ?: null;
            $slot->hora_inicio = $startDt->format('H:i:s');
            $slot->hora_fin = $slotEnd->format('H:i:s');
            $slot->disponible = 1;
            $slot->reservado_por_cita_id = null;
            $slot->creado_en = date('Y-m-d H:i:s');
            $slot->save();
            $created++;

            $startDt = $slotEnd;
        }

        return $created;
    }
}
