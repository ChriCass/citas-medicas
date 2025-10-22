<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicamento extends BaseModel
{
    protected $table = 'medicamentos';
    public $timestamps = false;
    protected $fillable = ['nombre','presentacion','observaciones'];

    public function recetas()
    {
        return $this->hasMany(Receta::class, 'id_medicamento');
    }
}
