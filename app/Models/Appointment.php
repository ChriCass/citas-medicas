<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Capsule\Manager as DB;

class Appointment extends BaseModel
{
    protected $table = 'citas';
    
    protected $fillable = [
        'paciente_id', 'doctor_id', 'sede_id', 'fecha', 
        'hora_inicio', 'hora_fin', 'razon', 'estado'
    ];
    
    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i:s',
        'hora_fin' => 'datetime:H:i:s'
    ];
    
    // Relaciones
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
    
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    
    // Métodos estáticos para compatibilidad
    public static function listAll(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with([
            'paciente.user', 
            'doctor.user', 
            'doctor.especialidad', 
            'sede'
        ])->orderBy('fecha', 'desc')->orderBy('hora_inicio', 'desc')->get();
    }
    
    public static function usercitas(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::with([
            'doctor.user', 
            'doctor.especialidad', 
            'sede'
        ])->whereHas('paciente', function($query) use ($userId) {
            $query->where('usuario_id', $userId);
        })->orderBy('fecha', 'desc')->orderBy('hora_inicio', 'desc')->get();
    }
    
    public static function doctorcitas(int $doctorId): \Illuminate\Database\Eloquent\Collection
    {
        return static::with([
            'paciente.user', 
            'doctor.especialidad', 
            'sede'
        ])->whereHas('doctor', function($query) use ($doctorId) {
            $query->where('usuario_id', $doctorId);
        })->orderBy('fecha', 'desc')->orderBy('hora_inicio', 'desc')->get();
    }
    
    public static function upcomingForUser(int $userId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $now = new \DateTime();
        
        return static::with(['doctor.especialidad'])
            ->whereHas('paciente', function($query) use ($userId) {
                $query->where('usuario_id', $userId);
            })
            ->where('estado', '!=', 'cancelado')
            ->where(function($query) use ($now) {
                $query->where('fecha', '>', $now->format('Y-m-d'))
                      ->orWhere(function($q) use ($now) {
                          $q->where('fecha', $now->format('Y-m-d'))
                            ->where('hora_inicio', '>', $now->format('H:i:s'));
                      });
            })
            ->orderBy('fecha', 'asc')
            ->orderBy('hora_inicio', 'asc')
            ->limit($limit)
            ->get();
    }
    
    public static function overlapsWindow(string $fecha, string $horaInicio, string $horaFin, int $doctorId, int $sedeId): bool
    {
        return static::where('estado', '!=', 'cancelado')
            ->where('fecha', $fecha)
            ->where(function($query) use ($horaInicio, $horaFin) {
                $query->where(function($q) use ($horaInicio, $horaFin) {
                    $q->where('hora_inicio', '<', $horaFin)
                      ->where('hora_fin', '>', $horaInicio);
                });
            })
            ->where(function($query) use ($doctorId, $sedeId) {
                $query->where('doctor_id', $doctorId)
                      ->orWhere('sede_id', $sedeId);
            })
            ->exists();
    }
    
    public static function create(
        int $pacienteId,
        int $doctorId,
        ?int $sedeId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?string $razon = ''
    ): int {
        $appointment = new static();
        $appointment->paciente_id = $pacienteId;
        $appointment->doctor_id = $doctorId;
        $appointment->sede_id = $sedeId;
        $appointment->fecha = $fecha;
        $appointment->hora_inicio = $horaInicio;
        $appointment->hora_fin = $horaFin;
        $appointment->razon = $razon;
        $appointment->estado = 'pendiente';
        $appointment->save();
        
        return $appointment->id;
    }
    
    public static function cancelByPatient(int $id, int $userId): bool
    {
        $appointment = static::with('paciente')
            ->whereHas('paciente', function($query) use ($userId) {
                $query->where('usuario_id', $userId);
            })
            ->find($id);
            
        if (!$appointment) {
            return false;
        }
        
        $citaDateTime = new \DateTimeImmutable($appointment->fecha . ' ' . $appointment->hora_inicio);
        $now = new \DateTimeImmutable('now');
        
        if ($citaDateTime->getTimestamp() - $now->getTimestamp() < 24*3600) {
            return false; // no se puede cancelar a menos de 24h
        }
        
        $appointment->estado = 'cancelado';
        return $appointment->save();
    }
    
    public static function updateStatus(int $id, string $estado): bool
    {
        $allowed = ['pendiente','confirmado','atendido','cancelado'];
        if (!in_array($estado,$allowed,true)) {
            return false;
        }
        
        $appointment = static::find($id);
        if (!$appointment) {
            return false;
        }
        
        $appointment->estado = $estado;
        return $appointment->save();
    }
    
    public static function updatePayment(int $id, string $paymentStatus): bool
    {
        $allowed = ['pendiente','pagado','rechazado'];
        if (!in_array($paymentStatus,$allowed,true)) {
            return false;
        }
        
        $appointment = static::where('id', $id)
                           ->where('estado', 'atendido')
                           ->first();
        
        if (!$appointment) {
            return false;
        }
        
        // Nota: El campo 'pago' no está en la estructura actual de la tabla
        // Si quieres usarlo, deberías agregarlo a la migración/schema
        return true;
    }
    
    public static function belongsToDoctor(int $id, int $doctorId): bool
    {
        return static::where('id', $id)->where('doctor_id', $doctorId)->exists();
    }
    
    public static function belongsToPatient(int $id, int $userId): bool
    {
        return static::whereHas('paciente', function($query) use ($userId) {
            $query->where('usuario_id', $userId);
        })->where('id', $id)->exists();
    }
    
    // Accessors para compatibilidad con código existente
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
