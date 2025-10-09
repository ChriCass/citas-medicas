<?php
require_once 'vendor/autoload.php';

use App\Core\{Availability, Database, Env};
use App\Models\DoctorSchedule;

// Cargar variables de entorno
Env::load(__DIR__ . '/.env');

// Configurar base de datos
Database::init([
    'driver'=>Env::get('DB_CONNECTION','mysql'),
    'host'=>Env::get('DB_HOST','127.0.0.1'),
    'port'=>(int)Env::get('DB_PORT','3306'),
    'database'=>Env::get('DB_DATABASE',''),
    'username'=>Env::get('DB_USERNAME',''),
    'password'=>Env::get('DB_PASSWORD',''),
]);

try {
    // Probar con una fecha más adelante que tenga menos citas
    $date = new \DateTimeImmutable('2025-10-20'); // Lunes de la siguiente semana
    $doctorId = 1;
    $locationId = 1;
    
    echo "=== PRUEBA CON FECHA FUTURA ===\n";
    echo "Fecha: " . $date->format('Y-m-d') . "\n";
    echo "Doctor ID: $doctorId\n";
    echo "Sede ID: $locationId\n\n";
    
    // Verificar horarios
    $schedules = DoctorSchedule::forDate($doctorId, $locationId, $date->format('Y-m-d'));
    echo "=== HORARIOS ===\n";
    foreach ($schedules as $sch) {
        echo "- {$sch['hora_inicio']} a {$sch['hora_fin']}\n";
    }
    echo "\n";
    
    // Obtener slots
    $slots = Availability::slotsForDate($date, $doctorId, $locationId);
    echo "=== SLOTS DISPONIBLES ===\n";
    foreach ($slots as $slot) {
        echo "- $slot\n";
    }
    echo "\nTotal slots: " . count($slots) . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}