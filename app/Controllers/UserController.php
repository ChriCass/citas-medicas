<?php

namespace App\Controllers;

use App\Core\{Request, Response, Auth};
use App\Models\{User, Role, Doctor, Paciente, Cajero, Superadmin, Especialidad, Appointment, DoctorSchedule, Payment};
use App\Services\{UserService, RoleService};
use App\Services\Validators\UserValidator;
use Illuminate\Database\Capsule\Manager as DB;

class UserController
{
    private UserService $userService;
    private RoleService $roleService;
    private UserValidator $validator;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->roleService = new RoleService();
        $this->validator = new UserValidator();
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
        $roles = Role::orderBy('nombre')->get()->toArray();
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
        
        $query = User::select('usuarios.id', 'usuarios.nombre', 'usuarios.apellido', 
                              'usuarios.email', 'usuarios.dni', 'usuarios.telefono', 
                              'usuarios.direccion', 'roles.nombre as rol')
            ->leftJoin('tiene_roles', 'usuarios.id', '=', 'tiene_roles.usuario_id')
            ->leftJoin('roles', 'tiene_roles.rol_id', '=', 'roles.id');
        
        if ($roleFilter !== '') {
            $query->where('roles.nombre', $roleFilter);
        }
        
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $searchTerm = "%{$search}%";
                $q->where('usuarios.nombre', 'LIKE', $searchTerm)
                  ->orWhere('usuarios.apellido', 'LIKE', $searchTerm)
                  ->orWhere('usuarios.email', 'LIKE', $searchTerm)
                  ->orWhere('usuarios.dni', 'LIKE', $searchTerm)
                  ->orWhere('usuarios.telefono', 'LIKE', $searchTerm)
                  ->orWhere('usuarios.direccion', 'LIKE', $searchTerm);
            });
        }
        
        $users = $query->orderBy('usuarios.nombre')
                       ->orderBy('usuarios.apellido')
                       ->get()
                       ->toArray();
        
        return $response->json(['success' => true, 'data' => $users]);
    }

    public function apiShow(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $id = (int)($request->params['id'] ?? 0);
        
        $user = User::select('usuarios.*', 'roles.nombre as rol', 'roles.id as rol_id')
            ->leftJoin('tiene_roles', 'usuarios.id', '=', 'tiene_roles.usuario_id')
            ->leftJoin('roles', 'tiene_roles.rol_id', '=', 'roles.id')
            ->where('usuarios.id', $id)
            ->first();
        
        if (!$user) {
            return $response->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }
        
        $user = $user->toArray();
        $roleData = null;
        
        if ($user['rol'] === 'doctor') {
            $doctor = Doctor::with('especialidad')
                ->where('usuario_id', $id)
                ->first();
            if ($doctor) {
                $roleData = $doctor->toArray();
                $roleData['especialidad_nombre'] = $doctor->especialidad->nombre ?? null;
            }
        } elseif ($user['rol'] === 'paciente') {
            $paciente = Paciente::where('usuario_id', $id)->first();
            $roleData = $paciente ? $paciente->toArray() : null;
        } elseif ($user['rol'] === 'cajero') {
            $cajero = Cajero::where('usuario_id', $id)->first();
            $roleData = $cajero ? $cajero->toArray() : null;
        } elseif ($user['rol'] === 'superadmin') {
            $superadmin = Superadmin::where('usuario_id', $id)->first();
            $roleData = $superadmin ? $superadmin->toArray() : null;
        }
        
        return $response->json(['success' => true, 'data' => ['user' => $user, 'roleData' => $roleData]]);
    }

    public function store(Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        $data = $this->getJsonBody();
        
        try {
            // Validar datos del usuario usando el validador (antes de la transacción)
            $this->validator->validateUserData($data, false);
            $this->validator->validateUniqueFields(
                trim($data['email']), 
                trim($data['dni'])
            );
            
            DB::beginTransaction();
            
            // Crear usuario usando el servicio
            $user = $this->userService->createUser($data);
            
            // Asignar rol
            $this->userService->assignRole($user->id, $data['rol']);
            
            // Crear datos específicos del rol
            $this->roleService->createRoleData($user->id, $data['rol'], $data);
            
            DB::commit();
            return $response->json(['success' => true, 'message' => 'Usuario creado exitosamente']);
        } catch (\Exception $e) {
            // Solo hacer rollback si hay una transacción activa
            if (DB::transactionLevel() > 0) {
                DB::rollback();
            }
            return $response->json(['success' => false, 'message' => $e->getMessage()], 
                $e->getCode() ?: 500);
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
        
        try {
            // Validar datos del usuario (antes de la transacción)
            $this->validator->validateUserData($data, true);
            
            // Verificar que el usuario existe
            if (!$this->userService->getUserById($id)) {
                return $response->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }
            
            // Validar campos únicos (excluyendo el usuario actual)
            $this->validator->validateUniqueFields(
                trim($data['email']), 
                trim($data['dni']), 
                $id
            );
            
            DB::beginTransaction();
            
            // Actualizar usuario usando el servicio
            $this->userService->updateUser($id, $data);
            
            // Obtener el rol actual
            $currentRole = $this->userService->getCurrentUserRole($id);
            
            // Manejar actualización o cambio de rol
            if ($currentRole) {
                $this->roleService->handleRoleChange($id, $data['rol'], $currentRole, $data);
            }
            
            DB::commit();
            return $response->json(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        } catch (\Exception $e) {
            // Solo hacer rollback si hay una transacción activa
            if (DB::transactionLevel() > 0) {
                DB::rollback();
            }
            return $response->json(['success' => false, 'message' => $e->getMessage()], 
                $e->getCode() ?: 500);
        }
    }

    public function destroy($id, Request $request, Response $response)
    {
        $this->verifySuperAdmin();
        
        try {
            // Usar el servicio para eliminar el usuario
            $this->userService->deleteUser($id);
            
            return $response->json(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
        } catch (\Exception $e) {
            return $response->json(['success' => false, 'message' => $e->getMessage()], 
                $e->getCode() ?: 500);
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
        
        // Verificar si el usuario es doctor
        $doctor = Doctor::where('usuario_id', $id)->first();
        if ($doctor) {
            $rel['citas'] += Appointment::where('doctor_id', $doctor->id)->count();
            $rel['horarios'] = DoctorSchedule::where('doctor_id', $doctor->id)->count();
        }
        
        // Verificar si el usuario es paciente
        $paciente = Paciente::where('usuario_id', $id)->first();
        if ($paciente) {
            $rel['citas'] += Appointment::where('paciente_id', $paciente->id)->count();
        }
        
        // Verificar si el usuario es cajero
        $cajero = Cajero::where('usuario_id', $id)->first();
        if ($cajero) {
            $rel['pagos'] = Payment::where('cajero_id', $cajero->id)->count();
        }
        
        return $response->json(['success' => true, 'data' => $rel]);
    }
}
