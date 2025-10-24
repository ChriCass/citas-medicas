<?php
namespace App\Controllers;

use App\Core\{Request, Response, Availability, Csrf, Auth};
use App\Models\{Appointment, User, Paciente, Doctor, Sede, Especialidad};

class AppointmentController
{
    public function index(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        
        $role = $user['rol'] ?? '';
        $appts = [];
        
        if ($role === 'paciente') { 
            $appts = Appointment::usercitas((int)$user['id']); 
        } elseif ($role === 'doctor') { 
            $doctor = Doctor::findByUsuarioId((int)$user['id']);
            if ($doctor) {
                $appts = Appointment::doctorcitas($doctor['id']);
            }
        } else { 
            $appts = Appointment::listAll(); 
        }

        return $res->view('citas/index', ['title'=>'Citas','appts'=>$appts,'user'=>$user]);
    }

    public function create(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']); // Solo superadmin crea

        // Obtener todos los pacientes usando Eloquent
        $pacientes = User::patients(null, 300);
        $doctores = Doctor::getAll();
        $sedes = Sede::getAll();
        $especialidades = Especialidad::getAll();
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        return $res->view('citas/create', compact('pacientes','doctores','sedes','especialidades','today') + ['title'=>'Reservar cita']);
    }

    public function store(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        // Verificar si es una cancelación
        if (isset($_POST['cancel'])) {
            return $res->redirect('/citas?cancel_creation=1');
        }

        if (!Csrf::verify((string)($_POST['_csrf'] ?? ''))) {
            return $res->view('citas/create', [
                'error'=>'CSRF inválido',
                'pacientes'=>User::patients(null,300),
                'doctores'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'especialidades'=>Especialidad::getAll(),
                'today'=>date('Y-m-d'),
            ]);
        }

        $pacienteUsuarioId = (int)($_POST['paciente_id'] ?? 0); // Este es el usuario_id del paciente o 0 para superadmin
        $doctorId = (int)($_POST['doctor_id'] ?? 0); // Este es el ID del doctor
        $sedeId = (int)($_POST['sede_id'] ?? 0);
        $date = (string)($_POST['date'] ?? '');
        $time = (string)($_POST['time'] ?? '');
        $notes = trim((string)($_POST['notes'] ?? ''));

        if (!$doctorId || !$date || !$time) {
            return $res->view('citas/create', [
                'error'=>'Datos incompletos',
                'pacientes'=>User::patients(null,300),
                'doctores'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'especialidades'=>Especialidad::getAll(),
                'today'=>date('Y-m-d'),
            ]);
        }

        // Si paciente_id es 0, la cita es para el superadmin
        $pacienteId = $pacienteUsuarioId;
        if ($pacienteUsuarioId > 0) {
            $paciente = Paciente::findByUsuarioId($pacienteUsuarioId);
            if (!$paciente) {
                return $res->view('citas/create', ['error'=>'Paciente no válido'] + self::bags());
            }
            $pacienteId = $paciente['id'];
        } else {
            // Para superadmin, crear un paciente temporal o usar uno existente
            $paciente = Paciente::findByUsuarioId((int)$user['id']);
            if (!$paciente) {
                return $res->view('citas/create', ['error'=>'No se pudo crear la cita para superadmin'] + self::bags());
            }
            $pacienteId = $paciente['id'];
        }

        // Validar doctor
        $doctorModel = new Doctor();
        $doctor = $doctorModel->find($doctorId);
        if (!$doctor) {
            return $res->view('citas/create', ['error'=>'Doctor no válido'] + self::bags());
        }

        // Validar sede si se proporciona
        $sedeModel = new Sede();
        $sede = $sedeId > 0 ? $sedeModel->find($sedeId) : null;
        if ($sedeId > 0 && !$sede) {
            return $res->view('citas/create', ['error'=>'Sede no válida'] + self::bags());
        }

        // Calcular hora de fin (15 minutos después)
        try {
            $startTime = new \DateTimeImmutable("$date $time");
            $endTime = $startTime->modify('+15 minutes');
            $horaInicio = $startTime->format('H:i:s');
            $horaFin = $endTime->format('H:i:s');
        } catch (\Exception $e) {
            return $res->view('citas/create', ['error'=>'Fecha u hora inválida'] + self::bags());
        }

        // Verificar solapamiento
        if (Appointment::overlapsWindow($date, $horaInicio, $horaFin, $doctorId, $sedeId)) {
            return $res->view('citas/create', ['error'=>'El horario ya no está disponible'] + self::bags());
        }

        $citaId = Appointment::createAppointment($pacienteId, $doctorId, $sedeId > 0 ? $sedeId : null, $date, $horaInicio, $horaFin, $notes);
        return $res->redirect('/citas?success=1');
    }

    /** El médico puede marcar su cita como 'atendido' */
    public function markAttended(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        if (($user['rol'] ?? '') !== 'doctor') return $res->abort(403,'Solo médicos');

        $id = (int)($req->params['id'] ?? 0);
        $doctor = Doctor::findByUsuarioId((int)$user['id']);
        if (!$doctor || !Appointment::belongsToDoctor($id, $doctor['id'])) {
            return $res->abort(403,'No autorizado');
        }
        Appointment::updateStatus($id,'atendido');
        return $res->redirect('/citas');
    }

    /** El cajero cambia pago (solo cuando está 'atendido') */
    public function updatePayment(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['cajero']);

        $id = (int)($req->params['id'] ?? 0);
        $p = (string)($_POST['payment_status'] ?? '');
        if (!Appointment::updatePayment($id, $p)) {
            return $res->abort(422, 'No es posible cambiar el pago (requiere estado atendida).');
        }
        return $res->redirect('/citas');
    }

    /** (Opcional) Cajero confirma/cancela; superadmin también puede */
    public function updateStatus(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        if (!in_array(($user['rol'] ?? ''), ['cajero','superadmin'], true)) {
            return $res->abort(403,'No autorizado');
        }

        $id = (int)($req->params['id'] ?? 0);
        $st = (string)($_POST['status'] ?? '');
        if (!in_array($st, ['pendiente','confirmado','atendido','cancelado'], true)) {
            return $res->abort(422,'Estado inválido');
        }
        Appointment::updateStatus($id, $st);
        return $res->redirect('/citas');
    }

    /** Paciente cancela respetando 24h */
    public function cancel(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        $role = $user['rol'] ?? '';
        $id = (int)($req->params['id'] ?? 0);

        if ($role === 'paciente') {
            if (!Appointment::cancelByPatient($id, (int)$user['id'])) {
                return $res->redirect('/citas?error=cancel_time');
            }
            return $res->redirect('/citas?canceled=1');
        } else {
            // otros roles (si lo deseas) podrían cancelar sin restricción
            Appointment::updateStatus($id,'cancelado');
            return $res->redirect('/citas?canceled=1');
        }
    }

    private static function bags(): array {
        return [
            'pacientes'=>User::patients(null,300),
            'doctores'=>Doctor::getAll(),
            'sedes'=>Sede::getAll(),
            'especialidades'=>Especialidad::getAll(),
            'today'=>date('Y-m-d'),
            'title'=> 'Reservar cita'
        ];
    }
    
    /** Mostrar formulario de modificación de cita */
    public function edit(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        
        $id = (int)($req->params['id'] ?? 0);
        $role = $user['rol'] ?? '';
        
        // Solo pacientes pueden modificar sus propias citas
        if ($role !== 'paciente') {
            return $res->redirect('/citas')->with('error', 'Solo los pacientes pueden modificar citas');
        }
        
        // Verificar que puede modificar la cita
        if (!Appointment::canModify($id, (int)$user['id'])) {
            return $res->redirect('/citas')->with('error', 'No se puede modificar esta cita. Debe ser futura y con al menos 24 horas de anticipación.');
        }
        
        // Obtener datos de la cita
        $appointment = new Appointment();
        $cita = $appointment->find($id);
        
        if (!$cita) {
            return $res->redirect('/citas')->with('error', 'Cita no encontrada');
        }
        
        // Obtener datos para el formulario
        $doctores = Doctor::getAll();
        $sedes = Sede::getAll();
        $especialidades = Especialidad::getAll();
        
        return $res->view('citas/edit', [
            'title' => 'Modificar Cita',
            'cita' => $cita,
            'doctores' => $doctores,
            'sedes' => $sedes,
            'especialidades' => $especialidades,
            'user' => $user
        ]);
    }
    
    /** Procesar modificación de cita */
    public function update(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        
        $id = (int)($req->params['id'] ?? 0);
        $role = $user['rol'] ?? '';
        
        // Solo pacientes pueden modificar sus propias citas
        if ($role !== 'paciente') {
            return $res->redirect('/citas')->with('error', 'Solo los pacientes pueden modificar citas');
        }
        
        // Obtener datos del formulario
        $doctorId = !empty($req->body['doctor_id']) ? (int)$req->body['doctor_id'] : null;
        $sedeId = !empty($req->body['sede_id']) ? (int)$req->body['sede_id'] : null;
        $fecha = !empty($req->body['fecha']) ? $req->body['fecha'] : null;
        $horaInicio = !empty($req->body['hora_inicio']) ? $req->body['hora_inicio'] : null;
        $horaFin = !empty($req->body['hora_fin']) ? $req->body['hora_fin'] : null;
        $razon = !empty($req->body['razon']) ? trim($req->body['razon']) : null;
        
        // Validaciones básicas
        if ($fecha && $horaInicio && $horaFin) {
            $fechaHora = $fecha . ' ' . $horaInicio;
            $fechaHoraObj = new \DateTimeImmutable($fechaHora);
            $now = new \DateTimeImmutable('now');
            
            if ($fechaHoraObj <= $now) {
                return $res->redirect("/citas/{$id}/edit")->with('error', 'La fecha y hora deben ser futuras');
            }
            
            if ($horaInicio >= $horaFin) {
                return $res->redirect("/citas/{$id}/edit")->with('error', 'La hora de inicio debe ser anterior a la hora de fin');
            }
        }
        
        // Intentar modificar la cita
        $success = Appointment::modifyAppointment(
            $id,
            (int)$user['id'],
            $doctorId,
            $sedeId,
            $fecha,
            $horaInicio,
            $horaFin,
            $razon
        );
        
        if ($success) {
            return $res->redirect('/citas')->with('success', 'Cita modificada exitosamente');
        } else {
            return $res->redirect("/citas/{$id}/edit")->with('error', 'No se pudo modificar la cita. Verifique la disponibilidad del horario seleccionado.');
        }
    }
}