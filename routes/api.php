<?php
use App\Models\Service;
use App\Core\Availability;


$router->group('/api/v1', function($r){
$r->get('/ping', fn($req,$res)=>$res->json(['ok'=>true,'pong'=>time()]), ['json']);


$r->get('/me', function($req,$res){
if (empty($_SESSION['user'])) return $res->json(['message'=>'Unauthorized'], 401);
return $res->json(['user'=>$_SESSION['user']]);
}, ['json']);


// Servicios activos (ajusta a tu modelo si mapea a 'servicios')
$r->get('/servicios', fn($req,$res)=>$res->json(['data'=>Service::allActive()]), ['json']);


// Doctores con especialidad
$r->get('/doctors', function($req,$res){
$pdo = \App\Core\Database::pdo();
$rows = $pdo->query("\n SELECT d.id, u.nombre, u.apellido, u.email, e.nombre AS especialidad_nombre\n FROM doctores d\n JOIN usuarios u ON d.usuario_id = u.id\n LEFT JOIN especialidades e ON d.especialidad_id = e.id\n ORDER BY u.nombre\n ")->fetchAll();
return $res->json(['data'=>$rows]);
}, ['json']);


// Pacientes
$r->get('/pacientes', function($req,$res){
$pdo = \App\Core\Database::pdo();
$rows = $pdo->query("\n SELECT p.id, u.nombre, u.apellido, u.email, u.telefono\n FROM pacientes p\n JOIN usuarios u ON p.usuario_id = u.id\n ORDER BY u.nombre\n ")->fetchAll();
return $res->json(['data'=>$rows]);
}, ['json']);


// Sedes (no 'ubicaciones')
$r->get('/sedes', function($req,$res){
$pdo = \App\Core\Database::pdo();
$doctorId = (int)($req->query['doctor_id'] ?? 0);
if ($doctorId > 0) {
	// Devolver solo sedes vinculadas al doctor (tabla pivot doctor_sede)
	$stmt = $pdo->prepare("\n SELECT s.id, s.nombre_sede AS nombre, s.direccion, s.telefono\n FROM sedes s\n JOIN doctor_sede ds ON ds.sede_id = s.id\n WHERE ds.doctor_id = :doctor_id\n ORDER BY s.nombre_sede\n ");
	$stmt->execute(['doctor_id' => $doctorId]);
	$rows = $stmt->fetchAll();
	return $res->json(['data'=>$rows]);
}

$rows = $pdo->query("\n SELECT id, nombre_sede AS nombre, direccion, telefono\n FROM sedes\n ORDER BY nombre_sede\n ")->fetchAll();
return $res->json(['data'=>$rows]);
}, ['json']);


// Especialidades
$r->get('/especialidades', function($req,$res){
$pdo = \App\Core\Database::pdo();
$rows = $pdo->query("\n SELECT id, nombre, descripcion\n FROM especialidades\n ORDER BY nombre\n ")->fetchAll();
return $res->json(['data'=>$rows]);
}, ['json']);


// Slots: se requiere date + doctor_id + location_id (id de sede)
$r->get('/slots', function($req,$res){
$date = $req->query['date'] ?? null;
$doctorId = (int)($req->query['doctor_id'] ?? 0);
$locationId = (int)($req->query['location_id'] ?? 0); // sede_id


if (!$date || !$doctorId) {
return $res->json(['message'=>'Bad Request (date, doctor_id requeridos)'], 400);
}
// try {
// // Usar el servicio híbrido para obtener o generar disponibilidad
// $service = new \App\Services\ScheduleGeneratorService();
// $availability = $service->getOrGenerateAvailability($doctorId, $date, $locationId ?: null);

// return $res->json([
//     'date' => $date,
//     'calendario_id' => $availability['calendario_id'],
//     'slot_minutes' => 15,
//     'slots' => $availability['slots']
// ]);
// } catch(\Throwable $e) {
// return $res->json(['message'=>'Error','error'=>$e->getMessage()], 500);
// }
}, ['json']);

// Fechas disponibles en calendario para un doctor (opcionalmente filtradas por sede)
$r->get('/calendario/fechas', function($req,$res){
	$doctorId = (int)($req->query['doctor_id'] ?? 0);
	$sedeId = (int)($req->query['sede_id'] ?? 0);
	if (!$doctorId) return $res->json(['message'=>'Bad Request (doctor_id requerido)'], 400);

	$pdo = \App\Core\Database::pdo();
	// Unir con horarios_medicos para poder filtrar por sede si se solicita
	$sql = "SELECT DISTINCT CONVERT(VARCHAR(10), c.fecha, 23) AS fecha_str, c.fecha
			FROM calendario c
			LEFT JOIN horarios_medicos hm ON c.horario_id = hm.id
			WHERE c.doctor_id = :doctor_id";
	if ($sedeId > 0) {
		$sql .= " AND hm.sede_id = :sede_id";
	}
	$sql .= " ORDER BY c.fecha";

	$stmt = $pdo->prepare($sql);
	$params = ['doctor_id' => $doctorId];
	if ($sedeId > 0) $params['sede_id'] = $sedeId;
	$stmt->execute($params);
	$rows = $stmt->fetchAll();

	// Normalizar a lista simple de strings YYYY-MM-DD
	$dates = array_map(function($r){ return $r['fecha_str'] ?? (is_string($r['fecha'])? $r['fecha'] : null); }, $rows);
	return $res->json(['data'=>$dates]);
}, ['json']);

// Slots desde tabla slots_calendario para doctor + fecha (+ opcional sede via horarios_medicos)
$r->get('/slots_db', function($req,$res){
	$date = $req->query['date'] ?? null;
	$doctorId = (int)($req->query['doctor_id'] ?? 0);
	$locationId = (int)($req->query['location_id'] ?? 0);

	if (!$date || !$doctorId) {
		return $res->json(['message'=>'Bad Request (date, doctor_id requeridos)'], 400);
	}

	try {
		$pdo = \App\Core\Database::pdo();
	$sql = "SELECT sc.hora_inicio, sc.hora_fin, sc.disponible, sc.reservado_por_cita_id, sc.calendario_id
		FROM slots_calendario sc
				JOIN calendario c ON sc.calendario_id = c.id
				LEFT JOIN horarios_medicos hm ON c.horario_id = hm.id
				WHERE c.doctor_id = :doctor_id AND c.fecha = :fecha";
		if ($locationId > 0) {
			$sql .= " AND hm.sede_id = :location_id";
		}
		$sql .= " ORDER BY sc.hora_inicio";

		$stmt = $pdo->prepare($sql);
		$params = ['doctor_id' => $doctorId, 'fecha' => $date];
		if ($locationId > 0) $params['location_id'] = $locationId;
		$stmt->execute($params);
		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		$slots = [];
		foreach ($rows as $r) {
			// considerar disponible solo si disponible truthy y no reservado
			$disponible = isset($r['disponible']) ? (bool)$r['disponible'] : true;
			$reservado = isset($r['reservado_por_cita_id']) && $r['reservado_por_cita_id'];
			if ($disponible && !$reservado) {
				// hora_inicio puede venir como 'HH:MM:SS' -> devolver 'HH:MM'
				$hi = substr($r['hora_inicio'],0,5);
				$hf = isset($r['hora_fin']) ? substr($r['hora_fin'],0,5) : null;
				$slots[] = [
					'calendario_id' => $r['calendario_id'] ?? null,
					'hora_inicio' => $hi,
					'hora_fin' => $hf
				];
			}
		}

		return $res->json(['date'=>$date, 'slots'=>$slots]);
	} catch (\Throwable $e) {
		return $res->json(['message'=>'Error','error'=>$e->getMessage()], 500);
	}
}, ['json']);

// Diagnósticos: autocompletado - devuelve lista simple de nombres
$r->get('/diagnosticos', function($req,$res){
	$q = trim((string)($req->query['q'] ?? ''));
    // Si la query está vacía, interpretamos que el cliente quiere la lista completa

	try {
		// Obtener PDO (Database::pdo o Eloquent fallback)
		try {
			$pdo = \App\Core\Database::pdo();
		} catch (\Throwable $e) {
			$pdo = \App\Core\Eloquent::connection()->getPdo();
		}

		// Buscar id y nombre_enfermedad
		$sql = "SELECT id, nombre_enfermedad FROM diagnosticos WHERE nombre_enfermedad LIKE :q";
		// Ajuste para MySQL
		if (!\App\Core\Database::isSqlServer()) {
			$sql = "SELECT id, nombre_enfermedad FROM diagnosticos WHERE nombre_enfermedad LIKE :q";
		}

		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':q', "%{$q}%", \PDO::PARAM_STR);
		$stmt->execute();
		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		return $res->json(['data'=>$rows]);
	} catch (\Throwable $e) {
		return $res->json(['data'=>[], 'error'=>$e->getMessage()], 500);
	}
}, ['json']);
});