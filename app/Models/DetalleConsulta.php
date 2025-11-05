<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Capsule\Manager as DB;

class DetalleConsulta extends BaseModel
{
    protected $table = 'detalle_consulta';

    protected $fillable = [
        'id_consulta', 'id_diagnostico'
    ];

    public $timestamps = false;

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'id_consulta');
    }

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class, 'id_diagnostico');
    }
}
