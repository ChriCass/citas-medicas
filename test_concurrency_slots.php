<?php

/**
 * TEST DE CONCURRENCIA DE SLOTS DE CALENDARIO
 * 
 * Este test simula dos pacientes intentando reservar el mismo slot
 * simultáneamente para verificar el bloqueo optimista con row_version
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\{Env, Eloquent};
use App\Models\{Appointment, Paciente, Doctor, Sede};
use Illuminate\Database\Capsule\Manager as DB;

// Configuración de colores para la consola
class TestColors {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

class ConcurrencySlotTest {
    
    public function __construct() {
        echo TestColors::BOLD . TestColors::BLUE . "=== TEST DE CONCURRENCIA DE SLOTS ===" . TestColors::RESET . "\n\n";
        
        // Inicializar Eloquent
        try {
            Env::load(__DIR__ . '/.env');
            Eloquent::init();
            $this->log("✓ Sistema inicializado correctamente", 'success');
        } catch (Exception $e) {
            $this->log("✗ Error inicializando sistema: " . $e->getMessage(), 'error');
            exit(1);
        }
    }
    
    private function log($message, $type = 'info') {
        $color = match($type) {
            'success' => TestColors::GREEN,
            'error' => TestColors::RED,
            'warning' => TestColors::YELLOW,
            'info' => TestColors::CYAN,
            default => TestColors::RESET
        };
        
        echo $color . $message . TestColors::RESET . "\n";
    }
    
    /**
     * Obtiene o crea datos de prueba necesarios
     */
    private function prepareTestData() {
        $this->log("\n--- Preparando datos de prueba ---", 'info');
        
        // Buscar dos pacientes existentes
        $pacientes = Paciente::with('user')->limit(2)->get();
        
        if ($pacientes->count() < 2) {
            $this->log("✗ Se necesitan al menos 2 pacientes en la base de datos", 'error');
            return null;
        }
        
        $paciente1 = $pacientes[0];
        $paciente2 = $pacientes[1];
        
        $this->log("✓ Paciente 1: {$paciente1->user->nombre} {$paciente1->user->apellido} (ID: {$paciente1->id})", 'success');
        $this->log("✓ Paciente 2: {$paciente2->user->nombre} {$paciente2->user->apellido} (ID: {$paciente2->id})", 'success');
        
        // Buscar un doctor
        $doctor = Doctor::with('user')->first();
        if (!$doctor) {
            $this->log("✗ No se encontró ningún doctor en la base de datos", 'error');
            return null;
        }
        
        $this->log("✓ Doctor: {$doctor->user->nombre} {$doctor->user->apellido} (ID: {$doctor->id})", 'success');
        
        // Buscar una sede
        $sede = Sede::first();
        if (!$sede) {
            $this->log("✗ No se encontró ninguna sede en la base de datos", 'error');
            return null;
        }
        
        $this->log("✓ Sede: {$sede->nombre_sede} (ID: {$sede->id})", 'success');
        
        // Buscar un slot disponible
        $slot = DB::table('slots_calendario')
            ->whereNull('reservado_por_cita_id')
            ->where('disponible', 1)
            ->first();
        
        if (!$slot) {
            $this->log("✗ No se encontró ningún slot disponible en slots_calendario", 'error');
            return null;
        }
        
        $this->log("✓ Slot encontrado: ID={$slot->id}, Calendario={$slot->calendario_id}, Hora={$slot->hora_inicio}", 'success');
        
        return [
            'paciente1' => $paciente1,
            'paciente2' => $paciente2,
            'doctor' => $doctor,
            'sede' => $sede,
            'slot' => $slot
        ];
    }
    
    /**
     * Intenta crear una cita para un paciente en un slot específico
     */
    private function reserveSlot($pacienteId, $doctorId, $sedeId, $calendarioId, $slotHora, $fecha, $pacienteNombre) {
        try {
            $horaInicio = substr($slotHora, 0, 5) . ':00'; // Convertir HH:MM a HH:MM:00
            $horaFin = date('H:i:s', strtotime($horaInicio . ' +15 minutes'));
            
            // Intentar crear la cita usando el método con bloqueo optimista
            $citaId = Appointment::create(
                $pacienteId,
                $doctorId,
                $sedeId,
                $fecha,
                $horaInicio,
                $horaFin,
                'Consulta de prueba concurrencia',
                $calendarioId,
                substr($slotHora, 0, 5) // HH:MM
            );
            
            return [
                'success' => true,
                'citaId' => $citaId,
                'paciente' => $pacienteNombre
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'paciente' => $pacienteNombre
            ];
        }
    }
    
    /**
     * Test 1: Simulación de reserva secuencial (sin concurrencia real)
     * Intenta que el paciente 1 reserve, luego el paciente 2 intente el mismo slot
     */
    public function testSequentialReservation() {
        $this->log("\n" . TestColors::BOLD . "--- TEST 1: Reserva Secuencial (Control) ---" . TestColors::RESET, 'info');
        
        $data = $this->prepareTestData();
        if (!$data) {
            return false;
        }
        
        $paciente1 = $data['paciente1'];
        $paciente2 = $data['paciente2'];
        $doctor = $data['doctor'];
        $sede = $data['sede'];
        $slot = $data['slot'];
        
        // Obtener calendario del slot
        $calendario = DB::table('calendario')->where('id', $slot->calendario_id)->first();
        if (!$calendario) {
            $this->log("✗ No se encontró el calendario asociado al slot", 'error');
            return false;
        }
        
        $fecha = $calendario->fecha;
        $slotHora = $slot->hora_inicio;
        
        $this->log("\nIntentando reserva para Paciente 1...", 'info');
        $result1 = $this->reserveSlot(
            $paciente1->id,
            $doctor->id,
            $sede->id,
            $slot->calendario_id,
            $slotHora,
            $fecha,
            "{$paciente1->user->nombre} {$paciente1->user->apellido}"
        );
        
        if ($result1['success']) {
            $this->log("✓ Paciente 1 reservó exitosamente el slot (Cita ID: {$result1['citaId']})", 'success');
        } else {
            $this->log("✗ Paciente 1 no pudo reservar: {$result1['error']}", 'error');
            return false;
        }
        
        // Esperar un momento para simular secuencialidad
        usleep(100000); // 100ms
        
        $this->log("\nIntentando reserva para Paciente 2 (mismo slot)...", 'info');
        $result2 = $this->reserveSlot(
            $paciente2->id,
            $doctor->id,
            $sede->id,
            $slot->calendario_id,
            $slotHora,
            $fecha,
            "{$paciente2->user->nombre} {$paciente2->user->apellido}"
        );
        
        if (!$result2['success']) {
            $this->log("✓ Paciente 2 fue correctamente rechazado: {$result2['error']}", 'success');
            $this->log("✓ El bloqueo optimista funcionó correctamente", 'success');
        } else {
            $this->log("✗ ERROR: Paciente 2 pudo reservar el mismo slot (Cita ID: {$result2['citaId']})", 'error');
            $this->log("✗ El bloqueo optimista NO funcionó", 'error');
            return false;
        }
        
        // Limpiar: eliminar la cita creada
        if ($result1['success']) {
            DB::table('citas')->where('id', $result1['citaId'])->delete();
            DB::table('slots_calendario')
                ->where('id', $slot->id)
                ->update([
                    'reservado_por_cita_id' => null,
                    'disponible' => 1
                ]);
            $this->log("\n✓ Limpieza completada", 'info');
        }
        
        return true;
    }
    
    /**
     * Test 2: Simulación de concurrencia usando procesos paralelos
     */
    public function testConcurrentReservation() {
        $this->log("\n" . TestColors::BOLD . "--- TEST 2: Reserva Concurrente (Procesos Paralelos) ---" . TestColors::RESET, 'info');
        
        $data = $this->prepareTestData();
        if (!$data) {
            return false;
        }
        
        $paciente1 = $data['paciente1'];
        $paciente2 = $data['paciente2'];
        $doctor = $data['doctor'];
        $sede = $data['sede'];
        $slot = $data['slot'];
        
        // Obtener calendario del slot
        $calendario = DB::table('calendario')->where('id', $slot->calendario_id)->first();
        if (!$calendario) {
            $this->log("✗ No se encontró el calendario asociado al slot", 'error');
            return false;
        }
        
        $fecha = $calendario->fecha;
        $slotHora = substr($slot->hora_inicio, 0, 5);
        
        $this->log("\nCreando script worker para procesos concurrentes...", 'info');
        
        // Crear un script PHP temporal que será ejecutado en paralelo
        $workerScript = __DIR__ . '/test_concurrency_worker.php';
        $workerCode = <<<'PHP'
<?php
// Worker script para test de concurrencia
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\{Env, Eloquent};
use App\Models\Appointment;

Env::load(__DIR__ . '/.env');
Eloquent::init();

// Obtener argumentos
$pacienteId = (int)$argv[1];
$doctorId = (int)$argv[2];
$sedeId = (int)$argv[3];
$calendarioId = (int)$argv[4];
$slotHora = $argv[5];
$fecha = $argv[6];
$pacienteNombre = $argv[7];

try {
    $horaInicio = $slotHora . ':00';
    $horaFin = date('H:i:s', strtotime($horaInicio . ' +15 minutes'));
    
    $citaId = Appointment::create(
        $pacienteId,
        $doctorId,
        $sedeId,
        $fecha,
        $horaInicio,
        $horaFin,
        'Consulta test concurrencia',
        $calendarioId,
        $slotHora
    );
    
    echo json_encode(['success' => true, 'citaId' => $citaId, 'paciente' => $pacienteNombre]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'paciente' => $pacienteNombre]);
}
PHP;
        
        file_put_contents($workerScript, $workerCode);
        
        $this->log("✓ Worker script creado", 'success');
        $this->log("\nLanzando 2 procesos simultáneos...", 'info');
        
        // Preparar comandos para los dos procesos
        $cmd1 = sprintf(
            'php %s %d %d %d %d "%s" "%s" "%s"',
            escapeshellarg($workerScript),
            $paciente1->id,
            $doctor->id,
            $sede->id,
            $slot->calendario_id,
            $slotHora,
            $fecha,
            "{$paciente1->user->nombre} {$paciente1->user->apellido}"
        );
        
        $cmd2 = sprintf(
            'php %s %d %d %d %d "%s" "%s" "%s"',
            escapeshellarg($workerScript),
            $paciente2->id,
            $doctor->id,
            $sede->id,
            $slot->calendario_id,
            $slotHora,
            $fecha,
            "{$paciente2->user->nombre} {$paciente2->user->apellido}"
        );
        
        // Ejecutar ambos procesos en paralelo
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process1 = proc_open($cmd1, $descriptors, $pipes1);
        $process2 = proc_open($cmd2, $descriptors, $pipes2);
        
        if (!is_resource($process1) || !is_resource($process2)) {
            $this->log("✗ Error al crear los procesos", 'error');
            return false;
        }
        
        // Esperar a que terminen ambos procesos
        $output1 = stream_get_contents($pipes1[1]);
        $output2 = stream_get_contents($pipes2[1]);
        
        fclose($pipes1[0]);
        fclose($pipes1[1]);
        fclose($pipes1[2]);
        fclose($pipes2[0]);
        fclose($pipes2[1]);
        fclose($pipes2[2]);
        
        $exitCode1 = proc_close($process1);
        $exitCode2 = proc_close($process2);
        
        // Decodificar resultados
        $result1 = json_decode($output1, true);
        $result2 = json_decode($output2, true);
        
        $this->log("\n--- Resultados de procesos concurrentes ---", 'info');
        
        if ($result1) {
            if ($result1['success']) {
                $this->log("Proceso 1 ({$result1['paciente']}): ✓ ÉXITO - Cita ID: {$result1['citaId']}", 'success');
            } else {
                $this->log("Proceso 1 ({$result1['paciente']}): ✗ RECHAZADO - {$result1['error']}", 'warning');
            }
        } else {
            $this->log("Proceso 1: Error al decodificar salida: $output1", 'error');
        }
        
        if ($result2) {
            if ($result2['success']) {
                $this->log("Proceso 2 ({$result2['paciente']}): ✓ ÉXITO - Cita ID: {$result2['citaId']}", 'success');
            } else {
                $this->log("Proceso 2 ({$result2['paciente']}): ✗ RECHAZADO - {$result2['error']}", 'warning');
            }
        } else {
            $this->log("Proceso 2: Error al decodificar salida: $output2", 'error');
        }
        
        // Verificar que solo uno tuvo éxito
        $successCount = 0;
        $failCount = 0;
        $createdCitaId = null;
        
        if ($result1 && $result1['success']) {
            $successCount++;
            $createdCitaId = $result1['citaId'];
        } else {
            $failCount++;
        }
        
        if ($result2 && $result2['success']) {
            $successCount++;
            $createdCitaId = $result2['citaId'];
        } else {
            $failCount++;
        }
        
        $this->log("\n--- Análisis de Concurrencia ---", 'info');
        $this->log("Reservas exitosas: $successCount", 'info');
        $this->log("Reservas rechazadas: $failCount", 'info');
        
        if ($successCount == 1 && $failCount == 1) {
            $this->log("\n✓ ¡BLOQUEO OPTIMISTA FUNCIONÓ CORRECTAMENTE!", 'success');
            $this->log("Solo un paciente pudo reservar el slot, el otro fue rechazado", 'success');
        } else if ($successCount == 2) {
            $this->log("\n✗ ERROR: Ambos pacientes pudieron reservar el mismo slot", 'error');
            $this->log("El bloqueo optimista NO funcionó", 'error');
        } else if ($successCount == 0) {
            $this->log("\n✗ ERROR: Ningún paciente pudo reservar", 'error');
        }
        
        // Limpiar: eliminar citas creadas
        if ($createdCitaId) {
            DB::table('citas')->where('id', $createdCitaId)->delete();
            DB::table('slots_calendario')
                ->where('id', $slot->id)
                ->update([
                    'reservado_por_cita_id' => null,
                    'disponible' => 1
                ]);
            $this->log("\n✓ Limpieza completada", 'info');
        }
        
        // Limpiar archivo worker
        if (file_exists($workerScript)) {
            unlink($workerScript);
            $this->log("✓ Worker script eliminado", 'info');
        }
        
        return $successCount == 1 && $failCount == 1;
    }
    
    /**
     * Ejecutar todos los tests
     */
    public function runAll() {
        $this->log("\n" . str_repeat("=", 60), 'info');
        
        $test1 = $this->testSequentialReservation();
        
        $this->log("\n" . str_repeat("=", 60), 'info');
        
        $test2 = $this->testConcurrentReservation();
        
        $this->log("\n" . str_repeat("=", 60), 'info');
        $this->log("\n" . TestColors::BOLD . "RESUMEN FINAL:" . TestColors::RESET, 'info');
        $this->log("Test 1 (Secuencial): " . ($test1 ? "✓ PASÓ" : "✗ FALLÓ"), $test1 ? 'success' : 'error');
        $this->log("Test 2 (Concurrente): " . ($test2 ? "✓ PASÓ" : "✗ FALLÓ"), $test2 ? 'success' : 'error');
        
        if ($test1 && $test2) {
            $this->log("\n" . TestColors::BOLD . TestColors::GREEN . "¡TODOS LOS TESTS PASARON!" . TestColors::RESET, 'success');
        } else {
            $this->log("\n" . TestColors::BOLD . TestColors::RED . "ALGUNOS TESTS FALLARON" . TestColors::RESET, 'error');
        }
        
        $this->log("\n" . str_repeat("=", 60) . "\n", 'info');
    }
}

// Ejecutar tests
$test = new ConcurrencySlotTest();
$test->runAll();
