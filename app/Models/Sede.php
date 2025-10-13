<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sede extends BaseModel
{
    protected $table = 'sedes';
    
    protected $fillable = ['nombre_sede', 'direccion', 'telefono'];
    
    // Relaciones
    public function doctores(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_sede', 'sede_id', 'doctor_id')
                    ->withPivot('fecha_inicio', 'fecha_fin');
    }
    
    public function citas(): HasMany
    {
        return $this->hasMany(Appointment::class, 'sede_id');
    }
    
    public function horarios(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class, 'sede_id');
    }
    
    // Métodos estáticos para compatibilidad
    public static function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('nombre_sede')->get();
    }
    
    public static function create(string $nombreSede, ?string $direccion = null, ?string $telefono = null): int
    {
        $sede = new static();
        $sede->nombre_sede = $nombreSede;
        $sede->direccion = $direccion;
        $sede->telefono = $telefono;
        $sede->save();
        
        return $sede->id;
    }
    
    public static function updateRecord(int $id, array $data): bool
    {
        $sede = parent::find($id);
        if (!$sede) {
            return false;
        }
        
        return $sede->update($data);
    }
    
    public static function deleteRecord(int $id): bool
    {
        $sede = parent::find($id);
        if (!$sede) {
            return false;
        }
        
        return $sede->delete();
    }
    
    public function getDoctores(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->doctores()->with(['user', 'especialidad'])->get();
    }
}
