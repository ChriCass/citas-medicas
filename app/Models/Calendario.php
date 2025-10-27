<?php
namespace App\Models;

use App\Models\BaseModel;

class Calendario extends BaseModel
{
    protected $table = 'calendario';

    protected $fillable = [
        'doctor_id', 'horario_id', 'fecha', 'estado', 'hora_inicio', 'hora_fin', 'tipo', 'motivo', 'auto_generado', 'creado_en'
    ];

    public $timestamps = false;

    public static function existsFor(int $doctorId, string $date, ?int $horarioId = null): bool
    {
        $query = static::where('doctor_id', $doctorId)
                       ->where('fecha', $date);

        if ($horarioId !== null && $horarioId > 0) {
            $query->where('horario_id', $horarioId);
        }

        return $query->exists();
    }

    public static function createEntry(int $doctorId, ?int $horarioId, string $date, ?string $horaInicio, ?string $horaFin): int
    {
        $c = new static();
        $c->doctor_id = $doctorId;
        $c->horario_id = $horarioId ?: null;
        $c->fecha = $date;
        $c->estado = 'activo';
        $c->tipo = 'normal';
        $c->auto_generado = 1;
        $c->creado_en = date('Y-m-d H:i:s');

        // Si se pasan horas explícitas, guardarlas; si no, dejar null y el sistema podrá usar horario_id
        $c->hora_inicio = $horaInicio ?: null;
        $c->hora_fin = $horaFin ?: null;

        $c->save();
        return (int)$c->id;
    }
}
