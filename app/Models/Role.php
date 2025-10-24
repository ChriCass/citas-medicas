<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $fillable = ['nombre', 'descripcion'];
    
    public $timestamps = false;
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'tiene_roles', 'rol_id', 'usuario_id');
    }
}
