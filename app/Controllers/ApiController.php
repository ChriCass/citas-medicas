<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class ApiController
{
    public function searchPacientes(Request $request, Response $response)
    {
        $search = $request->query['q'] ?? null;
        
        if (empty($search)) {
            return $response->json(['pacientes' => []]);
        }
        
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
                    AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.dni LIKE ?)
                    ORDER BY u.nombre ASC";
        } else {
            // SQLite/MySQL usan LIMIT
            $sql = "SELECT u.*, p.id as paciente_id
                    FROM usuarios u
                    INNER JOIN pacientes p ON u.id = p.usuario_id
                    INNER JOIN tiene_roles tr ON u.id = tr.usuario_id
                    INNER JOIN roles r ON tr.rol_id = r.id
                    WHERE r.nombre = 'paciente'
                    AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.dni LIKE ?)
                    ORDER BY u.nombre ASC
                    LIMIT 10";
        }
        
        $searchTerm = "%{$search}%";
        $pacientes = $db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
        
        // Formatear los resultados
        $results = array_map(function($paciente) {
            return [
                'usuario_id' => $paciente['id'],
                'paciente_id' => $paciente['paciente_id'],
                'nombre' => $paciente['nombre'],
                'apellido' => $paciente['apellido'],
                'dni' => $paciente['dni'],
                'email' => $paciente['email'],
                'telefono' => $paciente['telefono']
            ];
        }, $pacientes);
        
        return $response->json(['pacientes' => $results]);
    }
    
    public function getSlots(Request $request, Response $response)
    {
        $date = $request->query['date'] ?? null;
        $doctorId = $request->query['doctor_id'] ?? null;
        $locationId = $request->query['location_id'] ?? 0;
        
        if (!$date || !$doctorId) {
            return $response->json(['error' => 'Faltan parámetros requeridos'], 400);
        }
        
        try {
            // Usar conexión sencilla para construir consulta compatible con distintos motores
            $db = \App\Core\SimpleDatabase::getInstance();
            $pdoType = $db->getConnectionType();

            // Normalizar fecha según motor: devolver formato YYYY-MM-DD en la respuesta
            // SQL: unir horarios_medicos (h) -> calendario (c) -> slots_calendario (s)
            $sql = "SELECT h.id AS horario_id, c.id AS calendario_id, c.fecha, s.hora_inicio, s.hora_fin
                    FROM horarios_medicos h
                    JOIN calendario c ON h.id = c.horario_id
                    JOIN slots_calendario s ON s.calendario_id = c.id AND s.horario_id = h.id
                    WHERE h.doctor_id = :doctor_id AND c.fecha = :fecha";

            $params = ['doctor_id' => (int)$doctorId, 'fecha' => $date];

            // Si se especifica sede (locationId > 0), filtrar por ella
            if ((int)$locationId > 0) {
                $sql .= " AND h.sede_id = :sede_id";
                $params['sede_id'] = (int)$locationId;
            }

            $sql .= " ORDER BY s.hora_inicio";

            $rows = $db->fetchAll($sql, $params);

            $slots = [];
            foreach ($rows as $r) {
                // hora_inicio puede venir como HH:MM:SS o HH:MM:SS.000 -> normalizar a HH:MM
                $hi = isset($r['hora_inicio']) ? substr($r['hora_inicio'], 0, 5) : null;
                $hf = isset($r['hora_fin']) ? substr($r['hora_fin'], 0, 5) : null;

                // Si la tabla tiene columna 'disponible' y/o 'reservado_por_cita_id', excluir slots no disponibles
                $disponible = true;
                if (array_key_exists('disponible', $r)) {
                    $disponible = (bool)$r['disponible'];
                }
                if (array_key_exists('reservado_por_cita_id', $r) && $r['reservado_por_cita_id']) {
                    $disponible = false;
                }

                if (!$disponible) continue;

                $slots[] = [
                    'horario_id' => $r['horario_id'] ?? null,
                    'calendario_id' => $r['calendario_id'] ?? null,
                    'hora_inicio' => $hi,
                    'hora_fin' => $hf,
                ];
            }

            return $response->json(['date' => $date, 'slots' => $slots]);
        } catch (\Throwable $e) {
            return $response->json(['error' => 'Error al obtener horarios: ' . $e->getMessage()], 500);
        }
    }
    
    public function getEspecialidades(Request $request, Response $response)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        $especialidades = $db->fetchAll("SELECT id, nombre FROM especialidades ORDER BY nombre");
        return $response->json(['success' => true, 'data' => $especialidades]);
    }
}
