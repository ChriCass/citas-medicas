<?php
namespace App\Controllers;

use App\Core\{Request, Response};
use App\Models\{User, Paciente, Appointment, Doctor, Especialidad, Sede};
use Illuminate\Database\Capsule\Manager as DB;

class PacienteController
{
    // Autenticación
    public function login(Request $req, Response $res)
    {
        // Leer datos del body JSON o form data
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) {
            $body = $_POST;
        }
        
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if (!$email || !$password) {
            return $res->json(['message' => 'Email y contraseña son requeridos'], 400);
        }

        try {
            $user = User::where('email', $email)->first();
            if (!$user || !$user->verifyPassword($password)) {
                return $res->json(['message' => 'Credenciales inválidas'], 401);
            }

            // Verificar que sea paciente
            if ($user->getRoleName() !== 'paciente') {
                return $res->json(['message' => 'Acceso denegado. Solo pacientes pueden usar esta API'], 403);
            }

            // Obtener información del paciente usando Eloquent
            $paciente = Paciente::where('usuario_id', $user->id)->first();
            if (!$paciente) {
                return $res->json(['message' => 'Registro de paciente no encontrado'], 404);
            }

            // Crear sesión
            $_SESSION['user'] = [
                'id' => (int)$user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'rol' => 'paciente',
                'paciente_id' => $paciente->id
            ];

            return $res->json([
                'message' => 'Login exitoso',
                'data' => [
                    'usuario_id' => (int)$user->id,
                    'paciente_id' => $paciente->id,
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'email' => $user->email,
                    'telefono' => $user->telefono ?? null,
                    'dni' => $user->dni ?? null
                ]
            ]);
        } catch (\Throwable $e) {
            return $res->json(['message' => 'Error en el servidor', 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $req, Response $res)
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'paciente') {
            return $res->json(['message' => 'No hay sesión activa'], 401);
        }

        $_SESSION = [];
        session_destroy();

        return $res->json(['message' => 'Logout exitoso']);
    }

    // Perfil
    public function getProfile(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['rol'] !== 'paciente') {
            return $res->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $paciente = Paciente::with('usuario')
                ->where('usuario_id', $user['id'])
                ->first();

            if (!$paciente) {
                return $res->json(['message' => 'Paciente no encontrado'], 404);
            }

            $usuario = $paciente->usuario;
            
            $data = [
                'id' => $paciente->id,
                'usuario_id' => $paciente->usuario_id,
                'nombre' => $usuario->nombre ?? null,
                'apellido' => $usuario->apellido ?? null,
                'email' => $usuario->email ?? null,
                'telefono' => $usuario->telefono ?? null,
                'dni' => $usuario->dni ?? null,
                'direccion' => $usuario->direccion ?? null,
                'fecha_nacimiento' => $usuario->fecha_nacimiento ?? null,
                'genero' => $usuario->genero ?? null,
                'numero_historia_clinica' => $paciente->numero_historia_clinica ?? null,
                'tipo_sangre' => $paciente->tipo_sangre ?? null,
                'alergias' => $paciente->alergias ?? null,
                'condicion_cronica' => $paciente->condicion_cronica ?? null,
                'historial_cirugias' => $paciente->historial_cirugias ?? null,
                'historico_familiar' => $paciente->historico_familiar ?? null,
                'observaciones' => $paciente->observaciones ?? null,
                'contacto_emergencia_nombre' => $paciente->contacto_emergencia_nombre ?? null,
                'contacto_emergencia_telefono' => $paciente->contacto_emergencia_telefono ?? null,
                'contacto_emergencia_relacion' => $paciente->contacto_emergencia_relacion ?? null,
            ];

            return $res->json(['data' => $data]);
        } catch (\Throwable $e) {
            return $res->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateProfile(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['rol'] !== 'paciente') {
            return $res->json(['message' => 'Unauthorized'], 401);
        }

        // Leer JSON del body
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) {
            return $res->json(['message' => 'Datos inválidos'], 400);
        }

        try {
            DB::beginTransaction();

            // Actualizar datos de usuario usando Eloquent
            $usuario = User::find($user['id']);
            if (!$usuario) {
                DB::rollBack();
                return $res->json(['message' => 'Usuario no encontrado'], 404);
            }

            $allowedUsuarioFields = ['telefono', 'direccion', 'fecha_nacimiento', 'genero'];
            foreach ($allowedUsuarioFields as $field) {
                if (isset($body[$field])) {
                    $usuario->$field = $body[$field];
                }
            }
            $usuario->save();

            // Actualizar datos de paciente usando Eloquent
            $paciente = Paciente::where('usuario_id', $user['id'])->first();
            if (!$paciente) {
                DB::rollBack();
                return $res->json(['message' => 'Paciente no encontrado'], 404);
            }

            $allowedPacienteFields = [
                'tipo_sangre', 'alergias', 'condicion_cronica', 'historial_cirugias',
                'historico_familiar', 'observaciones', 'contacto_emergencia_nombre',
                'contacto_emergencia_telefono', 'contacto_emergencia_relacion'
            ];

            foreach ($allowedPacienteFields as $field) {
                if (isset($body[$field])) {
                    $paciente->$field = $body[$field];
                }
            }
            $paciente->save();

            DB::commit();

            return $res->json(['message' => 'Perfil actualizado exitosamente']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $res->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    // Citas
    public function getAppointments(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['rol'] !== 'paciente') {
            return $res->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $appointments = Appointment::usercitas((int)$user['id']);
            return $res->json(['data' => $appointments]);
        } catch (\Throwable $e) {
            return $res->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    public function getUpcomingAppointments(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['rol'] !== 'paciente') {
            return $res->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $limit = (int)($req->query['limit'] ?? 5);
            $appointments = Appointment::upcomingForUser((int)$user['id'], $limit);
            return $res->json(['data' => $appointments]);
        } catch (\Throwable $e) {
            return $res->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAppointment(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['rol'] !== 'paciente') {
            return $res->json(['message' => 'Unauthorized'], 401);
        }

        $appointmentId = (int)($req->params['id'] ?? 0);
        if (!$appointmentId) {
            return $res->json(['message' => 'ID de cita inválido'], 400);
        }

        try {
            $appointment = Appointment::with(['paciente.usuario', 'doctor.usuario', 'doctor.especialidad', 'sede'])
                ->whereHas('paciente', function($query) use ($user) {
                    $query->where('usuario_id', $user['id']);
                })
                ->find($appointmentId);

            if (!$appointment) {
                return $res->json(['message' => 'Cita no encontrada'], 404);
            }

            $data = [
                'id' => $appointment->id,
                'fecha' => $appointment->fecha,
                'hora_inicio' => $appointment->hora_inicio,
                'hora_fin' => $appointment->hora_fin,
                'razon' => $appointment->razon,
                'estado' => $appointment->estado,
                'pago' => $appointment->pago,
                'paciente_nombre' => $appointment->paciente->usuario->nombre ?? null,
                'paciente_apellido' => $appointment->paciente->usuario->apellido ?? null,
                'doctor_nombre' => $appointment->doctor->usuario->nombre ?? null,
                'doctor_apellido' => $appointment->doctor->usuario->apellido ?? null,
                'especialidad_nombre' => $appointment->doctor->especialidad->nombre ?? null,
                'nombre_sede' => $appointment->sede->nombre_sede ?? null,
                'sede_direccion' => $appointment->sede->direccion ?? null,
                'sede_telefono' => $appointment->sede->telefono ?? null,
            ];

            return $res->json(['data' => $data]);
        } catch (\Throwable $e) {
            return $res->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    public function createAppointment(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['rol'] !== 'paciente') {
            return $res->json(['message' => 'Unauthorized'], 401);
        }

        // Leer JSON del body
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) {
            return $res->json(['message' => 'Datos inválidos'], 400);
        }

        $doctorId = (int)($body['doctor_id'] ?? 0);
        $sedeId = (int)($body['sede_id'] ?? 0);
        $date = (string)($body['date'] ?? '');
        $time = (string)($body['time'] ?? '');
        $notes = trim((string)($body['notes'] ?? ''));

        if (!$doctorId || !$date || !$time) {
            return $res->json(['message' => 'Datos incompletos (doctor_id, date, time requeridos)'], 400);
        }

        try {
            DB::beginTransaction();

            // Obtener paciente_id del usuario usando Eloquent
            $paciente = Paciente::where('usuario_id', $user['id'])->first();
            if (!$paciente) {
                DB::rollBack();
                return $res->json(['message' => 'Paciente no encontrado'], 404);
            }

            // Calcular hora de fin (15 minutos)
            $startTime = new \DateTimeImmutable("$date $time");
            $endTime = $startTime->modify('+15 minutes');
            $horaInicio = $startTime->format('H:i:s');
            $horaFin = $endTime->format('H:i:s');

            // Verificar disponibilidad
            if (Appointment::overlapsWindow($date, $horaInicio, $horaFin, $doctorId, $sedeId)) {
                DB::rollBack();
                return $res->json(['message' => 'El horario seleccionado no está disponible'], 409);
            }

            // Crear la cita usando Eloquent
            $appointment = new Appointment();
            $appointment->paciente_id = $paciente->id;
            $appointment->doctor_id = $doctorId;
            $appointment->sede_id = $sedeId > 0 ? $sedeId : null;
            $appointment->fecha = $date;
            $appointment->hora_inicio = $horaInicio;
            $appointment->hora_fin = $horaFin;
            $appointment->razon = $notes;
            $appointment->estado = 'pendiente';
            $appointment->pago = 'pendiente';
            $appointment->save();

            // Marcar slot como reservado si existe
            DB::table('slots_calendario')
                ->whereIn('calendario_id', function($query) use ($doctorId, $date) {
                    $query->select('id')
                        ->from('calendario')
                        ->where('doctor_id', $doctorId)
                        ->where('fecha', $date);
                })
                ->where('hora_inicio', $horaInicio)
                ->update([
                    'reservado_por_cita_id' => $appointment->id,
                    'disponible' => 0
                ]);

            DB::commit();

            return $res->json([
                'message' => 'Cita creada exitosamente',
                'data' => ['cita_id' => $appointment->id]
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $res->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    public function cancelAppointment(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['rol'] !== 'paciente') {
            return $res->json(['message' => 'Unauthorized'], 401);
        }

        $appointmentId = (int)($req->params['id'] ?? 0);
        if (!$appointmentId) {
            return $res->json(['message' => 'ID de cita inválido'], 400);
        }

        try {
            // Verificar que la cita pertenece al paciente usando Eloquent
            $appointment = Appointment::whereHas('paciente', function($query) use ($user) {
                    $query->where('usuario_id', $user['id']);
                })
                ->find($appointmentId);

            if (!$appointment) {
                return $res->json(['message' => 'Cita no encontrada'], 404);
            }

            if ($appointment->estado === 'cancelado') {
                return $res->json(['message' => 'La cita ya está cancelada'], 400);
            }

            if ($appointment->estado === 'atendido') {
                return $res->json(['message' => 'No se puede cancelar una cita ya atendida'], 400);
            }

            // Cancelar la cita
            $result = Appointment::updateStatus($appointmentId, 'cancelado');

            if ($result) {
                return $res->json(['message' => 'Cita cancelada exitosamente']);
            } else {
                return $res->json(['message' => 'Error al cancelar la cita'], 500);
            }
        } catch (\Throwable $e) {
            return $res->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    // Pagos
    public function getPayments(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['rol'] !== 'paciente') {
            return $res->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $payments = DB::table('pagos as pg')
                ->join('citas as c', 'pg.cita_id', '=', 'c.id')
                ->join('pacientes as p', 'c.paciente_id', '=', 'p.id')
                ->leftJoin('doctores as d', 'c.doctor_id', '=', 'd.id')
                ->leftJoin('usuarios as du', 'd.usuario_id', '=', 'du.id')
                ->leftJoin('especialidades as e', 'd.especialidad_id', '=', 'e.id')
                ->leftJoin('sedes as s', 'c.sede_id', '=', 's.id')
                ->where('p.usuario_id', $user['id'])
                ->select([
                    'pg.*',
                    'c.fecha as cita_fecha',
                    'c.hora_inicio',
                    'c.hora_fin',
                    'du.nombre as doctor_nombre',
                    'du.apellido as doctor_apellido',
                    'e.nombre as especialidad_nombre',
                    's.nombre_sede'
                ])
                ->orderBy('pg.fecha_pago', 'desc')
                ->get()
                ->toArray();

            return $res->json(['data' => $payments]);
        } catch (\Throwable $e) {
            return $res->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    public function register(Request $req, Response $res)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar campos requeridos
        $required = ['nombre', 'apellido', 'email', 'password', 'dni'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $res->json(['success' => false, 'message' => "El campo $field es requerido"], 400);
            }
        }
        
        // Verificar email duplicado
        $existing = User::where('email', $data['email'])->first();
        if ($existing) {
            return $res->json(['success' => false, 'message' => 'El email ya está registrado'], 400);
        }
        
        // Verificar DNI duplicado
        $existingDni = User::where('dni', $data['dni'])->first();
        if ($existingDni) {
            return $res->json(['success' => false, 'message' => 'El DNI ya está registrado'], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // 1. Crear usuario
            $usuario = new User();
            $usuario->nombre = $data['nombre'];
            $usuario->apellido = $data['apellido'];
            $usuario->email = $data['email'];
            $usuario->contrasenia = password_hash($data['password'], PASSWORD_DEFAULT);
            $usuario->dni = $data['dni'];
            $usuario->telefono = $data['telefono'];
            $usuario->direccion = $data['direccion'];
            $usuario->save();
            
            // 2. Crear paciente
            $paciente = new Paciente();
            $paciente->usuario_id = $usuario->id;
            $paciente->save();
            
            // 3. Generar número de historia clínica
            $hc = 'HC-' . $paciente->id . date('Ymd');
            $paciente->numero_historia_clinica = $hc;
            $paciente->save();
            
            // 4. Asignar rol de paciente (rol_id = 3)
            DB::table('tiene_roles')->insert([
                'usuario_id' => $usuario->id,
                'rol_id' => 3,
                'creado_en' => date('Y-m-d H:i:s')
            ]);
            
            DB::commit();
            
            return $res->json([
                'success' => true,
                'message' => 'Registro exitoso',
                'data' => [
                    'usuario_id' => $usuario->id,
                    'paciente_id' => $paciente->id,
                    'numero_historia_clinica' => $hc
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $res->json(['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()], 500);
        }
    }
}
