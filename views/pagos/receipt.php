<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago</title>
    <link rel="stylesheet" href="/assets/styles.css?v=palette-teal-ff0063" />
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        .receipt-container {
            background: white;
            color: black;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        .receipt-subtitle {
            font-size: 18px;
            color: #7f8c8d;
            margin: 0;
        }
        .receipt-content {
            font-size: 14px;
            line-height: 1.6;
        }
        .receipt-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .receipt-section h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .receipt-label {
            font-weight: bold;
            color: #555;
        }
        .receipt-value {
            color: #333;
        }
        .receipt-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        .print-btn:hover {
            background: #0056b3;
        }
        @media print {
            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir</button>
    
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-title">Clínica Médica Demo</div>
            <div class="receipt-subtitle">COMPROBANTE DE PAGO</div>
        </div>
        
        <div class="receipt-content">
            <div class="receipt-section">
                <h3>Información del Paciente</h3>
                <div class="receipt-row">
                    <span class="receipt-label">Nombre:</span>
                    <span class="receipt-value"><?= htmlspecialchars(($pago['paciente_nombre'] ?? '') . ' ' . ($pago['paciente_apellido'] ?? '')) ?></span>
                </div>
            </div>
            
            <div class="receipt-section">
                <h3>Información de la Cita</h3>
                <div class="receipt-row">
                    <span class="receipt-label">Doctor:</span>
                    <span class="receipt-value"><?= htmlspecialchars(($pago['doctor_nombre'] ?? '') . ' ' . ($pago['doctor_apellido'] ?? '')) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Especialidad:</span>
                    <span class="receipt-value"><?= htmlspecialchars($pago['especialidad_nombre'] ?? 'N/A') ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Sede:</span>
                    <span class="receipt-value"><?= htmlspecialchars($pago['nombre_sede'] ?? 'N/A') ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Fecha:</span>
                    <span class="receipt-value"><?= htmlspecialchars(date('d/m/Y', strtotime($pago['fecha']))) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Hora:</span>
                    <span class="receipt-value"><?= htmlspecialchars($pago['hora_inicio']) ?> - <?= htmlspecialchars($pago['hora_fin']) ?></span>
                </div>
            </div>
            
            <div class="receipt-section">
                <h3>Detalles del Pago</h3>
                <div class="receipt-row">
                    <span class="receipt-label">Fecha de Pago:</span>
                    <span class="receipt-value"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($pago['fecha_pago']))) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Método de Pago:</span>
                    <span class="receipt-value"><?= htmlspecialchars(ucfirst($pago['metodo_pago'])) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Estado:</span>
                    <span class="receipt-value"><?= htmlspecialchars(ucfirst($pago['estado'])) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Monto:</span>
                    <span class="receipt-value" style="font-size: 18px; font-weight: bold; color: #28a745;">S/ <?= number_format($pago['monto'], 2) ?></span>
                </div>
                
                <?php if (!empty($pago['observaciones'])): ?>
                <div class="receipt-row">
                    <span class="receipt-label">Observaciones:</span>
                    <span class="receipt-value"><?= htmlspecialchars($pago['observaciones']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p>Este comprobante es válido como constancia de pago.</p>
            <p>Clínica Médica Demo - Sistema de Gestión de Citas</p>
            <p>Fecha de emisión: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
</body>
</html>