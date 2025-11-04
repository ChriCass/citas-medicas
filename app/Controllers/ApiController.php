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
            $dateObj = new \DateTimeImmutable($date);
            $slots = \App\Core\Availability::slotsForDate($dateObj, (int)$doctorId, (int)$locationId);
            
            return $response->json(['slots' => $slots]);
        } catch (\Exception $e) {
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
