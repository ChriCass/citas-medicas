<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Doctor extends BaseModel
{
    protected $table = 'doctores';
    
    protected $fillable = [
        'usuario_id', 'especialidad_id', 'cmp', 'biografia'
    ];
    
    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    
    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }
    
    public function citas(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }
    
    public function sedes(): BelongsToMany
    {
        return $this->belongsToMany(Sede::class, 'doctor_sede', 'doctor_id', 'sede_id')
                    ->withPivot('fecha_inicio', 'fecha_fin');
    }
    
    public function horarios(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }
    
    // Métodos estáticos para compatibilidad
    public static function find(int $id): ?Doctor
    {
        return static::with(['user', 'especialidad'])->find($id);
    }
    
    public static function findByUsuarioId(int $usuarioId): ?Doctor
    {
        return static::with(['user', 'especialidad'])->where('usuario_id', $usuarioId)->first();
    }
    
    public static function create(int $usuarioId, ?int $especialidadId = null, ?string $cmp = null, ?string $biografia = null): int
    {
        $doctor = new static();
        $doctor->usuario_id = $usuarioId;
        $doctor->especialidad_id = $especialidadId;
        $doctor->cmp = $cmp;
        $doctor->biografia = $biografia;
        $doctor->save();
        
        return $doctor->id;
    }
    
    public static function updateByUsuarioId(int $usuarioId, array $data): bool
    {
        $doctor = static::where('usuario_id', $usuarioId)->first();
        if (!$doctor) {
            return false;
        }
        
        return $doctor->update($data);
    }
    
    public static function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['user', 'especialidad'])->orderBy('id')->get();
    }
    
    public static function getByEspecialidad(int $especialidadId): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['user', 'especialidad'])
                     ->where('especialidad_id', $especialidadId)
                     ->orderBy('id')
                     ->get();
    }
    
    // Accessors para compatibilidad
    public function getNombreAttribute(): ?string
    {
        return $this->user?->nombre;
    }
    
    public function getApellidoAttribute(): ?string
    {
        return $this->user?->apellido;
    }
    
    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }
    
    public function getTelefonoAttribute(): ?string
    {
        return $this->user?->telefono;
    }
    
    public function getDniAttribute(): ?string
    {
        return $this->user?->dni;
    }
    
    public function getEspecialidadNombreAttribute(): ?string
    {
        return $this->especialidad?->nombre;
    }
}
