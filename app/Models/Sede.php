<?php
namespace App\Models;

class Sede extends SimpleModel
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'sedes';
        $this->fillable = [
            'nombre_sede', 'direccion', 'telefono'
        ];
    }

    // Propiedades públicas para evitar warnings de PHP 8.4
    public $id;
    public $nombre_sede;
    public $direccion;
    public $telefono;
    public $creado_en;
    public $actualizado_en;

    // Métodos estáticos para compatibilidad
    public static function getAll(): array
    {
        $instance = new static();
        $sql = "SELECT * FROM sedes ORDER BY nombre_sede";
        return $instance->db->fetchAll($sql);
    }

    public static function createSede(string $nombreSede, ?string $direccion = null, ?string $telefono = null): int
    {
        $sede = new static();
        $data = [
            'nombre_sede' => $nombreSede,
            'direccion' => $direccion,
            'telefono' => $telefono
        ];
        
        return $sede->create($data);
    }

    public static function updateSede(int $id, array $data): bool
    {
        $sede = new static();
        $sedeData = $sede->find($id);
        
        if (!$sedeData) {
            return false;
        }
        
        return $sede->update($id, $data);
    }
}