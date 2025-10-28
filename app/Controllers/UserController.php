<?php
namespace App\Controllers;

use App\Core\{Request, Response, Auth, Database};
use App\Models\User;

class UserController 
{
    public function index(Request $r, Response $res)
    {
        Auth::abortUnless($res, ['superadmin']);

        // Obtener todos los usuarios con sus roles
        $users = User::with('roles')->orderBy('nombre')->get();

        return $res->view('users/index', [
            'title' => 'Gestión de Usuarios',
            'users' => $users
        ]);
    }

    public function create(Request $r, Response $res)
    {
        Auth::abortUnless($res, ['superadmin']);
        
        return $res->view('users/create', [
            'title' => 'Gestión de Usuario'
        ]);
    }

    public function apiList(Request $r, Response $res)
    {
        Auth::abortUnless($res, ['superadmin']);

        try {
            $search = $_GET['q'] ?? '';
            $role = $_GET['role'] ?? '';

            $query = User::with('roles');

            // Filtro por rol
            if ($role) {
                $query->whereHas('roles', function($q) use ($role) {
                    $q->where('nombre', $role);
                });
            }

            // Filtro por búsqueda
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%{$search}%")
                      ->orWhere('apellido', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('dni', 'LIKE', "%{$search}%");
                });
            }

            $users = $query->orderBy('id')->get();

            $data = [];
            foreach($users as $user) {
                $roles = [];
                foreach($user->roles as $role) {
                    $roles[] = $role->nombre;
                }
                
                $data[] = [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'email' => $user->email,
                    'dni' => $user->dni,
                    'telefono' => $user->telefono,
                    'direccion' => $user->direccion,
                    'creado_en' => $user->creado_en ? date('d/m/Y H:i', strtotime($user->creado_en)) : '',
                    'roles' => implode(', ', $roles)
                ];
            }

            return $res->json(['users' => $data]);

        } catch (\Exception $e) {
            return $res->json(['error' => 'Error al obtener usuarios: ' . $e->getMessage()], 500);
        }
    }

    public function apiShow(Request $r, Response $res)
    {
        Auth::abortUnless($res, ['superadmin']);

        try {
            $id = $r->params['id'] ?? 0;
            $user = User::with('roles')->find($id);

            if (!$user) {
                return $res->json(['error' => 'Usuario no encontrado'], 404);
            }

            $roles = [];
            foreach($user->roles as $role) {
                $roles[] = $role->nombre;
            }

            $userData = [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'dni' => $user->dni,
                'telefono' => $user->telefono,
                'direccion' => $user->direccion,
                'creado_en' => $user->creado_en ? date('d/m/Y H:i', strtotime($user->creado_en)) : '',
                'roles' => implode(', ', $roles)
            ];

            // Agregar datos específicos según el rol
            $primaryRole = $roles[0] ?? '';
            $roleSpecificData = $this->getRoleSpecificData($user->id, $primaryRole);
            if ($roleSpecificData) {
                $userData['role_data'] = $roleSpecificData;
            }

            return $res->json(['user' => $userData]);

        } catch (\Exception $e) {
            return $res->json(['error' => 'Error al obtener usuario: ' . $e->getMessage()], 500);
        }
    }

    private function getRoleSpecificData($userId, $role) {
        try {
            switch ($role) {
                case 'doctor':
                    $doctor = \App\Models\Doctor::where('usuario_id', $userId)->first();
                    if ($doctor) {
                        $especialidad = \App\Models\Especialidad::find($doctor->especialidad_id);
                        return [
                            'especialidad_id' => $doctor->especialidad_id,
                            'especialidad_nombre' => $especialidad ? $especialidad->nombre : 'No especificada',
                            'cmp' => $doctor->cmp,
                            'biografia' => $doctor->biografia
                        ];
                    }
                    break;

                case 'paciente':
                    $paciente = \App\Models\Paciente::where('usuario_id', $userId)->first();
                    if ($paciente) {
                        return [
                            'tipo_sangre' => $paciente->tipo_sangre,
                            'alergias' => $paciente->alergias,
                            'condicion_cronica' => $paciente->condicion_cronica,
                            'historial_cirugias' => $paciente->historial_cirugias,
                            'historico_familiar' => $paciente->historico_familiar,
                            'observaciones' => $paciente->observaciones,
                            'contacto_emergencia_nombre' => $paciente->contacto_emergencia_nombre,
                            'contacto_emergencia_telefono' => $paciente->contacto_emergencia_telefono,
                            'contacto_emergencia_relacion' => $paciente->contacto_emergencia_relacion
                        ];
                    }
                    break;

                case 'cajero':
                    $cajero = \App\Models\Cajero::where('usuario_id', $userId)->first();
                    if ($cajero) {
                        return [
                            'nombre_cajero' => $cajero->usuario, // El nombre de usuario del sistema
                            // No incluimos la contraseña por seguridad
                        ];
                    }
                    break;

                case 'superadmin':
                    $superadmin = \App\Models\Superadmin::where('usuario_id', $userId)->first();
                    if ($superadmin) {
                        return [
                            'nombre_admin' => $superadmin->usuario, // El nombre de usuario del sistema
                            // No incluimos la contraseña por seguridad
                        ];
                    }
                    break;
            }

            return null;

        } catch (\Exception $e) {
            error_log("Error al obtener datos específicos del rol {$role}: " . $e->getMessage());
            return null;
        }
    }

    public function store(Request $r, Response $res)
    {
        Auth::abortUnless($res, ['superadmin']);

        try {
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $dni = trim($_POST['dni'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $role = trim($_POST['role'] ?? '');

            // Validaciones básicas - todos los campos obligatorios
            $requiredFields = ['nombre', 'apellido', 'email', 'dni', 'telefono', 'direccion', 'password'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($$field)) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return $res->json(['error' => 'Los siguientes campos son obligatorios: ' . implode(', ', $missingFields)], 400);
            }

            if (empty($role)) {
                return $res->json(['error' => 'Debe seleccionar un rol'], 400);
            }

            // Validaciones de formato
            $formatValidation = $this->validateFormats($dni, $telefono, $email);
            if ($formatValidation !== true) {
                return $res->json(['error' => $formatValidation], 400);
            }

            // Verificar duplicados
            $duplicateCheck = $this->checkDuplicates($email, $dni);
            if ($duplicateCheck !== true) {
                return $res->json(['error' => $duplicateCheck], 400);
            }

            // Validar campos específicos del rol
            $validationResult = $this->validateRoleSpecificFields($role);
            if ($validationResult !== true) {
                return $res->json(['error' => $validationResult], 400);
            }

            // Crear usuario
            $userId = User::createUser(
                $nombre,
                $apellido,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role, // Rol único
                $dni,
                $telefono
            );

            if (!$userId) {
                return $res->json(['error' => 'Error al crear el usuario'], 500);
            }

            // Actualizar dirección en el usuario
            $user = User::find($userId);
            if ($user) {
                $user->direccion = $direccion;
                $user->save();
            }

            // Crear registro específico según el rol
            $this->createRoleSpecificRecord($userId, $role, $nombre, $apellido);

            return $res->json(['message' => 'Usuario creado correctamente', 'id' => $userId]);

        } catch (\Exception $e) {
            error_log("Error en store: " . $e->getMessage());
            return $res->json(['error' => 'Error al crear usuario: ' . $e->getMessage()], 500);
        }
    }

    private function validateRoleSpecificFields($role, $isUpdate = false) {
        switch ($role) {
            case 'doctor':
                $especialidad_id = trim($_POST['especialidad_id'] ?? '');
                $cmp = trim($_POST['cmp'] ?? '');
                
                if (empty($especialidad_id)) {
                    return 'La especialidad es obligatoria para doctores';
                }
                if (empty($cmp)) {
                    return 'El CMP es obligatorio para doctores';
                }

                // Validar formato del CMP
                $cmpValidation = $this->validateCMPFormat($cmp);
                if ($cmpValidation !== true) {
                    return $cmpValidation;
                }

                // Verificar que la especialidad existe
                $especialidad = \App\Models\Especialidad::find($especialidad_id);
                if (!$especialidad) {
                    return 'La especialidad seleccionada no es válida';
                }

                // Verificar CMP único (excepto en actualización si es el mismo usuario)
                $query = \App\Models\Doctor::where('cmp', $cmp);
                if ($isUpdate && isset($_POST['userId'])) {
                    $query->where('usuario_id', '!=', $_POST['userId']);
                }
                $existingDoctor = $query->first();
                if ($existingDoctor) {
                    return 'El CMP ya está registrado por otro doctor';
                }
                break;

            case 'cajero':
                $nombre_cajero = trim($_POST['nombre_cajero'] ?? '');
                $contrasenia_cajero = trim($_POST['contrasenia_cajero'] ?? '');
                
                if (empty($nombre_cajero)) {
                    return 'El nombre de usuario es obligatorio para cajeros';
                }
                // En actualización, la contraseña es opcional
                if (!$isUpdate && empty($contrasenia_cajero)) {
                    return 'La contraseña del sistema es obligatoria para cajeros';
                }
                break;

            case 'superadmin':
                $nombre_admin = trim($_POST['nombre_admin'] ?? '');
                $contrasenia_admin = trim($_POST['contrasenia_admin'] ?? '');
                
                if (empty($nombre_admin)) {
                    return 'El nombre de usuario es obligatorio para super administradores';
                }
                // En actualización, la contraseña es opcional
                if (!$isUpdate && empty($contrasenia_admin)) {
                    return 'La contraseña del sistema es obligatoria para super administradores';
                }
                break;

            case 'paciente':
                break;

            default:
                return 'Rol no válido';
        }

        return true;
    }

    private function createRoleSpecificRecord($userId, $role, $nombre = '', $apellido = '') {
        switch ($role) {
            case 'doctor':
                $especialidad_id = $_POST['especialidad_id'] ?? '';
                $cmp = trim($_POST['cmp'] ?? '');
                $biografia = trim($_POST['biografia'] ?? '');

                $doctor = new \App\Models\Doctor();
                $doctor->usuario_id = $userId;
                $doctor->especialidad_id = $especialidad_id;
                $doctor->cmp = $cmp;
                $doctor->biografia = $biografia;
                $doctor->save();
                break;

            case 'paciente':
                $paciente = new \App\Models\Paciente();
                $paciente->usuario_id = $userId;
                $paciente->tipo_sangre = trim($_POST['tipo_sangre'] ?? '');
                $paciente->alergias = trim($_POST['alergias'] ?? '');
                $paciente->condicion_cronica = trim($_POST['condicion_cronica'] ?? '');
                $paciente->historial_cirugias = trim($_POST['historial_cirugias'] ?? '');
                $paciente->historico_familiar = trim($_POST['historico_familiar'] ?? '');
                $paciente->observaciones = trim($_POST['observaciones'] ?? '');
                $paciente->contacto_emergencia_nombre = trim($_POST['contacto_emergencia_nombre'] ?? '');
                $paciente->contacto_emergencia_telefono = trim($_POST['contacto_emergencia_telefono'] ?? '');
                $paciente->contacto_emergencia_relacion = trim($_POST['contacto_emergencia_relacion'] ?? '');
                $paciente->save();
                break;

            case 'cajero':
                $cajero = new \App\Models\Cajero();
                $cajero->usuario_id = $userId;
                $cajero->nombre = $nombre . ' ' . $apellido;
                $cajero->usuario = trim($_POST['nombre_cajero'] ?? '');
                $cajero->contrasenia = password_hash(trim($_POST['contrasenia_cajero'] ?? ''), PASSWORD_DEFAULT);
                $cajero->save();
                break;

            case 'superadmin':
                $superadmin = new \App\Models\Superadmin();
                $superadmin->usuario_id = $userId;
                $superadmin->nombre = $nombre . ' ' . $apellido;
                $superadmin->usuario = trim($_POST['nombre_admin'] ?? '');
                $superadmin->contrasenia = password_hash(trim($_POST['contrasenia_admin'] ?? ''), PASSWORD_DEFAULT);
                $superadmin->save();
                break;
        }
    }

    private function updateRoleSpecificRecord($userId, $role, $nombre = '', $apellido = '') {
        switch ($role) {
            case 'doctor':
                $doctor = \App\Models\Doctor::where('usuario_id', $userId)->first();
                if (!$doctor) {
                    $doctor = new \App\Models\Doctor();
                    $doctor->usuario_id = $userId;
                }

                $doctor->especialidad_id = $_POST['especialidad_id'] ?? '';
                $doctor->cmp = trim($_POST['cmp'] ?? '');
                $doctor->biografia = trim($_POST['biografia'] ?? '');
                $doctor->save();
                break;

            case 'paciente':
                $paciente = \App\Models\Paciente::where('usuario_id', $userId)->first();
                if (!$paciente) {
                    $paciente = new \App\Models\Paciente();
                    $paciente->usuario_id = $userId;
                }

                $paciente->tipo_sangre = trim($_POST['tipo_sangre'] ?? '');
                $paciente->alergias = trim($_POST['alergias'] ?? '');
                $paciente->condicion_cronica = trim($_POST['condicion_cronica'] ?? '');
                $paciente->historial_cirugias = trim($_POST['historial_cirugias'] ?? '');
                $paciente->historico_familiar = trim($_POST['historico_familiar'] ?? '');
                $paciente->observaciones = trim($_POST['observaciones'] ?? '');
                $paciente->contacto_emergencia_nombre = trim($_POST['contacto_emergencia_nombre'] ?? '');
                $paciente->contacto_emergencia_telefono = trim($_POST['contacto_emergencia_telefono'] ?? '');
                $paciente->contacto_emergencia_relacion = trim($_POST['contacto_emergencia_relacion'] ?? '');
                $paciente->save();
                break;

            case 'cajero':
                $cajero = \App\Models\Cajero::where('usuario_id', $userId)->first();
                if (!$cajero) {
                    $cajero = new \App\Models\Cajero();
                    $cajero->usuario_id = $userId;
                }

                $cajero->nombre = $nombre . ' ' . $apellido;
                $cajero->usuario = trim($_POST['nombre_cajero'] ?? '');
                
                $contrasenia_cajero = trim($_POST['contrasenia_cajero'] ?? '');
                if (!empty($contrasenia_cajero)) {
                    $cajero->contrasenia = password_hash($contrasenia_cajero, PASSWORD_DEFAULT);
                }
                $cajero->save();
                break;

            case 'superadmin':
                $superadmin = \App\Models\Superadmin::where('usuario_id', $userId)->first();
                if (!$superadmin) {
                    $superadmin = new \App\Models\Superadmin();
                    $superadmin->usuario_id = $userId;
                }

                $superadmin->nombre = $nombre . ' ' . $apellido;
                $superadmin->usuario = trim($_POST['nombre_admin'] ?? '');
                
                $contrasenia_admin = trim($_POST['contrasenia_admin'] ?? '');
                if (!empty($contrasenia_admin)) {
                    $superadmin->contrasenia = password_hash($contrasenia_admin, PASSWORD_DEFAULT);
                }
                $superadmin->save();
                break;
        }
    }

    public function updateOrDelete(Request $r, Response $res)
    {
        $method = $_POST['_method'] ?? 'PUT';
        
        if ($method === 'DELETE') {
            return $this->destroy($r, $res);
        } else {
            return $this->update($r, $res);
        }
    }

    public function update(Request $r, Response $res)
    {
        Auth::abortUnless($res, ['superadmin']);

        try {
            $id = $r->params['id'] ?? 0;
            $user = User::find($id);

            if (!$user) {
                return $res->json(['error' => 'Usuario no encontrado'], 404);
            }

            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $dni = trim($_POST['dni'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $role = trim($_POST['role'] ?? '');

            // Debug: Log de datos recibidos (remover en producción)
            error_log("Update user {$id} - Datos recibidos:");
            error_log("nombre: '{$nombre}'");
            error_log("email: '{$email}'");
            error_log("role: '{$role}'");

            $requiredFields = ['nombre', 'apellido', 'email', 'dni', 'telefono', 'direccion'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($$field)) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return $res->json(['error' => 'Los siguientes campos son obligatorios: ' . implode(', ', $missingFields)], 400);
            }

            if (empty($role)) {
                return $res->json(['error' => 'Debe seleccionar un rol'], 400);
            }

            $_POST['userId'] = $id;

            $formatValidation = $this->validateFormats($dni, $telefono, $email);
            if ($formatValidation !== true) {
                return $res->json(['error' => $formatValidation], 400);
            }

            $duplicateCheck = $this->checkDuplicates($email, $dni, $id);
            if ($duplicateCheck !== true) {
                return $res->json(['error' => $duplicateCheck], 400);
            }

            $validationResult = $this->validateRoleSpecificFields($role, true);
            if ($validationResult !== true) {
                return $res->json(['error' => $validationResult], 400);
            }

            // Actualizar datos básicos
            $user->nombre = $nombre;
            $user->apellido = $apellido;
            $user->email = $email;
            $user->dni = $dni;
            $user->telefono = $telefono;
            $user->direccion = $direccion;

            // Actualizar contraseña solo si se proporciona
            if (!empty($password)) {
                $user->contrasenia = password_hash($password, PASSWORD_DEFAULT);
            }

            $user->save();

            // Actualizar rol
            $user->roles()->detach();

            $roleModel = \App\Models\Role::where('nombre', $role)->first();
            if ($roleModel) {
                $user->roles()->attach($roleModel->id);
            }

            // Actualizar datos específicos del rol
            $this->updateRoleSpecificRecord($user->id, $role, $nombre, $apellido);

            return $res->json(['message' => 'Usuario actualizado correctamente']);

        } catch (\Exception $e) {
            error_log("Error en update: " . $e->getMessage());
            return $res->json(['error' => 'Error al actualizar usuario: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $r, Response $res)
    {
        Auth::abortUnless($res, ['superadmin']);

        try {
            $id = $r->params['id'] ?? 0;
            $user = User::find($id);

            if (!$user) {
                return $res->json(['error' => 'Usuario no encontrado'], 404);
            }

            // No permitir eliminar el usuario actual
            $currentUser = Auth::user();
            if ($currentUser && $currentUser['id'] == $id) {
                return $res->json(['error' => 'No puedes eliminar tu propio usuario'], 400);
            }

            // Verificar relaciones críticas antes de eliminar
            $relationshipCheck = $this->checkUserRelationships($user->id);
            if ($relationshipCheck !== true) {
                return $res->json([
                    'error' => 'No se puede eliminar el usuario',
                    'reason' => $relationshipCheck['reason'] ?? 'Usuario tiene relaciones críticas',
                    'details' => $relationshipCheck['details'] ?? [],
                    'suggestions' => $relationshipCheck['suggestions'] ?? []
                ], 400);
            }

            $this->performUserDeletion($user);

            return $res->json(['message' => 'Usuario eliminado exitosamente']);

        } catch (\Exception $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            return $res->json([
                'error' => 'No se pudo eliminar el usuario: revise que no tenga citas o turnos activos antes de volverlo a intentar.'
            ], 400);
        }
    }

    private function checkUserRelationships($userId) {
        try {
            $issues = [];
            $suggestions = [];

            // Obtener usuario con su rol
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('Usuario no encontrado');
            }
            
            error_log("DEBUG: Verificando usuario {$userId} - nombre: {$user->nombre}, rol_id: {$user->rol_id}");

            // Verificar si es un DOCTOR
            if ($user->rol_id == 2) {
                $doctor = \App\Models\Doctor::where('usuario_id', $userId)->first();
                error_log("DEBUG: Usuario {$userId} es doctor. Doctor encontrado: " . ($doctor ? $doctor->id : 'NO'));
                
                if ($doctor) {
                    // Verificar citas como doctor
                    $db = Database::pdo();
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM citas WHERE doctor_id = ?");
                    $stmt->execute([$doctor->id]);
                    $citasCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
                    
                    error_log("DEBUG: Doctor {$doctor->id} tiene {$citasCount} citas");

                    if ($citasCount > 0) {
                        $issues[] = "Tiene {$citasCount} cita(s) médica(s) registrada(s) como doctor";
                        $suggestions[] = "Cancele o transfiera las citas antes de eliminar";
                    }

                    // Verificar horarios médicos
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM horarios_medicos WHERE doctor_id = ?");
                    $stmt->execute([$doctor->id]);
                    $horariosCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
                    
                    error_log("DEBUG: Doctor {$doctor->id} tiene {$horariosCount} horarios");

                    if ($horariosCount > 0) {
                        $issues[] = "Tiene {$horariosCount} horario(s) médico(s) configurado(s)";
                        $suggestions[] = "Elimine los horarios médicos antes de eliminar";
                    }
                }
            }

            // Verificar si es un PACIENTE
            if ($user->rol_id == 3) {
                $paciente = \App\Models\Paciente::where('usuario_id', $userId)->first();
                if ($paciente) {
                    // Verificar citas como paciente
                    $db = Database::pdo();
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM citas WHERE paciente_id = ?");
                    $stmt->execute([$paciente->id]);
                    $citasCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

                    if ($citasCount > 0) {
                        $issues[] = "Tiene {$citasCount} cita(s) médica(s) registrada(s) como paciente";
                        $suggestions[] = "Cancele las citas antes de eliminar";
                    }
                }
            }

            if (!empty($issues)) {
                error_log("DEBUG: Usuario {$userId} NO puede ser eliminado. Issues: " . json_encode($issues));
                return [
                    'reason' => 'No se puede eliminar: tiene citas o turnos activos',
                    'details' => $issues,
                    'suggestions' => $suggestions
                ];
            }

            error_log("DEBUG: Usuario {$userId} SÍ puede ser eliminado - sin issues");
            return true;

        } catch (\Exception $e) {
            error_log("Error al verificar relaciones del usuario: " . $e->getMessage());
            return [
                'reason' => 'Error al verificar relaciones: revise que no tenga citas o turnos activos',
                'details' => ['Error interno del sistema'],
                'suggestions' => ['Contactar al administrador si el problema persiste']
            ];
        }
    }

    private function performUserDeletion($user) {
        try {
            // Eliminar registros específicos del rol primero (para evitar errores de FK)
            $this->deleteRoleSpecificRecords($user->id);

            // Eliminar roles
            $user->roles()->detach();

            // Finalmente eliminar el usuario
            $user->delete();

        } catch (\Exception $e) {
            throw new \Exception("Error al eliminar usuario: " . $e->getMessage());
        }
    }

    private function deleteRoleSpecificRecords($userId) {        
        // Eliminar registro de doctor si existe (solo si no tiene citas/horarios)
        \App\Models\Doctor::where('usuario_id', $userId)->delete();
        
        // Eliminar registro de paciente si existe (solo si no tiene citas)
        \App\Models\Paciente::where('usuario_id', $userId)->delete();
        
        // Eliminar otros roles
        \App\Models\Cajero::where('usuario_id', $userId)->delete();
        \App\Models\Superadmin::where('usuario_id', $userId)->delete();
    }

    public function getUserRelationships(Request $r, Response $res) {
        Auth::abortUnless($res, ['superadmin']);

        try {
            $id = $r->params['id'] ?? 0;
            $relationshipCheck = $this->checkUserRelationships($id);
            
            if ($relationshipCheck === true) {
                return $res->json([
                    'can_delete' => true,
                    'message' => 'El usuario puede ser eliminado de forma segura',
                    'debug_check_result' => 'checkUserRelationships returned TRUE'
                ]);
            } else {
                return $res->json([
                    'can_delete' => false,
                    'reason' => $relationshipCheck['reason'],
                    'details' => $relationshipCheck['details'],
                    'suggestions' => $relationshipCheck['suggestions'] ?? [],
                    'debug_check_result' => 'checkUserRelationships returned ARRAY: ' . json_encode($relationshipCheck)
                ]);
            }

        } catch (\Exception $e) {
            error_log("Error al verificar relaciones del usuario: " . $e->getMessage());
            return $res->json([
                'error' => 'No se pudo verificar el usuario: revise que no tenga citas o turnos activos antes de volverlo a intentar.'
            ], 400);
        }
    }

    // Endpoint para obtener especialidades médicas
    public function getEspecialidades($req, $res) {
        try {
            $especialidades = \App\Models\Especialidad::select('id', 'nombre', 'descripcion')
                                                     ->orderBy('nombre', 'asc')
                                                     ->get()
                                                     ->toArray();

            return $res->json([
                'especialidades' => $especialidades
            ]);

        } catch (\Exception $e) {
            error_log("Error al obtener especialidades: " . $e->getMessage());
            return $res->json(['error' => 'Error al cargar especialidades'], 500);
        }
    }

    private function validateFormats($dni, $telefono, $email) {
        // Validar DNI: exactamente 8 dígitos
        if (!preg_match('/^\d{8}$/', $dni)) {
            return 'El DNI debe tener exactamente 8 dígitos';
        }

        // Validar teléfono: exactamente 9 dígitos
        if (!preg_match('/^\d{9}$/', $telefono)) {
            return 'El teléfono debe tener exactamente 9 dígitos';
        }

        // Validar email con formato correcto
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'El formato del email no es válido';
        }

        return true;
    }

    private function checkDuplicates($email, $dni, $excludeUserId = null) {
        // Verificar email único
        $emailQuery = User::where('email', $email);
        if ($excludeUserId) {
            $emailQuery->where('id', '!=', $excludeUserId);
        }
        if ($emailQuery->exists()) {
            return 'El email ya está registrado por otro usuario';
        }

        // Verificar DNI único
        $dniQuery = User::where('dni', $dni);
        if ($excludeUserId) {
            $dniQuery->where('id', '!=', $excludeUserId);
        }
        if ($dniQuery->exists()) {
            return 'El DNI ya está registrado por otro usuario';
        }

        return true;
    }

    private function validateCMPFormat($cmp) {
        // Verificar formato: CMP-##### (exactamente 5 dígitos después de CMP-)
        if (!preg_match('/^CMP-\d{5}$/', $cmp)) {
            return 'El CMP debe tener el formato CMP-##### (ejemplo: CMP-12345) con exactamente 5 dígitos';
        }

        return true;
    }
}