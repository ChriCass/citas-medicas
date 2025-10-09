<?php
require_once 'vendor/autoload.php';

use App\Core\{Availability, Database, Env};
use App\Models\{DoctorSchedule, Appointment};

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
    $date = new \DateTimeImmutable('2025-10-13');
    $doctorId = 1;
    $locationId = 1;
    
    echo "=== ANÁLISIS DETALLADO DE SLOTS ===\n";
    echo "Fecha: " . $date->format('Y-m-d') . "\n";
    echo "Doctor ID: $doctorId\n";
    echo "Sede ID: $locationId\n\n";
    
    // Obtener horarios
    $schedules = DoctorSchedule::forDate($doctorId, $locationId, $date->format('Y-m-d'));
    
    foreach ($schedules as $i => $sch) {
        echo "=== HORARIO " . ($i + 1) . " ===\n";
        echo "Hora inicio: {$sch['hora_inicio']}\n";
        echo "Hora fin: {$sch['hora_fin']}\n";
        
        // Limpiar microsegundos
        $startTimeClean = substr($sch['hora_inicio'], 0, 8);
        $endTimeClean = substr($sch['hora_fin'], 0, 8);
        
        echo "Hora inicio limpia: $startTimeClean\n";
        echo "Hora fin limpia: $endTimeClean\n";
        
        $start = new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $startTimeClean);
        $end = new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $endTimeClean);
        
        echo "Inicio DateTime: " . $start->format('Y-m-d H:i:s') . "\n";
        echo "Fin DateTime: " . $end->format('Y-m-d H:i:s') . "\n";
        
        // Generar todos los slots teóricos
        echo "Slots teóricos:\n";
        $theoreticalSlots = [];
        for ($t = $start; $t < $end; $t = $t->modify('+15 minutes')) {
            $tEnd = $t->modify('+15 minutes');
            if ($tEnd > $end) break;
            
            $slot = $t->format('H:i');
            $theoreticalSlots[] = $slot;
            
            // Verificar si hay conflicto
            $startTimeOnly = $t->format('H:i:s');
            $endTimeOnly = $tEnd->format('H:i:s');
            $hasConflict = Appointment::overlapsWindow($date->format('Y-m-d'), $startTimeOnly, $endTimeOnly, $doctorId, $locationId);
            
            echo "  $slot - " . ($hasConflict ? "OCUPADO" : "DISPONIBLE") . "\n";
        }
        echo "\n";
    }
    
    // Mostrar citas existentes
    echo "=== CITAS EXISTENTES ===\n";
    $pdo = Database::pdo();
    $stmt = $pdo->prepare("
        SELECT hora_inicio, hora_fin, estado, razon 
        FROM citas 
        WHERE doctor_id = ? AND fecha = ? AND estado != 'cancelado'
        ORDER BY hora_inicio
    ");
    $stmt->execute([$doctorId, $date->format('Y-m-d')]);
    $existingAppointments = $stmt->fetchAll();
    
    foreach ($existingAppointments as $apt) {
        echo "- {$apt['hora_inicio']} a {$apt['hora_fin']} - {$apt['estado']} - {$apt['razon']}\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
