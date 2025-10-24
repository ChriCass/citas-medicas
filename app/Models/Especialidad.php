<?php
namespace App\Models;

class Especialidad extends SimpleModel
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'especialidades';
        $this->fillable = [
            'nombre', 'descripcion'
        ];
    }

    // Propiedades públicas para evitar warnings de PHP 8.4
    public $id;
    public $nombre;
    public $descripcion;
    public $creado_en;
    public $actualizado_en;

    // Métodos estáticos para compatibilidad
    public static function getAll(): array
    {
        $instance = new static();
        $sql = "SELECT * FROM especialidades ORDER BY nombre";
        return $instance->db->fetchAll($sql);
    }

    public static function createEspecialidad(string $nombre, ?string $descripcion = null): int
    {
        $especialidad = new static();
        $data = [
            'nombre' => $nombre,
            'descripcion' => $descripcion
        ];
        
        return $especialidad->create($data);
    }

    public static function updateEspecialidad(int $id, array $data): bool
    {
        $especialidad = new static();
        $especialidadData = $especialidad->find($id);
        
        if (!$especialidadData) {
            return false;
        }
        
        return $especialidad->update($id, $data);
    }
}