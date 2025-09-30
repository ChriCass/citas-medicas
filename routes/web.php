<?php
use App\Controllers\{HomeController,AuthController,AppointmentController,DoctorScheduleController};

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

$router->post('/citas/{id}/cancel',   [AppointmentController::class, 'cancel'], ['auth']);
$router->post('/citas/{id}/status',   [AppointmentController::class, 'updateStatus'], ['auth']);   // cashier/superadmin
$router->post('/citas/{id}/attended', [AppointmentController::class, 'markAttended'], ['auth']);   // doctor
$router->post('/citas/{id}/payment',  [AppointmentController::class, 'updatePayment'], ['auth']);  // cashier

// Horarios Doctores (solo superadmin; el controlador valida el rol)
$router->get('/doctor-schedules',          [DoctorScheduleController::class, 'index'],  ['auth']);
$router->get('/doctor-schedules/create',   [DoctorScheduleController::class, 'create'], ['auth']);
$router->post('/doctor-schedules',         [DoctorScheduleController::class, 'store'],  ['auth']);
$router->post('/doctor-schedules/{id}/delete', [DoctorScheduleController::class, 'destroy'], ['auth']);
