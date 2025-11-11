<?php

namespace App\Services;

use App\Models\{User, Role, Doctor, Paciente, Cajero, Superadmin, Appointment, DoctorSchedule, Payment};
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Servicio para gestión de usuarios
 * Maneja operaciones CRUD de usuarios
 */
class UserService
{
    /**
     * Crea un nuevo usuario
     * @param array $data Datos del usuario
     * @return User Usuario creado
     */
    public function createUser(array $data): User
    {
        return User::create([
            'nombre' => trim($data['nombre']),
            'apellido' => trim($data['apellido']),
            'email' => trim($data['email']),
            'contrasenia' => password_hash($data['password'], PASSWORD_DEFAULT),
            'dni' => trim($data['dni']),
            'telefono' => trim($data['telefono']),
            'direccion' => trim($data['direccion'])
        ]);
    }

    /**
     * Actualiza un usuario existente
     * @param int $id ID del usuario
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updateUser(int $id, array $data): bool
    {
        $updateData = [
            'nombre' => trim($data['nombre']),
            'apellido' => trim($data['apellido']),
            'email' => trim($data['email']),
            'dni' => trim($data['dni']),
            'telefono' => trim($data['telefono']),
            'direccion' => trim($data['direccion'])
        ];
        
        if (!empty($data['password'])) {
            $updateData['contrasenia'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return User::where('id', $id)->update($updateData) > 0;
    }

    /**
     * Elimina un usuario
     * @param int $id ID del usuario
     * @throws \Exception Si el usuario no existe o tiene relaciones
     */
    public function deleteUser(int $id): void
    {
        // Verificar que el usuario existe
        if (!User::find($id)) {
            throw new \Exception('Usuario no encontrado', 404);
        }
        
        // Obtener rol del usuario
        $roleData = DB::table('roles')
            ->join('tiene_roles', 'roles.id', '=', 'tiene_roles.rol_id')
            ->where('tiene_roles.usuario_id', $id)
            ->select('roles.nombre')
            ->first();
        
        DB::beginTransaction();
        try {
            if ($roleData) {
                $rol = $roleData->nombre;
                
                if ($rol === 'doctor') {
                    // Obtener el doctor_id
                    $doctor = Doctor::where('usuario_id', $id)->first();
                    
                    if ($doctor) {
                        // Verificar citas
                        $citasCount = Appointment::where('doctor_id', $doctor->id)->count();
                        if ($citasCount > 0) {
                            throw new \Exception(
                                "No se puede eliminar: El doctor tiene {$citasCount} cita(s) registrada(s). Elimine las citas primero.",
                                400
                            );
                        }
                        
                        // Verificar horarios médicos
                        $horariosCount = DoctorSchedule::where('doctor_id', $doctor->id)->count();
                        if ($horariosCount > 0) {
                            throw new \Exception(
                                "No se puede eliminar: El doctor tiene {$horariosCount} horario(s) programado(s). Elimine los horarios primero.",
                                400
                            );
                        }
                        
                        Doctor::where('usuario_id', $id)->delete();
                    }
                    
                } elseif ($rol === 'paciente') {
                    // Obtener el paciente_id
                    $paciente = Paciente::where('usuario_id', $id)->first();
                    
                    if ($paciente) {
                        // Verificar citas
                        $citasCount = Appointment::where('paciente_id', $paciente->id)->count();
                        if ($citasCount > 0) {
                            throw new \Exception(
                                "No se puede eliminar: El paciente tiene {$citasCount} cita(s) registrada(s). Elimine las citas primero.",
                                400
                            );
                        }
                        
                        Paciente::where('usuario_id', $id)->delete();
                    }
                    
                } elseif ($rol === 'cajero') {
                    // Obtener el cajero_id
                    $cajero = Cajero::where('usuario_id', $id)->first();
                    
                    if ($cajero) {
                        // Verificar pagos
                        $pagosCount = Payment::where('cajero_id', $cajero->id)->count();
                        if ($pagosCount > 0) {
                            throw new \Exception(
                                "No se puede eliminar: El cajero tiene {$pagosCount} pago(s) registrado(s). Elimine los pagos primero.",
                                400
                            );
                        }
                        
                        Cajero::where('usuario_id', $id)->delete();
                    }
                    
                } elseif ($rol === 'superadmin') {
                    // Los superadmins se pueden eliminar sin restricciones
                    Superadmin::where('usuario_id', $id)->delete();
                }
            }
            
            // Eliminar relación de roles
            DB::table('tiene_roles')->where('usuario_id', $id)->delete();
            
            // Eliminar usuario
            User::destroy($id);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Obtiene el usuario por ID
     * @param int $id ID del usuario
     * @return User|null Usuario o null si no existe
     */
    public function getUserById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Obtiene el rol actual de un usuario
     * @param int $userId ID del usuario
     * @return string|null Nombre del rol o null
     */
    public function getCurrentUserRole(int $userId): ?string
    {
        $currentRoleData = DB::table('roles')
            ->join('tiene_roles', 'roles.id', '=', 'tiene_roles.rol_id')
            ->where('tiene_roles.usuario_id', $userId)
            ->select('roles.nombre')
            ->first();
        
        return $currentRoleData ? $currentRoleData->nombre : null;
    }

    /**
     * Asigna un rol a un usuario
     * @param int $userId ID del usuario
     * @param string $roleName Nombre del rol
     * @throws \Exception Si el rol o usuario no existe (código 404)
     */
    public function assignRole(int $userId, string $roleName): void
    {
        $role = Role::where('nombre', $roleName)->first();
        if (!$role) {
            throw new \Exception('Rol no encontrado', 404);
        }
        
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('Usuario no encontrado', 404);
        }
        
        $user->roles()->attach($role->id);
    }
}
