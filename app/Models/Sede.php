<?php
namespace App\Models;

use App\Core\SimpleDatabase;

class Sede extends BaseModel
{
    /** @var SimpleDatabase|null DB compatibility instance used by legacy static methods */
    protected $db;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // Keep SimpleDatabase available for existing static/helper methods that expect $instance->db
        $this->db = SimpleDatabase::getInstance();
        $this->table = 'sedes';
        $this->fillable = [
            'nombre_sede', 'direccion', 'telefono'
        ];
    }

    // NOTE: do not declare public properties that match DB columns here.
    // Eloquent stores attributes in the internal $attributes array and
    // declaring public properties with the same names would shadow them
    // and prevent attribute access (e.g. $sede->nombre_sede).

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