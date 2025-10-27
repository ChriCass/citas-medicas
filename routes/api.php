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
try {
// Usar el servicio híbrido para obtener o generar disponibilidad
$service = new \App\Services\ScheduleGeneratorService();
$availability = $service->getOrGenerateAvailability($doctorId, $date, $locationId ?: null);

return $res->json([
    'date' => $date,
    'calendario_id' => $availability['calendario_id'],
    'slot_minutes' => 15,
    'slots' => $availability['slots']
]);
} catch(\Throwable $e) {
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