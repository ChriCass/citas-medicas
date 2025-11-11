<?php

namespace App\Services;

use App\Models\{Role, Doctor, Paciente, Cajero, Superadmin};
use App\Services\Validators\UserValidator;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Servicio para gestión de roles de usuarios
 * Maneja la creación, actualización y eliminación de datos específicos por rol
 */
class RoleService
{
    private UserValidator $validator;

    public function __construct()
    {
        $this->validator = new UserValidator();
    }

    /**
     * Crea los datos específicos del rol para un usuario
     * @param int $userId ID del usuario
     * @param string $rol Nombre del rol
     * @param array $data Datos del rol
     * @throws \Exception Si hay error en validación o creación
     */
    public function createRoleData(int $userId, string $rol, array $data): void
    {
        switch ($rol) {
            case 'doctor':
                $this->createDoctorData($userId, $data);
                break;
            case 'paciente':
                $this->createPacienteData($userId, $data);
                break;
            case 'cajero':
                $this->createCajeroData($userId, $data);
                break;
            case 'superadmin':
                $this->createSuperadminData($userId, $data);
                break;
        }
    }

    /**
     * Actualiza los datos específicos del rol
     * @param int $userId ID del usuario
     * @param string $rol Nombre del rol
     * @param array $data Datos a actualizar
     * @throws \Exception Si hay error en validación o actualización
     */
    public function updateRoleData(int $userId, string $rol, array $data): void
    {
        switch ($rol) {
            case 'doctor':
                $this->updateDoctorData($userId, $data);
                break;
            case 'paciente':
                $this->updatePacienteData($userId, $data);
                break;
            case 'cajero':
                $this->updateCajeroData($userId, $data);
                break;
            case 'superadmin':
                $this->updateSuperadminData($userId, $data);
                break;
        }
    }

    /**
     * Maneja el cambio de rol de un usuario
     * @param int $userId ID del usuario
     * @param string $newRol Nuevo rol
     * @param string $currentRol Rol actual
     * @param array $data Datos del nuevo rol
     * @throws \Exception Si hay error en el proceso (código 404 o 400)
     */
    public function handleRoleChange(int $userId, string $newRol, string $currentRol, array $data): void
    {
        if ($currentRol === $newRol) {
            // Mismo rol, solo actualizar datos
            $this->updateRoleData($userId, $newRol, $data);
            return;
        }
        
        // Rol cambió: eliminar datos del rol anterior
        $this->deleteRoleData($userId, $currentRol);
        
        // Actualizar la relación de roles
        DB::table('tiene_roles')->where('usuario_id', $userId)->delete();
        
        $role = Role::where('nombre', $newRol)->first();
        if (!$role) {
            throw new \Exception('Rol no encontrado', 404);
        }
        
        DB::table('tiene_roles')->insert([
            'usuario_id' => $userId,
            'rol_id' => $role->id
        ]);
        
        // Crear datos del nuevo rol
        $this->createRoleData($userId, $newRol, $data);
    }

    /**
     * Elimina los datos específicos del rol
     * @param int $userId ID del usuario
     * @param string $rol Nombre del rol
     */
    public function deleteRoleData(int $userId, string $rol): void
    {
        switch ($rol) {
            case 'doctor':
                Doctor::where('usuario_id', $userId)->delete();
                break;
            case 'paciente':
                Paciente::where('usuario_id', $userId)->delete();
                break;
            case 'cajero':
                Cajero::where('usuario_id', $userId)->delete();
                break;
            case 'superadmin':
                Superadmin::where('usuario_id', $userId)->delete();
                break;
        }
    }

    // ============================================================================
    // MÉTODOS PRIVADOS PARA CREACIÓN DE ROLES
    // ============================================================================

    /**
     * Crea el registro de doctor
     */
    private function createDoctorData(int $userId, array $data): void
    {
        if (empty($data['especialidad_id'])) {
            throw new \Exception('La especialidad es requerida para doctores', 400);
        }
        
        $cmpFormatted = $this->validator->validateAndFormatCMP($data['cmp'] ?? '');
        
        Doctor::create([
            'usuario_id' => $userId,
            'especialidad_id' => $data['especialidad_id'],
            'cmp' => $cmpFormatted,
            'biografia' => $data['biografia'] ?? null
        ]);
    }

    /**
     * Crea el registro de paciente
     */
    private function createPacienteData(int $userId, array $data): void
    {
        // Validar número de historia clínica único
        $this->validator->validateHistoriaClinica($data['numero_historia_clinica'] ?? null);
        
        // Validar teléfono de emergencia
        $telefonoEmergencia = $this->validator->validateEmergencyPhone($data['contacto_emergencia_telefono'] ?? null);
        
        Paciente::create([
            'usuario_id' => $userId,
            'numero_historia_clinica' => $data['numero_historia_clinica'] ?? null,
            'tipo_sangre' => $data['tipo_sangre'] ?? null,
            'alergias' => $data['alergias'] ?? null,
            'condicion_cronica' => $data['condicion_cronica'] ?? null,
            'historial_cirugias' => $data['historial_cirugias'] ?? null,
            'historico_familiar' => $data['historico_familiar'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'contacto_emergencia_nombre' => $data['contacto_emergencia_nombre'] ?? null,
            'contacto_emergencia_telefono' => $telefonoEmergencia,
            'contacto_emergencia_relacion' => $data['contacto_emergencia_relacion'] ?? null
        ]);
    }

    /**
     * Crea el registro de cajero
     */
    private function createCajeroData(int $userId, array $data): void
    {
        if (empty($data['cajero_nombre']) || empty($data['cajero_usuario']) || empty($data['cajero_contrasenia'])) {
            throw new \Exception('Todos los campos de cajero son requeridos', 400);
        }
        
        Cajero::create([
            'usuario_id' => $userId,
            'nombre' => $data['cajero_nombre'],
            'usuario' => $data['cajero_usuario'],
            'contrasenia' => password_hash($data['cajero_contrasenia'], PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Crea el registro de superadmin
     */
    private function createSuperadminData(int $userId, array $data): void
    {
        if (empty($data['superadmin_nombre']) || empty($data['superadmin_usuario']) || empty($data['superadmin_contrasenia'])) {
            throw new \Exception('Todos los campos de superadmin son requeridos', 400);
        }
        
        Superadmin::create([
            'usuario_id' => $userId,
            'nombre' => $data['superadmin_nombre'],
            'usuario' => $data['superadmin_usuario'],
            'contrasenia' => password_hash($data['superadmin_contrasenia'], PASSWORD_DEFAULT)
        ]);
    }

    // ============================================================================
    // MÉTODOS PRIVADOS PARA ACTUALIZACIÓN DE ROLES
    // ============================================================================

    /**
     * Actualiza el registro de doctor
     */
    private function updateDoctorData(int $userId, array $data): void
    {
        if (empty($data['especialidad_id'])) {
            throw new \Exception('La especialidad es requerida para doctores', 400);
        }
        
        $cmpFormatted = $this->validator->validateAndFormatCMP($data['cmp'] ?? '', $userId);
        
        Doctor::where('usuario_id', $userId)->update([
            'especialidad_id' => $data['especialidad_id'],
            'cmp' => $cmpFormatted,
            'biografia' => $data['biografia'] ?? null
        ]);
    }

    /**
     * Actualiza el registro de paciente
     */
    private function updatePacienteData(int $userId, array $data): void
    {
        // Validar número de historia clínica único (excluyendo el actual)
        $this->validator->validateHistoriaClinica($data['numero_historia_clinica'] ?? null, $userId);
        
        // Validar teléfono de emergencia
        $telefonoEmergencia = $this->validator->validateEmergencyPhone($data['contacto_emergencia_telefono'] ?? null);
        
        Paciente::where('usuario_id', $userId)->update([
            'numero_historia_clinica' => $data['numero_historia_clinica'] ?? null,
            'tipo_sangre' => $data['tipo_sangre'] ?? null,
            'alergias' => $data['alergias'] ?? null,
            'condicion_cronica' => $data['condicion_cronica'] ?? null,
            'historial_cirugias' => $data['historial_cirugias'] ?? null,
            'historico_familiar' => $data['historico_familiar'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'contacto_emergencia_nombre' => $data['contacto_emergencia_nombre'] ?? null,
            'contacto_emergencia_telefono' => $telefonoEmergencia,
            'contacto_emergencia_relacion' => $data['contacto_emergencia_relacion'] ?? null
        ]);
    }

    /**
     * Actualiza el registro de cajero
     */
    private function updateCajeroData(int $userId, array $data): void
    {
        $cajeroUpdateData = [
            'nombre' => $data['cajero_nombre'] ?? null,
            'usuario' => $data['cajero_usuario'] ?? null
        ];
        
        // Solo actualizar contraseña si se proporcionó una nueva
        if (!empty($data['cajero_contrasenia'])) {
            $cajeroUpdateData['contrasenia'] = password_hash($data['cajero_contrasenia'], PASSWORD_DEFAULT);
        }
        
        Cajero::where('usuario_id', $userId)->update($cajeroUpdateData);
    }

    /**
     * Actualiza el registro de superadmin
     */
    private function updateSuperadminData(int $userId, array $data): void
    {
        $superadminUpdateData = [
            'nombre' => $data['superadmin_nombre'] ?? null,
            'usuario' => $data['superadmin_usuario'] ?? null
        ];
        
        // Solo actualizar contraseña si se proporcionó una nueva
        if (!empty($data['superadmin_contrasenia'])) {
            $superadminUpdateData['contrasenia'] = password_hash($data['superadmin_contrasenia'], PASSWORD_DEFAULT);
        }
        
        Superadmin::where('usuario_id', $userId)->update($superadminUpdateData);
    }
}
