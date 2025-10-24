<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'pagos';
    protected $fillable = [
        'cita_id', 'cajero_id', 'monto', 'metodo_pago', 
        'estado', 'fecha_pago', 'comprobante', 'observaciones'
    ];
    
    public $timestamps = false;
    
    public function cita()
    {
        return $this->belongsTo(Appointment::class, 'cita_id');
    }
    
    public function cajero()
    {
        return $this->belongsTo(Cajero::class, 'cajero_id');
    }
    
    public static function createPayment($citaId, $cajeroId, $monto, $metodoPago, $observaciones = null)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $data = [
            'cita_id' => $citaId,
            'cajero_id' => $cajeroId,
            'monto' => $monto,
            'metodo_pago' => $metodoPago,
            'estado' => 'completado',
            'fecha_pago' => date('Y-m-d H:i:s'),
            'observaciones' => $observaciones
        ];
        
        $paymentId = $db->insert('pagos', $data);
        
        if ($paymentId) {
            // Actualizar estado de pago de la cita
            Appointment::updatePayment($citaId, 'pagado');
        }
        
        return $paymentId;
    }
    
    public static function getByCajero($cajeroId, $search = null)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT p.*, 
                       c.fecha, c.hora_inicio, c.hora_fin, c.razon,
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM pagos p
                LEFT JOIN citas c ON p.cita_id = c.id
                LEFT JOIN pacientes pa ON c.paciente_id = pa.id
                LEFT JOIN usuarios pu ON pa.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE p.cajero_id = ?";
        
        $params = [$cajeroId];
        
        if ($search) {
            $sql .= " AND (pu.nombre LIKE ? OR pu.apellido LIKE ? OR pu.dni LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY p.fecha_pago DESC";
        
        $pagos = $db->fetchAll($sql, $params);
        
        return $pagos;
    }
    
    public static function getByCita($citaId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT p.*, 
                       c.fecha, c.hora_inicio, c.hora_fin, c.razon,
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM pagos p
                LEFT JOIN citas c ON p.cita_id = c.id
                LEFT JOIN pacientes pa ON c.paciente_id = pa.id
                LEFT JOIN usuarios pu ON pa.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE p.cita_id = ?";
        
        return $db->fetchOne($sql, [$citaId]);
    }
    
    public static function updatePayment($citaId, $paymentStatus)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        return $db->update('pagos', ['estado' => $paymentStatus], 'cita_id = ?', [$citaId]);
    }
}
