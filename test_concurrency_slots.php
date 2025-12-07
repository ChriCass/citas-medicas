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
    // Breve: Verifica que una reserva previa impida una reserva posterior en el mismo slot
    public function testSequentialReservation() {
        $this->log("\n" . TestColors::BOLD . "--- TEST 1: Reserva Secuencial (Control) ---" . TestColors::RESET, 'info');
        $this->log("Dos pacientes reservando el mismo slot de forma secuencial (control).", 'info');
        
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
    // Breve: Dos procesos paralelos intentan crear una cita en el mismo slot; sólo uno debe ganar
    public function testConcurrentReservation() {
        $this->log("\n" . TestColors::BOLD . "--- TEST 2: Reserva Concurrente (Procesos Paralelos) ---" . TestColors::RESET, 'info');
        $this->log("Dos pacientes reservando el mismo slot simultáneamente", 'info');
        
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
     * Test 3: Dos usuarios moviendo sus citas al mismo slot disponible
     * Simula concurrencia en modifyAppointment
     */
    // Breve: Dos modifies concurrentes intentan mover distintas citas al mismo slot; sólo una debe tener éxito
    public function testConcurrentModifyToSameSlot() {
        $this->log("\n" . TestColors::BOLD . "--- TEST 3: Dos Modify Concurrentes al Mismo Slot ---" . TestColors::RESET, 'info');
        $this->log("Dos usuarios intentando mover sus citas al mismo slot disponible", 'info');
        
        $data = $this->prepareTestData();
        if (!$data) {
            return false;
        }
        
        $paciente1 = $data['paciente1'];
        $paciente2 = $data['paciente2'];
        $doctor = $data['doctor'];
        $sede = $data['sede'];
        
        // Buscar un calendario con al menos 3 slots disponibles del mismo doctor
        $calendarioId = DB::table('calendario')
            ->select('calendario.id')
            ->join('slots_calendario', 'calendario.id', '=', 'slots_calendario.calendario_id')
            ->whereNull('slots_calendario.reservado_por_cita_id')
            ->where('slots_calendario.disponible', 1)
            ->where('calendario.doctor_id', $doctor->id)
            ->where('calendario.fecha', '>=', date('Y-m-d'))
            ->groupBy('calendario.id')
            ->havingRaw('COUNT(*) >= 3')
            ->value('calendario.id');
        
        if (!$calendarioId) {
            $this->log("✗ No se encontró un calendario con 3+ slots disponibles", 'error');
            return false;
        }
        
        $calendario = DB::table('calendario')->where('id', $calendarioId)->first();
        
        // Obtener 3 slots del mismo calendario
        $slots = DB::table('slots_calendario')
            ->where('calendario_id', $calendarioId)
            ->whereNull('reservado_por_cita_id')
            ->where('disponible', 1)
            ->limit(3)
            ->get();
        
        if ($slots->count() < 3) {
            $this->log("✗ No se encontraron 3 slots disponibles", 'error');
            return false;
        }
        
        $slot1 = $slots[0];
        $slot2 = $slots[1];
        $slotDestino = $slots[2];
        
        // Crear dos citas en slots diferentes
        $this->log("\nCreando cita 1 para paciente 1 en slot {$slot1->id}...", 'info');
        $citaId1 = Appointment::create(
            $paciente1->id,
            $doctor->id,
            $sede->id,
            $calendario->fecha,
            substr($slot1->hora_inicio, 0, 8),
            date('H:i:s', strtotime(substr($slot1->hora_inicio, 0, 8) . ' +15 minutes')),
            'Cita test 1',
            $slot1->calendario_id,
            substr($slot1->hora_inicio, 0, 5)
        );
        $this->log("✓ Cita 1 creada: ID=$citaId1", 'success');
        
        $this->log("Creando cita 2 para paciente 2 en slot {$slot2->id}...", 'info');
        $citaId2 = Appointment::create(
            $paciente2->id,
            $doctor->id,
            $sede->id,
            $calendario->fecha,
            substr($slot2->hora_inicio, 0, 8),
            date('H:i:s', strtotime(substr($slot2->hora_inicio, 0, 8) . ' +15 minutes')),
            'Cita test 2',
            $slot2->calendario_id,
            substr($slot2->hora_inicio, 0, 5)
        );
        $this->log("✓ Cita 2 creada: ID=$citaId2", 'success');
        
        $this->log("\nAmbas citas intentarán moverse al slot destino: {$slotDestino->id}", 'info');
        
        // Crear worker script para modifyAppointment
        $workerScript = __DIR__ . '/test_modify_worker.php';
        $workerCode = <<<'PHP'
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\{Env, Eloquent};
use App\Models\Appointment;

Env::load(__DIR__ . '/.env');
Eloquent::init();

$citaId = (int)$argv[1];
$doctorId = (int)$argv[2];
$sedeId = (int)$argv[3];
$fecha = $argv[4];
$horaInicio = $argv[5];
$horaFin = $argv[6];
$razon = $argv[7];
$calendarioId = (int)$argv[8];
$slotId = (int)$argv[9];
$pacienteNombre = $argv[10];

try {
    $result = Appointment::modifyAppointment(
        $citaId,
        $doctorId,
        $sedeId,
        $fecha,
        $horaInicio,
        $horaFin,
        $razon,
        $calendarioId,
        $slotId
    );
    
    echo json_encode(['success' => $result, 'citaId' => $citaId, 'paciente' => $pacienteNombre]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'citaId' => $citaId, 'paciente' => $pacienteNombre]);
}
PHP;
        
        file_put_contents($workerScript, $workerCode);
        
        $horaDestinoInicio = substr($slotDestino->hora_inicio, 0, 8);
        $horaDestinoFin = date('H:i:s', strtotime($horaDestinoInicio . ' +15 minutes'));
        
        // Comandos para modificar ambas citas al mismo slot destino
        $cmd1 = sprintf(
            'php %s %d %d %d "%s" "%s" "%s" "Modificado" %d %d "%s"',
            escapeshellarg($workerScript),
            $citaId1,
            $doctor->id,
            $sede->id,
            $calendario->fecha,
            $horaDestinoInicio,
            $horaDestinoFin,
            $slotDestino->calendario_id,
            $slotDestino->id,
            $paciente1->user->nombre
        );
        
        $cmd2 = sprintf(
            'php %s %d %d %d "%s" "%s" "%s" "Modificado" %d %d "%s"',
            escapeshellarg($workerScript),
            $citaId2,
            $doctor->id,
            $sede->id,
            $calendario->fecha,
            $horaDestinoInicio,
            $horaDestinoFin,
            $slotDestino->calendario_id,
            $slotDestino->id,
            $paciente2->user->nombre
        );
        
        $this->log("\nLanzando 2 procesos de modify simultáneos...", 'info');
        
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
        
        $output1 = stream_get_contents($pipes1[1]);
        $output2 = stream_get_contents($pipes2[1]);
        
        fclose($pipes1[0]); fclose($pipes1[1]); fclose($pipes1[2]);
        fclose($pipes2[0]); fclose($pipes2[1]); fclose($pipes2[2]);
        
        proc_close($process1);
        proc_close($process2);
        
        $result1 = json_decode($output1, true);
        $result2 = json_decode($output2, true);
        
        $this->log("\n--- Resultados de procesos concurrentes ---", 'info');
        
        if ($result1) {
            if ($result1['success']) {
                $this->log("Proceso 1 (Cita $citaId1): ✓ ÉXITO", 'success');
            } else {
                $this->log("Proceso 1 (Cita $citaId1): ✗ RECHAZADO - " . ($result1['error'] ?? 'sin error'), 'warning');
            }
        }
        
        if ($result2) {
            if ($result2['success']) {
                $this->log("Proceso 2 (Cita $citaId2): ✓ ÉXITO", 'success');
            } else {
                $this->log("Proceso 2 (Cita $citaId2): ✗ RECHAZADO - " . ($result2['error'] ?? 'sin error'), 'warning');
            }
        }
        
        $successCount = (($result1 && $result1['success']) ? 1 : 0) + (($result2 && $result2['success']) ? 1 : 0);
        
        $this->log("\n--- Análisis de Concurrencia ---", 'info');
        $this->log("Modificaciones exitosas: $successCount", 'info');
        
        $testPassed = ($successCount == 1);
        
        if ($successCount == 1) {
            $this->log("\n✓ ¡BLOQUEO OPTIMISTA FUNCIONÓ!", 'success');
            $this->log("Solo una cita pudo moverse al slot destino", 'success');
        } else if ($successCount == 2) {
            $this->log("\n✗ ERROR: Ambas citas se movieron al mismo slot", 'error');
        } else {
            $this->log("\n✗ ERROR: Ninguna cita pudo moverse", 'error');
        }
        
        // Limpieza
        DB::table('citas')->whereIn('id', [$citaId1, $citaId2])->delete();
        DB::table('slots_calendario')
            ->whereIn('id', [$slot1->id, $slot2->id, $slotDestino->id])
            ->update(['reservado_por_cita_id' => null, 'disponible' => 1]);
        
        if (file_exists($workerScript)) {
            unlink($workerScript);
        }
        
        $this->log("✓ Limpieza completada", 'info');
        
        return $testPassed;
    }
    
    /**
     * Test 4: Un paciente reserva (create) mientras otro modifica su cita al mismo slot
     */
    // Breve: Un create y un modify compiten por el mismo slot; sólo una operación debe reservarlo
    public function testCreateVsModifyToSameSlot() {
        $this->log("\n" . TestColors::BOLD . "--- TEST 4: Create vs Modify al Mismo Slot ---" . TestColors::RESET, 'info');
        $this->log("Un paciente reserva mientras otro modifica su cita al mismo slot", 'info');
        
        $data = $this->prepareTestData();
        if (!$data) {
            return false;
        }
        
        $paciente1 = $data['paciente1'];
        $paciente2 = $data['paciente2'];
        $doctor = $data['doctor'];
        $sede = $data['sede'];
        
        // Buscar un calendario con al menos 2 slots disponibles
        $calendarioId = DB::table('calendario')
            ->select('calendario.id')
            ->join('slots_calendario', 'calendario.id', '=', 'slots_calendario.calendario_id')
            ->whereNull('slots_calendario.reservado_por_cita_id')
            ->where('slots_calendario.disponible', 1)
            ->where('calendario.doctor_id', $doctor->id)
            ->where('calendario.fecha', '>=', date('Y-m-d'))
            ->groupBy('calendario.id')
            ->havingRaw('COUNT(*) >= 2')
            ->value('calendario.id');
        
        if (!$calendarioId) {
            $this->log("✗ No se encontró un calendario con 2+ slots disponibles", 'error');
            return false;
        }
        
        $calendario = DB::table('calendario')->where('id', $calendarioId)->first();
        
        // Obtener 2 slots del mismo calendario
        $slots = DB::table('slots_calendario')
            ->where('calendario_id', $calendarioId)
            ->whereNull('reservado_por_cita_id')
            ->where('disponible', 1)
            ->limit(2)
            ->get();
        
        if ($slots->count() < 2) {
            $this->log("✗ No se encontraron 2 slots disponibles", 'error');
            return false;
        }
        
        $slotInicial = $slots[0];
        $slotDestino = $slots[1];
        
        // Crear cita inicial para paciente2
        $this->log("\nCreando cita inicial para paciente 2 en slot {$slotInicial->id}...", 'info');
        $citaId2 = Appointment::create(
            $paciente2->id,
            $doctor->id,
            $sede->id,
            $calendario->fecha,
            substr($slotInicial->hora_inicio, 0, 8),
            date('H:i:s', strtotime(substr($slotInicial->hora_inicio, 0, 8) . ' +15 minutes')),
            'Cita inicial paciente 2',
            $slotInicial->calendario_id,
            substr($slotInicial->hora_inicio, 0, 5)
        );
        $this->log("✓ Cita creada: ID=$citaId2", 'success');
        
        $this->log("\nSlot destino disputado: {$slotDestino->id}", 'info');
        $this->log("Paciente 1 intentará CREATE nuevo, Paciente 2 intentará MODIFY existente", 'info');
        
        // Worker para create
        $createWorkerScript = __DIR__ . '/test_create_worker.php';
        $createWorkerCode = <<<'PHP'
<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Core\{Env, Eloquent};
use App\Models\Appointment;

Env::load(__DIR__ . '/.env');
Eloquent::init();

$pacienteId = (int)$argv[1];
$doctorId = (int)$argv[2];
$sedeId = (int)$argv[3];
$fecha = $argv[4];
$horaInicio = $argv[5];
$horaFin = $argv[6];
$calendarioId = (int)$argv[7];
$slotHora = $argv[8];
$nombre = $argv[9];

try {
    $citaId = Appointment::create($pacienteId, $doctorId, $sedeId, $fecha, $horaInicio, $horaFin, 'Nueva cita test', $calendarioId, $slotHora);
    echo json_encode(['success' => true, 'citaId' => $citaId, 'tipo' => 'CREATE', 'nombre' => $nombre]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'tipo' => 'CREATE', 'nombre' => $nombre]);
}
PHP;
        
        // Worker para modify (reutilizar del test anterior)
        $modifyWorkerScript = __DIR__ . '/test_modify_worker2.php';
        $modifyWorkerCode = <<<'PHP'
<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Core\{Env, Eloquent};
use App\Models\Appointment;

Env::load(__DIR__ . '/.env');
Eloquent::init();

$citaId = (int)$argv[1];
$doctorId = (int)$argv[2];
$sedeId = (int)$argv[3];
$fecha = $argv[4];
$horaInicio = $argv[5];
$horaFin = $argv[6];
$razon = $argv[7];
$calendarioId = (int)$argv[8];
$slotId = (int)$argv[9];
$nombre = $argv[10];

try {
    $result = Appointment::modifyAppointment($citaId, $doctorId, $sedeId, $fecha, $horaInicio, $horaFin, $razon, $calendarioId, $slotId);
    echo json_encode(['success' => $result, 'citaId' => $citaId, 'tipo' => 'MODIFY', 'nombre' => $nombre]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'tipo' => 'MODIFY', 'nombre' => $nombre]);
}
PHP;
        
        file_put_contents($createWorkerScript, $createWorkerCode);
        file_put_contents($modifyWorkerScript, $modifyWorkerCode);
        
        $horaDestinoInicio = substr($slotDestino->hora_inicio, 0, 8);
        $horaDestinoFin = date('H:i:s', strtotime($horaDestinoInicio . ' +15 minutes'));
        $slotHora = substr($slotDestino->hora_inicio, 0, 5);
        
        // Comando para CREATE
        $cmdCreate = sprintf(
            'php %s %d %d %d "%s" "%s" "%s" %d "%s" "%s"',
            escapeshellarg($createWorkerScript),
            $paciente1->id,
            $doctor->id,
            $sede->id,
            $calendario->fecha,
            $horaDestinoInicio,
            $horaDestinoFin,
            $slotDestino->calendario_id,
            $slotHora,
            $paciente1->user->nombre
        );
        
        // Comando para MODIFY
        $cmdModify = sprintf(
            'php %s %d %d %d "%s" "%s" "%s" "Modificado" %d %d "%s"',
            escapeshellarg($modifyWorkerScript),
            $citaId2,
            $doctor->id,
            $sede->id,
            $calendario->fecha,
            $horaDestinoInicio,
            $horaDestinoFin,
            $slotDestino->calendario_id,
            $slotDestino->id,
            $paciente2->user->nombre
        );
        
        $this->log("\nLanzando CREATE + MODIFY simultáneos...", 'info');
        
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $processCreate = proc_open($cmdCreate, $descriptors, $pipesCreate);
        $processModify = proc_open($cmdModify, $descriptors, $pipesModify);
        
        if (!is_resource($processCreate) || !is_resource($processModify)) {
            $this->log("✗ Error al crear los procesos", 'error');
            return false;
        }
        
        $outputCreate = stream_get_contents($pipesCreate[1]);
        $outputModify = stream_get_contents($pipesModify[1]);
        
        fclose($pipesCreate[0]); fclose($pipesCreate[1]); fclose($pipesCreate[2]);
        fclose($pipesModify[0]); fclose($pipesModify[1]); fclose($pipesModify[2]);
        
        proc_close($processCreate);
        proc_close($processModify);
        
        $resultCreate = json_decode($outputCreate, true);
        $resultModify = json_decode($outputModify, true);
        
        $this->log("\n--- Resultados de procesos concurrentes ---", 'info');
        
        $createdCitaId = null;
        if ($resultCreate) {
            if ($resultCreate['success']) {
                $this->log("CREATE ({$resultCreate['nombre']}): ✓ ÉXITO - Cita ID: {$resultCreate['citaId']}", 'success');
                $createdCitaId = $resultCreate['citaId'];
            } else {
                $this->log("CREATE ({$resultCreate['nombre']}): ✗ RECHAZADO - " . ($resultCreate['error'] ?? 'sin error'), 'warning');
            }
        }
        
        if ($resultModify) {
            if ($resultModify['success']) {
                $this->log("MODIFY ({$resultModify['nombre']}): ✓ ÉXITO - Cita ID: {$resultModify['citaId']}", 'success');
            } else {
                $this->log("MODIFY ({$resultModify['nombre']}): ✗ RECHAZADO - " . ($resultModify['error'] ?? 'sin error'), 'warning');
            }
        }
        
        $successCount = (($resultCreate && $resultCreate['success']) ? 1 : 0) + (($resultModify && $resultModify['success']) ? 1 : 0);
        
        $this->log("\n--- Análisis de Concurrencia ---", 'info');
        $this->log("Operaciones exitosas: $successCount", 'info');
        
        $testPassed = ($successCount == 1);
        
        if ($successCount == 1) {
            $this->log("\n✓ ¡BLOQUEO OPTIMISTA FUNCIONÓ!", 'success');
            $this->log("Solo una operación obtuvo el slot destino", 'success');
        } else if ($successCount == 2) {
            $this->log("\n✗ ERROR: Ambas operaciones obtuvieron el mismo slot", 'error');
        } else {
            $this->log("\n✗ ERROR: Ninguna operación obtuvo el slot", 'error');
        }
        
        // Limpieza
        $citasToDelete = [$citaId2];
        if ($createdCitaId) {
            $citasToDelete[] = $createdCitaId;
        }
        DB::table('citas')->whereIn('id', $citasToDelete)->delete();
        DB::table('slots_calendario')
            ->whereIn('id', [$slotInicial->id, $slotDestino->id])
            ->update(['reservado_por_cita_id' => null, 'disponible' => 1]);
        
        if (file_exists($createWorkerScript)) unlink($createWorkerScript);
        if (file_exists($modifyWorkerScript)) unlink($modifyWorkerScript);
        
        $this->log("✓ Limpieza completada", 'info');
        
        return $testPassed;
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
        
        $test3 = $this->testConcurrentModifyToSameSlot();
        
        $this->log("\n" . str_repeat("=", 60), 'info');
        
        $test4 = $this->testCreateVsModifyToSameSlot();
        
        $this->log("\n" . str_repeat("=", 60), 'info');
        $this->log("\n" . TestColors::BOLD . "RESUMEN FINAL:" . TestColors::RESET, 'info');
        $this->log("Test 1 (Secuencial - Create): " . ($test1 ? "✓ PASÓ" : "✗ FALLÓ"), $test1 ? 'success' : 'error');
        $this->log("Test 2 (Concurrente - Create vs Create): " . ($test2 ? "✓ PASÓ" : "✗ FALLÓ"), $test2 ? 'success' : 'error');
        $this->log("Test 3 (Concurrente - Modify vs Modify): " . ($test3 ? "✓ PASÓ" : "✗ FALLÓ"), $test3 ? 'success' : 'error');
        $this->log("Test 4 (Concurrente - Create vs Modify): " . ($test4 ? "✓ PASÓ" : "✗ FALLÓ"), $test4 ? 'success' : 'error');
        
        if ($test1 && $test2 && $test3 && $test4) {
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
