<?php

namespace App\Services\Validators;

use App\Models\{User, Doctor, Paciente};

/**
 * Validador para datos de usuarios
 * Centraliza todas las validaciones relacionadas con usuarios
 */
class UserValidator
{
    /**
     * Valida los datos básicos del usuario
     * @param array $data Datos del usuario
     * @param bool $isUpdate Si es actualización (password opcional)
     * @throws \Exception Si hay errores de validación (código 400)
     */
    public function validateUserData(array $data, bool $isUpdate = false): void
    {
        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $dni = trim($data['dni'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        
        // Validar campos obligatorios
        $requiredFields = [
            'nombre' => $nombre, 
            'apellido' => $apellido, 
            'email' => $email, 
            'dni' => $dni, 
            'telefono' => $telefono, 
            'direccion' => $direccion
        ];
        
        if (!$isUpdate) {
            $requiredFields['password'] = $password;
        }
        
        foreach ($requiredFields as $field => $value) {
            if (empty($value)) {
                throw new \Exception('Todos los campos de usuario son obligatorios', 400);
            }
        }
        
        // Validar formato de nombre
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $nombre)) {
            throw new \Exception('El nombre solo debe contener letras y espacios', 400);
        }
        
        // Validar formato de apellido
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $apellido)) {
            throw new \Exception('El apellido solo debe contener letras y espacios', 400);
        }
        
        // Validar DNI: exactamente 8 dígitos numéricos
        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            throw new \Exception('El DNI debe contener exactamente 8 dígitos numéricos', 400);
        }
        
        // Validar teléfono: exactamente 9 dígitos numéricos
        if (!preg_match('/^[0-9]{9}$/', $telefono)) {
            throw new \Exception('El teléfono debe contener exactamente 9 dígitos numéricos', 400);
        }
    }

    /**
     * Valida que email y DNI sean únicos
     * @param string $email Email a validar
     * @param string $dni DNI a validar
     * @param int|null $excludeUserId ID del usuario a excluir (para updates)
     * @throws \Exception Si hay duplicados (código 400)
     */
    public function validateUniqueFields(string $email, string $dni, ?int $excludeUserId = null): void
    {
        // Validar email único
        $emailQuery = User::where('email', $email);
        if ($excludeUserId !== null) {
            $emailQuery->where('id', '!=', $excludeUserId);
        }
        if ($emailQuery->exists()) {
            $message = $excludeUserId 
                ? 'El email ya está registrado por otro usuario' 
                : 'El email ya está registrado';
            throw new \Exception($message, 400);
        }
        
        // Validar DNI único
        $dniQuery = User::where('dni', $dni);
        if ($excludeUserId !== null) {
            $dniQuery->where('id', '!=', $excludeUserId);
        }
        if ($dniQuery->exists()) {
            $message = $excludeUserId 
                ? 'El DNI ya está registrado por otro usuario' 
                : 'El DNI ya está registrado';
            throw new \Exception($message, 400);
        }
    }

    /**
     * Valida y formatea el CMP del doctor
     * @param string $cmp CMP a validar
     * @param int|null $excludeUserId Usuario a excluir en validación de unicidad
     * @return string CMP formateado como CMP-#####
     * @throws \Exception Si el CMP es inválido (código 400)
     */
    public function validateAndFormatCMP(string $cmp, ?int $excludeUserId = null): string
    {
        if (empty($cmp)) {
            throw new \Exception('El CMP es requerido para doctores', 400);
        }
        
        // Eliminar el prefijo "CMP-" si viene con él
        $cmp = preg_replace('/^CMP-/i', '', trim($cmp));
        
        // Validar que tenga exactamente 5 dígitos numéricos
        if (!preg_match('/^[0-9]{5}$/', $cmp)) {
            throw new \Exception('El CMP debe contener exactamente 5 dígitos numéricos', 400);
        }
        
        // Formatear como CMP-#####
        $cmpFormatted = 'CMP-' . $cmp;
        
        // Verificar CMP único
        $query = Doctor::where('cmp', $cmpFormatted);
        if ($excludeUserId !== null) {
            $query->where('usuario_id', '!=', $excludeUserId);
        }
        
        if ($query->exists()) {
            $message = $excludeUserId 
                ? 'El CMP ya está registrado por otro doctor' 
                : 'El CMP ya está registrado';
            throw new \Exception($message, 400);
        }
        
        return $cmpFormatted;
    }

    /**
     * Valida el teléfono de emergencia
     * @param string|null $telefono Teléfono a validar
     * @return string|null Teléfono validado o null
     * @throws \Exception Si el formato es inválido (código 400)
     */
    public function validateEmergencyPhone(?string $telefono): ?string
    {
        if (empty($telefono)) {
            return null;
        }
        
        $telefono = trim($telefono);
        
        if (!preg_match('/^[0-9]{9}$/', $telefono)) {
            throw new \Exception('El teléfono de emergencia debe contener exactamente 9 dígitos numéricos', 400);
        }
        
        return $telefono;
    }

    /**
     * Valida número de historia clínica único
     * @param string|null $numeroHistoria Número a validar
     * @param int|null $excludeUserId Usuario a excluir
     * @throws \Exception Si ya existe (código 400)
     */
    public function validateHistoriaClinica(?string $numeroHistoria, ?int $excludeUserId = null): void
    {
        if (empty($numeroHistoria)) {
            return;
        }
        
        $query = Paciente::where('numero_historia_clinica', $numeroHistoria);
        if ($excludeUserId !== null) {
            $query->where('usuario_id', '!=', $excludeUserId);
        }
        
        if ($query->exists()) {
            $message = $excludeUserId 
                ? 'El número de historia clínica ya está registrado por otro paciente' 
                : 'El número de historia clínica ya está registrado';
            throw new \Exception($message, 400);
        }
    }
}