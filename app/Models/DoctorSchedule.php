<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends BaseModel
{
    protected $table = 'horarios_medicos';
    
    protected $fillable = [
        'doctor_id', 'sede_id', 'fecha', 'hora_inicio', 
        'hora_fin', 'activo', 'observaciones'
    ];
    
    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i:s',
        'hora_fin' => 'datetime:H:i:s',
        'activo' => 'boolean'
    ];
    
    // Relaciones
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
    
    // Métodos estáticos para compatibilidad
    public static function listAll(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['doctor.user', 'sede'])
                     ->active()
                     ->orderBy('fecha')
                     ->orderBy('hora_inicio')
                     ->get();
    }
    
    public static function forDate(int $doctorId, int $locationId, string $date): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('doctor_id', $doctorId)
                       ->where('fecha', $date)
                       ->active()
                       ->orderBy('hora_inicio');
        
        if ($locationId > 0) {
            $query->where('sede_id', $locationId);
        } else {
            $query->whereNull('sede_id');
        }
        
        return $query->get();
    }
    
    public static function forDateRange(int $doctorId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('doctor_id', $doctorId)
                     ->whereBetween('fecha', [$startDate, $endDate])
                     ->active()
                     ->orderBy('fecha')
                     ->orderBy('hora_inicio')
                     ->get();
    }
    
    public static function create(int $doctorId, int $locationId, string $date, string $start, string $end, string $observaciones = null): int
    {
        $schedule = new static();
        $schedule->doctor_id = $doctorId;
        $schedule->sede_id = $locationId ?: null;
        $schedule->fecha = $date;
        $schedule->hora_inicio = $start;
        $schedule->hora_fin = $end;
        $schedule->observaciones = $observaciones;
        $schedule->activo = true;
        $schedule->save();
        
        return $schedule->id;
    }
    
    public static function deleteSchedule(int $id): bool
    {
        $schedule = static::find($id);
        if (!$schedule) {
            return false;
        }
        
        $schedule->activo = false;
        return $schedule->save();
    }
    
    public static function hardDelete(int $id): bool
    {
        $schedule = static::find($id);
        if (!$schedule) {
            return false;
        }
        
        return $schedule->delete();
    }
    
    public static function overlaps(int $doctorId, int $locationId, string $date, string $start, string $end, int $excludeId = null): bool
    {
        $query = static::where('doctor_id', $doctorId)
                       ->where('fecha', $date)
                       ->active()
                       ->where(function($q) use ($start, $end) {
                           $q->where(function($sub) use ($start, $end) {
                               $sub->where('hora_inicio', '<', $end)
                                   ->where('hora_fin', '>', $start);
                           });
                       });
        
        if ($locationId > 0) {
            $query->where('sede_id', $locationId);
        } else {
            $query->whereNull('sede_id');
        }
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    public static function for(int $doctorId, int $locationId, int $weekday): array
    {
        // Método obsoleto mantenido para compatibilidad
        return [];
    }
    
    // Accessors para compatibilidad
    public function getDoctorNameAttribute(): ?string
    {
        return $this->doctor?->user?->nombre;
    }
    
    public function getDoctorLastnameAttribute(): ?string
    {
        return $this->doctor?->user?->apellido;
    }
    
    public function getDoctorEmailAttribute(): ?string
    {
        return $this->doctor?->user?->email;
    }
    
    public function getSedeNombreAttribute(): ?string
    {
        return $this->sede?->nombre_sede;
    }
    
    public function getDiaNombreAttribute(): ?string
    {
        $date = new \DateTime($this->fecha);
        $days = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 
                 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado'];
        return $days[$date->format('l')] ?? $date->format('l');
    }
}
