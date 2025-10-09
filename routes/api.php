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
$slots = Availability::slotsForDate(new \DateTimeImmutable($date), $doctorId, $locationId);
return $res->json(['date'=>$date, 'slot_minutes'=>\App\Core\Availability::SLOT_MINUTES, 'slots'=>$slots]);
} catch(\Throwable $e) {
return $res->json(['message'=>'Error','error'=>$e->getMessage()], 500);
}
}, ['json']);
});