<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receta extends BaseModel
{
	protected $table = 'recetas';
	public $timestamps = false;
	protected $fillable = ['consulta_id','id_medicamento','indicacion','duracion'];

	public function consulta()
	{
		return $this->belongsTo(Consulta::class, 'consulta_id');
	}

	public function medicamento()
	{
		return $this->belongsTo(Medicamento::class, 'id_medicamento');
	}
}
