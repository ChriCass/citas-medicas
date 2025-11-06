<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table = 'citas';
    protected $fillable = [
            'paciente_id', 'doctor_id', 'sede_id', 'fecha', 
            'hora_inicio', 'hora_fin', 'razon', 'estado', 'pago',
            'diagnostico_id', 'observaciones_medicas', 'receta'
        ];
    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i:s',
        'hora_fin' => 'datetime:H:i:s'
    ];
    
    public $timestamps = false;
    
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
    
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    
    public function pagos()
    {
        return $this->hasMany(Payment::class, 'cita_id');
    }
    
    
    public static function updateStatus($id, $status): bool
    {
        $allowed = ['pendiente','confirmado','atendido','cancelado'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $appointment = static::find($id);
        if (!$appointment) {
            return false;
        }

        $appointment->estado = $status;
        return $appointment->save();
    }
    
    public static function updatePayment($id, $paymentStatus)
    {
        $allowed = ['pendiente','pagado','rechazado'];
        if (!in_array($paymentStatus, $allowed, true)) {
            return false;
        }
        
        $db = \App\Core\SimpleDatabase::getInstance();
        
        // Verificar que la cita existe y está en estado 'atendido'
        $cita = $db->fetchOne("SELECT estado FROM citas WHERE id = ?", [$id]);
        if (!$cita || $cita['estado'] !== 'atendido') {
            return false;
        }
        
        return $db->update('citas', ['pago' => $paymentStatus], 'id = ?', [$id]);
    }
    
    public static function overlapsWindow($date, $startTime, $endTime, $doctorId, $sedeId = null)
    {
        $db = \App\Core\SimpleDatabase::getInstance();

        $sql = "SELECT COUNT(*) as count FROM citas
                WHERE fecha = ?
                AND estado != 'cancelado'
                AND (hora_inicio < ? AND hora_fin > ?)
                AND (
                    doctor_id = ?
                    OR sede_id = ?
                )";

        $params = [$date, $endTime, $startTime, $doctorId, $sedeId, $sedeId];

        $result = $db->fetchOne($sql, $params);
        return $result['count'] > 0;
    }
    
    public static function upcomingForUser($userId, $limit = 5)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        // Detectar el tipo de base de datos y usar la sintaxis correcta
        $dbType = $db->getConnectionType();
        
        if ($dbType === 'sqlsrv') {
            // SQL Server usa TOP - el valor debe ser literal, no parámetro
            $sql = "SELECT TOP " . (int)$limit . " c.*, 
                           pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                           du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                           e.nombre as especialidad_nombre,
                           s.nombre_sede
                    FROM citas c
                    LEFT JOIN pacientes p ON c.paciente_id = p.id
                    LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                    LEFT JOIN doctores d ON c.doctor_id = d.id
                    LEFT JOIN usuarios du ON d.usuario_id = du.id
                    LEFT JOIN especialidades e ON d.especialidad_id = e.id
                    LEFT JOIN sedes s ON c.sede_id = s.id
                    WHERE pu.id = ? 
                    AND c.fecha >= ? 
                    AND c.estado != 'cancelado'
                    ORDER BY c.fecha ASC, c.hora_inicio ASC";
            
            return $db->fetchAll($sql, [$userId, date('Y-m-d')]);
        } else {
            // SQLite/MySQL usan LIMIT
            $sql = "SELECT c.*, 
                           pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                           du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                           e.nombre as especialidad_nombre,
                           s.nombre_sede
                    FROM citas c
                    LEFT JOIN pacientes p ON c.paciente_id = p.id
                    LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                    LEFT JOIN doctores d ON c.doctor_id = d.id
                    LEFT JOIN usuarios du ON d.usuario_id = du.id
                    LEFT JOIN especialidades e ON d.especialidad_id = e.id
                    LEFT JOIN sedes s ON c.sede_id = s.id
                    WHERE pu.id = ? 
                    AND c.fecha >= ? 
                    AND c.estado != 'cancelado'
                    ORDER BY c.fecha ASC, c.hora_inicio ASC
                    LIMIT ?";
            
            return $db->fetchAll($sql, [$userId, date('Y-m-d'), $limit]);
        }
    }
    
    public static function usercitas($userId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE pu.id = ?
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        
        $appointments = $db->fetchAll($sql, [$userId]);
        
        // Convertir a formato plano para la vista
        return array_map(function($appointment) {
            return [
                'id' => $appointment['id'],
                'fecha' => $appointment['fecha'],
                'hora_inicio' => $appointment['hora_inicio'],
                'hora_fin' => $appointment['hora_fin'],
                'razon' => $appointment['razon'],
                'estado' => $appointment['estado'],
                'pago' => $appointment['pago'],
                'paciente_nombre' => $appointment['paciente_nombre'] ?? '',
                'paciente_apellido' => $appointment['paciente_apellido'] ?? '',
                'doctor_nombre' => $appointment['doctor_nombre'] ?? '',
                'doctor_apellido' => $appointment['doctor_apellido'] ?? '',
                'especialidad_nombre' => $appointment['especialidad_nombre'] ?? 'N/A',
                'sede_nombre' => $appointment['nombre_sede'] ?? 'N/A'
            ];
        }, $appointments);
    }
    
    public static function doctorcitas($doctorId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE c.doctor_id = ?
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        
        $appointments = $db->fetchAll($sql, [$doctorId]);
        
        // Convertir a formato plano para la vista
        return array_map(function($appointment) {
            return [
                'id' => $appointment['id'],
                'fecha' => $appointment['fecha'],
                'hora_inicio' => $appointment['hora_inicio'],
                'hora_fin' => $appointment['hora_fin'],
                'razon' => $appointment['razon'],
                'estado' => $appointment['estado'],
                'pago' => $appointment['pago'],
                'paciente_nombre' => $appointment['paciente_nombre'] ?? '',
                'paciente_apellido' => $appointment['paciente_apellido'] ?? '',
                'doctor_nombre' => $appointment['doctor_nombre'] ?? '',
                'doctor_apellido' => $appointment['doctor_apellido'] ?? '',
                'especialidad_nombre' => $appointment['especialidad_nombre'] ?? 'N/A',
                'sede_nombre' => $appointment['nombre_sede'] ?? 'N/A'
            ];
        }, $appointments);
    }
    
    public static function doctorCitasToday($doctorId, $date)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE c.doctor_id = ? AND c.fecha = ?
                ORDER BY c.hora_inicio ASC";
        
        $appointments = $db->fetchAll($sql, [$doctorId, $date]);
        
        // Convertir a formato plano para la vista
        return array_map(function($appointment) {
            return [
                'id' => $appointment['id'],
                'fecha' => $appointment['fecha'],
                'hora_inicio' => $appointment['hora_inicio'],
                'hora_fin' => $appointment['hora_fin'],
                'razon' => $appointment['razon'],
                'estado' => $appointment['estado'],
                'pago' => $appointment['pago'],
                'paciente_nombre' => $appointment['paciente_nombre'] ?? '',
                'paciente_apellido' => $appointment['paciente_apellido'] ?? '',
                'doctor_nombre' => $appointment['doctor_nombre'] ?? '',
                'doctor_apellido' => $appointment['doctor_apellido'] ?? '',
                'especialidad_nombre' => $appointment['especialidad_nombre'] ?? 'N/A',
                'sede_nombre' => $appointment['nombre_sede'] ?? 'N/A'
            ];
        }, $appointments);
    }
    
    public static function listAll()
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        
        $appointments = $db->fetchAll($sql);
        
        // Convertir a formato plano para la vista
        return array_map(function($appointment) {
            return [
                'id' => $appointment['id'],
                'fecha' => $appointment['fecha'],
                'hora_inicio' => $appointment['hora_inicio'],
                'hora_fin' => $appointment['hora_fin'],
                'razon' => $appointment['razon'],
                'estado' => $appointment['estado'],
                'pago' => $appointment['pago'],
                'paciente_nombre' => $appointment['paciente_nombre'] ?? '',
                'paciente_apellido' => $appointment['paciente_apellido'] ?? '',
                'doctor_nombre' => $appointment['doctor_nombre'] ?? '',
                'doctor_apellido' => $appointment['doctor_apellido'] ?? '',
                'especialidad_nombre' => $appointment['especialidad_nombre'] ?? 'N/A',
                'sede_nombre' => $appointment['nombre_sede'] ?? 'N/A'
            ];
        }, $appointments);
    }
    
    public static function createAppointment($pacienteId, $doctorId, $sedeId, $fecha, $horaInicio, $horaFin, $razon)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $data = [
            'paciente_id' => $pacienteId,
            'doctor_id' => $doctorId,
            'sede_id' => $sedeId,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'razon' => $razon,
            'estado' => 'pendiente',
            'pago' => 'pendiente'
        ];
        
        $citaId = $db->insert('citas', $data);
        
        return $citaId;
    }
    
    public static function belongsToDoctor($citaId, $doctorId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM citas WHERE id = ? AND doctor_id = ?", [$citaId, $doctorId]);
        return $result['count'] > 0;
    }
    
    public static function cancelByPatient($citaId, $userId)
    {
        $cita = static::with('paciente.usuario')
                     ->where('id', $citaId)
                     ->whereHas('paciente.usuario', function($query) use ($userId) {
                         $query->where('id', $userId);
                     })
                     ->first();
        
        if (!$cita) {
            return false;
        }
        
        // Verificar si se puede cancelar (24 horas antes)
        $fechaCita = $cita->fecha . ' ' . $cita->hora_inicio;
        $fechaLimite = date('Y-m-d H:i:s', strtotime($fechaCita . ' -24 hours'));
        
        if (date('Y-m-d H:i:s') > $fechaLimite) {
            return false; // No se puede cancelar
        }
        
        return $cita->update(['estado' => 'cancelado']);
    }
    
    public static function canModify($citaId, $userId)
    {
        $cita = static::with('paciente.usuario')
                     ->where('id', $citaId)
                     ->whereHas('paciente.usuario', function($query) use ($userId) {
                         $query->where('id', $userId);
                     })
                     ->first();
        
        if (!$cita) {
            return false;
        }
        
        // Solo se puede modificar si está pendiente o confirmado
        return in_array($cita->estado, ['pendiente', 'confirmado']);
    }
    
    public static function modifyAppointment($citaId, $pacienteId, $doctorId, $sedeId, $fecha, $horaInicio, $horaFin, $razon)
    {
        $horaFinCalculada = date('H:i:s', strtotime($horaInicio . ' +15 minutes'));
        
        return static::where('id', $citaId)->update([
            'paciente_id' => $pacienteId,
            'doctor_id' => $doctorId,
            'sede_id' => $sedeId,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFinCalculada,
            'razon' => $razon
        ]);
    }

    public static function create(
        int $pacienteId,
        int $doctorId,
        ?int $sedeId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?string $razon = ''
    , ?int $calendarioId = null, ?string $slotHora = null
    ): int {
        // Crear cita y, si se proporciona calendario_id + slotHora, marcar el slot como reservado
        DB::beginTransaction();
        try {
            $appointment = new static();
            $appointment->paciente_id = $pacienteId;
            $appointment->doctor_id = $doctorId;
            $appointment->sede_id = $sedeId;
            $appointment->fecha = $fecha;
            $appointment->hora_inicio = $horaInicio;
            $appointment->hora_fin = $horaFin;
            $appointment->razon = $razon;
            $appointment->estado = 'pendiente';
            // si se proporciona calendario_id, guardarlo en la cita
            if ($calendarioId) $appointment->calendario_id = $calendarioId;
            $appointment->save();

            $createdId = $appointment->id;

            // Si recibimos calendario_id y slotHora (HH:MM) intentamos actualizar el slot correspondiente
            if ($calendarioId && $slotHora) {
                // Buscar slot por calendario_id y hora_inicio (match por prefijo HH:MM)
                $affected = DB::table('slots_calendario')
                    ->where('calendario_id', $calendarioId)
                    ->where('hora_inicio', 'like', $slotHora . '%')
                    ->whereNull('reservado_por_cita_id')
                    ->update(['reservado_por_cita_id' => $createdId]);

                if ($affected <= 0) {
                    // No se pudo reservar el slot (otro proceso lo reservó)
                    DB::rollBack();
                    throw new \Exception('El slot ya fue reservado');
                }
            }

            DB::commit();
            return $createdId;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function belongsToPatient(int $id, int $userId): bool
    {
        return static::whereHas('paciente', function($query) use ($userId) {
            $query->where('usuario_id', $userId);
        })->where('id', $id)->exists();
    }

    public function getPacienteNombreAttribute(): ?string
    {
        return $this->paciente?->user?->nombre;
    }
    
    public function getPacienteApellidoAttribute(): ?string
    {
        return $this->paciente?->user?->apellido;
    }
    
    public function getDoctorNombreAttribute(): ?string
    {
        return $this->doctor?->user?->nombre;
    }
    
    public function getDoctorApellidoAttribute(): ?string
    {
        return $this->doctor?->user?->apellido;
    }
    
    public function getSedeNombreAttribute(): ?string
    {
        return $this->sede?->nombre_sede;
    }
    
    public function getEspecialidadNombreAttribute(): ?string
    {
        return $this->doctor?->especialidad?->nombre;
    }
}
