<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Superadmin extends BaseModel
{
    protected $table = 'superadmins';
    
    protected $fillable = ['usuario_id', 'nombre', 'usuario', 'contrasenia'];
    
    protected $hidden = ['contrasenia'];
    
    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}