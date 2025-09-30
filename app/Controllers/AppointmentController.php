<?php
namespace App\Controllers;

use App\Core\{Request, Response, Availability, Csrf, Auth};
use App\Models\{Appointment, Service, User, Location};

class AppointmentController
{
    public function index(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        $role = $user['role'] ?? '';
        if     ($role === 'patient') { $appts = Appointment::usercitas((int)$user['id']); }
        elseif ($role === 'doctor')  { $appts = Appointment::doctorcitas((int)$user['id']); }
        else                          { $appts = Appointment::listAll(); }

        return $res->view('citas/index', ['title'=>'Citas','appts'=>$appts,'user'=>$user]);
    }

    public function create(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']); // Solo superadmin crea

        $servicios  = Service::allActive();
        $patients  = User::patients(null, 300);
        $doctors   = User::doctors(null, 300);
        $ubicaciones = Location::allActive();
        $today     = (new \DateTimeImmutable('today'))->format('Y-m-d');

        return $res->view('citas/create', compact('servicios','patients','doctors','ubicaciones','today') + ['title'=>'Reservar cita']);
    }

    public function store(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        if (!Csrf::verify((string)($_POST['_csrf'] ?? ''))) {
            return $res->view('citas/create', [
                'error'=>'CSRF inválido',
                'servicios'=>Service::allActive(),
                'patients'=>User::patients(null,300),
                'doctors'=>User::doctors(null,300),
                'ubicaciones'=>Location::allActive(),
                'today'=>date('Y-m-d'),
            ]);
        }

        $serviceId = (int)($_POST['service_id'] ?? 0);
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $doctorId  = (int)($_POST['doctor_id'] ?? 0);
        $locationId= (int)($_POST['location_id'] ?? 0);
        $date      = (string)($_POST['date'] ?? '');
        $time      = (string)($_POST['time'] ?? '');
        $notes     = trim((string)($_POST['notes'] ?? ''));

        $service = Service::find($serviceId);
        if (!$service || !$doctorId || !$locationId || !$date || !$time) {
            return $res->view('citas/create', [
                'error'=>'Datos incompletos',
                'servicios'=>Service::allActive(),
                'patients'=>User::patients(null,300),
                'doctors'=>User::doctors(null,300),
                'ubicaciones'=>Location::allActive(),
                'today'=>date('Y-m-d'),
            ]);
        }

        // Paciente por defecto: el super
        if ($patientId <= 0) $patientId = (int)$user['id'];

        // Validaciones básicas de roles
        $pat = User::findById($patientId);
        $doc = User::findById($doctorId);
        $loc = Location::find($locationId);
        if (!$pat || ($pat['role'] ?? '')!=='patient') return $res->view('citas/create', ['error'=>'Paciente no válido'] + self::bags());
        if (!$doc || ($doc['role'] ?? '')!=='doctor')  return $res->view('citas/create', ['error'=>'Doctor no válido'] + self::bags());
        if (!$loc)                                      return $res->view('citas/create', ['error'=>'Sede no válida']    + self::bags());

        // Citas SIEMPRE de 15 minutos
        $start = new \DateTimeImmutable("$date $time:00");
        $end   = $start->modify('+'.Availability::SLOT_MINUTES.' minutes');

        if (Appointment::overlapsWindow($start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $doctorId, $locationId)) {
            return $res->view('citas/create', ['error'=>'El horario ya no está disponible'] + self::bags());
        }

        Appointment::create($patientId, $serviceId, $doctorId, $locationId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $notes);
        return $res->redirect('/citas');
    }

    /** El médico puede marcar su cita como 'attended' */
    public function markAttended(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        if (($user['role'] ?? '') !== 'doctor') return $res->abort(403,'Solo médicos');

        $id = (int)($req->params['id'] ?? 0);
        if (!Appointment::belongsToDoctor($id,(int)$user['id'])) return $res->abort(403,'No autorizado');
        Appointment::updateStatus($id,'attended');
        return $res->redirect('/citas');
    }

    /** El cajero cambia pago (solo cuando está 'attended') */
    public function updatePayment(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['cashier']);

        $id = (int)($req->params['id'] ?? 0);
        $p  = (string)($_POST['payment_status'] ?? '');
        if (!Appointment::updatePayment($id, $p)) return $res->abort(422, 'No es posible cambiar el pago (requiere estado atendida).');
        return $res->redirect('/citas');
    }

    /** (Opcional) Cajero confirma/cancela; superadmin también puede */
    public function updateStatus(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        if (!in_array(($user['role'] ?? ''), ['cashier','superadmin'], true)) return $res->abort(403,'No autorizado');

        $id = (int)($req->params['id'] ?? 0);
        $st = (string)($_POST['status'] ?? '');
        if (!in_array($st, ['pending','confirmed','cancelled'], true)) return $res->abort(422,'Estado inválido');
        Appointment::updateStatus($id, $st);
        return $res->redirect('/citas');
    }

    /** Paciente cancela respetando 24h */
    public function cancel(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        $role = $user['role'] ?? '';
        $id   = (int)($req->params['id'] ?? 0);

        if ($role === 'patient') {
            if (!Appointment::cancelByPatient($id,(int)$user['id'])) {
                return $res->abort(422,'Solo puedes cancelar hasta 24 horas antes.');
            }
        } else {
            // otros roles (si lo deseas) podrían cancelar sin restricción
            Appointment::updateStatus($id,'cancelled');
        }
        return $res->redirect('/citas');
    }

    private static function bags(): array {
        return [
            'servicios'=>\App\Models\Service::allActive(),
            'patients'=>\App\Models\User::patients(null,300),
            'doctors'=>\App\Models\User::doctors(null,300),
            'ubicaciones'=>\App\Models\Location::allActive(),
            'today'=>date('Y-m-d'),
            'title'=> 'Reservar cita'
        ];
    }
}
