<?php
namespace App\Controllers;

use App\Core\{Request, Response, Auth};
use App\Core\SimpleDatabase;

class ReportsController
{
    public function form(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        return $res->view('reports/form', ['title' => 'Generar Reportes']);
    }

    public function generate(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        $desde = isset($_POST['desde']) ? trim($_POST['desde']) : '';
        $hasta = isset($_POST['hasta']) ? trim($_POST['hasta']) : '';
        $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'todos'; // atendidas/no_atendidas/todos

        if (!$desde || !$hasta) {
            return $res->view('reports/form', ['error' => 'Seleccione rango de fechas', 'title' => 'Generar Reportes']);
        }

        try {
            $db = SimpleDatabase::getInstance();

            // Preparar filtro por estado (parameterizado para evitar inyección)
            $stateFilter = '';
            $params = [$desde, $hasta];
            $validStates = ['pendiente','confirmado','atendido','ausente','cancelado'];
            if (in_array($tipo, $validStates, true)) {
                $stateFilter = 'AND c.estado = ?';
                $params[] = $tipo;
            } elseif ($tipo === 'atendidas') {
                $stateFilter = 'AND c.estado = ?';
                $params[] = 'atendido';
            } elseif ($tipo === 'no_atendidas') {
                $stateFilter = 'AND c.estado != ?';
                $params[] = 'atendido';
            }

            // Construir consulta que incluye datos de paciente y concatena diagnósticos por cita
            $dbType = $db->getConnectionType();

            if ($dbType === 'sqlsrv') {
                // SQL Server: usar STRING_AGG
                $sql = "SELECT c.id AS cita_id, c.fecha, c.hora_inicio, c.estado, c.pago,
                            u.nombre AS paciente_nombre, u.apellido AS paciente_apellido, u.dni,
                                           u.email, u.telefono,
                                           p.numero_historia_clinica,
                                           u_doc.nombre AS doctor_nombre, u_doc.apellido AS doctor_apellido,
                                           s.nombre_sede AS sede_nombre,
                                           STRING_AGG(d.nombre_enfermedad, ', ') AS diagnostico_nombre
                        FROM citas c
                        LEFT JOIN pacientes p ON c.paciente_id = p.id
                        LEFT JOIN usuarios u ON p.usuario_id = u.id
                        LEFT JOIN doctores doc ON c.doctor_id = doc.id
                        LEFT JOIN usuarios u_doc ON doc.usuario_id = u_doc.id
                        LEFT JOIN sedes s ON c.sede_id = s.id
                        LEFT JOIN consultas con ON con.cita_id = c.id
                        LEFT JOIN detalle_consulta dc ON dc.id_consulta = con.id
                        LEFT JOIN diagnosticos d ON dc.id_diagnostico = d.id
                        WHERE c.fecha BETWEEN ? AND ? " . $stateFilter . "
                        GROUP BY c.id, c.fecha, c.hora_inicio, c.estado, c.pago, u.nombre, u.apellido, u.dni, u.email, u.telefono, p.numero_historia_clinica, u_doc.nombre, u_doc.apellido, s.nombre_sede
                        ORDER BY c.fecha ASC, c.hora_inicio ASC";
            } else {
                // SQLite / MySQL: usar GROUP_CONCAT (sintaxis distinta entre motores)
                if ($dbType === 'mysql') {
                    $diagExpr = "GROUP_CONCAT(DISTINCT d.nombre_enfermedad SEPARATOR ', ')";
                } else {
                    // sqlite
                    $diagExpr = "GROUP_CONCAT(DISTINCT d.nombre_enfermedad, ', ')";
                }

                $sql = "SELECT c.id AS cita_id, c.fecha, c.hora_inicio, c.estado, c.pago,
                               u.nombre AS paciente_nombre, u.apellido AS paciente_apellido, u.dni,
                               u.email, u.telefono,
                               p.numero_historia_clinica,
                               u_doc.nombre AS doctor_nombre, u_doc.apellido AS doctor_apellido,
                               s.nombre_sede AS sede_nombre,
                               " . $diagExpr . " AS diagnostico_nombre
                        FROM citas c
                        LEFT JOIN pacientes p ON c.paciente_id = p.id
                        LEFT JOIN usuarios u ON p.usuario_id = u.id
                        LEFT JOIN doctores doc ON c.doctor_id = doc.id
                        LEFT JOIN usuarios u_doc ON doc.usuario_id = u_doc.id
                        LEFT JOIN sedes s ON c.sede_id = s.id
                        LEFT JOIN consultas con ON con.cita_id = c.id
                        LEFT JOIN detalle_consulta dc ON dc.id_consulta = con.id
                        LEFT JOIN diagnosticos d ON dc.id_diagnostico = d.id
                        WHERE c.fecha BETWEEN ? AND ? " . $stateFilter . "
                        GROUP BY c.id, c.fecha, c.hora_inicio, c.estado, c.pago, u.nombre, u.apellido, u.dni, u.email, u.telefono, p.numero_historia_clinica, u_doc.nombre, u_doc.apellido, s.nombre_sede
                        ORDER BY c.fecha ASC, c.hora_inicio ASC";
            }

            $rows = $db->fetchAll($sql, $params);

            if (!$rows || count($rows) === 0) {
                return $res->view('reports/result', [
                    'title' => 'Reporte',
                    'rows' => [],
                    'message' => 'No se encontraron registros para los parámetros ingresados.',
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'tipo' => $tipo
                ]);
            }

            // Mostrar resultado en pantalla y ofrecer descarga CSV
            return $res->view('reports/result', [
                'title' => 'Reporte',
                'rows' => $rows,
                'desde' => $desde,
                'hasta' => $hasta,
                'tipo' => $tipo
            ]);

        } catch (\Throwable $e) {
            return $res->view('reports/form', ['error' => 'Error al generar el reporte: ' . $e->getMessage(), 'title' => 'Generar Reportes']);
        }
    }

    
}
