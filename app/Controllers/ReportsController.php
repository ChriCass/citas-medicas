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

    /**
     * Exportar resultado en PDF o XLSX
     * Usa Dompdf para PDF y PhpSpreadsheet para XLSX si están disponibles.
     */
    public function export(Request $req, Response $res)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return $res->redirect('/login');
        Auth::abortUnless($res, ['superadmin']);

        $desde = isset($_GET['desde']) ? trim($_GET['desde']) : '';
        $hasta = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';
        $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'todos';
        $format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'pdf';

        if (!$desde || !$hasta) {
            return $res->redirect('/reports');
        }

        try {
            $db = SimpleDatabase::getInstance();

            // Preparar filtro por estado (parameterizado)
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

            $dbType = $db->getConnectionType();
            if ($dbType === 'sqlsrv') {
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
                if ($dbType === 'mysql') {
                    $diagExpr = "GROUP_CONCAT(DISTINCT d.nombre_enfermedad SEPARATOR ', ')";
                } else {
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

            // Prepare filename
            $filenameBase = 'reporte_citas_' . str_replace('-', '', $desde) . '_' . str_replace('-', '', $hasta);

            // metadata for exports
            $generatedBy = trim((($_SESSION['user']['nombre'] ?? '') . ' ' . ($_SESSION['user']['apellido'] ?? '')));
            if ($generatedBy === '') $generatedBy = $_SESSION['user']['username'] ?? ($_SESSION['user']['email'] ?? 'Usuario desconocido');
            $generatedAt = date('Y-m-d H:i:s');

            // Export XLSX
            if ($format === 'xlsx') {
                if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                    return $res->view('reports/result', ['title' => 'Reporte', 'rows' => $rows, 'desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo, 'message' => 'La librería PhpSpreadsheet no está instalada. Instale phpoffice/phpspreadsheet via composer.']);
                }

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                // Metadata rows
                $sheet->setCellValue('A1', 'Reporte');
                $sheet->setCellValue('A2', 'Generado por: ' . $generatedBy);
                $sheet->setCellValue('A3', 'Fecha/Hora: ' . $generatedAt);
                $sheet->setCellValue('A4', 'Desde ' . $desde . ' Hasta ' . $hasta);
                // Blank separator row (row 5)
                // Header (start at row 6)
                $headers = ['Estado','Pago','Sede','Doctor','Paciente','Email','Teléfono','Diagnóstico'];
                // Column letters for 8 columns
                $cols = ['A','B','C','D','E','F','G','H'];
                foreach ($headers as $i => $h) {
                    $sheet->setCellValue($cols[$i] . '6', $h);
                }
                $rowNum = 7;

                // Styling: title (A1), metadata bold small, header bold with fill and borders
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A4')->getFont()->setBold(false)->setSize(11);
                // Header style
                $sheet->getStyle('A6:H6')->getFont()->setBold(true);
                $sheet->getStyle('A6:H6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
                // Borders for header and data range will be applied after writing data
                foreach ($rows as $r) {
                    $sheet->setCellValue('A' . $rowNum, $r['estado'] ?? '');
                    $sheet->setCellValue('B' . $rowNum, $r['pago'] ?? '');
                    $sheet->setCellValue('C' . $rowNum, $r['sede_nombre'] ?? '');
                    $sheet->setCellValue('D' . $rowNum, (($r['doctor_nombre'] ?? '') . ' ' . ($r['doctor_apellido'] ?? '')) );
                    $sheet->setCellValue('E' . $rowNum, (($r['paciente_nombre'] ?? '') . ' ' . ($r['paciente_apellido'] ?? '')) );
                    $sheet->setCellValue('F' . $rowNum, $r['email'] ?? '');
                    $sheet->setCellValue('G' . $rowNum, $r['telefono'] ?? '');
                    $sheet->setCellValue('H' . $rowNum, $r['diagnostico_nombre'] ?? '');
                    $rowNum++;
                }

                // Apply borders for header+data range
                $lastRow = $rowNum - 1;
                $range = 'A6:H' . $lastRow;
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE)->getColor()->setRGB('CCCCCC');

                // Autosize columns
                foreach ($cols as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Freeze pane so header row remains visible (freeze just below header)
                $sheet->freezePane('A7');

                // Output XLSX
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filenameBase . '.xlsx"');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
            }

            // Export PDF
            if ($format === 'pdf') {
                if (!class_exists('\Dompdf\Dompdf')) {
                    return $res->view('reports/result', ['title' => 'Reporte', 'rows' => $rows, 'desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo, 'message' => 'La librería Dompdf no está instalada. Instale dompdf/dompdf via composer.']);
                }

                // Build simple HTML for PDF
                $generatedBy = trim((($_SESSION['user']['nombre'] ?? '') . ' ' . ($_SESSION['user']['apellido'] ?? '')));
                if ($generatedBy === '') $generatedBy = $_SESSION['user']['username'] ?? ($_SESSION['user']['email'] ?? 'Usuario desconocido');
                $generatedAt = date('Y-m-d H:i:s');

                $html = '<html><head><meta charset="utf-8"><style>body{font-family: Arial, Helvetica, sans-serif; font-size:12px;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:6px; text-align:left;} th{background:#f0f0f0;}</style></head><body>';
                $html .= '<h2>' . htmlspecialchars('Reporte') . '</h2>';
                $html .= '<div><strong>Generado por:</strong> ' . htmlspecialchars($generatedBy) . '</div>';
                $html .= '<div><strong>Fecha/Hora:</strong> ' . htmlspecialchars($generatedAt) . '</div>';
                $html .= '<div><strong>Desde</strong> ' . htmlspecialchars($desde) . ' <strong>Hasta</strong> ' . htmlspecialchars($hasta) . '</div>';
                $html .= '<br/><table><thead><tr>';
                $cols = ['Estado','Pago','Sede','Doctor','Paciente','Email','Teléfono','Diagnóstico'];
                foreach ($cols as $c) $html .= '<th>' . htmlspecialchars($c) . '</th>';
                $html .= '</tr></thead><tbody>';
                foreach ($rows as $r) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($r['estado'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['pago'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['sede_nombre'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars((($r['doctor_nombre'] ?? '') . ' ' . ($r['doctor_apellido'] ?? ''))) . '</td>';
                    $html .= '<td>' . htmlspecialchars((($r['paciente_nombre'] ?? '') . ' ' . ($r['paciente_apellido'] ?? ''))) . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['email'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['telefono'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['diagnostico_nombre'] ?? '') . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></body></html>';

                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $dompdf->stream($filenameBase . '.pdf', ['Attachment' => 1]);
                exit;
            }

            // Format not supported
            return $res->view('reports/result', ['title' => 'Reporte', 'rows' => $rows, 'desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo, 'message' => 'Formato de exportación no soportado.']);

        } catch (\Throwable $e) {
            return $res->view('reports/result', ['title' => 'Reporte', 'rows' => [], 'message' => 'Error al exportar: ' . $e->getMessage(), 'desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo]);
        }
    }

    
}
