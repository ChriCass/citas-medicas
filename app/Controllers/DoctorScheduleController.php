<?php
namespace App\Controllers;

use App\Core\{Request, Response, Csrf, Auth};
use App\Models\{DoctorSchedule, Doctor, Sede};

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

        $doctors   = Doctor::getAll();
        $sedes = Sede::getAll();

        return $res->view('horarios_doctores/create', [
            'title'     => 'Nuevo Horario',
            'doctors'   => $doctors,
            'sedes' => $sedes,
        ]);
    }

    public function store(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        if (!Csrf::verify((string)($_POST['_csrf'] ?? ''))) {
            return $res->abort(419, 'CSRF inválido');
        }

        $doctorId   = (int)($_POST['doctor_id'] ?? 0);   // id de doctores
        $sedeId     = (int)($_POST['sede_id'] ?? 0);     // id de sedes (nullable)
        $weekdayIn  = (int)($_POST['weekday'] ?? 0);     // 1=Lun .. 7=Dom (UI)
        $start      = trim((string)($_POST['start_time'] ?? ''));
        $end        = trim((string)($_POST['end_time'] ?? ''));

        // Validaciones simples
        if ($doctorId<=0 || $weekdayIn<1 || $weekdayIn>7 || !$start || !$end) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'Completa todos los campos.',
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }
        if (strtotime($start) >= strtotime($end)) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'La hora de inicio debe ser menor que la hora fin.',
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        // Mapear weekday 1..7 (L..D) a 0..6 (D..S) del esquema: 7->0, 1->1, ... 6->6
        $weekday = $weekdayIn % 7; // 7 -> 0

        // Evitar traslapes en doctor+sede+día
        if (DoctorSchedule::overlaps($doctorId, $sedeId, $weekday, $start, $end)) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'Este rango se solapa con otro horario existente para ese doctor/sede/día.',
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        DoctorSchedule::create($doctorId, $sedeId, $weekday, $start, $end, 1);
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
