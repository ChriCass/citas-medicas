<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class DoctorSedeController
{
    /**
     * Obtener todas las asignaciones doctor-sede
     */
    public function index(Request $request, Response $response)
    {
        try {
            $pdo = \App\Core\Database::pdo();

            // SQL Server usa + para concatenación, MySQL usa CONCAT()
            $concatFunction = \App\Core\Database::isSqlServer() ?
                "u.nombre + ' ' + u.apellido" :
                "CONCAT(u.nombre, ' ', u.apellido)";

            $sql = "SELECT
                    ds.sede_id,
                    ds.doctor_id,
                    ds.fecha_inicio,
                    ds.fecha_fin,
                    s.nombre_sede AS sede_nombre,
                    {$concatFunction} AS doctor_nombre
                    FROM doctor_sede ds
                    JOIN sedes s ON ds.sede_id = s.id
                    JOIN doctores d ON ds.doctor_id = d.id
                    JOIN usuarios u ON d.usuario_id = u.id
                    ORDER BY u.nombre, s.nombre_sede";

            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Debug: agregar log
            error_log('Doctor-Sede rows: ' . print_r($rows, true));

            return $response->json(['success' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            error_log('Error en doctor-sede: ' . $e->getMessage());
            return $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva asignación doctor-sede
     */
    public function store(Request $request, Response $response)
    {
        try {
            // Leer datos JSON del cuerpo de la solicitud
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            $doctorId = (int)($input['doctor_id'] ?? 0);
            $sedeId = (int)($input['sede_id'] ?? 0);
            $fechaInicio = $input['fecha_inicio'] ?? null;
            $fechaFin = $input['fecha_fin'] ?? null;

            if (!$doctorId || !$sedeId || !$fechaInicio) {
                return $response->json(['success' => false, 'message' => 'Datos incompletos'], 400);
            }

            $pdo = \App\Core\Database::pdo();

            // Verificar si ya existe la asignación
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctor_sede WHERE doctor_id = :doctor_id AND sede_id = :sede_id");
            $stmt->execute(['doctor_id' => $doctorId, 'sede_id' => $sedeId]);

            if ($stmt->fetchColumn() > 0) {
                return $response->json(['success' => false, 'message' => 'Esta asignación ya existe'], 400);
            }

            // Insertar nueva asignación
            $sql = "INSERT INTO doctor_sede (doctor_id, sede_id, fecha_inicio, fecha_fin)
                    VALUES (:doctor_id, :sede_id, :fecha_inicio, :fecha_fin)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'doctor_id' => $doctorId,
                'sede_id' => $sedeId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]);

            return $response->json(['success' => true, 'message' => 'Asignación creada correctamente']);
        } catch (\Throwable $e) {
            return $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar asignación doctor-sede
     */
    public function destroy(Request $request, Response $response)
    {
        try {
            $doctorId = (int)($request->params['doctor_id'] ?? 0);
            $sedeId = (int)($request->params['sede_id'] ?? 0);

            if (!$doctorId || !$sedeId) {
                return $response->json(['success' => false, 'message' => 'Parámetros inválidos'], 400);
            }

            $pdo = \App\Core\Database::pdo();

            // Verificar si el doctor tiene horarios programados en esta sede
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM horarios_medicos
                WHERE doctor_id = :doctor_id AND sede_id = :sede_id
            ");
            $stmt->execute(['doctor_id' => $doctorId, 'sede_id' => $sedeId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                return $response->json([
                    'success' => false,
                    'message' => 'No se puede eliminar esta sede porque el doctor tiene horarios programados en ella. Primero elimine los horarios.',
                    'has_schedules' => true,
                    'schedule_count' => $result['count']
                ], 400);
            }

            // Si no tiene horarios, proceder a eliminar
            $stmt = $pdo->prepare("DELETE FROM doctor_sede WHERE doctor_id = :doctor_id AND sede_id = :sede_id");
            $stmt->execute(['doctor_id' => $doctorId, 'sede_id' => $sedeId]);

            return $response->json(['success' => true, 'message' => 'Asignación eliminada correctamente']);
        } catch (\Throwable $e) {
            error_log('Error al eliminar doctor-sede: ' . $e->getMessage());
            return $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}