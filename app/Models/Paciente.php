<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $table = 'pacientes';
    protected $fillable = [
        'usuario_id', 'numero_historia_clinica', 'tipo_sangre', 'alergias',
        'condicion_cronica', 'historial_cirugias', 'historico_familiar',
        'observaciones', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
        'contacto_emergencia_relacion'
    ];
    
    public $timestamps = false;
    
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function user()
    {
        return $this->usuario();
    }
    
    public function citas()
    {
        return $this->hasMany(Appointment::class, 'paciente_id');
    }
    
    public static function findByUsuarioId($usuarioId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        $paciente = $db->fetchOne("SELECT * FROM pacientes WHERE usuario_id = ?", [$usuarioId]);
        
        if (!$paciente) {
            return null;
        }
        
        // Convertir a formato plano para la vista
        return [
            'id' => $paciente['id'],
            'usuario_id' => $paciente['usuario_id'],
            'numero_historia_clinica' => $paciente['numero_historia_clinica'] ?? '',
            'tipo_sangre' => $paciente['tipo_sangre'] ?? '',
            'alergias' => $paciente['alergias'] ?? '',
            'condicion_cronica' => $paciente['condicion_cronica'] ?? '',
            'historial_cirugias' => $paciente['historial_cirugias'] ?? '',
            'historico_familiar' => $paciente['historico_familiar'] ?? '',
            'observaciones' => $paciente['observaciones'] ?? '',
            'contacto_emergencia_nombre' => $paciente['contacto_emergencia_nombre'] ?? '',
            'contacto_emergencia_telefono' => $paciente['contacto_emergencia_telefono'] ?? '',
            'contacto_emergencia_relacion' => $paciente['contacto_emergencia_relacion'] ?? ''
        ];
    }
}
