<?php
namespace App\Controllers;

use App\Core\{Request, Response, Availability, Csrf, Auth};
use App\Models\{Appointment, User, Paciente, Doctor, Sede, Especialidad};
use App\Models\Consulta;
use App\Models\Receta;
use App\Models\Medicamento;
use App\Models\Diagnostico as DiagnosticoModel;
use Illuminate\Database\Capsule\Manager as DB;

class AppointmentController
{
    public function index(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        
        $role = $user['rol'] ?? '';
        $appts = [];

        // Detect 'today' either via query param (?today=1) or via path '/citas/today'
        $todayParam = false;
        $q = $req->query ?? [];
        if (isset($q['today'])) {
            $raw = (string)$q['today'];
            $todayParam = in_array(strtolower($raw), ['1','true','yes'], true);
        }
        // Also accept route-based signal: if path is '/citas/today' consider it as today
        $reqPath = $req->uri ?? $_SERVER['REQUEST_URI'] ?? '';
        $reqPath = parse_url($reqPath, PHP_URL_PATH) ?: '/';
        if (rtrim($reqPath, '/') === '/citas/today') {
            $todayParam = true;
        }

        if ($role === 'paciente') {
            $appts = Appointment::usercitas((int)$user['id']);
        } elseif ($role === 'doctor') {
            $doctor = Doctor::findByUsuarioId((int)$user['id']);
            if ($doctor) {
                if ($todayParam) {
                    // Only this doctor's appointments for today
                    $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
                    $appts = Appointment::with([
                        'paciente.user', 
                        'doctor.user', 
                        'doctor.especialidad', 
                        'sede'
                    ])->where('doctor_id', $doctor['id'])
                      ->where('fecha', $today)
                      ->orderBy('hora_inicio', 'asc')
                      ->get();
                } else {
                    $appts = Appointment::doctorcitas($doctor['id']);
                }
            }
        } else {
            $appts = Appointment::listAll();
        }

        $title = $todayParam && $role === 'doctor' ? 'Mis citas del día' : 'Citas';
        if ($todayParam && $role === 'doctor') {
            return $res->view('citas/today', ['title'=>$title,'appts'=>$appts,'user'=>$user]);
        }
        return $res->view('citas/index', ['title'=>$title,'appts'=>$appts,'user'=>$user]);
    }

    public function create(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']); // Solo superadmin crea

        $pacientes = User::patients(null, 300);
        $doctores = Doctor::getAll(); // Cambiado a usar Doctor::getAll() que devuelve doctores con sus IDs correctos
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
        $doctor = Doctor::find($doctorId);
        if (!$doctor) {
            return $res->view('citas/create', ['error'=>'Doctor no válido'] + self::bags());
        }

        // Validar sede si se proporciona
        $sede = $sedeId > 0 ? Sede::find($sedeId) : null;
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

        Appointment::create($pacienteId, $doctorId, $sedeId > 0 ? $sedeId : null, $date, $horaInicio, $horaFin, $notes);
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
    return $res->redirect('/citas/today');
    }

    /** El médico puede marcar su cita como 'ausente' cuando el paciente no se presentó */
    public function markAbsent(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->redirect('/login');
        if (($user['rol'] ?? '') !== 'doctor') return $res->abort(403,'Solo médicos');

        $id = (int)($req->params['id'] ?? 0);
        $doctor = Doctor::findByUsuarioId((int)$user['id']);
        if (!$doctor || !Appointment::belongsToDoctor($id, $doctor['id'])) {
            return $res->abort(403,'No autorizado');
        }

        // Marcar como ausente
        $cita = Appointment::find($id);
        if ($cita) {
            $cita->estado = 'ausente';
            $cita->save();
        }
        return $res->redirect('/citas/today');
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

    /** Mostrar formulario para atender una cita (médico) */
    public function attendForm(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->redirect('/login');
        if (($user['rol'] ?? '') !== 'doctor') return $res->abort(403,'Solo médicos');

        $id = (int)($req->params['id'] ?? 0);
        $doctor = Doctor::findByUsuarioId((int)$user['id']);
        if (!$doctor || !Appointment::belongsToDoctor($id, $doctor['id'])) {
            return $res->abort(403,'No autorizado');
        }

        $appointment = Appointment::with(['paciente.user'])->find($id);
        if (!$appointment) {
            return $res->abort(404,'Cita no encontrada');
        }

        // Cargar consulta existente si ya existe
        $consulta = Consulta::findByCitaId($id);

        // Intentar cargar lista de diagnosticos para la vista (id + nombre_enfermedad)
        $diagnosticos = [];
        try {
            $diagnosticos = \App\Models\Diagnostico::search('');
        } catch (\Throwable $e) {
            $diagnosticos = [];
        }

        // Cargar lista de medicamentos para el select de recetas
        $medicamentos = [];
        try {
            $medicamentos = \App\Models\Medicamento::all()->toArray();
        } catch (\Throwable $e) {
            $medicamentos = [];
        }

        // Cargar recetas existentes para la consulta (si existe)
        $recetas = [];
        try {
            if ($consulta) {
                $recetas = \App\Models\Receta::where('consulta_id', $consulta->id)->with('medicamento')->get()->toArray();
            }
        } catch (\Throwable $e) {
            $recetas = [];
        }

        return $res->view('citas/attend', [
            'title' => 'Atender cita',
            'appointment' => $appointment,
            'consulta' => $consulta,
            'diagnosticos' => $diagnosticos,
            'medicamentos' => $medicamentos,
            'recetas' => $recetas
        ]);
    }

    /** Procesar el formulario de atención (guardar diagnóstico, receta, estado_postconsulta y marcar cita) */
    public function attendStore(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->redirect('/login');
        if (($user['rol'] ?? '') !== 'doctor') return $res->abort(403,'Solo médicos');

        $id = (int)($req->params['id'] ?? 0);
        $doctor = Doctor::findByUsuarioId((int)$user['id']);
        if (!$doctor || !Appointment::belongsToDoctor($id, $doctor['id'])) {
            return $res->abort(403,'No autorizado');
        }

    $diagnosticoText = trim((string)($_POST['diagnostico'] ?? ''));
    $diagnosticoId = (int)($_POST['diagnostico_id'] ?? 0);
    $observaciones = trim((string)($_POST['observaciones'] ?? ''));
    // recetas expected as array: $_POST['recetas'][0]['id_medicamento'] etc.
    // Accept either the legacy array input or a JSON payload built client-side
    $recetasInput = [];
    if (!empty($_POST['recetas_payload'])) {
        $raw = $_POST['recetas_payload'];
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $recetasInput = $decoded;
        } else {
            // if invalid JSON, fallback to old format
            $recetasInput = $_POST['recetas'] ?? [];
        }
    } else {
        $recetasInput = $_POST['recetas'] ?? [];
    }
    $estadoPost = trim((string)($_POST['estado_postconsulta'] ?? ''));
    $marcar = trim((string)($_POST['marcar_estado'] ?? ''));

        // Validaciones mínimas
    // Si el médico no marca 'ausente' se requiere seleccionar un diagnóstico existente
    if ($marcar !== 'ausente') {
            if ($diagnosticoId <= 0 || $estadoPost === '') {
                return $res->view('citas/attend', [
                    'error' => 'Debe seleccionar un diagnóstico existente y completar el estado postconsulta. No está permitido crear diagnósticos nuevos desde este formulario.',
                    'appointment' => Appointment::with(['paciente.user'])->find($id),
                    'consulta' => Consulta::findByCitaId($id),
                    'diagnosticos' => \App\Models\Diagnostico::search(''),
                    'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                    'recetas' => [] ,
                    'title' => 'Atender cita'
                ]);
            }
            // Verificar que el diagnóstico exista en la base de datos
            $exists = DB::table('diagnosticos')->where('id', $diagnosticoId)->exists();
            if (!$exists) {
                return $res->view('citas/attend', [
                    'error' => 'Diagnóstico seleccionado no existe. Por favor selecciona uno existente.',
                    'appointment' => Appointment::with(['paciente.user'])->find($id),
                    'consulta' => Consulta::findByCitaId($id),
                    'diagnosticos' => \App\Models\Diagnostico::search(''),
                    'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                    'recetas' => [] ,
                    'title' => 'Atender cita'
                ]);
            }
        }

        // Mapeo de estado_postconsulta a valores permitidos en la BD (según el script)
        $allowedPost = ['No problemático','Pasivo','Problemático'];
        if (!in_array($estadoPost, $allowedPost, true)) {
            return $res->abort(422, 'Estado postconsulta inválido');
        }

        // Usar transacción para actualizar consultas y citas
        try {
            DB::beginTransaction();

            // Usar el diagnóstico seleccionado (no se permiten creaciones desde este formulario)
            $diagId = $diagnosticoId > 0 ? $diagnosticoId : null;

            // Insertar o actualizar registro en 'consultas' (campo 'receta' ya no existe)
            $consulta = Consulta::findByCitaId($id);
            if ($consulta) {
                $consulta->diagnostico_id = $diagId;
                $consulta->observaciones = $observaciones;
                $consulta->estado_postconsulta = $estadoPost;
                $consulta->save();
            } else {
                $c = new Consulta();
                $c->cita_id = $id;
                $c->diagnostico_id = $diagId;
                $c->observaciones = $observaciones;
                $c->estado_postconsulta = $estadoPost;
                $c->save();
            }

            // Persistir recetas por diff: UPDATE existing by id, INSERT new, DELETE those removed
            $consultaId = ($consulta ?? $c)->id;
            $incomingIds = [];

            if (is_array($recetasInput)) {
                foreach ($recetasInput as $r) {
                    if (!is_array($r)) continue;
                    $rid = isset($r['id']) && trim((string)$r['id']) !== '' ? (int)$r['id'] : 0;
                    $mid = isset($r['id_medicamento']) && trim((string)$r['id_medicamento']) !== '' ? (int)$r['id_medicamento'] : null;
                    $indic = trim((string)($r['indicacion'] ?? ''));
                    $dur = trim((string)($r['duracion'] ?? ''));

                    // ignorar filas totalmente vacías
                    if ($rid === 0 && $mid === null && $indic === '' && $dur === '') continue;

                    if ($rid > 0) {
                        // actualizar sólo si la receta pertenece a esta consulta (evitar manipulación)
                        $existing = Receta::find($rid);
                        if ($existing && (int)$existing->consulta_id === (int)$consultaId) {
                            $existing->id_medicamento = $mid;
                            $existing->indicacion = $indic;
                            $existing->duracion = $dur;
                            $existing->save();
                            $incomingIds[] = $existing->id;
                            continue;
                        }
                        // si id inválido o no pertenece a esta consulta, tratamos como nueva inserción
                        $rid = 0;
                    }

                    if ($rid === 0) {
                        $new = new Receta();
                        $new->consulta_id = $consultaId;
                        $new->id_medicamento = $mid;
                        $new->indicacion = $indic;
                        $new->duracion = $dur;
                        $new->save();
                        $incomingIds[] = $new->id;
                    }
                }

                // Borrar sólo las recetas de esta consulta que NO aparezcan en $incomingIds
                if (count($incomingIds) > 0) {
                    Receta::where('consulta_id', $consultaId)
                         ->whereNotIn('id', $incomingIds)
                         ->delete();
                } else {
                    // si no llegaron recetas -> eliminar todas las de la consulta
                    Receta::where('consulta_id', $consultaId)->delete();
                }
            }

            // Actualizar estado de la cita según el botón seleccionado
            // Si el médico indica que no llegó el paciente, marcar como 'ausente'
            if ($marcar === 'ausente') {
                $cita = Appointment::find($id);
                if ($cita) {
                    $cita->estado = 'ausente';
                    $cita->save();
                }
            } else {
                $cita = Appointment::find($id);
                if ($cita) {
                    $cita->estado = 'atendido';
                    $cita->save();
                }
            }

            DB::commit();
            return $res->redirect('/citas/today');
        } catch (\Exception $e) {
            DB::rollBack();
            return $res->view('citas/attend', [
                'error' => 'Error al guardar la consulta: ' . $e->getMessage(),
                'appointment' => Appointment::with(['paciente.user'])->find($id),
                'consulta' => Consulta::findByCitaId($id),
                'diagnosticos' => \App\Models\Diagnostico::search(''),
                'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                'recetas' => \App\Models\Receta::where('consulta_id', Consulta::findByCitaId($id)?->id ?? 0)->with('medicamento')->get()->toArray(),
                'title' => 'Atender cita'
            ]);
        }
    }

    /** Mostrar formulario para editar una cita (médico) */
    public function editForm(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->redirect('/login');
        if (($user['rol'] ?? '') !== 'doctor') return $res->abort(403,'Solo médicos');

        $id = (int)($req->params['id'] ?? 0);
        $doctor = Doctor::findByUsuarioId((int)$user['id']);
        if (!$doctor || !Appointment::belongsToDoctor($id, $doctor['id'])) {
            return $res->abort(403,'No autorizado');
        }

        $appointment = Appointment::with(['paciente.user'])->find($id);
        if (!$appointment) {
            return $res->abort(404,'Cita no encontrada');
        }

        // Cargar consulta existente si ya existe
        $consulta = Consulta::findByCitaId($id);

        // Diagnósticos y medicamentos para los controles
        $diagnosticos = [];
        try { $diagnosticos = \App\Models\Diagnostico::search(''); } catch (\Throwable $e) { $diagnosticos = []; }
        $medicamentos = [];
        try { $medicamentos = \App\Models\Medicamento::all()->toArray(); } catch (\Throwable $e) { $medicamentos = []; }

        $recetas = [];
        try {
            if ($consulta) {
                $recetas = \App\Models\Receta::where('consulta_id', $consulta->id)->with('medicamento')->get()->toArray();
            }
        } catch (\Throwable $e) { $recetas = []; }

        // Normalizar a arrays para que la vista pueda usar acceso por índice
        $appointmentArr = (is_object($appointment) && method_exists($appointment, 'toArray')) ? $appointment->toArray() : $appointment;
        $consultaArr = (is_object($consulta) && method_exists($consulta, 'toArray')) ? $consulta->toArray() : $consulta;

        // Si existe diagnostico_id en la consulta, intentar cargar su nombre para mostrarlo en la vista
        try {
            if (is_array($consultaArr) && !empty($consultaArr['diagnostico_id'])) {
                $diagId = (int)$consultaArr['diagnostico_id'];
                $diag = DB::table('diagnosticos')->where('id', $diagId)->first();
                if ($diag) {
                    // $diag puede ser stdClass desde PDO
                    $consultaArr['diagnostico_nombre'] = $diag->nombre_enfermedad ?? ($diag->nombre ?? null);
                }
            }
        } catch (\Throwable $e) {
            // no crítico: si falla, la vista seguirá mostrando el campo vacío
        }

        return $res->view('citas/edit', [
            'title' => 'Editar cita',
            'appointment' => $appointmentArr,
            'consulta' => $consultaArr,
            'diagnosticos' => $diagnosticos,
            'medicamentos' => $medicamentos,
            'recetas' => $recetas
        ]);
    }

    /** Procesar formulario de edición: persistir cambios en consultas y recetas (solo médico) */
    public function editStore(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->redirect('/login');
        if (($user['rol'] ?? '') !== 'doctor') return $res->abort(403,'Solo médicos');

        $id = (int)($req->params['id'] ?? 0);
        $doctor = Doctor::findByUsuarioId((int)$user['id']);
        if (!$doctor || !Appointment::belongsToDoctor($id, $doctor['id'])) {
            return $res->abort(403,'No autorizado');
        }

        $diagnosticoId = (int)($_POST['diagnostico_id'] ?? 0);
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));
        $recetasInput = [];
        if (!empty($_POST['recetas_payload'])) {
            $raw = $_POST['recetas_payload'];
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $recetasInput = $decoded;
            } else {
                $recetasInput = $_POST['recetas'] ?? [];
            }
        } else {
            $recetasInput = $_POST['recetas'] ?? [];
        }

        $estadoPost = trim((string)($_POST['estado_postconsulta'] ?? ''));

        // Validaciones mínimas
        $allowedPost = ['No problemático','Pasivo','Problemático'];
        if ($diagnosticoId <= 0) {
            return $res->view('citas/edit', [
                'error' => 'Debe seleccionar un diagnóstico existente.',
                'appointment' => Appointment::with(['paciente.user'])->find($id),
                'consulta' => Consulta::findByCitaId($id),
                'diagnosticos' => \App\Models\Diagnostico::search(''),
                'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                'recetas' => [] ,
                'title' => 'Editar cita'
            ]);
        }
        if (!in_array($estadoPost, $allowedPost, true)) {
            return $res->view('citas/edit', [
                'error' => 'Estado postconsulta inválido',
                'appointment' => Appointment::with(['paciente.user'])->find($id),
                'consulta' => Consulta::findByCitaId($id),
                'diagnosticos' => \App\Models\Diagnostico::search(''),
                'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                'recetas' => [] ,
                'title' => 'Editar cita'
            ]);
        }

        try {
            DB::beginTransaction();

            $diagId = $diagnosticoId > 0 ? $diagnosticoId : null;

            $consulta = Consulta::findByCitaId($id);
            if ($consulta) {
                $consulta->diagnostico_id = $diagId;
                $consulta->observaciones = $observaciones;
                $consulta->estado_postconsulta = $estadoPost;
                $consulta->save();
            } else {
                $c = new Consulta();
                $c->cita_id = $id;
                $c->diagnostico_id = $diagId;
                $c->observaciones = $observaciones;
                $c->estado_postconsulta = $estadoPost;
                $c->save();
            }

            $consultaId = ($consulta ?? $c)->id;
            $incomingIds = [];

            if (is_array($recetasInput)) {
                foreach ($recetasInput as $r) {
                    if (!is_array($r)) continue;
                    $rid = isset($r['id']) && trim((string)$r['id']) !== '' ? (int)$r['id'] : 0;
                    $mid = isset($r['id_medicamento']) && trim((string)$r['id_medicamento']) !== '' ? (int)$r['id_medicamento'] : null;
                    $indic = trim((string)($r['indicacion'] ?? ''));
                    $dur = trim((string)($r['duracion'] ?? ''));

                    if ($rid === 0 && $mid === null && $indic === '' && $dur === '') continue;

                    if ($rid > 0) {
                        $existing = Receta::find($rid);
                        if ($existing && (int)$existing->consulta_id === (int)$consultaId) {
                            $existing->id_medicamento = $mid;
                            $existing->indicacion = $indic;
                            $existing->duracion = $dur;
                            $existing->save();
                            $incomingIds[] = $existing->id;
                            continue;
                        }
                        $rid = 0;
                    }

                    if ($rid === 0) {
                        $new = new Receta();
                        $new->consulta_id = $consultaId;
                        $new->id_medicamento = $mid;
                        $new->indicacion = $indic;
                        $new->duracion = $dur;
                        $new->save();
                        $incomingIds[] = $new->id;
                    }
                }

                if (count($incomingIds) > 0) {
                    Receta::where('consulta_id', $consultaId)
                         ->whereNotIn('id', $incomingIds)
                         ->delete();
                } else {
                    Receta::where('consulta_id', $consultaId)->delete();
                }
            }

            DB::commit();
            return $res->redirect('/citas/today');
        } catch (\Exception $e) {
            DB::rollBack();
            return $res->view('citas/edit', [
                'error' => 'Error al guardar los cambios: ' . $e->getMessage(),
                'appointment' => Appointment::with(['paciente.user'])->find($id),
                'consulta' => Consulta::findByCitaId($id),
                'diagnosticos' => \App\Models\Diagnostico::search(''),
                'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                'recetas' => \App\Models\Receta::where('consulta_id', Consulta::findByCitaId($id)?->id ?? 0)->with('medicamento')->get()->toArray(),
                'title' => 'Editar cita'
            ]);
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
}