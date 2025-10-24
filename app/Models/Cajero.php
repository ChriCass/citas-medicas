<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cajero extends Model
{
    protected $table = 'cajeros';
    protected $fillable = ['usuario_id', 'nombre', 'usuario', 'contrasenia'];
    
    public $timestamps = false;
    
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    
    public function pagos()
    {
        return $this->hasMany(Payment::class, 'cajero_id');
    }
}
