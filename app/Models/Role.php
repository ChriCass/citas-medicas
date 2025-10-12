<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends BaseModel
{
    protected $table = 'roles';
    
    protected $fillable = ['nombre', 'descripcion'];
    
    // Relaciones
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tiene_roles', 'rol_id', 'usuario_id')
                    ->withPivot('creado_en');
    }
}