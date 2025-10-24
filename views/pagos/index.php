<?php $role = $user['rol'] ?? ''; ?>
<h1>Gestión de Pagos</h1>

<!-- Buscador y Acciones -->
<div class="search-container" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <form method="GET" action="/pagos" style="display: flex; gap: 10px; align-items: center; flex: 1;">
            <input type="text" 
                   name="search" 
                   value="<?= htmlspecialchars($search ?? '') ?>" 
                   placeholder="Buscar por nombre o DNI del paciente..."
                   style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="btn primary">Buscar</button>
            <?php if ($search): ?>
                <a href="/pagos" class="btn secondary">Limpiar</a>
            <?php endif; ?>
        </form>
        <a href="/pagos/registrar-manual" class="btn success" style="margin-left: 15px;">
            <i class="icon-plus"></i> Registrar Pago Manual
        </a>
    </div>
</div>

<div class="grid">
    <div class="col-6">
        <h2>Citas Elegibles para Pago</h2>
        
        <?php if (!empty($citasAtendidas)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>DNI</th>
                            <th>Doctor</th>
                            <th>Especialidad</th>
                            <th>Sede</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citasAtendidas as $cita): ?>
                            <tr>
                                <td><?= htmlspecialchars(($cita['paciente_nombre'] ?? '') . ' ' . ($cita['paciente_apellido'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($cita['paciente_dni'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(($cita['doctor_nombre'] ?? '') . ' ' . ($cita['doctor_apellido'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($cita['especialidad_nombre'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($cita['nombre_sede'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($cita['fecha']))) ?></td>
                                <td><?= htmlspecialchars($cita['hora_inicio']) ?></td>
                                <td>
                                    <span class="chip status-<?= htmlspecialchars($cita['estado']) ?>">
                                        <?= htmlspecialchars(ucfirst($cita['estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/pagos/registrar?id=<?= (int)$cita['id'] ?>" class="btn small primary">
                                        Registrar Pago
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>
                <?php if ($search): ?>
                    No se encontraron citas elegibles para pago que coincidan con "<?= htmlspecialchars($search) ?>".
                <?php else: ?>
                    No hay citas elegibles para pago.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    
    <div class="col-6">
        <h2>Pagos Registrados</h2>
        
        <?php if (!empty($pagos)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Doctor</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Fecha Pago</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td><?= htmlspecialchars(($pago['paciente_nombre'] ?? '') . ' ' . ($pago['paciente_apellido'] ?? '')) ?></td>
                                <td><?= htmlspecialchars(($pago['doctor_nombre'] ?? '') . ' ' . ($pago['doctor_apellido'] ?? '')) ?></td>
                                <td>S/ <?= number_format($pago['monto'], 2) ?></td>
                                <td><?= htmlspecialchars(ucfirst($pago['metodo_pago'])) ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($pago['fecha_pago']))) ?></td>
                                <td>
                                    <span class="chip status-<?= htmlspecialchars($pago['estado']) ?>">
                                        <?= htmlspecialchars(ucfirst($pago['estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="/pagos/comprobante?id=<?= (int)$pago['id'] ?>" class="btn small" target="_blank">
                                            Ver Comprobante
                                        </a>
                                        <a href="/pagos/<?= (int)$pago['id'] ?>/editar" class="btn small warning">
                                            Editar
                                        </a>
                                        <button onclick="confirmDelete(<?= (int)$pago['id'] ?>)" class="btn small danger">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No hay pagos registrados.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Formulario oculto para eliminación -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
</form>

<script>
// Función para confirmar eliminación de pago
function confirmDelete(pagoId) {
    Swal.fire({
        title: '¿Eliminar pago?',
        text: 'Esta acción no se puede deshacer. El pago será eliminado permanentemente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario dinámico para eliminación
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/pagos/${pagoId}/eliminar`;
            
            // Agregar token CSRF
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf';
            csrfInput.value = '<?= htmlspecialchars(\App\Core\Csrf::token()) ?>';
            form.appendChild(csrfInput);
            
            // Enviar formulario
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
