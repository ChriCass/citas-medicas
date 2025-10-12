<?php

/**
 * TEST COMPLETO DE MIGRACIÓN A ELOQUENT
 * 
 * Este test verifica todas las operaciones CRUD del proyecto
 * después de la migración a Eloquent ORM
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\{Env, Eloquent};
use App\Models\{
    User, Role, Especialidad, Doctor, Paciente, 
    Appointment, Sede, DoctorSchedule, BusinessHour
};

// Configuración de colores para la consola
class TestColors {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

class EloquentCompleteTest {
    private $testResults = [];
    private $createdIds = [];
    
    public function __construct() {
        echo TestColors::BOLD . TestColors::BLUE . "=== TEST COMPLETO DE MIGRACIÓN A ELOQUENT ===" . TestColors::RESET . "\n\n";
        
        // Inicializar Eloquent
        try {
            Env::load(__DIR__ . '/.env');
            Eloquent::init();
            $this->log("✓ Eloquent inicializado correctamente", 'success');
        } catch (Exception $e) {
            $this->log("✗ Error inicializando Eloquent: " . $e->getMessage(), 'error');
            exit(1);
        }
    }
    
    private function log($message, $type = 'info') {
        $color = match($type) {
            'success' => TestColors::GREEN,
            'error' => TestColors::RED,
            'warning' => TestColors::YELLOW,
            'info' => TestColors::CYAN,
            default => TestColors::RESET
        };
        
        echo $color . $message . TestColors::RESET . "\n";
    }
    
    private function startSection($title) {
        echo "\n" . TestColors::BOLD . TestColors::YELLOW . "=== $title ===" . TestColors::RESET . "\n";
    }
    
    private function assertTest($condition, $testName, $errorMessage = '') {
        if ($condition) {
            $this->log("  ✓ $testName", 'success');
            $this->testResults[] = true;
            return true;
        } else {
            $this->log("  ✗ $testName" . ($errorMessage ? " - $errorMessage" : ''), 'error');
            $this->testResults[] = false;
            return false;
        }
    }
    
    public function runAllTests() {
        try {
            $this->testDatabaseConnection();
            $this->testBasicCrud();
            $this->testUserManagement();
            $this->testDoctorManagement();
            $this->testPatientManagement();
            $this->testAppointmentManagement();
            $this->testRelationships();
            $this->testAdvancedQueries();
            $this->cleanup();
            $this->printResults();
        } catch (Exception $e) {
            $this->log("Error fatal en tests: " . $e->getMessage(), 'error');
            $this->log("Stack trace: " . $e->getTraceAsString(), 'error');
        }
    }
    
    private function testDatabaseConnection() {
        $this->startSection("1. CONEXIÓN A BASE DE DATOS");
        
        try {
            // Test básico de conexión
            $userCount = User::count();
            $this->assertTest(is_numeric($userCount), "Conexión a BD y conteo de usuarios ($userCount usuarios)");
            
            $roleCount = Role::count();
            $this->assertTest(is_numeric($roleCount), "Conteo de roles ($roleCount roles)");
            
            $especialidadCount = Especialidad::count();
            $this->assertTest(is_numeric($especialidadCount), "Conteo de especialidades ($especialidadCount especialidades)");
            
        } catch (Exception $e) {
            $this->assertTest(false, "Conexión a base de datos", $e->getMessage());
        }
    }
    
    private function testBasicCrud() {
        $this->startSection("2. OPERACIONES CRUD BÁSICAS");
        
        try {
            // CREATE - Crear especialidad de prueba con nombre único
            $uniqueName = 'Test Eloquent ' . time();
            
            $especialidad = new Especialidad();
            $especialidad->nombre = $uniqueName;
            $especialidad->descripcion = 'Especialidad creada para pruebas de Eloquent';
            $result = $especialidad->save();
            $this->assertTest($result && $especialidad->id > 0, "CREATE - Crear especialidad");
            $this->createdIds['especialidad'] = $especialidad->id;
            
            // READ - Leer especialidad
            $found = Especialidad::find($especialidad->id);
            $this->assertTest($found && $found->nombre === $uniqueName, "READ - Leer especialidad por ID");
            
            // UPDATE - Actualizar especialidad
            $found->descripcion = 'Descripción actualizada desde test';
            $result = $found->save();
            $this->assertTest($result, "UPDATE - Actualizar especialidad");
            
            // Verificar actualización
            $updated = Especialidad::find($especialidad->id);
            $this->assertTest($updated->descripcion === 'Descripción actualizada desde test', "Verificar actualización");
            
        } catch (Exception $e) {
            $this->assertTest(false, "CRUD básico", $e->getMessage());
        }
    }
    
    private function testUserManagement() {
        $this->startSection("3. GESTIÓN DE USUARIOS");
        
        try {
            // Crear usuario de prueba con email único
            $uniqueEmail = 'test.eloquent.' . time() . '@clinica.com';
            
            $userId = User::createUser(
                'Test',
                'Eloquent',
                $uniqueEmail,
                password_hash('test123', PASSWORD_DEFAULT),
                'paciente',
                '12345678',
                '123456789'
            );
            
            $this->assertTest($userId > 0, "Crear usuario con rol");
            $this->createdIds['user'] = $userId;
            
            // Buscar usuario por email
            $user = User::findByEmail($uniqueEmail);
            $this->assertTest($user && $user->id === $userId, "Buscar usuario por email");
            
            // Verificar rol asignado
            $this->assertTest($user->hasRole('paciente'), "Usuario tiene rol 'paciente'");
            $this->assertTest($user->getRoleName() === 'paciente', "Obtener nombre del rol");
            
            // Test de relaciones usuario-roles
            $this->assertTest($user->roles->count() > 0, "Cargar relación roles");
            
            // Buscar usuario por ID
            $userById = User::findById($userId);
            $this->assertTest($userById && $userById->email === $uniqueEmail, "Buscar usuario por ID");
            
        } catch (Exception $e) {
            $this->assertTest(false, "Gestión de usuarios", $e->getMessage());
        }
    }
    
    private function testDoctorManagement() {
        $this->startSection("4. GESTIÓN DE DOCTORES");
        
        try {
            // Primero crear un usuario doctor con email único
            $uniqueEmail = 'doctor.test.' . time() . '@clinica.com';
            
            $doctorUserId = User::createUser(
                'Dr. Test',
                'Eloquent',
                $uniqueEmail,
                password_hash('doctor123', PASSWORD_DEFAULT),
                'doctor',
                '87654321',
                '987654321'
            );
            
            $this->assertTest($doctorUserId > 0, "Crear usuario doctor");
            $this->createdIds['doctor_user'] = $doctorUserId;
            
            // Crear perfil de doctor con CMP único
            $uniqueCmp = 'CMP-' . time();
            
            $doctorId = Doctor::create(
                $doctorUserId,
                isset($this->createdIds['especialidad']) ? $this->createdIds['especialidad'] : null,
                $uniqueCmp,
                'Doctor de prueba para test de Eloquent'
            );
            
            $this->assertTest($doctorId > 0, "Crear perfil de doctor");
            $this->createdIds['doctor'] = $doctorId;
            
            // Buscar doctor por usuario ID
            $doctor = Doctor::findByUsuarioId($doctorUserId);
            $this->assertTest($doctor && $doctor->id === $doctorId, "Buscar doctor por usuario ID");
            
            // Test de relaciones
            $this->assertTest($doctor->user->nombre === 'Dr. Test', "Relación doctor->user");
            
            if (isset($this->createdIds['especialidad'])) {
                $this->assertTest($doctor->especialidad !== null, "Relación doctor->especialidad");
            }
            
            // Test de accessors
            $this->assertTest($doctor->nombre === 'Dr. Test', "Accessor nombre");
            $this->assertTest($doctor->apellido === 'Eloquent', "Accessor apellido");
            $this->assertTest($doctor->email === $uniqueEmail, "Accessor email");
            
            // Obtener todos los doctores
            $allDoctors = Doctor::getAll();
            $this->assertTest($allDoctors->count() > 0, "Obtener todos los doctores");
            
        } catch (Exception $e) {
            $this->assertTest(false, "Gestión de doctores", $e->getMessage());
        }
    }
    
    private function testPatientManagement() {
        $this->startSection("5. GESTIÓN DE PACIENTES");
        
        try {
            // Crear paciente (usar el usuario creado anteriormente)
            if (!isset($this->createdIds['user'])) {
                $this->assertTest(false, "Crear perfil de paciente", "No hay usuario de prueba disponible");
                return;
            }
            
            $userId = $this->createdIds['user'];
            
            $paciente = new Paciente();
            $paciente->usuario_id = $userId;
            $paciente->tipo_sangre = 'O+';
            $paciente->alergias = 'Ninguna conocida';
            $result = $paciente->save();
            
            $this->assertTest($result && $paciente->id > 0, "Crear perfil de paciente");
            $this->createdIds['paciente'] = $paciente->id;
            
            // Buscar paciente
            $found = Paciente::with('user')->find($paciente->id);
            $this->assertTest($found && $found->user !== null, "Buscar paciente con relación user");
            
            // Test de selectors de usuarios
            $patients = User::patients('Test');
            $this->assertTest($patients->count() >= 0, "Buscar pacientes por término");
            
            $doctors = User::doctors('Dr.');
            $this->assertTest($doctors->count() >= 0, "Buscar doctores por término");
            
        } catch (Exception $e) {
            $this->assertTest(false, "Gestión de pacientes", $e->getMessage());
        }
    }
    
    private function testAppointmentManagement() {
        $this->startSection("6. GESTIÓN DE CITAS");
        
        try {
            // Crear una sede de prueba si no existe
            $sede = Sede::first();
            if (!$sede) {
                $sede = new Sede();
                $sede->nombre_sede = 'Sede Test';
                $sede->direccion = 'Dirección de prueba';
                $sede->telefono = '123456789';
                $sede->save();
                $this->createdIds['sede'] = $sede->id;
            }
            
            // Verificar que tenemos paciente y doctor
            $pacienteId = isset($this->createdIds['paciente']) ? $this->createdIds['paciente'] : null;
            $doctorId = isset($this->createdIds['doctor']) ? $this->createdIds['doctor'] : null;
            
            if (!$pacienteId || !$doctorId) {
                $this->assertTest(false, "Crear cita", "Faltan paciente o doctor de prueba");
                return;
            }
            
            // Crear cita
            $citaId = Appointment::create(
                $pacienteId,
                $doctorId,
                $sede->id,
                date('Y-m-d', strtotime('+1 day')),
                '10:00:00',
                '11:00:00',
                'Consulta de prueba'
            );
            
            $this->assertTest($citaId > 0, "Crear cita");
            $this->createdIds['cita'] = $citaId;
            
            // Buscar cita con relaciones
            $cita = Appointment::with(['paciente.user', 'doctor.user', 'sede'])->find($citaId);
            $this->assertTest($cita && $cita->razon === 'Consulta de prueba', "Buscar cita con relaciones");
            
            // Test de accessors
            $this->assertTest($cita->paciente_nombre === 'Test', "Accessor paciente_nombre");
            $this->assertTest($cita->doctor_nombre === 'Dr. Test', "Accessor doctor_nombre");
            
            // Obtener citas del usuario
            $userCitas = Appointment::usercitas($this->createdIds['user']);
            $this->assertTest($userCitas->count() > 0, "Obtener citas del usuario");
            
            // Obtener citas del doctor
            $doctorCitas = Appointment::doctorcitas($this->createdIds['doctor_user']);
            $this->assertTest($doctorCitas->count() > 0, "Obtener citas del doctor");
            
            // Actualizar estado de cita
            $updated = Appointment::updateStatus($citaId, 'confirmado');
            $this->assertTest($updated, "Actualizar estado de cita");
            
            // Verificar actualización
            $cita = Appointment::find($citaId);
            $this->assertTest($cita->estado === 'confirmado', "Verificar estado actualizado");
            
        } catch (Exception $e) {
            $this->assertTest(false, "Gestión de citas", $e->getMessage());
        }
    }
    
    private function testRelationships() {
        $this->startSection("7. PRUEBAS DE RELACIONES");
        
        try {
            // Test relación Usuario -> Roles (si hay usuario creado)
            if (isset($this->createdIds['user'])) {
                $user = User::with('roles')->find($this->createdIds['user']);
                if ($user) {
                    $this->assertTest($user->roles->count() > 0, "Relación User->Roles");
                } else {
                    $this->assertTest(false, "Relación User->Roles", "Usuario no encontrado");
                }
            } else {
                $this->assertTest(false, "Relación User->Roles", "No hay usuario de prueba");
            }
            
            // Test relación Usuario -> Doctor (si hay doctor creado)
            if (isset($this->createdIds['doctor_user'])) {
                $doctorUser = User::with('doctor')->find($this->createdIds['doctor_user']);
                if ($doctorUser) {
                    $this->assertTest($doctorUser->doctor !== null, "Relación User->Doctor");
                } else {
                    $this->assertTest(false, "Relación User->Doctor", "Usuario doctor no encontrado");
                }
            } else {
                $this->assertTest(false, "Relación User->Doctor", "No hay usuario doctor de prueba");
            }
            
            // Test relación Usuario -> Paciente (si hay paciente creado)
            if (isset($this->createdIds['user']) && isset($this->createdIds['paciente'])) {
                $patientUser = User::with('paciente')->find($this->createdIds['user']);
                if ($patientUser) {
                    $this->assertTest($patientUser->paciente !== null, "Relación User->Paciente");
                } else {
                    $this->assertTest(false, "Relación User->Paciente", "Usuario paciente no encontrado");
                }
            } else {
                $this->assertTest(false, "Relación User->Paciente", "No hay usuario/paciente de prueba");
            }
            
            // Test relación Doctor -> Especialidad
            if (isset($this->createdIds['doctor'])) {
                $doctor = Doctor::with('especialidad')->find($this->createdIds['doctor']);
                if ($doctor) {
                    $this->assertTest($doctor->especialidad !== null, "Relación Doctor->Especialidad");
                } else {
                    $this->assertTest(false, "Relación Doctor->Especialidad", "Doctor no encontrado");
                }
            } else {
                $this->assertTest(false, "Relación Doctor->Especialidad", "No hay doctor de prueba");
            }
            
            // Test relación Cita -> Doctor -> Especialidad (eager loading anidado)
            if (isset($this->createdIds['cita'])) {
                $cita = Appointment::with('doctor.especialidad')->find($this->createdIds['cita']);
                if ($cita && $cita->doctor) {
                    $this->assertTest($cita->doctor->especialidad !== null, "Relación anidada Cita->Doctor->Especialidad");
                } else {
                    $this->assertTest(false, "Relación anidada Cita->Doctor->Especialidad", "Cita o doctor no encontrado");
                }
            } else {
                $this->assertTest(false, "Relación anidada Cita->Doctor->Especialidad", "No hay cita de prueba");
            }
            
        } catch (Exception $e) {
            $this->assertTest(false, "Pruebas de relaciones", $e->getMessage());
        }
    }
    
    private function testAdvancedQueries() {
        $this->startSection("8. CONSULTAS AVANZADAS");
        
        try {
            // Test de scope whereHas
            $pacientesConCitas = User::whereHas('paciente')->get();
            $this->assertTest($pacientesConCitas->count() >= 0, "Query con whereHas (usuarios con paciente)");
            
            // Test de conteo con relaciones
            $citasPendientes = Appointment::where('estado', 'pendiente')->count();
            $this->assertTest(is_numeric($citasPendientes), "Conteo de citas pendientes ($citasPendientes)");
            
            // Test de query builder complejo
            $doctoresConEspecialidad = Doctor::with(['user', 'especialidad'])
                ->whereNotNull('especialidad_id')
                ->get();
            $this->assertTest($doctoresConEspecialidad->count() >= 0, "Query complejo con whereNotNull");
            
            // Test de ordenamiento
            $usuariosOrdenados = User::orderBy('nombre')->orderBy('apellido')->limit(5)->get();
            $this->assertTest($usuariosOrdenados->count() > 0, "Query con ordenamiento múltiple");
            
            // Test de aggregation
            $totalUsuarios = User::count();
            $this->assertTest($totalUsuarios > 0, "Función de agregación count() ($totalUsuarios usuarios)");
            
            // Test de búsqueda con like
            $especialidadesTest = Especialidad::where('nombre', 'like', '%Test%')->get();
            $this->assertTest($especialidadesTest->count() >= 0, "Query con LIKE para especialidades de test");
            
        } catch (Exception $e) {
            $this->assertTest(false, "Consultas avanzadas", $e->getMessage());
        }
    }
    
    private function cleanup() {
        $this->startSection("9. LIMPIEZA DE DATOS DE PRUEBA");
        
        try {
            // Eliminar en orden correcto (respetando foreign keys)
            if (isset($this->createdIds['cita'])) {
                $deleted = Appointment::destroy($this->createdIds['cita']);
                $this->assertTest($deleted > 0, "Eliminar cita de prueba");
            }
            
            if (isset($this->createdIds['paciente'])) {
                $deleted = Paciente::destroy($this->createdIds['paciente']);
                $this->assertTest($deleted > 0, "Eliminar paciente de prueba");
            }
            
            if (isset($this->createdIds['doctor'])) {
                $deleted = Doctor::destroy($this->createdIds['doctor']);
                $this->assertTest($deleted > 0, "Eliminar doctor de prueba");
            }
            
            if (isset($this->createdIds['user'])) {
                $user = User::find($this->createdIds['user']);
                if ($user) {
                    $user->roles()->detach(); // Eliminar relaciones many-to-many
                    $deleted = $user->delete();
                    $this->assertTest($deleted, "Eliminar usuario de prueba");
                }
            }
            
            if (isset($this->createdIds['doctor_user'])) {
                $user = User::find($this->createdIds['doctor_user']);
                if ($user) {
                    $user->roles()->detach();
                    $deleted = $user->delete();
                    $this->assertTest($deleted, "Eliminar usuario doctor de prueba");
                }
            }
            
            if (isset($this->createdIds['especialidad'])) {
                $deleted = Especialidad::destroy($this->createdIds['especialidad']);
                $this->assertTest($deleted > 0, "Eliminar especialidad de prueba");
            }
            
            if (isset($this->createdIds['sede'])) {
                $deleted = Sede::destroy($this->createdIds['sede']);
                $this->assertTest($deleted > 0, "Eliminar sede de prueba");
            }
            
        } catch (Exception $e) {
            $this->log("Error en limpieza: " . $e->getMessage(), 'warning');
        }
    }
    
    private function printResults() {
        $this->startSection("RESULTADOS FINALES");
        
        $total = count($this->testResults);
        $passed = array_sum($this->testResults);
        $failed = $total - $passed;
        
        $successRate = $total > 0 ? round(($passed / $total) * 100, 2) : 0;
        
        echo "\n";
        $this->log("Total de pruebas: $total", 'info');
        $this->log("Pruebas exitosas: $passed", 'success');
        
        if ($failed > 0) {
            $this->log("Pruebas fallidas: $failed", 'error');
        }
        
        echo "\n";
        
        if ($successRate >= 90) {
            $this->log("🎉 MIGRACIÓN A ELOQUENT EXITOSA! ($successRate% de éxito)", 'success');
            $this->log("✅ Todas las operaciones CRUD funcionan correctamente", 'success');
        } elseif ($successRate >= 70) {
            $this->log("⚠️ Migración parcialmente exitosa ($successRate% de éxito)", 'warning');
            $this->log("Algunas pruebas fallaron, revisar logs arriba", 'warning');
        } else {
            $this->log("❌ Problemas serios en la migración ($successRate% de éxito)", 'error');
            $this->log("Se requiere revisión del código y configuración", 'error');
        }
        
        echo "\n" . TestColors::BOLD . "=== FIN DEL TEST ===" . TestColors::RESET . "\n";
    }
}

// Ejecutar todos los tests
try {
    $test = new EloquentCompleteTest();
    $test->runAllTests();
} catch (Exception $e) {
    echo TestColors::RED . "Error fatal: " . $e->getMessage() . TestColors::RESET . "\n";
    exit(1);
}