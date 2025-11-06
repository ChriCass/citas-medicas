<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    protected $table = 'especialidades';
    protected $fillable = [
        'nombre', 'descripcion'
    ];

    public $timestamps = false;

    public $id;
    public $nombre;
    public $descripcion;
    public $creado_en;
    public $actualizado_en;

    public static function getAll(): array
    {
        return static::orderBy('nombre')->get()->toArray();
    }

    public static function createEspecialidad(string $nombre, ?string $descripcion = null): int
    {
        $created = static::create([
            'nombre' => $nombre,
            'descripcion' => $descripcion
        ]);

        return (int)($created->id ?? 0);
    }

    public static function updateEspecialidad(int $id, array $data): bool
    {
        $model = static::find($id);
        if (!$model) return false;
        return (bool)$model->update($data);
    }
}