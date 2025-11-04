<?php

namespace App\Controllers;

use App\Core\{Request, Response, SimpleDatabase, Auth};
use App\Models\{User, Role, Doctor, Paciente, Cajero, Superadmin, Especialidad};

class UserController
{
    private $db;

    public function __construct()
    {
        $this->db = SimpleDatabase::getInstance();
    }

    private function verifySuperAdmin()
    {
        $user = Auth::user();
        if (!$user || !isset($user['rol']) || $user['rol'] !== 'superadmin') {
            $response = new Response();
            $response->redirect('/dashboard');
            exit;
        }
    }

    private function getJsonBody()
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }

    public function index(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY nombre");
        return $response->view('users/index', ['title' => 'Gestión de Usuarios', 'roles' => $roles]);
    }

    public function create(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        return $response->view('users/create', ['title' => 'Crear Usuario']);
    }
    
    public function edit(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $id = (int)($request->params['id'] ?? 0);
        return $response->view('users/create', ['title' => 'Editar Usuario', 'userId' => $id]);
    }

    public function apiList(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $roleFilter = $request->query['role'] ?? '';
        $search = $request->query['search'] ?? '';
        $sql = "SELECT u.id, u.nombre, u.apellido, u.email, u.dni, u.telefono, u.direccion, r.nombre as rol FROM usuarios u LEFT JOIN tiene_roles tr ON u.id = tr.usuario_id LEFT JOIN roles r ON tr.rol_id = r.id WHERE 1=1";
        $params = [];
        if ($roleFilter !== '') {
            $sql .= " AND r.nombre = ?";
            $params[] = $roleFilter;
        }
        if ($search !== '') {
            $searchTerm = "%{$search}%";
            $sql .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ? OR u.dni LIKE ? OR u.telefono LIKE ? OR u.direccion LIKE ?)";
            for ($i = 0; $i < 6; $i++) $params[] = $searchTerm;
        }
        $sql .= " ORDER BY u.nombre, u.apellido";
        $users = $this->db->fetchAll($sql, $params);
        return $response->json(['success' => true, 'data' => $users]);
    }

    public function apiShow(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $id = (int)($request->params['id'] ?? 0);
        $user = $this->db->fetchOne("SELECT u.*, r.nombre as rol, r.id as rol_id FROM usuarios u LEFT JOIN tiene_roles tr ON u.id = tr.usuario_id LEFT JOIN roles r ON tr.rol_id = r.id WHERE u.id = ?", [$id]);
        if (!$user) return $response->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        $roleData = null;
        if ($user['rol'] === 'doctor') {
            $roleData = $this->db->fetchOne("SELECT d.*, e.nombre as especialidad_nombre FROM doctores d LEFT JOIN especialidades e ON d.especialidad_id = e.id WHERE d.usuario_id = ?", [$id]);
        } elseif ($user['rol'] === 'paciente') {
            $roleData = $this->db->fetchOne("SELECT * FROM pacientes WHERE usuario_id = ?", [$id]);
        } elseif ($user['rol'] === 'cajero') {
            $roleData = $this->db->fetchOne("SELECT * FROM cajeros WHERE usuario_id = ?", [$id]);
        } elseif ($user['rol'] === 'superadmin') {
            $roleData = $this->db->fetchOne("SELECT * FROM superadmins WHERE usuario_id = ?", [$id]);
        }
        return $response->json(['success' => true, 'data' => ['user' => $user, 'roleData' => $roleData]]);
    }

    public function store(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $data = $this->getJsonBody();
        
        // Debug: mostrar datos recibidos
        error_log("Datos recibidos en store: " . print_r($data, true));
        
        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $dni = trim($data['dni'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $rol = $data['rol'] ?? '';
        
        // Validaciones de campos obligatorios
        if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($dni) || empty($telefono) || empty($direccion) || empty($rol)) {
            return $response->json(['success' => false, 'message' => 'Todos los campos de usuario son obligatorios'], 400);
        }
        
        // Validar que nombre y apellido solo contengan letras y espacios
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $nombre)) {
            return $response->json(['success' => false, 'message' => 'El nombre solo debe contener letras y espacios'], 400);
        }
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $apellido)) {
            return $response->json(['success' => false, 'message' => 'El apellido solo debe contener letras y espacios'], 400);
        }
        
        // Validar DNI: exactamente 8 dígitos numéricos
        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            return $response->json(['success' => false, 'message' => 'El DNI debe contener exactamente 8 dígitos numéricos'], 400);
        }
        
        // Validar teléfono: exactamente 9 dígitos numéricos
        if (!preg_match('/^[0-9]{9}$/', $telefono)) {
            return $response->json(['success' => false, 'message' => 'El teléfono debe contener exactamente 9 dígitos numéricos'], 400);
        }
        
        // Validar email único
        if ($this->db->fetchOne("SELECT id FROM usuarios WHERE email = ?", [$email])) {
            return $response->json(['success' => false, 'message' => 'El email ya está registrado'], 400);
        }
        
        // Validar DNI único
        if ($this->db->fetchOne("SELECT id FROM usuarios WHERE dni = ?", [$dni])) {
            return $response->json(['success' => false, 'message' => 'El DNI ya está registrado'], 400);
        }
        try {
            $this->db->beginTransaction();
            $userId = $this->db->insert('usuarios', ['nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'contrasenia' => password_hash($password, PASSWORD_DEFAULT), 'dni' => $dni, 'telefono' => $telefono, 'direccion' => $direccion]);
            $roleData = $this->db->fetchOne("SELECT id FROM roles WHERE nombre = ?", [$rol]);
            if (!$roleData) throw new \Exception('Rol no encontrado');
            $this->db->insert('tiene_roles', ['usuario_id' => $userId, 'rol_id' => $roleData['id']]);
            
            if ($rol === 'doctor') {
                // Verificar que la especialidad es requerida
                if (empty($data['especialidad_id'])) {
                    throw new \Exception('La especialidad es requerida para doctores');
                }
                
                // Validar y formatear CMP: debe tener exactamente 5 dígitos
                $cmp = trim($data['cmp'] ?? '');
                if (empty($cmp)) {
                    throw new \Exception('El CMP es requerido para doctores');
                }
                
                // Eliminar el prefijo "CMP-" si viene con él
                $cmp = preg_replace('/^CMP-/i', '', $cmp);
                
                // Validar que tenga exactamente 5 dígitos numéricos
                if (!preg_match('/^[0-9]{5}$/', $cmp)) {
                    throw new \Exception('El CMP debe contener exactamente 5 dígitos numéricos');
                }
                
                // Formatear como CMP-#####
                $cmpFormatted = 'CMP-' . $cmp;
                
                // Verificar CMP único
                $existingCmp = $this->db->fetchOne("SELECT id FROM doctores WHERE cmp = ?", [$cmpFormatted]);
                if ($existingCmp) {
                    throw new \Exception('El CMP ya está registrado');
                }
                
                $this->db->insert('doctores', [
                    'usuario_id' => $userId,
                    'especialidad_id' => $data['especialidad_id'],
                    'cmp' => $cmpFormatted,
                    'biografia' => $data['biografia'] ?? null
                ]);
                
            } elseif ($rol === 'paciente') {
                // Verificar número de historia clínica único si se proporciona
                if (!empty($data['numero_historia_clinica'])) {
                    $existingHc = $this->db->fetchOne("SELECT id FROM pacientes WHERE numero_historia_clinica = ?", [$data['numero_historia_clinica']]);
                    if ($existingHc) {
                        throw new \Exception('El número de historia clínica ya está registrado');
                    }
                }
                
                // Validar teléfono de emergencia si se proporciona (9 dígitos)
                $telefonoEmergencia = trim($data['contacto_emergencia_telefono'] ?? '');
                if (!empty($telefonoEmergencia) && !preg_match('/^[0-9]{9}$/', $telefonoEmergencia)) {
                    throw new \Exception('El teléfono de emergencia debe contener exactamente 9 dígitos numéricos');
                }
                
                $this->db->insert('pacientes', [
                    'usuario_id' => $userId,
                    'numero_historia_clinica' => $data['numero_historia_clinica'] ?? null,
                    'tipo_sangre' => $data['tipo_sangre'] ?? null,
                    'alergias' => $data['alergias'] ?? null,
                    'condicion_cronica' => $data['condicion_cronica'] ?? null,
                    'historial_cirugias' => $data['historial_cirugias'] ?? null,
                    'historico_familiar' => $data['historico_familiar'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null,
                    'contacto_emergencia_nombre' => $data['contacto_emergencia_nombre'] ?? null,
                    'contacto_emergencia_telefono' => !empty($telefonoEmergencia) ? $telefonoEmergencia : null,
                    'contacto_emergencia_relacion' => $data['contacto_emergencia_relacion'] ?? null
                ]);
                
            } elseif ($rol === 'cajero') {
                // Validar campos requeridos para cajero
                if (empty($data['cajero_nombre']) || empty($data['cajero_usuario']) || empty($data['cajero_contrasenia'])) {
                    throw new \Exception('Todos los campos de cajero son requeridos');
                }
                
                $cajeroData = [
                    'usuario_id' => $userId,
                    'nombre' => $data['cajero_nombre'],
                    'usuario' => $data['cajero_usuario'],
                    'contrasenia' => password_hash($data['cajero_contrasenia'], PASSWORD_DEFAULT)
                ];
                error_log("Insertando cajero: " . print_r($cajeroData, true));
                $this->db->insert('cajeros', $cajeroData);
                
            } elseif ($rol === 'superadmin') {
                // Validar campos requeridos para superadmin
                if (empty($data['superadmin_nombre']) || empty($data['superadmin_usuario']) || empty($data['superadmin_contrasenia'])) {
                    throw new \Exception('Todos los campos de superadmin son requeridos');
                }
                
                $superadminData = [
                    'usuario_id' => $userId,
                    'nombre' => $data['superadmin_nombre'],
                    'usuario' => $data['superadmin_usuario'],
                    'contrasenia' => password_hash($data['superadmin_contrasenia'], PASSWORD_DEFAULT)
                ];
                error_log("Insertando superadmin: " . print_r($superadminData, true));
                $this->db->insert('superadmins', $superadminData);
            }
            
            $this->db->commit();
            return $response->json(['success' => true, 'message' => 'Usuario creado exitosamente']);
        } catch (\Exception $e) {
            $this->db->rollback();
            return $response->json(['success' => false, 'message' => 'Error al crear el usuario: ' . $e->getMessage()], 500);
        }
    }

    public function updateOrDelete(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $id = (int)($request->params['id'] ?? 0);
        $data = $this->getJsonBody();
        if (($data['_method'] ?? '') === 'DELETE') {
            return $this->destroy($id, $request, $response);
        }
        return $this->update($id, $request, $response);
    }

    private function update($id, Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $data = $this->getJsonBody();
        
        // Debug
        error_log("Datos recibidos en update: " . print_r($data, true));
        
        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $dni = trim($data['dni'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $rol = $data['rol'] ?? '';
        
        // Validaciones de campos obligatorios
        if (empty($nombre) || empty($apellido) || empty($email) || empty($dni) || empty($telefono) || empty($direccion) || empty($rol)) {
            return $response->json(['success' => false, 'message' => 'Todos los campos de usuario son obligatorios'], 400);
        }
        
        // Validar que nombre y apellido solo contengan letras y espacios
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $nombre)) {
            return $response->json(['success' => false, 'message' => 'El nombre solo debe contener letras y espacios'], 400);
        }
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $apellido)) {
            return $response->json(['success' => false, 'message' => 'El apellido solo debe contener letras y espacios'], 400);
        }
        
        // Validar DNI: exactamente 8 dígitos numéricos
        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            return $response->json(['success' => false, 'message' => 'El DNI debe contener exactamente 8 dígitos numéricos'], 400);
        }
        
        // Validar teléfono: exactamente 9 dígitos numéricos
        if (!preg_match('/^[0-9]{9}$/', $telefono)) {
            return $response->json(['success' => false, 'message' => 'El teléfono debe contener exactamente 9 dígitos numéricos'], 400);
        }
        
        if (!$this->db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id])) {
            return $response->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }
        
        // Validar email único (excluyendo el usuario actual)
        if ($this->db->fetchOne("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $id])) {
            return $response->json(['success' => false, 'message' => 'El email ya está registrado por otro usuario'], 400);
        }
        
        // Validar DNI único (excluyendo el usuario actual)
        if ($this->db->fetchOne("SELECT id FROM usuarios WHERE dni = ? AND id != ?", [$dni, $id])) {
            return $response->json(['success' => false, 'message' => 'El DNI ya está registrado por otro usuario'], 400);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Actualizar datos generales del usuario
            $updateData = ['nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'dni' => $dni, 'telefono' => $telefono, 'direccion' => $direccion];
            if (!empty($password)) {
                $updateData['contrasenia'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $this->db->update('usuarios', $updateData, 'id = ?', [$id]);
            
            // Obtener el rol actual
            $currentRole = $this->db->fetchOne("SELECT r.nombre FROM roles r INNER JOIN tiene_roles tr ON r.id = tr.rol_id WHERE tr.usuario_id = ?", [$id]);
            
            // Si cambió el rol, eliminar la tabla anterior y crear la nueva
            if ($currentRole && $currentRole['nombre'] !== $rol) {
                error_log("Cambiando rol de {$currentRole['nombre']} a {$rol}");
                
                // Eliminar de la tabla del rol anterior
                $this->db->execute("DELETE FROM tiene_roles WHERE usuario_id = ?", [$id]);
                if ($currentRole['nombre'] === 'doctor') {
                    $this->db->execute("DELETE FROM doctores WHERE usuario_id = ?", [$id]);
                } elseif ($currentRole['nombre'] === 'paciente') {
                    $this->db->execute("DELETE FROM pacientes WHERE usuario_id = ?", [$id]);
                } elseif ($currentRole['nombre'] === 'cajero') {
                    $this->db->execute("DELETE FROM cajeros WHERE usuario_id = ?", [$id]);
                } elseif ($currentRole['nombre'] === 'superadmin') {
                    $this->db->execute("DELETE FROM superadmins WHERE usuario_id = ?", [$id]);
                }
                
                // Insertar nuevo rol
                $newRoleData = $this->db->fetchOne("SELECT id FROM roles WHERE nombre = ?", [$rol]);
                if (!$newRoleData) throw new \Exception('Rol no encontrado');
                $this->db->insert('tiene_roles', ['usuario_id' => $id, 'rol_id' => $newRoleData['id']]);
                
                // Crear registro en la nueva tabla del rol
                if ($rol === 'doctor') {
                    if (empty($data['especialidad_id'])) {
                        throw new \Exception('La especialidad es requerida para doctores');
                    }
                    
                    // Validar y formatear CMP: debe tener exactamente 5 dígitos
                    $cmp = trim($data['cmp'] ?? '');
                    if (empty($cmp)) {
                        throw new \Exception('El CMP es requerido para doctores');
                    }
                    
                    // Eliminar el prefijo "CMP-" si viene con él
                    $cmp = preg_replace('/^CMP-/i', '', $cmp);
                    
                    // Validar que tenga exactamente 5 dígitos numéricos
                    if (!preg_match('/^[0-9]{5}$/', $cmp)) {
                        throw new \Exception('El CMP debe contener exactamente 5 dígitos numéricos');
                    }
                    
                    // Formatear como CMP-#####
                    $cmpFormatted = 'CMP-' . $cmp;
                    
                    // Verificar CMP único
                    $existingCmp = $this->db->fetchOne("SELECT id FROM doctores WHERE cmp = ?", [$cmpFormatted]);
                    if ($existingCmp) {
                        throw new \Exception('El CMP ya está registrado');
                    }
                    
                    $this->db->insert('doctores', [
                        'usuario_id' => $id,
                        'especialidad_id' => $data['especialidad_id'],
                        'cmp' => $cmpFormatted,
                        'biografia' => $data['biografia'] ?? null
                    ]);
                    
                } elseif ($rol === 'paciente') {
                    // Validar número de historia clínica único si se proporciona
                    if (!empty($data['numero_historia_clinica'])) {
                        $existingHc = $this->db->fetchOne("SELECT id FROM pacientes WHERE numero_historia_clinica = ?", [$data['numero_historia_clinica']]);
                        if ($existingHc) {
                            throw new \Exception('El número de historia clínica ya está registrado');
                        }
                    }
                    
                    // Validar teléfono de emergencia si se proporciona (9 dígitos)
                    $telefonoEmergencia = trim($data['contacto_emergencia_telefono'] ?? '');
                    if (!empty($telefonoEmergencia) && !preg_match('/^[0-9]{9}$/', $telefonoEmergencia)) {
                        throw new \Exception('El teléfono de emergencia debe contener exactamente 9 dígitos numéricos');
                    }
                    
                    $this->db->insert('pacientes', [
                        'usuario_id' => $id,
                        'numero_historia_clinica' => $data['numero_historia_clinica'] ?? null,
                        'tipo_sangre' => $data['tipo_sangre'] ?? null,
                        'alergias' => $data['alergias'] ?? null,
                        'condicion_cronica' => $data['condicion_cronica'] ?? null,
                        'historial_cirugias' => $data['historial_cirugias'] ?? null,
                        'historico_familiar' => $data['historico_familiar'] ?? null,
                        'observaciones' => $data['observaciones'] ?? null,
                        'contacto_emergencia_nombre' => $data['contacto_emergencia_nombre'] ?? null,
                        'contacto_emergencia_telefono' => !empty($telefonoEmergencia) ? $telefonoEmergencia : null,
                        'contacto_emergencia_relacion' => $data['contacto_emergencia_relacion'] ?? null
                    ]);
                    
                } elseif ($rol === 'cajero') {
                    // Validar campos requeridos para cajero al cambiar rol
                    if (empty($data['cajero_nombre']) || empty($data['cajero_usuario']) || empty($data['cajero_contrasenia'])) {
                        throw new \Exception('Todos los campos de cajero son requeridos al cambiar a este rol');
                    }
                    
                    $cajeroData = [
                        'usuario_id' => $id,
                        'nombre' => $data['cajero_nombre'],
                        'usuario' => $data['cajero_usuario'],
                        'contrasenia' => password_hash($data['cajero_contrasenia'], PASSWORD_DEFAULT)
                    ];
                    $this->db->insert('cajeros', $cajeroData);
                    
                } elseif ($rol === 'superadmin') {
                    // Validar campos requeridos para superadmin al cambiar rol
                    if (empty($data['superadmin_nombre']) || empty($data['superadmin_usuario']) || empty($data['superadmin_contrasenia'])) {
                        throw new \Exception('Todos los campos de superadmin son requeridos al cambiar a este rol');
                    }
                    
                    $superadminData = [
                        'usuario_id' => $id,
                        'nombre' => $data['superadmin_nombre'],
                        'usuario' => $data['superadmin_usuario'],
                        'contrasenia' => password_hash($data['superadmin_contrasenia'], PASSWORD_DEFAULT)
                    ];
                    $this->db->insert('superadmins', $superadminData);
                }
            } else {
                // Si NO cambió el rol, solo actualizar los campos específicos del rol actual
                if ($rol === 'doctor') {
                    if (empty($data['especialidad_id'])) {
                        throw new \Exception('La especialidad es requerida para doctores');
                    }
                    
                    // Validar y formatear CMP: debe tener exactamente 5 dígitos
                    $cmp = trim($data['cmp'] ?? '');
                    if (empty($cmp)) {
                        throw new \Exception('El CMP es requerido para doctores');
                    }
                    
                    // Eliminar el prefijo "CMP-" si viene con él
                    $cmp = preg_replace('/^CMP-/i', '', $cmp);
                    
                    // Validar que tenga exactamente 5 dígitos numéricos
                    if (!preg_match('/^[0-9]{5}$/', $cmp)) {
                        throw new \Exception('El CMP debe contener exactamente 5 dígitos numéricos');
                    }
                    
                    // Formatear como CMP-#####
                    $cmpFormatted = 'CMP-' . $cmp;
                    
                    // Verificar CMP único (excluyendo el doctor actual)
                    $existingCmp = $this->db->fetchOne("SELECT id FROM doctores WHERE cmp = ? AND usuario_id != ?", [$cmpFormatted, $id]);
                    if ($existingCmp) {
                        throw new \Exception('El CMP ya está registrado por otro doctor');
                    }
                    
                    $this->db->update('doctores', [
                        'especialidad_id' => $data['especialidad_id'],
                        'cmp' => $cmpFormatted,
                        'biografia' => $data['biografia'] ?? null
                    ], 'usuario_id = ?', [$id]);
                    
                } elseif ($rol === 'paciente') {
                    // Verificar número de historia clínica único si se proporciona (excluyendo el paciente actual)
                    if (!empty($data['numero_historia_clinica'])) {
                        $existingHc = $this->db->fetchOne("SELECT id FROM pacientes WHERE numero_historia_clinica = ? AND usuario_id != ?", [$data['numero_historia_clinica'], $id]);
                        if ($existingHc) {
                            throw new \Exception('El número de historia clínica ya está registrado por otro paciente');
                        }
                    }
                    
                    // Validar teléfono de emergencia si se proporciona (9 dígitos)
                    $telefonoEmergencia = trim($data['contacto_emergencia_telefono'] ?? '');
                    if (!empty($telefonoEmergencia) && !preg_match('/^[0-9]{9}$/', $telefonoEmergencia)) {
                        throw new \Exception('El teléfono de emergencia debe contener exactamente 9 dígitos numéricos');
                    }
                    
                    $this->db->update('pacientes', [
                        'numero_historia_clinica' => $data['numero_historia_clinica'] ?? null,
                        'tipo_sangre' => $data['tipo_sangre'] ?? null,
                        'alergias' => $data['alergias'] ?? null,
                        'condicion_cronica' => $data['condicion_cronica'] ?? null,
                        'historial_cirugias' => $data['historial_cirugias'] ?? null,
                        'historico_familiar' => $data['historico_familiar'] ?? null,
                        'observaciones' => $data['observaciones'] ?? null,
                        'contacto_emergencia_nombre' => $data['contacto_emergencia_nombre'] ?? null,
                        'contacto_emergencia_telefono' => !empty($telefonoEmergencia) ? $telefonoEmergencia : null,
                        'contacto_emergencia_relacion' => $data['contacto_emergencia_relacion'] ?? null
                    ], 'usuario_id = ?', [$id]);
                    
                } elseif ($rol === 'cajero') {
                    $cajeroUpdateData = [
                        'nombre' => $data['cajero_nombre'] ?? null,
                        'usuario' => $data['cajero_usuario'] ?? null
                    ];
                    // Solo actualizar contraseña si se proporcionó una nueva
                    if (!empty($data['cajero_contrasenia'])) {
                        $cajeroUpdateData['contrasenia'] = password_hash($data['cajero_contrasenia'], PASSWORD_DEFAULT);
                    }
                    $this->db->update('cajeros', $cajeroUpdateData, 'usuario_id = ?', [$id]);
                    
                } elseif ($rol === 'superadmin') {
                    $superadminUpdateData = [
                        'nombre' => $data['superadmin_nombre'] ?? null,
                        'usuario' => $data['superadmin_usuario'] ?? null
                    ];
                    // Solo actualizar contraseña si se proporcionó una nueva
                    if (!empty($data['superadmin_contrasenia'])) {
                        $superadminUpdateData['contrasenia'] = password_hash($data['superadmin_contrasenia'], PASSWORD_DEFAULT);
                    }
                    $this->db->update('superadmins', $superadminUpdateData, 'usuario_id = ?', [$id]);
                }
            }
            
            $this->db->commit();
            return $response->json(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        } catch (\Exception $e) {
            $this->db->rollback();
            return $response->json(['success' => false, 'message' => 'Error al actualizar el usuario: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id, Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        if (!$this->db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id])) {
            return $response->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }
        $roleData = $this->db->fetchOne("SELECT r.nombre FROM roles r INNER JOIN tiene_roles tr ON r.id = tr.rol_id WHERE tr.usuario_id = ?", [$id]);
        try {
            $this->db->beginTransaction();
            if ($roleData) {
                $rol = $roleData['nombre'];
                if ($rol === 'doctor') {
                    // Verificar citas
                    $citasCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM citas WHERE doctor_id IN (SELECT id FROM doctores WHERE usuario_id = ?)", [$id]);
                    if ($citasCount && $citasCount['count'] > 0) {
                        $this->db->rollback();
                        return $response->json(['success' => false, 'message' => 'No se puede eliminar: El doctor tiene ' . $citasCount['count'] . ' cita(s) registrada(s). Elimine las citas primero.'], 400);
                    }
                    
                    // Verificar horarios médicos
                    $horariosCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM horarios_medicos WHERE doctor_id IN (SELECT id FROM doctores WHERE usuario_id = ?)", [$id]);
                    if ($horariosCount && $horariosCount['count'] > 0) {
                        $this->db->rollback();
                        return $response->json(['success' => false, 'message' => 'No se puede eliminar: El doctor tiene ' . $horariosCount['count'] . ' horario(s) programado(s). Elimine los horarios primero.'], 400);
                    }
                    
                    $this->db->execute("DELETE FROM doctores WHERE usuario_id = ?", [$id]);
                    
                } elseif ($rol === 'paciente') {
                    // Verificar citas
                    $citasCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM citas WHERE paciente_id IN (SELECT id FROM pacientes WHERE usuario_id = ?)", [$id]);
                    if ($citasCount && $citasCount['count'] > 0) {
                        $this->db->rollback();
                        return $response->json(['success' => false, 'message' => 'No se puede eliminar: El paciente tiene ' . $citasCount['count'] . ' cita(s) registrada(s). Elimine las citas primero.'], 400);
                    }
                    $this->db->execute("DELETE FROM pacientes WHERE usuario_id = ?", [$id]);
                    
                } elseif ($rol === 'cajero') {
                    // Verificar pagos
                    $pagosCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM pagos WHERE cajero_id IN (SELECT id FROM cajeros WHERE usuario_id = ?)", [$id]);
                    if ($pagosCount && $pagosCount['count'] > 0) {
                        $this->db->rollback();
                        return $response->json(['success' => false, 'message' => 'No se puede eliminar: El cajero tiene ' . $pagosCount['count'] . ' pago(s) registrado(s). Elimine los pagos primero.'], 400);
                    }
                    $this->db->execute("DELETE FROM cajeros WHERE usuario_id = ?", [$id]);
                    
                } elseif ($rol === 'superadmin') {
                    // Los superadmins se pueden eliminar sin restricciones
                    $this->db->execute("DELETE FROM superadmins WHERE usuario_id = ?", [$id]);
                }
            }
            $this->db->execute("DELETE FROM tiene_roles WHERE usuario_id = ?", [$id]);
            $this->db->execute("DELETE FROM usuarios WHERE id = ?", [$id]);
            $this->db->commit();
            return $response->json(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
        } catch (\Exception $e) {
            $this->db->rollback();
            return $response->json(['success' => false, 'message' => 'Error al eliminar el usuario: ' . $e->getMessage()], 500);
        }
    }

    public function getEspecialidades(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        return $response->json(['success' => true, 'data' => Especialidad::getAll()]);
    }

    public function getUserRelationships(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $id = (int)($request->params['id'] ?? 0);
        $rel = ['citas' => 0, 'pagos' => 0, 'horarios' => 0];
        $citasDoctor = $this->db->fetchOne("SELECT COUNT(*) as count FROM citas WHERE doctor_id IN (SELECT id FROM doctores WHERE usuario_id = ?)", [$id]);
        if ($citasDoctor) $rel['citas'] += (int)$citasDoctor['count'];
        $citasPaciente = $this->db->fetchOne("SELECT COUNT(*) as count FROM citas WHERE paciente_id IN (SELECT id FROM pacientes WHERE usuario_id = ?)", [$id]);
        if ($citasPaciente) $rel['citas'] += (int)$citasPaciente['count'];
        $pagos = $this->db->fetchOne("SELECT COUNT(*) as count FROM pagos WHERE cajero_id IN (SELECT id FROM cajeros WHERE usuario_id = ?)", [$id]);
        if ($pagos) $rel['pagos'] = (int)$pagos['count'];
        $horarios = $this->db->fetchOne("SELECT COUNT(*) as count FROM horarios_medicos WHERE doctor_id IN (SELECT id FROM doctores WHERE usuario_id = ?)", [$id]);
        if ($horarios) $rel['horarios'] = (int)$horarios['count'];
        return $response->json(['success' => true, 'data' => $rel]);
    }
}
