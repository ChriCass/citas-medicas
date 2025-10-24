<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    protected $table = 'horarios_medicos';
    protected $fillable = [
        'doctor_id', 'sede_id', 'fecha', 'hora_inicio', 
        'hora_fin', 'activo', 'observaciones'
    ];
    
    public $timestamps = false;
    
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    
    public static function listAll()
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT hm.*, 
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       s.nombre_sede
                FROM horarios_medicos hm
                LEFT JOIN doctores d ON hm.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN sedes s ON hm.sede_id = s.id
                ORDER BY hm.fecha DESC, hm.hora_inicio ASC";
        
        return $db->fetchAll($sql);
    }
    
    public static function overlaps($doctorId, $sedeId, $fecha, $startTime, $endTime)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT * FROM horarios_medicos 
                WHERE doctor_id = ? AND fecha = ? AND activo = 1";
        $params = [$doctorId, $fecha];
        
        if ($sedeId) {
            $sql .= " AND sede_id = ?";
            $params[] = $sedeId;
        }
        
        $existingSchedules = $db->fetchAll($sql, $params);
        
        foreach ($existingSchedules as $schedule) {
            $existingStart = strtotime($schedule['hora_inicio']);
            $existingEnd = strtotime($schedule['hora_fin']);
            $newStart = strtotime($startTime);
            $newEnd = strtotime($endTime);
            
            // Verificar si hay solapamiento
            if (($newStart < $existingEnd) && ($newEnd > $existingStart)) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function createSchedule($doctorId, $sedeId, $fecha, $startTime, $endTime, $observaciones = '')
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $data = [
            'doctor_id' => $doctorId,
            'sede_id' => $sedeId ?: null,
            'fecha' => $fecha,
            'hora_inicio' => $startTime,
            'hora_fin' => $endTime,
            'activo' => 1,
            'observaciones' => $observaciones
        ];
        
        return $db->insert('horarios_medicos', $data);
    }
    
    public static function forDate($doctorId, $sedeId, $fecha)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT * FROM horarios_medicos 
                WHERE doctor_id = ? AND fecha = ? AND activo = 1";
        $params = [$doctorId, $fecha];
        
        if ($sedeId) {
            $sql .= " AND sede_id = ?";
            $params[] = $sedeId;
        }
        
        $sql .= " ORDER BY hora_inicio ASC";
        
        return $db->fetchAll($sql, $params);
    }
    
    public static function deleteSchedule($id)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        return $db->delete('horarios_medicos', 'id = ?', [$id]);
    }
}
