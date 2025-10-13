<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends BaseModel
{
    protected $table = 'pacientes';
    
    protected $fillable = [
        'usuario_id', 'tipo_sangre', 'alergias', 'condicion_cronica', 
        'historial_cirugias', 'historico_familiar', 'observaciones',
        'contacto_emergencia_nombre', 'contacto_emergencia_telefono', 
        'contacto_emergencia_relacion'
    ];
    
    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    
    public function citas(): HasMany
    {
        return $this->hasMany(Appointment::class, 'paciente_id');
    }
    
    // Métodos estáticos para compatibilidad
    public static function findByUsuarioId(int $usuarioId): ?Paciente
    {
        return static::with('user')->where('usuario_id', $usuarioId)->first();
    }
    
    public static function create(int $usuarioId, ?string $tipoSangre = null, ?string $alergias = null, ?string $condicionCronica = null, ?string $historialCirugias = null, ?string $historicoFamiliar = null, ?string $observaciones = null, ?string $contactoEmergenciaNombre = null, ?string $contactoEmergenciaTelefono = null, ?string $contactoEmergenciaRelacion = null): int
    {
        $paciente = new static();
        $paciente->usuario_id = $usuarioId;
        $paciente->tipo_sangre = $tipoSangre;
        $paciente->alergias = $alergias;
        $paciente->condicion_cronica = $condicionCronica;
        $paciente->historial_cirugias = $historialCirugias;
        $paciente->historico_familiar = $historicoFamiliar;
        $paciente->observaciones = $observaciones;
        $paciente->contacto_emergencia_nombre = $contactoEmergenciaNombre;
        $paciente->contacto_emergencia_telefono = $contactoEmergenciaTelefono;
        $paciente->contacto_emergencia_relacion = $contactoEmergenciaRelacion;
        $paciente->save();
        
        return $paciente->id;
    }
    
    public static function updateByUsuarioId(int $usuarioId, array $data): bool
    {
        $paciente = static::where('usuario_id', $usuarioId)->first();
        if (!$paciente) {
            return false;
        }
        
        return $paciente->update($data);
    }
    
    public static function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with('user')->orderBy('id')->get();
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
}
