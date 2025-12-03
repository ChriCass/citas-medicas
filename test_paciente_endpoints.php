<?php
/**
 * Script de prueba para endpoints de API de Pacientes
 * 
 * Ejecutar desde la línea de comandos:
 * php test_paciente_endpoints.php
 * 
 * O acceder desde el navegador copiando este archivo a public/
 */

// Configuración
$baseUrl = 'http://localhost:8000'; // Ajustar según tu servidor
$apiUrl = $baseUrl . '/api/v1';

// Colores para terminal
class Color {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

// Helper para hacer peticiones HTTP
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    return [
        'status' => $statusCode,
        'headers' => $header,
        'body' => $body,
        'data' => json_decode($body, true)
    ];
}

// Helper para imprimir resultados
function printResult($testName, $response, $expectedStatus = 200) {
    echo "\n" . Color::BOLD . Color::BLUE . "=== $testName ===" . Color::RESET . "\n";
    echo "Status: ";
    
    if ($response['status'] == $expectedStatus) {
        echo Color::GREEN . $response['status'] . " ✓" . Color::RESET . "\n";
    } else {
        echo Color::RED . $response['status'] . " ✗ (esperado: $expectedStatus)" . Color::RESET . "\n";
    }
    
    echo "Respuesta: " . Color::YELLOW . json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . Color::RESET . "\n";
}

// Limpiar cookies previas
@unlink(__DIR__ . '/cookies.txt');

echo Color::BOLD . Color::BLUE . "\n";
echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║     PRUEBA DE ENDPOINTS API - PACIENTES              ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n";
echo Color::RESET;

// Variables globales para las pruebas
$testEmail = 'paciente@demo.local';
$testPassword = 'password';
$citaId = null;

// ============================================
// 1. LOGIN - Autenticación
// ============================================
echo "\n" . Color::BOLD . "--- AUTENTICACIÓN ---" . Color::RESET . "\n";

$loginData = [
    'email' => $testEmail,
    'password' => $testPassword
];

$response = makeRequest("$apiUrl/pacientes/auth/login", 'POST', $loginData);
printResult("POST /pacientes/auth/login", $response, 200);

if ($response['status'] !== 200) {
    echo "\n" . Color::RED . "⚠️  NOTA: El login falló. Asegúrate de tener un usuario paciente con:" . Color::RESET . "\n";
    echo "   Email: $testEmail\n";
    echo "   Password: $testPassword\n";
    echo "   Rol: paciente\n\n";
    echo "   Puedes crear uno con el registro web o manualmente en la BD.\n\n";
}

// ============================================
// 2. OBTENER PERFIL
// ============================================
echo "\n" . Color::BOLD . "--- PERFIL ---" . Color::RESET . "\n";

$response = makeRequest("$apiUrl/pacientes/profile", 'GET');
printResult("GET /pacientes/profile", $response, 200);

// ============================================
// 3. ACTUALIZAR PERFIL
// ============================================
$updateData = [
    'telefono' => '987654321',
    'direccion' => 'Av. Test 123, Lima',
    'tipo_sangre' => 'O+',
    'alergias' => 'Ninguna conocida',
    'contacto_emergencia_nombre' => 'Juan Pérez',
    'contacto_emergencia_telefono' => '912345678',
    'contacto_emergencia_relacion' => 'Hermano'
];

$response = makeRequest("$apiUrl/pacientes/profile", 'PUT', $updateData);
printResult("PUT /pacientes/profile", $response, 200);

// ============================================
// 4. VER CITAS
// ============================================
echo "\n" . Color::BOLD . "--- GESTIÓN DE CITAS ---" . Color::RESET . "\n";

$response = makeRequest("$apiUrl/pacientes/appointments", 'GET');
printResult("GET /pacientes/appointments (Todas las citas)", $response, 200);

// ============================================
// 5. VER PRÓXIMAS CITAS
// ============================================
$response = makeRequest("$apiUrl/pacientes/appointments/upcoming?limit=3", 'GET');
printResult("GET /pacientes/appointments/upcoming (Próximas citas)", $response, 200);

// ============================================
// 6. CREAR NUEVA CITA
// ============================================
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$newAppointmentData = [
    'doctor_id' => 1, // Ajustar según tu BD
    'sede_id' => 1,   // Ajustar según tu BD
    'date' => $tomorrow,
    'time' => '10:00',
    'notes' => 'Consulta de prueba desde API'
];

$response = makeRequest("$apiUrl/pacientes/appointments", 'POST', $newAppointmentData);
printResult("POST /pacientes/appointments (Crear cita)", $response, 201);

if ($response['status'] == 201 && isset($response['data']['data']['cita_id'])) {
    $citaId = $response['data']['data']['cita_id'];
    echo Color::GREEN . "✓ Cita creada con ID: $citaId" . Color::RESET . "\n";
}

// ============================================
// 7. VER DETALLE DE UNA CITA
// ============================================
if ($citaId) {
    $response = makeRequest("$apiUrl/pacientes/appointments/$citaId", 'GET');
    printResult("GET /pacientes/appointments/{id} (Detalle de cita)", $response, 200);
}

// ============================================
// 8. VER HISTORIAL DE PAGOS
// ============================================
echo "\n" . Color::BOLD . "--- PAGOS ---" . Color::RESET . "\n";

$response = makeRequest("$apiUrl/pacientes/payments", 'GET');
printResult("GET /pacientes/payments (Historial de pagos)", $response, 200);

// ============================================
// 9. CANCELAR CITA
// ============================================
if ($citaId) {
    echo "\n" . Color::BOLD . "--- CANCELACIÓN ---" . Color::RESET . "\n";
    
    $response = makeRequest("$apiUrl/pacientes/appointments/$citaId", 'DELETE');
    printResult("DELETE /pacientes/appointments/{id} (Cancelar cita)", $response, 200);
}

// ============================================
// 10. LOGOUT
// ============================================
echo "\n" . Color::BOLD . "--- CERRAR SESIÓN ---" . Color::RESET . "\n";

$response = makeRequest("$apiUrl/pacientes/auth/logout", 'POST');
printResult("POST /pacientes/auth/logout", $response, 200);

// ============================================
// 11. PRUEBAS DE SEGURIDAD (sin autenticación)
// ============================================
echo "\n" . Color::BOLD . "--- PRUEBAS DE SEGURIDAD (sin sesión) ---" . Color::RESET . "\n";

// Limpiar cookies
@unlink(__DIR__ . '/cookies.txt');

$response = makeRequest("$apiUrl/pacientes/profile", 'GET');
printResult("GET /pacientes/profile (sin auth)", $response, 401);

$response = makeRequest("$apiUrl/pacientes/appointments", 'GET');
printResult("GET /pacientes/appointments (sin auth)", $response, 401);

// ============================================
// RESUMEN
// ============================================
echo "\n" . Color::BOLD . Color::GREEN . "\n";
echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║              PRUEBAS COMPLETADAS                      ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n";
echo Color::RESET . "\n";

echo Color::YELLOW . "Endpoints probados:\n" . Color::RESET;
echo "  ✓ POST   /api/v1/pacientes/auth/login\n";
echo "  ✓ POST   /api/v1/pacientes/auth/logout\n";
echo "  ✓ GET    /api/v1/pacientes/profile\n";
echo "  ✓ PUT    /api/v1/pacientes/profile\n";
echo "  ✓ GET    /api/v1/pacientes/appointments\n";
echo "  ✓ GET    /api/v1/pacientes/appointments/upcoming\n";
echo "  ✓ GET    /api/v1/pacientes/appointments/{id}\n";
echo "  ✓ POST   /api/v1/pacientes/appointments\n";
echo "  ✓ DELETE /api/v1/pacientes/appointments/{id}\n";
echo "  ✓ GET    /api/v1/pacientes/payments\n";
echo "\n";

echo Color::BLUE . "Para ejecutar las pruebas nuevamente:\n" . Color::RESET;
echo "  php test_paciente_endpoints.php\n\n";

echo Color::YELLOW . "IMPORTANTE:\n" . Color::RESET;
echo "  - Ajusta \$baseUrl en el script según tu configuración\n";
echo "  - Asegúrate de tener un usuario paciente en la BD\n";
echo "  - Ajusta doctor_id y sede_id en la creación de citas\n";
echo "  - Verifica que el servidor esté corriendo\n\n";
