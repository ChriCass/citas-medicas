<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends BaseModel
{
    protected $table = 'horarios_medicos';
    
    protected $fillable = [
        'doctor_id', 'sede_id', 'fecha', 'hora_inicio', 
        'hora_fin', 'activo', 'observaciones', 'dia_semana', 'mes'
    ];

    
    protected $casts = [
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
        // La tabla `horarios_medicos` puede no tener columna `fecha` (patrones por día de semana),
        // ordenar por `dia_semana` y luego por hora de inicio para mostrar en UI.
        return static::with(['doctor.user', 'sede'])
                     ->active()
                     ->orderBy('dia_semana')
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

    /**
     * Crear un patrón semanal en la tabla `horarios_medicos` (dia_semana en texto, p.e. 'lunes').
     * Devuelve el id del registro creado.
     */
    public static function createPattern(int $doctorId, ?int $locationId, string $diaSemana, string $start, string $end, string $observaciones = null): int
    {
        $p = new static();
        $p->doctor_id = $doctorId;
        $p->sede_id = $locationId ?: null;
        $p->dia_semana = $diaSemana;
        $p->hora_inicio = $start;
        $p->hora_fin = $end;
        $p->observaciones = $observaciones;
        $p->activo = true;
        $p->save();
        return (int)$p->id;
    }

    /**
     * Comprueba si existe un patrón activo igual para evitar duplicados.
     */
    public static function patternExists(int $doctorId, ?int $locationId, string $diaSemana, string $start, string $end): bool
    {
        $query = static::where('doctor_id', $doctorId)
                       ->where('dia_semana', $diaSemana)
                       ->where('hora_inicio', $start)
                       ->where('hora_fin', $end)
                       ->active();

        if ($locationId && (int)$locationId > 0) {
            $query->where('sede_id', (int)$locationId);
        } else {
            $query->whereNull('sede_id');
        }

        return $query->exists();
    }

    /**
     * Buscar un patrón activo para el doctor/sede y día de la semana (devuelve id o null).
     */
    public static function findPatternId(int $doctorId, ?int $locationId, string $diaSemana): ?int
    {
        $query = static::where('doctor_id', $doctorId)
                       ->where('dia_semana', $diaSemana)
                       ->active();

        if ($locationId && (int)$locationId > 0) {
            $query->where('sede_id', (int)$locationId);
        } else {
            $query->whereNull('sede_id');
        }

        $first = $query->orderBy('hora_inicio')->first();
        return $first ? (int)$first->id : null;
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
