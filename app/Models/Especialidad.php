<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidad extends BaseModel
{
    protected $table = 'especialidades';
    
    protected $fillable = ['nombre', 'descripcion'];
    
    // Relaciones
    public function doctores(): HasMany
    {
        return $this->hasMany(Doctor::class, 'especialidad_id');
    }
    
    // Métodos estáticos para compatibilidad
    public static function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('nombre')->get();
    }
    
    public static function create(string $nombre, ?string $descripcion = null): int
    {
        $especialidad = new static();
        $especialidad->nombre = $nombre;
        $especialidad->descripcion = $descripcion;
        $especialidad->save();
        
        return $especialidad->id;
    }
    
    public static function updateRecord(int $id, array $data): bool
    {
        $especialidad = parent::find($id);
        if (!$especialidad) {
            return false;
        }
        
        return $especialidad->update($data);
    }
    
    public static function deleteRecord(int $id): bool
    {
        $especialidad = parent::find($id);
        if (!$especialidad) {
            return false;
        }
        
        return $especialidad->delete();
    }
}
