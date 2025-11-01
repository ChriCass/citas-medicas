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

        // Permitir seleccionar mes/año vía querystring (GET)
        $selMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $selYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        if ($selMonth < 1 || $selMonth > 12) $selMonth = (int)date('n');
        if ($selYear < 1970) $selYear = (int)date('Y');

        $startDate = \DateTime::createFromFormat('Y-n-j', "{$selYear}-{$selMonth}-1");
        if (!$startDate) $startDate = new \DateTime("{$selYear}-{$selMonth}-01");
        $endDate = (clone $startDate)->modify('last day of this month');

        // Patrones (para legend/edición) y entradas concretas del calendario (fechas exactas)
        $schedules = DoctorSchedule::listAll();
        $calendars = Calendario::with(['horario', 'doctor'])
                        ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->get();

        return $res->view('horarios_doctores/index', [
            'title'     => 'Horarios Doctores',
            'schedules' => $schedules,
            'calendars' => $calendars,
            'user'      => $user,
            'selMonth'  => $selMonth,
            'selYear'   => $selYear,
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

        // Validar solapamiento usando la nueva lógica que permite múltiples horarios por día/sede
        // siempre que no se solapen en tiempo
        if (DoctorSchedule::patternsOverlap($doctorId, $sedeId, strtolower($diaSemana), $start, $end)) {
            return $res->view('horarios_doctores/create', [
                'title'=>'Nuevo Horario',
                'error'=>'Este rango de horario se solapa con otro horario existente para ese doctor/sede/día. Un doctor puede tener múltiples horarios en el mismo día siempre que no se solapen.',
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        // Crear un patrón semanal (dia_semana) usando createPattern en lugar de create (que crea entradas con fecha)
        $createdId = DoctorSchedule::createPattern($doctorId, $sedeId ?: null, strtolower($diaSemana), $start, $end, null, null, null);
        $_SESSION['flash'] = ['success' => 'Patrón creado'];
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
    // Año puede no venir desde la vista. Si no se envía, usar el año actual,
    // salvo cuando estemos en diciembre y se quiera crear para otro mes -> usar siguiente año.
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : 0;
        $days = $_POST['days'] ?? [];
        $generateSlots = isset($_POST['generate_slots']) && (int)$_POST['generate_slots'] === 1;

        // Validaciones básicas
        // Si no llegó año válido, calcular según la regla: usar año actual. Si hoy es diciembre y el mes objetivo no es diciembre, usar año siguiente.
        $nowMonth = (int)date('n');
        $currentYear = (int)date('Y');
        // Mapa de meses en minúsculas para guardar en la columna `mes`
        $months = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
        if ($anio < 1970) {
            $anio = $currentYear;
            if ($nowMonth === 12 && $mes !== 12) {
                $anio = $currentYear + 1;
            }
        }

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

        // Se reciben horarios por fila (índices numéricos 0, 1, 2...) con days[], horarios_inicio[], horarios_fin[], sede_for_day[]
        $horariosInicio = $_POST['horarios_inicio'] ?? [];
        $horariosFin = $_POST['horarios_fin'] ?? [];
        $sedeForDay = $_POST['sede_for_day'] ?? [];

        // Validación básica de formato 24h HH:MM y duración mínima 15 minutos por cada fila
        $timeRe = '/^([01]\d|2[0-3]):[0-5]\d$/';
        foreach ($days as $idx => $dayName) {
            $dayName = trim((string)$dayName);
            if ($dayName === '') continue; // fila sin día seleccionado, se ignora
            
            $s = trim((string)($horariosInicio[$idx] ?? ''));
            $e = trim((string)($horariosFin[$idx] ?? ''));
            if ($s === '' && $e === '') continue; // ambos vacíos -> se ignora la creación para esa fila
            if ($s === '' || $e === '') {
                return $res->view('horarios_doctores/create', [
                    'title' => 'Asignar horarios de atención',
                    'error' => "Fila " . ($idx + 1) . " (día {$dayName}): debes proporcionar hora de inicio y hora de fin o dejar ambos vacíos.",
                    'doctors' => Doctor::getAll(),
                    'sedes' => Sede::getAll(),
                    'horarios' => DoctorSchedule::listAll(),
                    'old' => $_POST
                ]);
            }
            if (!preg_match($timeRe, $s) || !preg_match($timeRe, $e)) {
                return $res->view('horarios_doctores/create', [
                    'title' => 'Asignar horarios de atención',
                    'error' => "Fila " . ($idx + 1) . " (día {$dayName}): formato de hora inválido. Usa HH:MM 24h.",
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
                    'error' => "Fila " . ($idx + 1) . " (día {$dayName}): la hora de inicio debe ser menor que la hora fin.",
                    'doctors' => Doctor::getAll(),
                    'sedes' => Sede::getAll(),
                    'horarios' => DoctorSchedule::listAll(),
                    'old' => $_POST
                ]);
            }
            if ((($tEnd - $tStart) / 60) < 15) {
                return $res->view('horarios_doctores/create', [
                    'title' => 'Asignar horarios de atención',
                    'error' => "Fila " . ($idx + 1) . " (día {$dayName}): la duración mínima debe ser de 15 minutos.",
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

        // Si el mes/año objetivo coincide con el mes/año actual, empezar desde el día siguiente (mañana)
        $today = new \DateTime();
        $nowYear = (int)$today->format('Y');
        $nowMonth = (int)$today->format('n');
        if ($anio === $nowYear && $mes === $nowMonth) {
            $tomorrow = (clone $today)->modify('+1 day')->setTime(0,0,0);
            // Si mañana está dentro del mes objetivo, comenzar desde mañana
            if ($tomorrow <= $endDate) {
                $startDate = $tomorrow;
            } else {
                // Nada que crear en este mes (mañana ya fuera de mes) -> no generar
                $_SESSION['flash'] = ['warning' => 'No hay días hábiles por generar en el mes seleccionado (restan 0 días).'];
                return $res->redirect('/doctor-schedules');
            }
        }

        // Crear patrones (horarios_medicos) únicamente para los días seleccionados y con horas provistas.
        $weekdayMapReverse = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado',7=>'domingo'];
        
        // Build prospective patterns from rows (indexed 0, 1, 2...) — each row can create one pattern
        // Group by day name to build a structure for scanning: dayKey => [ [start, end, sede], ... ]
        $prospectivePatterns = [];
        foreach ($days as $idx => $dayName) {
            $dayKey = mb_strtolower(trim((string)$dayName));
            if ($dayKey === '') continue;
            $sVal = trim((string)($horariosInicio[$idx] ?? ''));
            $eVal = trim((string)($horariosFin[$idx] ?? ''));
            if ($sVal === '' || $eVal === '') continue;
            $sede = isset($sedeForDay[$idx]) ? (int)$sedeForDay[$idx] : ($sedeId ?: 0);
            if (!isset($prospectivePatterns[$dayKey])) $prospectivePatterns[$dayKey] = [];
            $prospectivePatterns[$dayKey][] = ['start' => $sVal, 'end' => $eVal, 'sede' => $sede];
        }

        // Si no hay patrones con horas provistas, no hay nada que crear
        if (empty($prospectivePatterns)) {
            $_SESSION['flash'] = ['warning' => 'No se proporcionaron horarios con horas válidas para el mes seleccionado.'];
            return $res->redirect('/doctor-schedules');
        }

        // Escanear el mes objetivo para comprobar si existe al menos UNA fecha disponible
        // disponible = fecha dentro del rango, coincide con algún día seleccionado,
        // no es feriado (global o para la sede) y no existe ya un registro en calendario para el médico en esa fecha.
        $pdoCheck = Database::pdo();
        $availableDates = 0;
        $scanDate = clone $startDate;
        
        // Build list of weekdays from prospective patterns
        $daysNums = [];
        foreach (array_keys($prospectivePatterns) as $dayKey) {
            $num = $map[mb_strtolower(trim((string)$dayKey))] ?? null;
            if ($num !== null && !in_array($num, $daysNums, true)) $daysNums[] = $num;
        }
        
        while ($scanDate <= $endDate) {
            $weekday = (int)$scanDate->format('N');
            if (!in_array($weekday, $daysNums, true)) { $scanDate->modify('+1 day'); continue; }

            $fecha = $scanDate->format('Y-m-d');

            // Para cada patrón prospectivo validar si aplica a este weekday
            foreach ($prospectivePatterns as $pDayKey => $pList) {
                // Mapear clave de día a número (si existe en el map)
                $mapNum = $map[mb_strtolower(trim((string)$pDayKey))] ?? null;
                if ($mapNum === null || $mapNum !== $weekday) continue;

                foreach ($pList as $p) {
                    // Comprobar feriados para la fecha
                    $isFeriado = false;
                    try {
                        $stmt = $pdoCheck->prepare('SELECT id, fecha, tipo, activo, sede_id FROM feriados WHERE fecha = :f');
                        $stmt->execute([':f' => $fecha]);
                        $feriados = $stmt->fetchAll();
                    } catch (\Throwable $e) {
                        $feriados = [];
                    }

                    if (!empty($feriados)) {
                        foreach ($feriados as $fer) {
                            $activo = $fer['activo'];
                            $activoFlag = ($activo === null) ? true : (bool)$activo;
                            if (!$activoFlag) continue;
                            // feriado global
                            if ($fer['sede_id'] === null || $fer['sede_id'] === '') { $isFeriado = true; break; }
                            // feriado por sede
                            if ($p['sede'] !== null && $p['sede'] !== '' && ((int)$fer['sede_id'] === (int)$p['sede'])) { $isFeriado = true; break; }
                        }
                    }
                    if ($isFeriado) continue;

                    // Si llegamos aquí, hay al menos una fecha elegible (no es feriado y coincide con día seleccionado)
                    // No verificamos si ya existe calendario, porque permitimos múltiples horarios por día
                    $availableDates++;
                    break 3; // no necesitamos más, basta con 1 fecha disponible
                }
            }

            $scanDate->modify('+1 day');
        }

        if ($availableDates === 0) {
            // No crear patrones ni calendario; mostrar mensaje en la vista de creación y preservar los datos del formulario
            return $res->view('horarios_doctores/create', [
                'title' => 'Asignar horarios de atención',
                'error' => 'No hay fechas disponibles para el mes seleccionado. Ninguna fecha será creada.',
                'doctors' => Doctor::getAll(),
                'sedes' => Sede::getAll(),
                'horarios' => DoctorSchedule::listAll(),
                'old' => $_POST
            ]);
        }

        // Continuar: habrá al menos una fecha disponible para crear calendario
    $patternIds = [];
    // Track newly created pattern IDs so we can cleanup on failure (avoid partial state)
    $createdPatternIds = [];
        $slotsErrors = [];
        $createdDays = 0; $skippedDuplicates = 0; $slotsCreated = 0;
    $skippedHolidays = 0;

        // Crear patrones procesando cada fila (permite múltiples patrones por día siempre que no se solapen)
        foreach ($days as $idx => $dayName) {
            $dayKey = mb_strtolower(trim((string)$dayName));
            if ($dayKey === '') continue;
            
            $sVal = trim((string)($horariosInicio[$idx] ?? ''));
            $eVal = trim((string)($horariosFin[$idx] ?? ''));
            if ($sVal === '' || $eVal === '') {
                // No hay horas para esta fila; no se crea patrón ni se marcará como error.
                continue;
            }

            $sedeForThisRow = isset($sedeForDay[$idx]) ? (int)$sedeForDay[$idx] : ($sedeId ?: 0);

            // Validar que no se solape con patrones existentes (DB) o con los patrones creados anteriormente en esta misma petición
            $targetMonthName = strtolower($months[$mes] ?? '');
            $tNewStart = \App\Models\DoctorSchedule::timeToSeconds($sVal);
            $tNewEnd = \App\Models\DoctorSchedule::timeToSeconds($eVal);
            // Prevent creating the exact same horario for the same doctor in the same day/month/year regardless of sede
            if (\App\Models\DoctorSchedule::patternExistsIgnoreSede($doctorId, $dayKey, $sVal, $eVal, $targetMonthName, $anio)) {
                return $res->view('horarios_doctores/create', [
                    'title' => 'Asignar horarios de atención',
                    'error' => "Fila " . ($idx + 1) . " (día {$dayKey}): ya existe el mismo horario para este médico en el mes/año indicado (no se permiten duplicados en distintas sedes).",
                    'doctors' => Doctor::getAll(),
                    'sedes' => Sede::getAll(),
                    'horarios' => DoctorSchedule::listAll(),
                    'old' => $_POST
                ]);
            }
            // Fetch DB candidates and check overlap using DB-returned rows (works across DB engines, including SQL Server)
            $tNewStart = \App\Models\DoctorSchedule::timeToSeconds($sVal);
            $tNewEnd = \App\Models\DoctorSchedule::timeToSeconds($eVal);
            try {
                $pdoCheck = Database::pdo();
                $sql = 'SELECT id, sede_id, dia_semana, hora_inicio, hora_fin, mes, anio FROM horarios_medicos WHERE doctor_id = :doctorId AND activo = 1';
                $params = [':doctorId' => $doctorId];
                // Scope by sede if provided (also consider global patterns sede_id IS NULL)
                $loc = ($sedeForThisRow !== null && (int)$sedeForThisRow > 0) ? (int)$sedeForThisRow : null;
                if ($loc !== null) {
                    $sql .= ' AND (sede_id = :loc OR sede_id IS NULL)';
                    $params[':loc'] = $loc;
                }
                $stmt = $pdoCheck->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();
            } catch (\Throwable $_e) {
                $rows = [];
            }

            foreach ($rows as $existingRow) {
                try {
                    // Normalize day names for comparison
                    $existingDay = mb_strtolower(trim((string)$existingRow['dia_semana'] ?? ''));
                    $normDay = mb_strtolower(trim((string)$dayKey));
                    // normalize accents
                    $existingDay = str_replace(['á','é','í','ó','ú','ü'], ['a','e','i','o','u','u'], $existingDay);
                    $normDay = str_replace(['á','é','í','ó','ú','ü'], ['a','e','i','o','u','u'], $normDay);
                    if ($existingDay !== $normDay) continue;

                    // mes/anio scoping: if existing row has mes set and it doesn't match target month, skip
                    if (!empty($existingRow['mes'])) {
                        $existingMes = mb_strtolower(trim((string)$existingRow['mes']));
                        if ($targetMonthName !== '' && $existingMes !== $targetMonthName) continue;
                    }
                    if (!empty($existingRow['anio']) && $anio !== null && (int)$existingRow['anio'] !== (int)$anio) continue;

                    $tExStart = \App\Models\DoctorSchedule::timeToSeconds((string)$existingRow['hora_inicio']);
                    $tExEnd = \App\Models\DoctorSchedule::timeToSeconds((string)$existingRow['hora_fin']);
                    if ($tExStart === null || $tExEnd === null || $tNewStart === null || $tNewEnd === null) continue;
                    if (\App\Models\DoctorSchedule::intervalsOverlap($tExStart, $tExEnd, $tNewStart, $tNewEnd)) {
                        return $res->view('horarios_doctores/create', [
                            'title' => 'Asignar horarios de atención',
                            'error' => "Fila " . ($idx + 1) . " (día {$dayKey}): el rango de horario se solapa con otro horario existente para este doctor/sede.",
                            'doctors' => Doctor::getAll(),
                            'sedes' => Sede::getAll(),
                            'horarios' => DoctorSchedule::listAll(),
                            'old' => $_POST
                        ]);
                    }
                } catch (\Throwable $__e) { continue; }
            }

            // Check patterns created earlier in this same request
            if (!empty($createdPatternIds)) {
                foreach ($createdPatternIds as $cpid) {
                    try {
                        $existing = DoctorSchedule::find((int)$cpid);
                        if (!$existing) continue;
                        // normalize day
                        if (mb_strtolower(trim((string)$existing->dia_semana)) !== mb_strtolower(trim((string)$dayKey))) continue;
                        // sede scoping
                        $existingSede = $existing->sede_id ?? null;
                        $loc = ($sedeForThisRow !== null && (int)$sedeForThisRow > 0) ? (int)$sedeForThisRow : null;
                        if ($loc !== null) {
                            if (!($existingSede === null || (int)$existingSede === $loc)) continue;
                        }
                        $tExStart = \App\Models\DoctorSchedule::timeToSeconds((string)$existing->hora_inicio);
                        $tExEnd = \App\Models\DoctorSchedule::timeToSeconds((string)$existing->hora_fin);
                        if ($tExStart === null || $tExEnd === null || $tNewStart === null || $tNewEnd === null) continue;
                        if (\App\Models\DoctorSchedule::intervalsOverlap($tExStart, $tExEnd, $tNewStart, $tNewEnd)) {
                            return $res->view('horarios_doctores/create', [
                                'title' => 'Asignar horarios de atención',
                                'error' => "Fila " . ($idx + 1) . " (día {$dayKey}): el rango de horario se solapa con otro horario creado en esta misma asignación.",
                                'doctors' => Doctor::getAll(),
                                'sedes' => Sede::getAll(),
                                'horarios' => DoctorSchedule::listAll(),
                                'old' => $_POST
                            ]);
                        }
                    } catch (\Throwable $__e) { continue; }
                }
            }

            // Evitar duplicar patrones idénticos (mismo doctor/sede/dia_semana/horas/mes/anio)
            if (DoctorSchedule::patternExists($doctorId, $sedeForThisRow, $dayKey, $sVal, $eVal, $targetMonthName, $anio)) {
                $existingId = DoctorSchedule::findPatternId($doctorId, $sedeForThisRow, $dayKey, $targetMonthName, $anio);
                // actualizar mes en el patrón existente si es diferente
                if ($existingId) {
                    $existing = DoctorSchedule::find($existingId);
                    if ($existing && (empty($existing->mes) || $existing->mes !== strtolower($months[$mes] ?? ''))) {
                        $existing->mes = strtolower($months[$mes] ?? '');
                        $existing->anio = $anio;
                        $existing->save();
                    }
                }
                // Acumular todos los patrones para este día (puede haber múltiples)
                if (!isset($patternIds[$dayKey])) $patternIds[$dayKey] = [];
                $patternIds[$dayKey][] = $existingId;
                continue;
            }

            try {
                $newId = DoctorSchedule::createPattern($doctorId, $sedeForThisRow ?: null, $dayKey, $sVal, $eVal, 'Creado desde asignación masiva', ($months[$mes] ?? null), $anio);
                // guardar el mes y año en el registro (si por alguna razón createPattern no lo setea)
                $months = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
                if ($newId) {
                    $p = DoctorSchedule::find($newId);
                    if ($p) {
                        $p->mes = $months[$mes] ?? null;
                        $p->anio = $anio;
                        $p->save();
                    }
                }
                // track created pattern to allow cleanup if later steps fail
                if ($newId && is_numeric($newId)) $createdPatternIds[] = (int)$newId;
                // Acumular patrones (puede haber múltiples por día)
                if (!isset($patternIds[$dayKey])) $patternIds[$dayKey] = [];
                $patternIds[$dayKey][] = $newId;
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

                // Puede haber múltiples patrones para el mismo día (diferentes horarios/sedes)
                $dayPatterns = is_array($patternIds[$dayKey]) ? $patternIds[$dayKey] : [$patternIds[$dayKey]];

                // Antes de crear, comprobar si la fecha es feriado (global o para la sede del patrón)
                try {
                    $pdoCheck = $pdo->prepare('SELECT id, fecha, tipo, activo, sede_id FROM feriados WHERE fecha = :f');
                    $pdoCheck->execute([':f' => $fecha]);
                    $feriados = $pdoCheck->fetchAll();
                } catch (\Throwable $e) {
                    $feriados = [];
                }

                // Procesar cada patrón del día (puede haber múltiples horarios/sedes)
                foreach ($dayPatterns as $usedHorarioId) {
                    // Validar feriado por sede del patrón
                    $pattern = DoctorSchedule::find($usedHorarioId);
                    $patternSedeId = $pattern?->sede_id ?? null;
                    
                    $isFeriado = false;
                    if (!empty($feriados)) {
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
                        continue; // Skip este patrón pero seguir con los demás del día
                    }

                    if (Calendario::existsFor($doctorId, $fecha, $usedHorarioId)) {
                        $skippedDuplicates++;
                        continue; // Skip este patrón pero seguir con los demás
                    }

                    // Obtener horas desde el patrón (guardadas en horarios_medicos)
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
                }

                $current->modify('+1 day');
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            // Rollback DB transaction if active
            if ($pdo->inTransaction()) $pdo->rollBack();

            // Cleanup any patterns we created earlier in this request to avoid leaving partial state
            if (!empty($createdPatternIds) && is_array($createdPatternIds)) {
                foreach ($createdPatternIds as $pid) {
                    try {
                        if ($pid && is_numeric($pid)) {
                            DoctorSchedule::hardDelete((int)$pid);
                        }
                    } catch (\Throwable $_e) {
                        // ignore cleanup errors
                    }
                }
            }

            return $res->abort(500, 'Error al crear registros en calendario/slots: ' . $e->getMessage());
        }

        // Preparar mensaje final: simplificar a una notificación concisa
        $msg = 'Horario creado';
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
     * Mostrar formulario de edición para un patrón existente
     */
    public function edit(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        $id = (int)($req->params['id'] ?? 0);
        if ($id <= 0) return $res->redirect('/doctor-schedules');

        $pattern = DoctorSchedule::find($id);
        if (!$pattern) {
            $_SESSION['flash'] = ['error' => 'Patrón no encontrado'];
            return $res->redirect('/doctor-schedules');
        }

        // Additionally load all patterns (horarios) for the same doctor and sede so the view can show them
        $doctorId = (int)($pattern->doctor_id ?? 0);
        $sedeId = $pattern->sede_id ?? null;
        $query = DoctorSchedule::where('doctor_id', $doctorId);
        if ($sedeId !== null) {
            $query->where(function($q) use ($sedeId) {
                $q->where('sede_id', $sedeId)->orWhereNull('sede_id');
            });
        } else {
            $query->whereNull('sede_id');
        }
        // Only patterns (dia_semana) — no concrete fechas here
        // Additionally exclude patterns that have ANY reserved slots in slots_calendario
        // i.e. where exists a slot with horario_id = horarios_medicos.id and reservado_por_cita_id IS NOT NULL
        $query->whereNotNull('dia_semana')
              ->whereNotIn('id', function($sub) {
                  $sub->select('horario_id')
                      ->from('slots_calendario')
                      ->whereNotNull('reservado_por_cita_id');
              })
              ->orderBy('dia_semana')
              ->orderBy('hora_inicio');

        $horarios = $query->get();

        return $res->view('horarios_doctores/edit', [
            'title' => 'Editar patrón',
            'pattern' => $pattern,
            'doctors' => Doctor::getAll(),
            'sedes' => Sede::getAll(),
            'horarios' => $horarios,
        ]);
    }

    /**
     * Mostrar edición basada en doctor y sede: /doctor-schedules/{doctor_id}/{sede_id}
     * Si existe al menos un patrón para ese doctor/sede, renderiza la vista de edición
     * para el primer patrón disponible (no reservado). Si no existe patrón, redirige a create
     * con doctor_id/sede_id en la querystring.
     */
    public function editByDoctorSede(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        $doctorId = (int)($req->params['doctor_id'] ?? 0);
        $sedeId = isset($req->params['sede_id']) ? (int)$req->params['sede_id'] : null;
        if ($doctorId <= 0) return $res->redirect('/doctor-schedules');

        // Normalize sedeId: treat 0 or missing as null
        if ($sedeId === 0) $sedeId = null;

        // Find first pattern for this doctor/sede that does NOT have reserved slots
        $query = DoctorSchedule::where('doctor_id', $doctorId)->whereNotNull('dia_semana');
        if ($sedeId !== null) {
            $query->where(function($q) use ($sedeId) {
                $q->where('sede_id', $sedeId)->orWhereNull('sede_id');
            });
        } else {
            $query->whereNull('sede_id');
        }
        // Exclude patterns with reserved slots
        $query->whereNotIn('id', function($sub) {
            $sub->select('horario_id')
                ->from('slots_calendario')
                ->whereNotNull('reservado_por_cita_id');
        });
        $pattern = $query->orderBy('hora_inicio')->first();

        if ($pattern) {
            // reuse existing edit flow: load horarios and render view
            $sedeIdForQuery = $pattern->sede_id ?? $sedeId;
            $q2 = DoctorSchedule::where('doctor_id', $doctorId);
            if ($sedeIdForQuery !== null) {
                $q2->where(function($q) use ($sedeIdForQuery) { $q->where('sede_id', $sedeIdForQuery)->orWhereNull('sede_id'); });
            } else {
                $q2->whereNull('sede_id');
            }
            $q2->whereNotNull('dia_semana')
               ->whereNotIn('id', function($sub) {
                   $sub->select('horario_id')->from('slots_calendario')->whereNotNull('reservado_por_cita_id');
               })->orderBy('dia_semana')->orderBy('hora_inicio');
            $horarios = $q2->get();

            return $res->view('horarios_doctores/edit', [
                'title' => 'Editar patrón',
                'pattern' => $pattern,
                'doctors' => Doctor::getAll(),
                'sedes' => Sede::getAll(),
                'horarios' => $horarios,
            ]);
        }

        // No pattern found: redirect to create with prefilled doctor_id and sede_id
        $qs = '?doctor_id=' . $doctorId;
        if ($sedeId !== null) $qs .= '&sede_id=' . $sedeId;
        return $res->redirect('/doctor-schedules/create' . $qs);
    }

    /**
     * Mostrar edición basada en doctor, sede y mes/año: /doctor-schedules/{doctor_id}/{sede_id}/{month}/{year}
     */
    public function editByDoctorSedeMonth(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        $doctorId = (int)($req->params['doctor_id'] ?? 0);
        $sedeId = isset($req->params['sede_id']) ? (int)$req->params['sede_id'] : null;
        $month = isset($req->params['month']) ? (int)$req->params['month'] : 0;
        $year = isset($req->params['year']) ? (int)$req->params['year'] : 0;

        if ($doctorId <= 0) return $res->redirect('/doctor-schedules');
        if ($month < 1 || $month > 12) return $res->redirect('/doctor-schedules');
        if ($year < 1970) $year = (int)date('Y');

        // Normalize sedeId: treat 0 as null
        if ($sedeId === 0) $sedeId = null;

        // Map month number to name used in DB
        $months = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
        $mesName = $months[$month] ?? null;

        // Find patterns for this doctor/sede that match the requested month/year (or are global) and have no reserved slots
        $query = DoctorSchedule::where('doctor_id', $doctorId)->whereNotNull('dia_semana');
        if ($sedeId !== null) {
            $query->where(function($q) use ($sedeId) { $q->where('sede_id', $sedeId)->orWhereNull('sede_id'); });
        } else {
            $query->whereNull('sede_id');
        }

        // month/year scoping: allow patterns that are global (mes NULL/empty) or match the requested month; year can be NULL or equal
        $query->where(function($q) use ($mesName, $year) {
            $q->whereNull('mes')->orWhere('mes', '')->orWhere('mes', $mesName);
        });
        $query->where(function($q) use ($year) {
            $q->whereNull('anio')->orWhere('anio', $year);
        });

        // exclude patterns that have reserved slots
        $query->whereNotIn('id', function($sub) {
            $sub->select('horario_id')->from('slots_calendario')->whereNotNull('reservado_por_cita_id');
        });

        $pattern = $query->orderBy('hora_inicio')->first();

        if ($pattern) {
            // load horarios similarly but scoped to mes/anio
            $q2 = DoctorSchedule::where('doctor_id', $doctorId)->whereNotNull('dia_semana');
            if ($sedeId !== null) {
                $q2->where(function($q) use ($sedeId) { $q->where('sede_id', $sedeId)->orWhereNull('sede_id'); });
            } else {
                $q2->whereNull('sede_id');
            }
            $q2->where(function($q) use ($mesName, $year) {
                $q->whereNull('mes')->orWhere('mes','')->orWhere('mes', $mesName);
            });
            $q2->where(function($q) use ($year) {
                $q->whereNull('anio')->orWhere('anio', $year);
            });
            $q2->whereNotIn('id', function($sub) {
                $sub->select('horario_id')->from('slots_calendario')->whereNotNull('reservado_por_cita_id');
            });
            $horarios = $q2->orderBy('dia_semana')->orderBy('hora_inicio')->get();

            // Provide the selected mes/anio to the view via $pattern (pattern may already have mes/anio)
            return $res->view('horarios_doctores/edit', [
                'title' => 'Editar patrón',
                'pattern' => $pattern,
                'doctors' => Doctor::getAll(),
                'sedes' => Sede::getAll(),
                'horarios' => $horarios,
            ]);
        }

        // No pattern for given criteria: redirect to create with doctor/sede/mes/anio
        $qs = '?doctor_id=' . $doctorId . '&mes=' . $month . '&anio=' . $year;
        if ($sedeId !== null) $qs .= '&sede_id=' . $sedeId;
        return $res->redirect('/doctor-schedules/create' . $qs);
    }

    /**
     * Procesar actualización de un patrón
     */
    public function update(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        if (!Csrf::verify((string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
            return $res->abort(419, 'CSRF inválido');
        }

        $id = (int)($req->params['id'] ?? 0);
        if ($id <= 0) return $res->redirect('/doctor-schedules');

        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $sedeId = (int)($_POST['sede_id'] ?? 0) ?: null;
    $postedMes = isset($_POST['mes']) ? (int)$_POST['mes'] : null;
    $postedAnio = isset($_POST['anio']) ? (int)$_POST['anio'] : null;
        $diaSemana = mb_strtolower(trim((string)($_POST['dia_semana'] ?? '')));
        $horaInicio = trim((string)($_POST['hora_inicio'] ?? ''));
        $horaFin = trim((string)($_POST['hora_fin'] ?? ''));
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));
        $activo = isset($_POST['activo']) && (int)$_POST['activo'] === 1 ? 1 : 0;

        $diasValidos = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo','miercoles','sabado'];
        if ($doctorId <= 0 || !$diaSemana || !$horaInicio || !$horaFin) {
            return $res->view('horarios_doctores/edit', [
                'title'=>'Editar patrón',
                'error'=>'Completa todos los campos obligatorios.',
                'pattern'=>DoctorSchedule::find($id),
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        if (!in_array($diaSemana, $diasValidos, true)) {
            return $res->view('horarios_doctores/edit', [
                'title'=>'Editar patrón',
                'error'=>'Día de la semana inválido.',
                'pattern'=>DoctorSchedule::find($id),
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        $timeRe = '/^([01]\d|2[0-3]):[0-5]\d$/';
        if (!preg_match($timeRe, $horaInicio) || !preg_match($timeRe, $horaFin)) {
            return $res->view('horarios_doctores/edit', [
                'title'=>'Editar patrón',
                'error'=>'Formato de hora inválido. Usa HH:MM.',
                'pattern'=>DoctorSchedule::find($id),
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        if (strtotime($horaInicio) >= strtotime($horaFin) || ((strtotime($horaFin)-strtotime($horaInicio))/60) < 15) {
            return $res->view('horarios_doctores/edit', [
                'title'=>'Editar patrón',
                'error'=>'La hora inicio debe ser menor que la hora fin y con al menos 15 minutos.',
                'pattern'=>DoctorSchedule::find($id),
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        // Validar solapamiento usando la nueva lógica
        $pattern = DoctorSchedule::find($id);
        // Determine mes/anio for overlap checks: prefer posted values, fallback to existing pattern
        $monthsMap = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
        $mes = null; $anio = null;
        if ($postedMes && isset($monthsMap[$postedMes])) {
            $mes = mb_strtolower($monthsMap[$postedMes]);
        } elseif ($pattern?->mes) {
            $mes = $pattern->mes;
        }
        if ($postedAnio && $postedAnio >= 1970) {
            $anio = (int)$postedAnio;
        } elseif ($pattern?->anio) {
            $anio = (int)$pattern->anio;
        }
        
        if (DoctorSchedule::patternsOverlap($doctorId, $sedeId, $diaSemana, $horaInicio, $horaFin, $mes, $anio, $id)) {
            return $res->view('horarios_doctores/edit', [
                'title'=>'Editar patrón',
                'error'=>'Este rango de horario se solapa con otro patrón existente. Un doctor puede tener múltiples horarios en el mismo día siempre que no se solapen.',
                'pattern'=>DoctorSchedule::find($id),
                'doctors'=>Doctor::getAll(),
                'sedes'=>Sede::getAll(),
                'old'=>$_POST
            ]);
        }

        // If the form submitted bulk horarios (multiple rows) handle them
        $pdo = Database::pdo();
        $postedHorarios = $_POST['horarios'] ?? null;
        if (is_array($postedHorarios) && count($postedHorarios) > 0) {
            try {
                $pdo->beginTransaction();
                $mainPattern = DoctorSchedule::find($id);
                if (!$mainPattern) throw new \RuntimeException('Patrón no encontrado');

                // Iterate posted horarios: expected structure horarios[<id>][field]
                $timeRe = '/^([01]\d|2[0-3]):[0-5]\d$/';
                $diasValidos = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo','miercoles','sabado'];
                foreach ($postedHorarios as $hid => $vals) {
                    $hid = (int)$hid;
                    if ($hid <= 0) continue;
                    $sch = DoctorSchedule::find($hid);
                    if (!$sch) continue;
                    // Only allow editing schedules belonging to the same doctor as the main pattern
                    if ((int)$sch->doctor_id !== (int)$mainPattern->doctor_id) continue;

                    $dSemana = mb_strtolower(trim((string)($vals['dia_semana'] ?? '')));
                    $hInicio = trim((string)($vals['hora_inicio'] ?? ''));
                    $hFin = trim((string)($vals['hora_fin'] ?? ''));
                    $sedeVal = isset($vals['sede_id']) && (string)$vals['sede_id'] !== '' ? (int)$vals['sede_id'] : null;
                    $obsVal = trim((string)($vals['observaciones'] ?? ''));
                    $activoVal = isset($vals['activo']) && (int)$vals['activo'] === 1 ? 1 : 0;

                    if ($dSemana === '' || $hInicio === '' || $hFin === '') {
                        throw new \RuntimeException('Campos obligatorios faltantes en uno de los horarios.');
                    }
                    if (!in_array($dSemana, $diasValidos, true)) {
                        throw new \RuntimeException('Día de la semana inválido en uno de los horarios.');
                    }
                    if (!preg_match($timeRe, $hInicio) || !preg_match($timeRe, $hFin)) {
                        throw new \RuntimeException('Formato de hora inválido en uno de los horarios.');
                    }
                    if (strtotime($hInicio) >= strtotime($hFin) || ((strtotime($hFin)-strtotime($hInicio))/60) < 15) {
                        throw new \RuntimeException('Uno de los horarios tiene hora inicio mayor o igual a hora fin o duración < 15 min.');
                    }

                    // Check overlaps (exclude current schedule id)
                    if (DoctorSchedule::patternsOverlap((int)$sch->doctor_id, $sedeVal, $dSemana, $hInicio, $hFin, $sch?->mes ?? null, $sch?->anio ?? null, $hid)) {
                        throw new \RuntimeException('Un horario se solapa con otro existente para el mismo doctor.');
                    }

                    // Persist changes
                    $sch->dia_semana = $dSemana;
                    $sch->hora_inicio = $hInicio;
                    $sch->hora_fin = $hFin;
                    $sch->sede_id = $sedeVal ?: null;
                    $sch->observaciones = $obsVal;
                    // Only update 'activo' if the field was present in the submitted data.
                    if (array_key_exists('activo', $vals)) {
                        $sch->activo = (bool)$activoVal;
                    }
                    $sch->save();
                }

                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                return $res->view('horarios_doctores/edit', [
                    'title'=>'Editar patrón',
                    'error'=>'Error al guardar: ' . $e->getMessage(),
                    'pattern'=>DoctorSchedule::find($id),
                    'doctors'=>Doctor::getAll(),
                    'sedes'=>Sede::getAll(),
                    'horarios'=>DoctorSchedule::where('doctor_id', (int)($pattern->doctor_id ?? 0))->get(),
                    'old'=>$_POST
                ]);
            }
        } else {
            try {
                $pdo->beginTransaction();

                $pattern = DoctorSchedule::find($id);
                if (!$pattern) throw new \RuntimeException('Patrón no encontrado');

                $pattern->doctor_id = $doctorId;
                $pattern->sede_id = $sedeId ?: null;
                $pattern->dia_semana = $diaSemana;
                $pattern->hora_inicio = $horaInicio;
                $pattern->hora_fin = $horaFin;
                $pattern->observaciones = $observaciones;
                $pattern->activo = (bool)$activo;
                // Save mes/anio if provided
                if (isset($mes)) $pattern->mes = $mes;
                if (isset($anio)) $pattern->anio = $anio;
                $pattern->save();

                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                return $res->view('horarios_doctores/edit', [
                    'title'=>'Editar patrón',
                    'error'=>'Error al guardar: ' . $e->getMessage(),
                    'pattern'=>DoctorSchedule::find($id),
                    'doctors'=>Doctor::getAll(),
                    'sedes'=>Sede::getAll(),
                    'old'=>$_POST
                ]);
            }
        }

        $_SESSION['flash'] = ['success' => 'Patrón actualizado'];
        return $res->redirect('/doctor-schedules');
    }

    /**
     * Aplicar un patrón existente al calendario (crea calendario + slots para próximos 30 días)
     * Responde JSON { success: bool, message: string, created: int, slots: int }
     */
    public function apply(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null; if (!$user) return $res->json(['success'=>false,'message'=>'Forbidden'], 403);
        Auth::abortUnless($res, ['superadmin']);

        $id = (int)($req->params['id'] ?? 0);
        if ($id <= 0) return $res->json(['success'=>false,'message'=>'ID inválido'], 400);

        // CSRF: accept POST body or header
        $token = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!Csrf::verify($token)) {
            return $res->json(['success'=>false,'message'=>'CSRF inválido'], 419);
        }

        $pattern = DoctorSchedule::find($id);
        if (!$pattern) return $res->json(['success'=>false,'message'=>'Patrón no encontrado'], 404);

        // Map day name to ISO weekday
        $map = ['lunes'=>1,'martes'=>2,'miércoles'=>3,'miercoles'=>3,'jueves'=>4,'viernes'=>5,'sábado'=>6,'sabado'=>6,'domingo'=>7];
        $targetDay = $map[mb_strtolower(trim((string)$pattern->dia_semana))] ?? null;
        if (!$targetDay) return $res->json(['success'=>false,'message'=>'Día de patrón inválido'], 400);

        // Ahora se requiere `mes` (1..12). No usamos el comportamiento de "próximos 30 días".
        $postedMes = isset($_POST['mes']) ? (int)$_POST['mes'] : null;
        $postedAnio = isset($_POST['anio']) ? (int)$_POST['anio'] : null;

        if (!($postedMes && $postedMes >= 1 && $postedMes <= 12)) {
            return $res->json(['success'=>false,'message'=>'Parámetro `mes` obligatorio (1-12).'], 400);
        }

        $nowMonth = (int)date('n');
        $currentYear = (int)date('Y');
        // Calcular año por defecto si no se envió
        if (!$postedAnio || $postedAnio < 1970) {
            $postedAnio = $currentYear;
            if ($nowMonth === 12 && $postedMes !== 12) {
                $postedAnio = $currentYear + 1;
            }
        }

        $start = \DateTimeImmutable::createFromFormat('Y-n-j', "{$postedAnio}-{$postedMes}-1");
        if (!$start) return $res->json(['success'=>false,'message'=>'Mes/año inválidos'], 400);
        $end = $start->modify('last day of this month');

        // Si el mes objetivo coincide con mes/año actual, comenzar desde mañana
        $today = new \DateTimeImmutable();
        $nowYear = (int)$today->format('Y');
        $nowMonth = (int)$today->format('n');
        if ($postedAnio === $nowYear && $postedMes === $nowMonth) {
            $tomorrow = $today->modify('+1 day');
            if ($tomorrow <= $end) {
                $start = $tomorrow;
            } else {
                return $res->json(['success'=>true,'message'=>'No hay días por generar en el mes seleccionado (restan 0 días).', 'created'=>0,'slots'=>0]);
            }
        }

        $pdo = Database::pdo();
        $created = 0; $slotsCreated = 0; $skipped = 0; $skippedHolidays = 0;

        try {
            $pdo->beginTransaction();

            $current = $start;
            while ($current <= $end) {
                $weekday = (int)$current->format('N');
                if ($weekday !== $targetDay) { $current = $current->modify('+1 day'); continue; }

                $fecha = $current->format('Y-m-d');

                // Check feriados
                $feriados = [];
                try {
                    $stmt = $pdo->prepare('SELECT id, fecha, tipo, activo, sede_id FROM feriados WHERE fecha = :f');
                    $stmt->execute([':f' => $fecha]);
                    $feriados = $stmt->fetchAll();
                } catch (\Throwable $e) {
                    $feriados = [];
                }

                $isFeriado = false;
                if (!empty($feriados)) {
                    $patternSedeId = $pattern->sede_id ?? null;
                    foreach ($feriados as $fer) {
                        $activo = $fer['activo'];
                        $activoFlag = ($activo === null) ? true : (bool)$activo;
                        if (!$activoFlag) continue;
                        if ($fer['sede_id'] === null || $fer['sede_id'] === '') { $isFeriado = true; break; }
                        if ($patternSedeId !== null && ((int)$fer['sede_id'] === (int)$patternSedeId)) { $isFeriado = true; break; }
                    }
                }

                if ($isFeriado) { $skippedHolidays++; $current = $current->modify('+1 day'); continue; }

                // Skip duplicates
                if (Calendario::existsFor($pattern->doctor_id, $fecha, $pattern->id)) { $skipped++; $current = $current->modify('+1 day'); continue; }

                $baseStart = $pattern->hora_inicio ? date('H:i', strtotime($pattern->hora_inicio)) : null;
                $baseEnd = $pattern->hora_fin ? date('H:i', strtotime($pattern->hora_fin)) : null;

                $calId = Calendario::createEntry($pattern->doctor_id, $pattern->id, $fecha, $baseStart, $baseEnd);
                $created++;

                if ($baseStart && $baseEnd) {
                    $n = SlotCalendario::createSlots($calId, $pattern->id, $baseStart, $baseEnd, 15);
                    $slotsCreated += $n;
                }

                $current = $current->modify('+1 day');
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return $res->json(['success'=>false,'message'=>'Error: '.$e->getMessage()], 500);
        }

        return $res->json(['success'=>true,'message'=>"Se crearon {$created} día(s). {$slotsCreated} slot(s) creados. {$skipped} omitidos por duplicado. {$skippedHolidays} omitidos por feriado.", 'created'=>$created, 'slots'=>$slotsCreated]);
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
