<?php
namespace App\Controllers;

use App\Core\{Request, Response, Csrf, Auth};
use App\Models\{DoctorSchedule, User, Location};

class DoctorScheduleController
{
    public function index(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        $schedules = DoctorSchedule::listAll();
        return $res->view('horarios_doctores/index', [
            'title'     => 'Horarios Doctores',
            'schedules' => $schedules,
            'user'      => $user,
        ]);
    }

    public function create(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        $doctors   = User::doctors(null, 500);
        $ubicaciones = Location::allActive();

        return $res->view('horarios_doctores/create', [
            'title'     => 'Nuevo Horario',
            'doctors'   => $doctors,
            'ubicaciones' => $ubicaciones,
        ]);
    }

    public function store(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        if (!Csrf::verify((string)($_POST['_csrf'] ?? ''))) {
            return $res->abort(419, 'CSRF inválido');
        }

        $doctorId   = (int)($_POST['doctor_id'] ?? 0);
        $locationId = (int)($_POST['location_id'] ?? 0);
        $weekday    = (int)($_POST['weekday'] ?? 0);   // 1=Lun .. 7=Dom
        $start      = trim((string)($_POST['start_time'] ?? ''));
        $end        = trim((string)($_POST['end_time'] ?? ''));

        // Validaciones simples
        if ($doctorId<=0 || $locationId<=0 || $weekday<1 || $weekday>7 || !$start || !$end) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'Completa todos los campos.',
                'doctors'=>User::doctors(null,500),
                'ubicaciones'=>\App\Models\Location::allActive(),
                'old'=>$_POST
            ]);
        }
        if (strtotime($start) >= strtotime($end)) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'La hora de inicio debe ser menor que la hora fin.',
                'doctors'=>User::doctors(null,500),
                'ubicaciones'=>\App\Models\Location::allActive(),
                'old'=>$_POST
            ]);
        }

        // Evitar traslapes en doctor+sede+día
        if (DoctorSchedule::overlaps($doctorId, $locationId, $weekday, $start, $end)) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'Este rango se solapa con otro horario existente para ese doctor/sede/día.',
                'doctors'=>User::doctors(null,500),
                'ubicaciones'=>\App\Models\Location::allActive(),
                'old'=>$_POST
            ]);
        }

        DoctorSchedule::create($doctorId, $locationId, $weekday, $start, $end, 1);
        return $res->redirect('/doctor-schedules');
    }

    public function destroy(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        if (!Csrf::verify((string)($_POST['_csrf'] ?? ''))) {
            return $res->abort(419, 'CSRF inválido');
        }

        $id = (int)($req->params['id'] ?? 0);
        if ($id>0) DoctorSchedule::delete($id);
        return $res->redirect('/doctor-schedules');
    }
}
