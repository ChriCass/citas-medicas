<?php
use App\Models\Service;
use App\Core\Availability;
use App\Models\{Location, User};

$router->group('/api/v1', function($r){
    $r->get('/ping', fn($req,$res)=>$res->json(['ok'=>true,'pong'=>time()]), ['json']);

    $r->get('/me', function($req,$res){
        if (empty($_SESSION['user'])) return $res->json(['message'=>'Unauthorized'], 401);
        return $res->json(['user'=>$_SESSION['user']]);
    }, ['json']);

    $r->get('/servicios', fn($req,$res)=>$res->json(['data'=>Service::allActive()]), ['json']);

    // NUEVO: lista de doctores y sedes activas (útil para autocompletar si deseas)
    $r->get('/doctors', function($req,$res){
        // Por simplicidad, todos los usuarios con role='doctor'
        $pdo = \App\Core\Database::pdo();
        $rows = $pdo->query("SELECT id,name,email FROM usuarios WHERE role='doctor' ORDER BY name")->fetchAll();
        return $res->json(['data'=>$rows]);
    }, ['json']);

    $r->get('/ubicaciones', function($req,$res){
        $pdo = \App\Core\Database::pdo();
        $rows = $pdo->query("SELECT id,name FROM ubicaciones WHERE is_active=1 ORDER BY name")->fetchAll();
        return $res->json(['data'=>$rows]);
    }, ['json']);

    // Slots requieren date + doctor_id + location_id (service_id opcional)
    $r->get('/slots', function($req,$res){
        $date = $req->query['date']        ?? null;
        $doctorId  = (int)($req->query['doctor_id']   ?? 0);
        $locationId= (int)($req->query['location_id'] ?? 0);

        if (!$date || !$doctorId || !$locationId) {
            return $res->json(['message'=>'Bad Request (date, doctor_id, location_id)'], 400);
        }
        try {
            $slots = Availability::slotsForDate(new \DateTimeImmutable($date), $doctorId, $locationId);
            return $res->json(['date'=>$date, 'slot_minutes'=>\App\Core\Availability::SLOT_MINUTES, 'slots'=>$slots]);
        } catch(\Throwable $e) {
            return $res->json(['message'=>'Error','error'=>$e->getMessage()], 500);
        }
    }, ['json']);
});
