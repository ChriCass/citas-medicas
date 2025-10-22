<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Capsule\Manager as DB;

class Consulta extends BaseModel
{
    protected $table = 'consultas';

    protected $fillable = [
        'cita_id', 'diagnostico_id', 'observaciones', 'receta', 'estado_postconsulta'
    ];

    public $timestamps = false;

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'cita_id');
    }

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(DiagnosticoModel::class, 'diagnostico_id');
    }

    public static function findByCitaId(int $citaId): ?self
    {
        return static::where('cita_id', $citaId)->first();
    }
}
