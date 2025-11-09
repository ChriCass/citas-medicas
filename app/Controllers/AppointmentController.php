<?php
namespace App\Controllers;

use App\Core\{Request, Response, Availability, Csrf, Auth};
use App\Models\{Appointment, User, Paciente, Doctor, Sede, Especialidad, Consulta, Receta, Medicamento, Diagnostico as DiagnosticoModel};
use Illuminate\Database\Capsule\Manager as DB;

class AppointmentController
{
    public function index(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; 
        if (!$user) return $res->redirect('/login');
        
        $role = $user['rol'] ?? '';
        $appts = [];
        
        $todayParam = false;
        $q = $req->query ?? [];
        if (isset($q['today'])) {
            $raw = (string)$q['today'];
            $todayParam = in_array(strtolower($raw), ['1','true','yes'], true);
        }

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
                    $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
                    $appts = Appointment::doctorCitasToday($doctor['id'], $today);
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
        // if (Appointment::overlapsWindow($date, $horaInicio, $horaFin, $doctorId, $sedeId)) {
        //     return $res->view('citas/create', ['error'=>'El horario ya no está disponible'] + self::bags());
        // }

        // $citaId = Appointment::createAppointment($pacienteId, $doctorId, $sedeId > 0 ? $sedeId : null, $date, $horaInicio, $horaFin, $notes);
        // return $res->redirect('/citas?success=1');

        // Si el frontend envía calendario_id lo usamos para reservar el slot en la misma operación
        $calendarioId = isset($_POST['calendario_id']) ? (int)$_POST['calendario_id'] : null;

        try {
            Appointment::create($pacienteId, $doctorId, $sedeId > 0 ? $sedeId : null, $date, $horaInicio, $horaFin, $notes, $calendarioId, $time);
            return $res->redirect('/citas');
        } catch (\Throwable $e) {
            // Si no fue posible reservar el slot (otro proceso lo reservó), mostrar mensaje amigable
            $msg = $e->getMessage();
            if (stripos($msg, 'reserv') !== false) {
                $err = 'El horario ya no está disponible';
            } else {
                $err = 'Error al crear la cita';
            }
            return $res->view('citas/create', ['error'=>$err] + self::bags());
        }

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
        
        // Obtener datos de la cita (usar Eloquent find y convertir a array para compatibilidad con las vistas)
        $citaModel = Appointment::find($id);
        if (!$citaModel) {
            return $res->redirect('/citas')->with('error', 'Cita no encontrada');
        }
        $cita = (is_object($citaModel) && method_exists($citaModel, 'toArray')) ? $citaModel->toArray() : $citaModel;
        
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

        // Cargar diagnósticos asociados a la consulta (detalle_consulta)
        $consultaDiagnosticos = [];
        try {
            if ($consulta && !empty($consulta->id)) {
                $consultaDiagnosticos = DB::table('detalle_consulta')
                    ->join('diagnosticos', 'detalle_consulta.id_diagnostico', '=', 'diagnosticos.id')
                    ->where('detalle_consulta.id_consulta', $consulta->id)
                    ->select('diagnosticos.id', DB::raw("COALESCE(diagnosticos.nombre_enfermedad, '') as nombre"))
                    ->get()->map(function($r){ return (array)$r; })->toArray();
            }
        } catch (\Throwable $e) {
            $consultaDiagnosticos = [];
        }

    return $res->view('consultas/attend', [
            'title' => 'Atender cita',
            'appointment' => $appointment,
            'consulta' => $consulta,
            'consulta_diagnosticos' => $consultaDiagnosticos,
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

        // soportar múltiples diagnósticos enviados desde la vista: diagnosticos[][id]
        $diagnosticosRaw = $_POST['diagnosticos'] ?? [];
        $diagnosticoId = 0; // conserva compatibilidad: primer id si existe
        $diagnosticosInput = [];
        if (is_array($diagnosticosRaw)) {
            foreach ($diagnosticosRaw as $r) {
                if (!is_array($r) && !is_object($r)) continue;
                $idVal = isset($r['id']) ? (int)$r['id'] : 0;
                if ($idVal > 0) $diagnosticosInput[$idVal] = true;
            }
        }
        $diagnosticosInput = array_keys($diagnosticosInput);
        if (count($diagnosticosInput) > 0) {
            $diagnosticoId = (int)$diagnosticosInput[0];
        }
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
        // Si el médico no marca 'ausente' se requiere seleccionar al menos un diagnóstico existente
        if ($marcar !== 'ausente') {
            if (count($diagnosticosInput) <= 0 || $estadoPost === '') {
                $consultaObj = Consulta::findByCitaId($id);
                $consultaDiagnosticos = [];
                if ($consultaObj) {
                    $consultaDiagnosticos = DB::table('detalle_consulta')
                        ->join('diagnosticos', 'detalle_consulta.id_diagnostico', '=', 'diagnosticos.id')
                        ->where('detalle_consulta.id_consulta', $consultaObj->id)
                        ->select('diagnosticos.id', DB::raw("COALESCE(diagnosticos.nombre_enfermedad, '') as nombre"))
                        ->get()->map(function($r){ return (array)$r; })->toArray();
                }
                return $res->view('consultas/attend', [
                    'error' => 'Debe seleccionar al menos un diagnóstico existente y completar el estado postconsulta. No está permitido crear diagnósticos nuevos desde este formulario.',
                    'appointment' => Appointment::with(['paciente.user'])->find($id),
                    'consulta' => $consultaObj,
                    'consulta_diagnosticos' => $consultaDiagnosticos,
                    'diagnosticos' => \App\Models\Diagnostico::search(''),
                    'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                    'recetas' => [] ,
                    'title' => 'Atender cita'
                ]);
            }
            // Verificar que todos los diagnósticos existan en la base de datos
            if (count($diagnosticosInput) > 0) {
                $foundCount = DB::table('diagnosticos')->whereIn('id', $diagnosticosInput)->count();
                if ($foundCount !== count($diagnosticosInput)) {
                    $consultaObj = Consulta::findByCitaId($id);
                    $consultaDiagnosticos = [];
                    if ($consultaObj) {
                        $consultaDiagnosticos = DB::table('detalle_consulta')
                            ->join('diagnosticos', 'detalle_consulta.id_diagnostico', '=', 'diagnosticos.id')
                            ->where('detalle_consulta.id_consulta', $consultaObj->id)
                            ->select('diagnosticos.id', DB::raw("COALESCE(diagnosticos.nombre_enfermedad, '') as nombre"))
                            ->get()->map(function($r){ return (array)$r; })->toArray();
                    }
                    return $res->view('consultas/attend', [
                        'error' => 'Uno o más diagnósticos seleccionados no existen. Por favor selecciona diagnósticos válidos.',
                        'appointment' => Appointment::with(['paciente.user'])->find($id),
                        'consulta' => $consultaObj,
                        'consulta_diagnosticos' => $consultaDiagnosticos,
                        'diagnosticos' => \App\Models\Diagnostico::search(''),
                        'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                        'recetas' => [] ,
                        'title' => 'Atender cita'
                    ]);
                }
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

            // Usar los diagnósticos seleccionados (no se permiten creaciones desde este formulario)
            $diagId = $diagnosticoId > 0 ? $diagnosticoId : null; // mantener compatibilidad en el campo diagnóstico de consulta

            // Insertar o actualizar registro en 'consultas' (campo 'receta' ya no existe)
            $consulta = Consulta::findByCitaId($id);
            if ($consulta) {
                $consulta->observaciones = $observaciones;
                $consulta->estado_postconsulta = $estadoPost;
                $consulta->save();
            } else {
                $c = new Consulta();
                $c->cita_id = $id;
                $c->observaciones = $observaciones;
                $c->estado_postconsulta = $estadoPost;
                $c->save();
            }

            // Sincronizar la tabla detalle_consulta con los diagnósticos entrantes
            $consultaId = ($consulta ?? $c)->id;
            $this->syncDetalleConsulta($consultaId, $diagnosticosInput);

            // Persistir recetas por diff: UPDATE existing by id, INSERT new, DELETE those removed
            // recetas handling permanece igual
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
            $consultaObj = Consulta::findByCitaId($id);
            $consultaDiagnosticos = [];
            if ($consultaObj) {
                $consultaDiagnosticos = DB::table('detalle_consulta')
                    ->join('diagnosticos', 'detalle_consulta.id_diagnostico', '=', 'diagnosticos.id')
                    ->where('detalle_consulta.id_consulta', $consultaObj->id)
                    ->select('diagnosticos.id', DB::raw("COALESCE(diagnosticos.nombre_enfermedad, '') as nombre"))
                    ->get()->map(function($r){ return (array)$r; })->toArray();
            }
            return $res->view('consultas/attend', [
                'error' => 'Error al guardar la consulta: ' . $e->getMessage(),
                'appointment' => Appointment::with(['paciente.user'])->find($id),
                'consulta' => $consultaObj,
                'consulta_diagnosticos' => $consultaDiagnosticos,
                'diagnosticos' => \App\Models\Diagnostico::search(''),
                'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                'recetas' => \App\Models\Receta::where('consulta_id', $consultaObj?->id ?? 0)->with('medicamento')->get()->toArray(),
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

        // Cargar diagnosticos asociados a la consulta a través de detalle_consulta
        $consultaDiagnosticos = [];
        try {
            if ($consulta && !empty($consulta->id)) {
                $consultaDiagnosticos = DB::table('detalle_consulta')
                    ->join('diagnosticos', 'detalle_consulta.id_diagnostico', '=', 'diagnosticos.id')
                    ->where('detalle_consulta.id_consulta', $consulta->id)
                    ->select('diagnosticos.id', DB::raw("COALESCE(diagnosticos.nombre_enfermedad, '') as nombre"))
                    ->get()->map(function($r){ return (array)$r; })->toArray();
                // añadir al array de consulta para compatibilidad con la vista
                if (is_array($consultaArr)) $consultaArr['diagnosticos'] = $consultaDiagnosticos;
            }
        } catch (\Throwable $e) {
            $consultaDiagnosticos = [];
        }

    return $res->view('consultas/edit', [
            'title' => 'Editar cita',
            'appointment' => $appointmentArr,
            'consulta' => $consultaArr,
            'consulta_diagnosticos' => $consultaDiagnosticos,
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

        // soportar múltiples diagnósticos enviados desde la vista: diagnosticos[][id]
        $diagnosticosRaw = $_POST['diagnosticos'] ?? [];
        $diagnosticoId = 0;
        $diagnosticosInput = [];
        if (is_array($diagnosticosRaw)) {
            foreach ($diagnosticosRaw as $r) {
                if (!is_array($r) && !is_object($r)) continue;
                $idVal = isset($r['id']) ? (int)$r['id'] : 0;
                if ($idVal > 0) $diagnosticosInput[$idVal] = true;
            }
        }
        $diagnosticosInput = array_keys($diagnosticosInput);
        if (count($diagnosticosInput) > 0) {
            $diagnosticoId = (int)$diagnosticosInput[0];
        }
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
        if (count($diagnosticosInput) <= 0) {
            return $res->view('consultas/edit', [
                'error' => 'Debe seleccionar al menos un diagnóstico existente.',
                'appointment' => Appointment::with(['paciente.user'])->find($id),
                'consulta' => Consulta::findByCitaId($id),
                'diagnosticos' => \App\Models\Diagnostico::search(''),
                'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                'recetas' => [] ,
                'title' => 'Editar cita'
            ]);
        }
        if (!in_array($estadoPost, $allowedPost, true)) {
            return $res->view('consultas/edit', [
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
                $consulta->observaciones = $observaciones;
                $consulta->estado_postconsulta = $estadoPost;
                $consulta->save();
            } else {
                $c = new Consulta();
                $c->cita_id = $id;
                $c->observaciones = $observaciones;
                $c->estado_postconsulta = $estadoPost;
                $c->save();
            }

            $consultaId = ($consulta ?? $c)->id;
            // Sincronizar detalle_consulta con los ids entrantes
            $this->syncDetalleConsulta($consultaId, $diagnosticosInput);
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
            $consultaObj = Consulta::findByCitaId($id);
            $consultaDiagnosticos = [];
            if ($consultaObj) {
                $consultaDiagnosticos = DB::table('detalle_consulta')
                    ->join('diagnosticos', 'detalle_consulta.id_diagnostico', '=', 'diagnosticos.id')
                    ->where('detalle_consulta.id_consulta', $consultaObj->id)
                    ->select('diagnosticos.id', DB::raw("COALESCE(diagnosticos.nombre_enfermedad, '') as nombre"))
                    ->get()->map(function($r){ return (array)$r; })->toArray();
            }
            return $res->view('citas/edit', [
                'error' => 'Error al guardar los cambios: ' . $e->getMessage(),
                'appointment' => Appointment::with(['paciente.user'])->find($id),
                'consulta' => $consultaObj,
                'consulta_diagnosticos' => $consultaDiagnosticos,
                'diagnosticos' => \App\Models\Diagnostico::search(''),
                'medicamentos' => \App\Models\Medicamento::all()->toArray(),
                'recetas' => \App\Models\Receta::where('consulta_id', $consultaObj?->id ?? 0)->with('medicamento')->get()->toArray(),
                'title' => 'Editar cita'
            ]);
        }
    }

    /**
     * Sincroniza la tabla detalle_consulta para una consulta dada
     * Recibe los ids de diagnóstico entrantes (array de ints) y mantiene
     * INSERTs y DELETEs necesarios para que la tabla refleje exactamente
     * esos ids para la consulta.
     */
    private function syncDetalleConsulta(int $consultaId, array $incomingIds): void
    {
        // normalizar
        $incomingIds = array_values(array_filter(array_map('intval', array_unique($incomingIds)), function ($v) { return $v > 0; }));

        // obtener existentes
        $existing = DB::table('detalle_consulta')
            ->where('id_consulta', $consultaId)
            ->pluck('id_diagnostico')
            ->toArray();

        $toInsert = array_diff($incomingIds, $existing);
        $toDelete = array_diff($existing, $incomingIds);

        if (!empty($toInsert)) {
            foreach ($toInsert as $did) {
                DB::table('detalle_consulta')->insert([
                    'id_consulta' => $consultaId,
                    'id_diagnostico' => $did
                ]);
            }
        }

        if (!empty($toDelete)) {
            DB::table('detalle_consulta')
                ->where('id_consulta', $consultaId)
                ->whereIn('id_diagnostico', $toDelete)
                ->delete();
        }
    }

}