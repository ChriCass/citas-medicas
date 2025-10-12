<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    // Desactivar timestamps automáticos por defecto 
    // (se puede reactivar en modelos específicos si es necesario)
    public $timestamps = false;

    // Configuración común para todos los modelos
    protected $guarded = ['id']; // Proteger ID de asignación masiva
    
    /**
     * Obtener la conexión de base de datos configurada
     */
    public function getConnectionName()
    {
        return null; // Usar conexión por defecto
    }
    
    /**
     * Scope para búsqueda por término general
     */
    public function scopeSearch($query, $term, $fields = [])
    {
        if (empty($term) || empty($fields)) {
            return $query;
        }
        
        return $query->where(function ($q) use ($term, $fields) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$term}%");
            }
        });
    }
    
    /**
     * Obtener registros con límite
     */
    public function scopeLimited($query, $limit = 100)
    {
        return $query->limit($limit);
    }
}