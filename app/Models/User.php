<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'usuarios';
    protected $fillable = [
        'nombre', 'apellido', 'email', 'contrasenia', 'dni', 'telefono', 'direccion'
    ];
    protected $hidden = ['contrasenia'];
    
    public $timestamps = false;
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'tiene_roles', 'usuario_id', 'rol_id');
    }
    
    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'usuario_id');
    }
    
    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'usuario_id');
    }
    
    public function cajero()
    {
        return $this->hasOne(Cajero::class, 'usuario_id');
    }
    
    public function getRoleName()
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        $role = $db->fetchOne(
            "SELECT r.nombre FROM roles r 
             INNER JOIN tiene_roles tr ON r.id = tr.rol_id 
             WHERE tr.usuario_id = ?", 
            [$this->id]
        );
        
        return $role ? $role['nombre'] : 'usuario';
    }
    
    public function hasRole($roleName)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        $role = $db->fetchOne(
            "SELECT r.id FROM roles r 
             INNER JOIN tiene_roles tr ON r.id = tr.rol_id 
             WHERE tr.usuario_id = ? AND r.nombre = ?", 
            [$this->id, $roleName]
        );
        
        return $role !== null;
    }
    
    public function verifyPassword($password)
    {
        return password_verify($password, $this->contrasenia);
    }
    
    public static function findByEmail($email)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        $userData = $db->fetchOne("SELECT * FROM usuarios WHERE email = ?", [$email]);
        
        if (!$userData) {
            return null;
        }
        
        // Crear una instancia de User con los datos obtenidos
        $user = new static();
        foreach ($userData as $key => $value) {
            $user->$key = $value;
        }
        
        return $user;
    }
    
    public static function patients($search = null, $limit = 100)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT u.*, p.id as paciente_id
                FROM usuarios u
                INNER JOIN pacientes p ON u.id = p.usuario_id
                INNER JOIN tiene_roles tr ON u.id = tr.usuario_id
                INNER JOIN roles r ON tr.rol_id = r.id
                WHERE r.nombre = 'paciente'";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.dni LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Detectar el tipo de base de datos para usar la sintaxis correcta
        $dbType = $db->getConnectionType();
        if ($dbType === 'sqlsrv') {
            $sql .= " ORDER BY u.nombre ASC";
            // SQL Server no tiene LIMIT, usamos TOP
            $sql = "SELECT TOP " . (int)$limit . " u.*, p.id as paciente_id
                    FROM usuarios u
                    INNER JOIN pacientes p ON u.id = p.usuario_id
                    INNER JOIN tiene_roles tr ON u.id = tr.usuario_id
                    INNER JOIN roles r ON tr.rol_id = r.id
                    WHERE r.nombre = 'paciente'";
            
            if ($search) {
                $sql .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.dni LIKE ?)";
            }
            $sql .= " ORDER BY u.nombre ASC";
        } else {
            $sql .= " ORDER BY u.nombre ASC LIMIT ?";
            $params[] = $limit;
        }
        
        $usuarios = $db->fetchAll($sql, $params);
        
        // Convertir a formato plano para la vista
        return array_map(function($usuario) {
            return [
                'id' => $usuario['usuario_id'] ?? $usuario['id'],
                'usuario_id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'email' => $usuario['email'],
                'dni' => $usuario['dni'],
                'telefono' => $usuario['telefono'],
                'direccion' => $usuario['direccion'],
                'paciente_id' => $usuario['paciente_id']
            ];
        }, $usuarios);
    }
}
