<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = 'doctores';
    protected $fillable = [
        'usuario_id', 'especialidad_id', 'cmp', 'biografia'
    ];
    
    public $timestamps = false;
    
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    
    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }
    
    public function citas()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }
    
    public function horarios()
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }

    /**
     * Many-to-many relationship: doctors <-> sedes via pivot table doctor_sede
     */
    public function sedes()
    {
        return $this->belongsToMany(Sede::class, 'doctor_sede', 'doctor_id', 'sede_id');
    }
    
    public static function findByUsuarioId($usuarioId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT d.*, 
                       u.nombre as user_nombre, u.apellido as user_apellido,
                       e.nombre as especialidad_nombre, e.descripcion as especialidad_descripcion
                FROM doctores d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                WHERE d.usuario_id = ?";
        
        $doctor = $db->fetchOne($sql, [$usuarioId]);
        
        if (!$doctor) {
            return null;
        }
        
        // Convertir a formato plano para la vista
        return [
            'id' => $doctor['id'],
            'usuario_id' => $doctor['usuario_id'],
            'especialidad_id' => $doctor['especialidad_id'],
            'cmp' => $doctor['cmp'],
            'biografia' => $doctor['biografia'],
            'user_nombre' => $doctor['user_nombre'] ?? '',
            'user_apellido' => $doctor['user_apellido'] ?? '',
            'especialidad_nombre' => $doctor['especialidad_nombre'] ?? '',
            'especialidad_descripcion' => $doctor['especialidad_descripcion'] ?? ''
        ];
    }
    
    public static function getAll()
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT d.*, 
                       u.nombre as user_nombre, u.apellido as user_apellido,
                       e.nombre as especialidad_nombre, e.descripcion as especialidad_descripcion
                FROM doctores d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                ORDER BY u.nombre ASC";
        
        $doctors = $db->fetchAll($sql);
        
        // Convertir a formato plano para la vista
        return array_map(function($doctor) {
            return [
                'id' => $doctor['id'],
                'usuario_id' => $doctor['usuario_id'],
                'especialidad_id' => $doctor['especialidad_id'],
                'cmp' => $doctor['cmp'],
                'biografia' => $doctor['biografia'],
                'user_nombre' => $doctor['user_nombre'] ?? '',
                'user_apellido' => $doctor['user_apellido'] ?? '',
                'especialidad_nombre' => $doctor['especialidad_nombre'] ?? '',
                'especialidad_descripcion' => $doctor['especialidad_descripcion'] ?? ''
            ];
        }, $doctors);
    }

    public function user()
    {
        // The users table is called `usuarios` and the FK in `doctores` is `usuario_id`.
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }
}
