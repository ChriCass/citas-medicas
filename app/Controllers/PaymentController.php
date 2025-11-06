<?php
namespace App\Controllers;

use App\Core\{Request, Response};
use App\Models\{Payment, Appointment, Paciente, User, Cajero};

class PaymentController
{
    public function index(Request $request, Response $response)
    {
        // Verificar que el usuario sea cajero
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        $search = $request->query['search'] ?? null;
        $cajeroId = $_SESSION['user']['cajero_id'] ?? null;
        
        if (!$cajeroId) {
            return $response->redirect('/dashboard')->with('error', 'Error: No se encontró información del cajero');
        }
        
        // Obtener pagos del cajero usando Eloquent
        $pagos = Payment::getByCajero($cajeroId, $search);
        
        // Obtener citas elegibles para pago usando consulta directa para SQL Server
        $db = \App\Core\SimpleDatabase::getInstance();
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido, pu.dni as paciente_dni,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE c.estado IN ('atendido', 'confirmado', 'programada')
                AND NOT EXISTS (SELECT 1 FROM pagos WHERE cita_id = c.id)
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        
        $citasAtendidas = $db->fetchAll($sql);
        return $response->view('pagos/index', [
            'pagos' => $pagos,
            'citasAtendidas' => $citasAtendidas,
            'search' => $search
        ]);
    }
    
    public function search(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->json(['error' => 'No autorizado'], 403);
        }
        
        $search = $request->query['q'] ?? null;
        
        if (empty($search)) {
            return $response->json(['results' => []]);
        }
        
        // Buscar pacientes por nombre, apellido, DNI o número de historia clínica
        $db = \App\Core\SimpleDatabase::getInstance();
        
        // Detectar el tipo de base de datos y usar la sintaxis correcta
        $dbType = $db->getConnectionType();
        
        if ($dbType === 'sqlsrv') {
            // SQL Server usa TOP
            $sql = "SELECT TOP 10 u.*, p.id as paciente_id
                    FROM usuarios u
                    INNER JOIN pacientes p ON u.id = p.usuario_id
                    INNER JOIN tiene_roles tr ON u.id = tr.usuario_id
                    INNER JOIN roles r ON tr.rol_id = r.id
                    WHERE r.nombre = 'paciente'
                    AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.dni LIKE ?)";
        } else {
            // SQLite/MySQL usan LIMIT
            $sql = "SELECT u.*, p.id as paciente_id
                    FROM usuarios u
                    INNER JOIN pacientes p ON u.id = p.usuario_id
                    INNER JOIN tiene_roles tr ON u.id = tr.usuario_id
                    INNER JOIN roles r ON tr.rol_id = r.id
                    WHERE r.nombre = 'paciente'
                    AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.dni LIKE ?)
                    LIMIT 10";
        }
        
        $searchTerm = "%{$search}%";
        $pacientes = $db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
        
        $results = [];
        foreach ($pacientes as $paciente) {
            $results[] = [
                'id' => $paciente['id'],
                'nombre' => $paciente['nombre'] . ' ' . $paciente['apellido'],
                'dni' => $paciente['dni'],
                'email' => $paciente['email'],
                'telefono' => $paciente['telefono']
            ];
        }
        
        return $response->json(['results' => $results]);
    }
    
    public function show(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        $citaId = $request->query['id'] ?? null;
        
        $db = \App\Core\SimpleDatabase::getInstance();
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE c.id = ? AND c.estado = 'atendido'";
        
        $cita = $db->fetchOne($sql, [$citaId]);
        
        if (!$cita) {
            return $response->redirect('/pagos')->with('error', 'Cita no encontrada o no está en estado atendido');
        }
        
        // Verificar si ya tiene pago
        $pago = Payment::getByCita($citaId);
        if ($pago) {
            return $response->redirect('/pagos')->with('error', 'Esta cita ya tiene un pago registrado');
        }
        
        return $response->view('pagos/show', [
            'cita' => $cita
        ]);
    }
    
    public function store(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        $citaId = $request->body['cita_id'] ?? null;
        $monto = $request->body['monto'] ?? null;
        $metodoPago = $request->body['metodo_pago'] ?? 'efectivo';
        $observaciones = $request->body['observaciones'] ?? null;
        
        // Validaciones
        if (empty($citaId) || empty($monto)) {
            return $response->redirect('/pagos')->with('error', 'Faltan datos requeridos');
        }
        
        // Obtener cita usando Eloquent y normalizar a array para compatibilidad
        $citaModel = Appointment::find($citaId);
        $cita = (is_object($citaModel) && method_exists($citaModel, 'toArray')) ? $citaModel->toArray() : $citaModel;
        if (!$cita || ($cita['estado'] ?? null) !== 'atendido') {
            return $response->redirect('/pagos')->with('error', 'Cita no válida para pago');
        }
        
        // Verificar si ya tiene pago
        if (Payment::getByCita($citaId)) {
            return $response->redirect('/pagos')->with('error', 'Esta cita ya tiene un pago registrado');
        }
        
        $cajeroId = $_SESSION['user']['cajero_id'] ?? null;
        if (!$cajeroId) {
            return $response->redirect('/dashboard')->with('error', 'Error: No se encontró información del cajero');
        }
        
        try {
            // Crear el pago
            $paymentId = Payment::createPayment(
                $citaId,
                $cajeroId,
                (float)$monto,
                $metodoPago,
                $observaciones
            );
            
            // Actualizar estado de la cita a pagado
            Appointment::updatePayment($citaId, 'pagado');
            
            return $response->redirect('/pagos')->with('success', 'Pago registrado exitosamente');
            
        } catch (\Exception $e) {
            return $response->redirect('/pagos')->with('error', 'Error al registrar el pago: ' . $e->getMessage());
        }
    }
    
    public function showManual(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        // Obtener todos los pacientes para el selector
        $pacientes = Paciente::with('usuario')->get()->map(function($paciente) {
            return [
                'id' => $paciente->usuario->id,
                'nombre' => $paciente->usuario->nombre . ' ' . $paciente->usuario->apellido,
                'dni' => $paciente->usuario->dni
            ];
        })->toArray();
        
        // Obtener todos los doctores para el selector
        $doctores = Doctor::with(['usuario', 'especialidad'])->get()->map(function($doctor) {
            return [
                'id' => $doctor->id,
                'nombre' => $doctor->usuario->nombre . ' ' . $doctor->usuario->apellido,
                'especialidad' => $doctor->especialidad->nombre ?? 'N/A'
            ];
        })->toArray();
        
        // Obtener todas las sedes para el selector
        $sedes = Sede::all()->map(function($sede) {
            return [
                'id' => $sede->id,
                'nombre' => $sede->nombre_sede
            ];
        })->toArray();
        
        return $response->view('pagos/manual', [
            'pacientes' => $pacientes,
            'doctores' => $doctores,
            'sedes' => $sedes
        ]);
    }
    
    public function storeManual(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        $pacienteId = $request->body['paciente_id'] ?? null;
        $doctorId = $request->body['doctor_id'] ?? null;
        $sedeId = $request->body['sede_id'] ?? null;
        $fecha = $request->body['fecha'] ?? null;
        $hora = $request->body['hora'] ?? null;
        $monto = $request->body['monto'] ?? null;
        $metodoPago = $request->body['metodo_pago'] ?? 'efectivo';
        $observaciones = $request->body['observaciones'] ?? null;
        $estado = $request->body['estado'] ?? 'pagado';
        
        // Validaciones
        if (empty($pacienteId) || empty($doctorId) || empty($monto) || empty($fecha) || empty($hora)) {
            $missing = [];
            if (empty($pacienteId)) $missing[] = 'paciente_id';
            if (empty($doctorId)) $missing[] = 'doctor_id';
            if (empty($monto)) $missing[] = 'monto';
            if (empty($fecha)) $missing[] = 'fecha';
            if (empty($hora)) $missing[] = 'hora';
            return $response->redirect('/pagos/registrar-manual')->with('error', 'Faltan datos requeridos: ' . implode(', ', $missing));
        }
        
        $cajeroId = $_SESSION['user']['cajero_id'] ?? null;
        if (!$cajeroId) {
            return $response->redirect('/dashboard')->with('error', 'Error: No se encontró información del cajero');
        }
        
        try {
            $db = \App\Core\SimpleDatabase::getInstance();
            
            // Obtener el ID del paciente en la tabla pacientes
            $pacienteRecord = $db->fetchOne("SELECT id FROM pacientes WHERE usuario_id = ?", [$pacienteId]);
            if (!$pacienteRecord) {
                throw new \Exception('No se encontró el registro del paciente');
            }
            $pacienteIdReal = $pacienteRecord['id'];
            
            // Crear una cita temporal para el pago manual
            $horaFin = date('H:i:s', strtotime($hora . ' +15 minutes'));
            
            $citaId = $db->insert('citas', [
                'paciente_id' => $pacienteIdReal,
                'doctor_id' => $doctorId,
                'sede_id' => $sedeId,
                'fecha' => $fecha,
                'hora_inicio' => $hora,
                'hora_fin' => $horaFin,
                'razon' => 'Pago manual registrado',
                'estado' => 'atendido',
                'pago' => $estado,
                'creado_en' => date('Y-m-d H:i:s')
            ]);
            
            if (!$citaId) {
                throw new \Exception('No se pudo crear la cita');
            }
            
            // Crear el pago
            $paymentId = Payment::createPayment(
                $citaId,
                $cajeroId,
                (float)$monto,
                $metodoPago,
                $observaciones
            );
            
            if (!$paymentId) {
                throw new \Exception('No se pudo crear el pago');
            }
            
            return $response->redirect('/pagos')->with('success', 'Pago manual registrado exitosamente. Cita ID: ' . $citaId . ', Pago ID: ' . $paymentId);
            
        } catch (\Exception $e) {
            return $response->redirect('/pagos/registrar-manual')->with('error', 'Error al registrar el pago: ' . $e->getMessage());
        }
    }
    
    public function edit(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        $pagoId = $request->params['id'] ?? null;
        
        if (!$pagoId) {
            return $response->redirect('/pagos')->with('error', 'ID de pago no válido');
        }
        
        // Obtener datos completos del pago con todas las relaciones
        $db = \App\Core\SimpleDatabase::getInstance();
        $sql = "SELECT p.*, 
                       c.fecha, c.hora_inicio, c.hora_fin, c.razon,
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       pu.dni as paciente_dni, pu.telefono as paciente_telefono,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM pagos p
                LEFT JOIN citas c ON p.cita_id = c.id
                LEFT JOIN pacientes pa ON c.paciente_id = pa.id
                LEFT JOIN usuarios pu ON pa.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE p.id = ?";
        
        $pago = $db->fetchOne($sql, [$pagoId]);
        
        if (!$pago) {
            return $response->redirect('/pagos')->with('error', 'Pago no encontrado');
        }
        
        return $response->view('pagos/edit', [
            'pago' => $pago
        ]);
    }
    
    public function update(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        $pagoId = $request->params['id'] ?? null;
        $monto = $request->body['monto'] ?? null;
        $metodoPago = $request->body['metodo_pago'] ?? 'efectivo';
        $estado = $request->body['estado'] ?? 'completado';
        $observaciones = $request->body['observaciones'] ?? null;
        
        if (!$pagoId || !$monto) {
            return $response->redirect('/pagos')->with('error', 'Faltan datos requeridos');
        }
        
        try {
            $db = \App\Core\SimpleDatabase::getInstance();
            
            // Actualizar el pago
            $db->update('pagos', [
                'monto' => (float)$monto,
                'metodo_pago' => $metodoPago,
                'estado' => $estado,
                'observaciones' => $observaciones,
                'actualizado_en' => date('Y-m-d H:i:s')
            ], 'id = ?', [$pagoId]);
            
            return $response->redirect('/pagos')->with('success', 'Pago actualizado exitosamente');
            
        } catch (\Exception $e) {
            return $response->redirect('/pagos')->with('error', 'Error al actualizar el pago: ' . $e->getMessage());
        }
    }
    
    public function destroy(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        $pagoId = $request->params['id'] ?? null;
        
        if (!$pagoId) {
            return $response->redirect('/pagos')->with('error', 'ID de pago no válido');
        }
        
        try {
            $db = \App\Core\SimpleDatabase::getInstance();
            
            // Obtener información del pago antes de eliminarlo
            $pago = $db->fetchOne("SELECT cita_id FROM pagos WHERE id = ?", [$pagoId]);
            
            if (!$pago) {
                return $response->redirect('/pagos')->with('error', 'Pago no encontrado');
            }
            
            // Eliminar el pago
            $db->delete('pagos', 'id = ?', [$pagoId]);
            
            // Actualizar el estado de pago de la cita a pendiente
            $db->update('citas', [
                'pago' => 'pendiente'
            ], 'id = ?', [$pago['cita_id']]);
            
            return $response->redirect('/pagos')->with('success', 'Pago eliminado exitosamente');
            
        } catch (\Exception $e) {
            return $response->redirect('/pagos')->with('error', 'Error al eliminar el pago: ' . $e->getMessage());
        }
    }
    
    public function receipt(Request $request, Response $response)
    {
        if (!$this->isCajero()) {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }
        
        $pagoId = $request->query['id'] ?? null;
        
        // Obtener datos completos del pago con todas las relaciones
        $db = \App\Core\SimpleDatabase::getInstance();
        $sql = "SELECT p.*, 
                       c.fecha, c.hora_inicio, c.hora_fin, c.razon,
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       pu.dni as paciente_dni, pu.telefono as paciente_telefono,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM pagos p
                LEFT JOIN citas c ON p.cita_id = c.id
                LEFT JOIN pacientes pa ON c.paciente_id = pa.id
                LEFT JOIN usuarios pu ON pa.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE p.id = ?";
        
        $pago = $db->fetchOne($sql, [$pagoId]);
        
        if (!$pago) {
            return $response->redirect('/pagos')->with('error', 'Pago no encontrado');
        }
        
        return $response->view('pagos/receipt', [
            'pago' => $pago
        ]);
    }
    
    
    private function isCajero(): bool
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return false;
        }
        
        $role = $user['rol'] ?? $user['role'] ?? null;
        return $role === 'cajero' || $role === 'cashier';
    }
}
