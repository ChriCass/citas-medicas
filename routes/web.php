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
$router->get('/citas/today', [AppointmentController::class, 'index'], ['auth']);
$router->get('/citas/create', [AppointmentController::class, 'create'], ['auth']);
$router->post('/citas', [AppointmentController::class, 'store'], ['auth']);

$router->post('/citas/{id}/cancel',   [AppointmentController::class, 'cancel'], ['auth']);
$router->post('/citas/{id}/status',   [AppointmentController::class, 'updateStatus'], ['auth']);   // cashier/superadmin
$router->post('/citas/{id}/attended', [AppointmentController::class, 'markAttended'], ['auth']);   // doctor
// marcar ausente (médico) - botón en la vista today
$router->post('/citas/{id}/ausente',  [AppointmentController::class, 'markAbsent'], ['auth']);
$router->post('/citas/{id}/payment',  [AppointmentController::class, 'updatePayment'], ['auth']);  // cashier

// Atención de citas: mostrar formulario y procesar (médico)
$router->get('/citas/{id}/attend',  [AppointmentController::class, 'attendForm'], ['auth']);
$router->post('/citas/{id}/attend', [AppointmentController::class, 'attendStore'], ['auth']);

// Editar cita (médico)
$router->get('/citas/{id}/edit',  [AppointmentController::class, 'editForm'], ['auth']);
$router->post('/citas/{id}/edit', [AppointmentController::class, 'editStore'], ['auth']);

// Horarios Doctores (solo superadmin; el controlador valida el rol)
$router->get('/doctor-schedules',          [DoctorScheduleController::class, 'index'],  ['auth']);
$router->get('/doctor-schedules/create',   [DoctorScheduleController::class, 'create'], ['auth']);
$router->post('/doctor-schedules',         [DoctorScheduleController::class, 'store'],  ['auth']);
$router->post('/doctor-schedules/{id}/delete', [DoctorScheduleController::class, 'destroy'], ['auth']);
// Ruta para asignación masiva de horarios (UC-09)
$router->post('/doctor-schedules/assign',  [DoctorScheduleController::class, 'assign'], ['auth']);
// Editar/actualizar patrón y aplicar al calendario
$router->get('/doctor-schedules/{doctor_id}/{sede_id}', [DoctorScheduleController::class, 'editByDoctorSede'], ['auth']);
// Edit by doctor/sede for a specific month/year (new route)
$router->get('/doctor-schedules/{doctor_id}/{sede_id}/{month}/{year}', [DoctorScheduleController::class, 'editByDoctorSedeMonth'], ['auth']);
$router->get('/doctor-schedules/{id}/edit',   [DoctorScheduleController::class, 'edit'],   ['auth']);
$router->post('/doctor-schedules/{id}/update', [DoctorScheduleController::class, 'update'], ['auth']);
$router->post('/doctor-schedules/{id}/apply',  [DoctorScheduleController::class, 'apply'],  ['auth']);
// Endpoint AJAX: obtener sedes de un doctor
$router->get('/doctors/{id}/sedes', [DoctorScheduleController::class, 'doctorSedes'], ['auth']);
