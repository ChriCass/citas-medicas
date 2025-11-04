<?php
use App\Controllers\{HomeController,AuthController,AppointmentController,DoctorScheduleController,PaymentController,ApiController, UserController};

$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'dashboard'], ['auth']);

$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

$router->get('/citas', [AppointmentController::class, 'index'], ['auth']);
$router->get('/citas/create', [AppointmentController::class, 'create'], ['auth']);
$router->post('/citas', [AppointmentController::class, 'store'], ['auth']);
$router->get('/citas/{id}/edit', [AppointmentController::class, 'edit'], ['auth']);
$router->post('/citas/{id}/update', [AppointmentController::class, 'update'], ['auth']);

$router->post('/citas/{id}/cancel',   [AppointmentController::class, 'cancel'], ['auth']);
$router->post('/citas/{id}/status',   [AppointmentController::class, 'updateStatus'], ['auth']);   // cashier/superadmin
$router->post('/citas/{id}/attended', [AppointmentController::class, 'markAttended'], ['auth']);   // doctor
$router->post('/citas/{id}/payment',  [AppointmentController::class, 'updatePayment'], ['auth']);  // cashier

// Horarios Doctores (solo superadmin; el controlador valida el rol)
$router->get('/doctor-schedules',          [DoctorScheduleController::class, 'index'],  ['auth']);
$router->get('/doctor-schedules/create',   [DoctorScheduleController::class, 'create'], ['auth']);
$router->post('/doctor-schedules',         [DoctorScheduleController::class, 'store'],  ['auth']);
$router->post('/doctor-schedules/{id}/delete', [DoctorScheduleController::class, 'destroy'], ['auth']);

// Gestión de Pagos (solo cajeros)
$router->get('/pagos',                     [PaymentController::class, 'index'],         ['auth', 'cashier']);
$router->get('/pagos/buscar',              [PaymentController::class, 'search'],        ['auth', 'cashier']);
$router->get('/pagos/registrar',           [PaymentController::class, 'show'],          ['auth', 'cashier']);
$router->post('/pagos/registrar',          [PaymentController::class, 'store'],         ['auth', 'cashier']);
$router->get('/pagos/registrar-manual',    [PaymentController::class, 'showManual'],    ['auth', 'cashier']);
$router->post('/pagos/registrar-manual',   [PaymentController::class, 'storeManual'],   ['auth', 'cashier']);
$router->get('/pagos/{id}/editar',         [PaymentController::class, 'edit'],          ['auth', 'cashier']);
$router->post('/pagos/{id}/editar',        [PaymentController::class, 'update'],        ['auth', 'cashier']);
$router->post('/pagos/{id}/eliminar',      [PaymentController::class, 'destroy'],       ['auth', 'cashier']);
$router->get('/pagos/comprobante',         [PaymentController::class, 'receipt'],       ['auth', 'cashier']);

// API endpoints
$router->get('/api/v1/pacientes/search',   [ApiController::class, 'searchPacientes'],   ['auth']);
$router->get('/api/v1/slots',              [ApiController::class, 'getSlots'],           ['auth']);
$router->get('/api/especialidades',        [ApiController::class, 'getEspecialidades'],  ['auth']);

// Gestión de Usuarios (solo superadmin; el controlador valida el rol)
$router->get('/users', [UserController::class, 'index'], ['auth']);
$router->get('/users/create', [UserController::class, 'create'], ['auth']);
$router->get('/users/{id}/edit', [UserController::class, 'edit'], ['auth']);
$router->get('/api/users', [UserController::class, 'apiList'], ['auth']);
$router->get('/api/users/{id}', [UserController::class, 'apiShow'], ['auth']);
$router->post('/api/users', [UserController::class, 'store'], ['auth']);
$router->post('/api/users/{id}', [UserController::class, 'updateOrDelete'], ['auth']);
$router->delete('/api/users/{id}', [UserController::class, 'destroy'], ['auth']);

// Endpoint para verificar relaciones antes de eliminar usuario
$router->get('/api/users/{id}/relationships', [UserController::class, 'getUserRelationships'], ['auth']);
