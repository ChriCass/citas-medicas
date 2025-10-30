<?php
namespace App\Controllers;

use App\Core\{Request, Response, Csrf, Auth, Database};
use App\Models\{DoctorSchedule, Doctor, Sede, Calendario, SlotCalendario};
use DateTime;

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
        $horarios = DoctorSchedule::listAll(); // patrones existentes

        return $res->view('horarios_doctores/create', [
            'title'     => 'Nuevo Horario',
            'doctors'   => $doctors,
            'sedes' => $sedes,
            'horarios' => $horarios,
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
        $diaSemana  = trim((string)($_POST['dia_semana'] ?? ''));  // Día de la semana específico
        $start      = trim((string)($_POST['start_time'] ?? ''));
        $end        = trim((string)($_POST['end_time'] ?? ''));

        // Validaciones simples
        if ($doctorId<=0 || !$diaSemana || !$start || !$end) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'Completa todos los campos.',
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        // Validar día de la semana
        $diasValidos = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        if (!in_array(strtolower($diaSemana), $diasValidos)) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'Día de la semana inválido.',
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

        // Evitar traslapes en doctor+sede+dia_semana
        if (DoctorSchedule::overlaps($doctorId, $sedeId, $diaSemana, $start, $end)) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'Este rango se solapa con otro horario existente para ese doctor/sede/día.',
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        DoctorSchedule::create($doctorId, $sedeId, $diaSemana, $start, $end, 1);
        return $res->redirect('/doctor-schedules');
    }

    /**
     * Asignar horarios masivamente según UC-09
     * Espera: doctor_id, sede_id (opcional), horario_id (opcional), mes, anio, days[] (nombres en español), generate_slots
     */
    public function assign(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        if (!Csrf::verify((string)($_POST['_csrf'] ?? ''))) {
            return $res->abort(419, 'CSRF inválido');
        }

        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $sedeId = (int)($_POST['sede_id'] ?? 0);
        $horarioId = (int)($_POST['horario_id'] ?? 0) ?: null;
        $mes = (int)($_POST['mes'] ?? 0);
        $anio = (int)($_POST['anio'] ?? 0);
        $days = $_POST['days'] ?? [];
        $generateSlots = isset($_POST['generate_slots']) && (int)$_POST['generate_slots'] === 1;

        // Validaciones básicas
        if ($doctorId <= 0 || $mes < 1 || $mes > 12 || $anio < 1970 || !is_array($days) || count($days) === 0) {
            return $res->view('horarios_doctores/create', [
                'title' => 'Asignar horarios de atención',
                'error' => 'Faltan datos obligatorios o son inválidos (médico, mes/año o días).',
                'doctors' => Doctor::getAll(),
                'sedes' => Sede::getAll(),
                'horarios' => DoctorSchedule::listAll(),
                'old' => $_POST
            ]);
        }

        // Mapear días de la semana en español a números ISO-8601 (1=Lunes, 7=Domingo)
        $map = [
            'lunes' => 1, 'martes' => 2, 'miércoles' => 3, 'miercoles' => 3, 'jueves' => 4,
            'viernes' => 5, 'sábado' => 6, 'sabado' => 6, 'domingo' => 7
        ];

        $daysNums = [];
        foreach ($days as $d) {
            $k = mb_strtolower(trim($d));
            if (isset($map[$k])) $daysNums[] = $map[$k];
        }

        if (empty($daysNums)) {
            return $res->view('horarios_doctores/create', [
                'title' => 'Asignar horarios de atención',
                'error' => 'Selecciona al menos un día de la semana válido.',
                'doctors' => Doctor::getAll(),
                'sedes' => Sede::getAll(),
                'horarios' => DoctorSchedule::listAll(),
                'old' => $_POST
            ]);
        }

        // Se reciben horarios por día como horarios_inicio[dayKey] y horarios_fin[dayKey]
        $horariosInicio = $_POST['horarios_inicio'] ?? [];
        $horariosFin = $_POST['horarios_fin'] ?? [];

        // Validación básica de formato 24h HH:MM y duración mínima 15 minutos
        $timeRe = '/^([01]\d|2[0-3]):[0-5]\d$/';
        foreach ($horariosInicio as $k => $v) {
            $s = trim((string)$v);
            $e = trim((string)($horariosFin[$k] ?? ''));
            if ($s === '' && $e === '') continue; // ambos vacíos -> se ignora la creación de slots para ese día
            if ($s === '' || $e === '') {
                return $res->view('horarios_doctores/create', [
                    'title' => 'Asignar horarios de atención',
                    'error' => "Día {$k}: debes proporcionar hora de inicio y hora de fin o dejar ambos vacíos.",
                    'doctors' => Doctor::getAll(),
                    'sedes' => Sede::getAll(),
                    'horarios' => DoctorSchedule::listAll(),
                    'old' => $_POST
                ]);
            }
            if (!preg_match($timeRe, $s) || !preg_match($timeRe, $e)) {
                return $res->view('horarios_doctores/create', [
                    'title' => 'Asignar horarios de atención',
                    'error' => "Día {$k}: formato de hora inválido. Usa HH:MM 24h.",
                    'doctors' => Doctor::getAll(),
                    'sedes' => Sede::getAll(),
                    'horarios' => DoctorSchedule::listAll(),
                    'old' => $_POST
                ]);
            }
            // comprobar que start < end y al menos 15 minutos
            $tStart = strtotime($s);
            $tEnd = strtotime($e);
            if ($tStart >= $tEnd) {
                return $res->view('horarios_doctores/create', [
                    'title' => 'Asignar horarios de atención',
                    'error' => "Día {$k}: la hora de inicio debe ser menor que la hora fin.",
                    'doctors' => Doctor::getAll(),
                    'sedes' => Sede::getAll(),
                    'horarios' => DoctorSchedule::listAll(),
                    'old' => $_POST
                ]);
            }
            if ((($tEnd - $tStart) / 60) < 15) {
                return $res->view('horarios_doctores/create', [
                    'title' => 'Asignar horarios de atención',
                    'error' => "Día {$k}: la duración mínima debe ser de 15 minutos.",
                    'doctors' => Doctor::getAll(),
                    'sedes' => Sede::getAll(),
                    'horarios' => DoctorSchedule::listAll(),
                    'old' => $_POST
                ]);
            }
        }

        // Rango de fechas
        $startDate = \DateTime::createFromFormat('Y-n-j', "{$anio}-{$mes}-1");
        if (!$startDate) return $res->abort(400, 'Mes/año inválidos');
        $endDate = (clone $startDate)->modify('last day of this month');

        // Crear patrones (horarios_medicos) únicamente para los días seleccionados y con horas provistas.
        $weekdayMapReverse = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado',7=>'domingo'];
        // Normalizar lista de días seleccionados (clave en minúsculas)
        $selectedDays = [];
        foreach ($days as $d) {
            $k = mb_strtolower(trim((string)$d));
            if ($k !== '') $selectedDays[$k] = true;
        }

        $patternIds = [];
        $slotsErrors = [];
        $createdDays = 0; $skippedDuplicates = 0; $slotsCreated = 0;
    $skippedHolidays = 0;

        // Crear patrones solo cuando el formulario trae horarios para ese día. Usar sede por día si existe.
        foreach ($selectedDays as $dayKey => $_) {
            $sVal = trim((string)($horariosInicio[$dayKey] ?? ''));
            $eVal = trim((string)($horariosFin[$dayKey] ?? ''));
            if ($sVal === '' || $eVal === '') {
                // No hay horas para este día; no se crea patrón ni se marcará como error.
                continue;
            }

            $sedeForDay = isset($_POST['sede_for_day'][$dayKey]) ? (int)$_POST['sede_for_day'][$dayKey] : ($sedeId ?: 0);

            // Evitar duplicar patrones idénticos (mismo doctor/sede/dia_semana/horas)
            if (DoctorSchedule::patternExists($doctorId, $sedeForDay, $dayKey, $sVal, $eVal)) {
                $patternIds[$dayKey] = DoctorSchedule::findPatternId($doctorId, $sedeForDay, $dayKey);
                continue;
            }

            try {
                $patternIds[$dayKey] = DoctorSchedule::createPattern($doctorId, $sedeForDay ?: null, $dayKey, $sVal, $eVal, 'Creado desde asignación masiva');
            } catch (\Throwable $e) {
                return $res->abort(500, 'Error al crear patrón de horario: ' . $e->getMessage());
            }
        }

        // Iterar fechas y crear calendario/slots. Si un día seleccionado no tiene patrón (no se completó), simplemente se salta.
        $current = clone $startDate;

        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();

            while ($current <= $endDate) {
                $weekday = (int)$current->format('N'); // 1 (Mon) - 7 (Sun)
                if (!in_array($weekday, $daysNums, true)) {
                    $current->modify('+1 day');
                    continue;
                }

                $fecha = $current->format('Y-m-d');
                $dayKey = $weekdayMapReverse[$weekday] ?? null;

                if (!$dayKey) { $current->modify('+1 day'); continue; }

                // Si no existe patrón creado/preexistente para este dayKey, saltar (no es obligatorio completar todos los días)
                if (empty($patternIds[$dayKey])) {
                    $current->modify('+1 day');
                    continue;
                }

                $usedHorarioId = $patternIds[$dayKey];

                // Evitar duplicados: mismo doctor + fecha + horario
                // Antes de crear, comprobar si la fecha es feriado (global o para la sede del patrón)
                try {
                    $pdoCheck = $pdo->prepare('SELECT id, fecha, tipo, activo, sede_id FROM feriados WHERE fecha = :f');
                    $pdoCheck->execute([':f' => $fecha]);
                    $feriados = $pdoCheck->fetchAll();
                } catch (\Throwable $e) {
                    $feriados = [];
                }

                $isFeriado = false;
                if (!empty($feriados)) {
                    // Obtener sede del patrón (si existe)
                    $patternSedeId = $pattern?->sede_id ?? null;
                    foreach ($feriados as $fer) {
                        // Considerar feriado activo cuando activo IS NULL o activo == 1
                        $activo = $fer['activo'];
                        $activoFlag = ($activo === null) ? true : (bool)$activo;
                        if (!$activoFlag) continue;

                        // Si feriado.sede_id IS NULL => aplica a todas las sedes (global)
                        if ($fer['sede_id'] === null || $fer['sede_id'] === '' ) {
                            $isFeriado = true; break;
                        }

                        // Si feriado tiene sede_id y coincide con el patrón -> aplica
                        if ($patternSedeId !== null && ((int)$fer['sede_id'] === (int)$patternSedeId)) {
                            $isFeriado = true; break;
                        }
                    }
                }

                if ($isFeriado) {
                    $skippedHolidays++;
                    $current->modify('+1 day');
                    continue;
                }

                if (Calendario::existsFor($doctorId, $fecha, $usedHorarioId)) {
                    $skippedDuplicates++;
                    $current->modify('+1 day');
                    continue;
                }

                // Obtener horas desde el patrón (guardadas en horarios_medicos)
                $pattern = DoctorSchedule::find($usedHorarioId);
                $baseStart = $pattern?->hora_inicio ? date('H:i', strtotime($pattern->hora_inicio)) : null;
                $baseEnd = $pattern?->hora_fin ? date('H:i', strtotime($pattern->hora_fin)) : null;

                // Crear calendario y slots
                $calId = Calendario::createEntry($doctorId, $usedHorarioId, $fecha, $baseStart, $baseEnd);
                $createdDays++;

                if ($generateSlots) {
                    if ($baseStart && $baseEnd) {
                        $n = SlotCalendario::createSlots($calId, $usedHorarioId, $baseStart, $baseEnd, 15);
                        $slotsCreated += $n;
                    } else {
                        $slotsErrors[] = "No hay horas definidas para el día {$fecha} (horario_id={$usedHorarioId}).";
                    }
                }

                $current->modify('+1 day');
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return $res->abort(500, 'Error al crear registros en calendario/slots: ' . $e->getMessage());
        }

        // Preparar mensaje final
        $msg = "Se generaron {$createdDays} día(s).";
        if ($skippedDuplicates) $msg .= " {$skippedDuplicates} día(s) omitidos por duplicidad o sin patrón definido.";
        if ($generateSlots) $msg .= " {$slotsCreated} slot(s) creados.";
        if (!empty($slotsErrors)) $msg .= ' Algunos errores: ' . implode(' | ', $slotsErrors);
    if (!empty($skippedHolidays)) $msg .= " {$skippedHolidays} día(s) omitidos por feriado.";

        $_SESSION['flash'] = ['success' => $msg];
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

    /**
     * Devuelve en JSON las sedes (id, nombre_sede) asociadas a un doctor (tabla doctor_sede join sedes).
     */
    public function doctorSedes(Request $req, Response $res)
    {
        // Permitir que cualquier usuario autenticado obtenga las sedes vía AJAX desde la UI.
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->abort(403, 'Forbidden');

        $id = (int)($req->params['id'] ?? 0);
        if ($id <= 0) return $res->json([], 400);

        try {
            // Cargar doctor con relación sedes
            $doctor = Doctor::with('sedes')->where('id', $id)->first();
            if (!$doctor) return $res->json([], 404);

            $sedes = [];
            foreach ($doctor->sedes ?? [] as $s) {
                $sedes[] = [ 'id' => $s->id, 'nombre_sede' => $s->nombre_sede ];
            }

            return $res->json($sedes);
        } catch (\Throwable $e) {
            error_log('[doctorSedes] Error: ' . $e->getMessage());
            return $res->json([], 500);
        }
    }
}
