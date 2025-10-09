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
    // Probar obtener slots para el Dr. Juan Carlos (doctor_id=1) en la sede central (sede_id=1) para mañana
    $date = new \DateTimeImmutable('2025-10-13'); // Fecha con horarios definidos en el schema
    $doctorId = 1;
    $locationId = 1;
    
    echo "=== PRUEBA DE SLOTS ===\n";
    echo "Fecha: " . $date->format('Y-m-d') . "\n";
    echo "Doctor ID: $doctorId\n";
    echo "Sede ID: $locationId\n\n";
    
    // Primero verificar horarios en la base de datos
    echo "=== HORARIOS EN BD ===\n";
    $schedules = DoctorSchedule::forDate($doctorId, $locationId, $date->format('Y-m-d'));
    var_dump($schedules);
    echo "\n";
    
    // Ahora obtener slots disponibles
    echo "=== SLOTS DISPONIBLES ===\n";
    $slots = Availability::slotsForDate($date, $doctorId, $locationId);
    var_dump($slots);
    echo "\n";
    
    echo "Total slots: " . count($slots) . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}