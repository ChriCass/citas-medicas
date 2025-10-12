<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Capsule\Manager as DB;

class User extends BaseModel
{
    protected $table = 'usuarios';
    
    protected $fillable = [
        'nombre', 'apellido', 'email', 'contrasenia', 
        'dni', 'telefono', 'direccion'
    ];
    
    protected $hidden = ['contrasenia'];
    
    // Relaciones
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'tiene_roles', 'usuario_id', 'rol_id')
                    ->withPivot('creado_en');
    }
    
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class, 'usuario_id');
    }
    
    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class, 'usuario_id');
    }
    
    public function cajero(): HasOne
    {
        return $this->hasOne(Cajero::class, 'usuario_id');
    }
    
    public function superadmin(): HasOne
    {
        return $this->hasOne(Superadmin::class, 'usuario_id');
    }
    
    // Métodos estáticos para compatibilidad
    public static function findByEmail(string $email): ?User
    {
        return static::with('roles')->where('email', $email)->first();
    }
    
    public static function createUser(string $nombre, string $apellido, string $email, string $passwordHash, string $rol = 'paciente', ?string $dni = null, ?string $telefono = null): int
    {
        DB::beginTransaction();
        
        try {
            // Crear usuario
            $user = new static();
            $user->nombre = $nombre;
            $user->apellido = $apellido;
            $user->email = $email;
            $user->contrasenia = $passwordHash;
            $user->dni = $dni;
            $user->telefono = $telefono;
            $user->save();
            
            // Obtener rol
            $roleModel = Role::where('nombre', $rol)->first();
            if (!$roleModel) {
                throw new \Exception("Rol no encontrado: $rol");
            }
            
            // Asignar rol
            $user->roles()->attach($roleModel->id);
            
            DB::commit();
            return $user->id;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public static function findById(int $id): ?User
    {
        return static::with('roles')->find($id);
    }
    
    public function hasRole(string $rol): bool
    {
        return $this->roles->contains('nombre', $rol);
    }
    
    public function getRoles(): array
    {
        return $this->roles->pluck('nombre')->toArray();
    }
    
    public function getRoleName(): ?string
    {
        return $this->roles->first()?->nombre;
    }
    
    // Scopes para selectores
    public function scopePatients($query, ?string $term = null, int $limit = 100)
    {
        return $this->scopeUsersByRole($query, 'paciente', $term, $limit);
    }
    
    public function scopeDoctors($query, ?string $term = null, int $limit = 100)
    {
        return $this->scopeUsersByRole($query, 'doctor', $term, $limit);
    }
    
    public function scopeUsersByRole($query, string $rol, ?string $term = null, int $limit = 100)
    {
        $query = $query->whereHas('roles', function($q) use ($rol) {
            $q->where('nombre', $rol);
        });
        
        if ($term !== null && $term !== '') {
            $query->where(function($q) use ($term) {
                $q->where('nombre', 'LIKE', "%{$term}%")
                  ->orWhere('apellido', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }
        
        return $query->orderBy('nombre')->limit($limit);
    }
    
    // Métodos estáticos de conveniencia
    public static function patients(?string $term = null, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()->patients($term, $limit)->get();
    }
    
    public static function doctors(?string $term = null, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()->doctors($term, $limit)->get();
    }
    
    // Getter para compatibilidad con código existente
    public function getRolNombreAttribute(): ?string
    {
        return $this->getRoleName();
    }
    
    // Mantener método create original para compatibilidad
    public static function create(string $nombre, string $apellido, string $email, string $passwordHash, string $rol = 'paciente', ?string $dni = null, ?string $telefono = null): int
    {
        return static::createUser($nombre, $apellido, $email, $passwordHash, $rol, $dni, $telefono);
    }
}
