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

        $pacientes = User::patients(null, 300);
        $doctores = User::doctors(null, 300);
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

        if (!Csrf::verify((string)($_POST['_csrf'] ?? ''))) {
            return $res->view('citas/create', [
                'error'=>'CSRF inválido',
                'pacientes'=>User::patients(null,300),
                'doctores'=>User::doctors(null,300),
                'sedes'=>Sede::getAll(),
                'especialidades'=>Especialidad::getAll(),
                'today'=>date('Y-m-d'),
            ]);
        }

        $pacienteId = (int)($_POST['paciente_id'] ?? 0);
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $sedeId = (int)($_POST['sede_id'] ?? 0);
        $fecha = (string)($_POST['fecha'] ?? '');
        $horaInicio = (string)($_POST['hora_inicio'] ?? '');
        $horaFin = (string)($_POST['hora_fin'] ?? '');
        $razon = trim((string)($_POST['razon'] ?? ''));

        if (!$pacienteId || !$doctorId || !$fecha || !$horaInicio || !$horaFin) {
            return $res->view('citas/create', [
                'error'=>'Datos incompletos',
                'pacientes'=>User::patients(null,300),
                'doctores'=>User::doctors(null,300),
                'sedes'=>Sede::getAll(),
                'especialidades'=>Especialidad::getAll(),
                'today'=>date('Y-m-d'),
            ]);
        }

        // Validaciones básicas
        $paciente = Paciente::findByUsuarioId($pacienteId);
        $doctor = Doctor::findByUsuarioId($doctorId);
        $sede = $sedeId > 0 ? Sede::find($sedeId) : null;
        
        if (!$paciente) return $res->view('citas/create', ['error'=>'Paciente no válido'] + self::bags());
        if (!$doctor) return $res->view('citas/create', ['error'=>'Doctor no válido'] + self::bags());
        if ($sedeId > 0 && !$sede) return $res->view('citas/create', ['error'=>'Sede no válida'] + self::bags());

        // Verificar solapamiento
        if (Appointment::overlapsWindow($fecha, $horaInicio, $horaFin, $doctor['id'], $sedeId)) {
            return $res->view('citas/create', ['error'=>'El horario ya no está disponible'] + self::bags());
        }

        Appointment::create($paciente['id'], $doctor['id'], $sedeId > 0 ? $sedeId : null, $fecha, $horaInicio, $horaFin, $razon);
        return $res->redirect('/citas');
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
        if (!in_array($st, ['pendiente','confirmado','cancelado'], true)) {
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
                return $res->abort(422,'Solo puedes cancelar hasta 24 horas antes.');
            }
        } else {
            // otros roles (si lo deseas) podrían cancelar sin restricción
            Appointment::updateStatus($id,'cancelado');
        }
        return $res->redirect('/citas');
    }

    private static function bags(): array {
        return [
            'pacientes'=>User::patients(null,300),
            'doctores'=>User::doctors(null,300),
            'sedes'=>Sede::getAll(),
            'especialidades'=>Especialidad::getAll(),
            'today'=>date('Y-m-d'),
            'title'=> 'Reservar cita'
        ];
    }
}