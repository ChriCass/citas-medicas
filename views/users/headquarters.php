<!-- Sección de asignación de sedes -->
<div class="card mb-4" style="padding: 1rem;">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h2 style="margin: 0;">🏢 Asignación de Sedes a Doctores</h2>
            <a href="/users" class="btn" style="text-decoration: none; background-color: #6c757d; color: white;">
                ← Volver a Usuarios
            </a>
        </div>
    </div>
</div>

<!-- Tabla de doctores con asignación de sedes -->
<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem;">
        <h3 style="margin: 0; font-size: 1.2rem;">👨‍⚕️ Doctores - Click en una fila para asignar sedes</h3>
    </div>
    <div class="card-body">
        <div id="loadingDoctors" style="text-align: center; padding: 2rem; display: none;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 1rem; color: #666;">Cargando doctores...</p>
        </div>
        
        <div id="doctorsTableContainer">
            <!-- La tabla de doctores se cargará aquí dinámicamente -->
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .mb-4 {
        margin-bottom: 1.5rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .btn.primary {
        background-color: #3498db;
        color: white;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    table thead {
        background-color: #f8f9fa;
    }
    
    table th,
    table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
        font-size: 0.9rem;
    }
    
    table th {
        font-weight: 600;
        color: #2c3e50;
    }
    
    table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .badge-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .badge-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    /* Estilos para filas expandibles */
    .doctor-row {
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .doctor-row:hover {
        background-color: #e8f4f8 !important;
    }
    
    .doctor-row.expanded {
        background-color: #d4edff !important;
    }
    
    .expand-icon {
        display: inline-block;
        transition: transform 0.3s;
        margin-right: 0.5rem;
        font-weight: bold;
    }
    
    .doctor-row.expanded .expand-icon {
        transform: rotate(90deg);
    }
    
    .sede-assignment-row {
        display: none;
        background-color: #f8f9fa;
    }
    
    .sede-assignment-row.show {
        display: table-row;
    }
    
    .sede-assignment-content {
        padding: 1.5rem;
    }
    
    .sede-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .sede-checkbox-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        background: white;
        transition: all 0.3s;
    }
    
    .sede-checkbox-item:hover {
        border-color: #667eea;
        background-color: #f8f9ff;
    }
    
    .sede-checkbox-item input[type="checkbox"] {
        margin-right: 0.75rem;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .sede-checkbox-item label {
        cursor: pointer;
        margin: 0;
        flex: 1;
        font-weight: 500;
    }
    
    .sede-checkbox-item.checked {
        border-color: #667eea;
        background-color: #e8f0ff;
    }
    
    .sede-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .sede-name {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .sede-details {
        font-size: 0.85rem;
        color: #666;
    }
    
    .assignment-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px solid #e0e0e0;
    }
    
    .btn-save-assignments {
        background-color: #28a745;
        color: white;
        padding: 0.5rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-save-assignments:hover {
        background-color: #218838;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }
    
    .btn-cancel {
        background-color: #6c757d;
        color: white;
        padding: 0.5rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background-color: #5a6268;
    }
    
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        overflow-y: auto;
        padding: 20px;
    }
    
    .modal-content {
        background: white;
        border-radius: 8px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        color: #2c3e50;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 2rem;
        cursor: pointer;
        color: #999;
        line-height: 1;
        padding: 0;
        width: 30px;
        height: 30px;
    }
    
    .modal-close:hover {
        color: #333;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e0e0e0;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-icon {
        padding: 0.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    
    .btn-delete {
        background-color: #f44336;
        color: white;
    }
    
    .btn-delete:hover {
        background-color: #d32f2f;
    }
    
    @media (max-width: 1024px) {
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
    let doctors = [];
    let sedes = [];
    let assignments = [];
    let expandedDoctorId = null;
    
    // Cargar datos al cargar la página
    document.addEventListener('DOMContentLoaded', async function() {
        await loadSedes();
        await loadAssignments();
        await loadDoctors();
    });
    
    // Cargar doctores
    async function loadDoctors() {
        showLoading('doctors', true);
        
        try {
            const response = await fetch('/users/data?role=doctor');
            const data = await response.json();
            
            if (data.success) {
                doctors = data.data;
                renderDoctorsTable(doctors);
            } else {
                showError('Error al cargar doctores');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión al cargar doctores');
        } finally {
            showLoading('doctors', false);
        }
    }
    
    // Cargar sedes
    async function loadSedes() {
        try {
            const response = await fetch('/api/v1/sedes');
            const data = await response.json();
            
            if (data.data) {
                sedes = data.data;
                console.log('Sedes cargadas:', sedes);
            } else {
                console.error('Error al cargar sedes:', data);
                showError('Error al cargar sedes');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión al cargar sedes');
        }
    }
    
    // Cargar asignaciones
    async function loadAssignments() {
        try {
            const response = await fetch('/doctor-sede');
            const data = await response.json();
            
            console.log('=== Respuesta de API doctor-sede ===');
            console.log('Raw response:', data);
            
            if (data.success && data.data) {
                assignments = data.data;
                console.log('Asignaciones cargadas (array completo):', assignments);
                console.log('Cantidad de asignaciones:', assignments.length);
                
                // Verificar tipos de datos
                if (assignments.length > 0) {
                    console.log('Ejemplo de asignación:', assignments[0]);
                    console.log('Tipo de doctor_id:', typeof assignments[0].doctor_id);
                    console.log('Tipo de sede_id:', typeof assignments[0].sede_id);
                }
                
                // Actualizar la tabla de doctores si ya está renderizada
                if (doctors.length > 0) {
                    renderDoctorsTable(doctors);
                }
            } else {
                console.error('Error al cargar asignaciones:', data);
            }
        } catch (error) {
            console.error('Error:', error);
            console.error('Error de conexión al cargar asignaciones');
        }
    }
    
    // Renderizar tabla de doctores
    function renderDoctorsTable(doctors) {
        const container = document.getElementById('doctorsTableContainer');
        
        if (doctors.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 2rem; color: #999;">No hay doctores registrados</p>';
            return;
        }
        
        let html = `
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;"></th>
                            <th>ID Doctor</th>
                            <th>Nombre Completo</th>
                            <th>Especialidad</th>
                            <th>CMP</th>
                            <th>Sedes Asignadas</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        doctors.forEach(doctor => {
            const fullName = `${doctor.nombre || ''} ${doctor.apellido || ''}`.trim();
            const especialidad = doctor.especialidad_nombre || 'N/A';
            const cmp = doctor.cmp || 'N/A';
            const doctorId = doctor.doctor_id || doctor.id; // Usar doctor_id si está disponible
            
            // Contar sedes asignadas a este doctor usando doctor_id
            const sedesCount = assignments.filter(a => a.doctor_id === doctorId).length;
            
            console.log(`Doctor ${fullName} (doctor_id: ${doctorId}) tiene ${sedesCount} sedes asignadas`);
            
            html += `
                <tr class="doctor-row" onclick="toggleDoctorSedes(${doctorId})" id="doctor-row-${doctorId}">
                    <td><span class="expand-icon">▶</span></td>
                    <td>${doctorId}</td>
                    <td><strong>${fullName}</strong></td>
                    <td>${especialidad}</td>
                    <td>${cmp}</td>
                    <td><span class="badge badge-active">${sedesCount} sede(s)</span></td>
                </tr>
                <tr class="sede-assignment-row" id="sede-assignment-${doctorId}">
                    <td colspan="6">
                        <div class="sede-assignment-content" id="sede-content-${doctorId}">
                            <!-- El contenido se cargará dinámicamente -->
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    // Toggle expandir/contraer sedes del doctor
    function toggleDoctorSedes(doctorId) {
        const row = document.getElementById(`doctor-row-${doctorId}`);
        const assignmentRow = document.getElementById(`sede-assignment-${doctorId}`);
        
        // Si ya está expandido, contraer
        if (expandedDoctorId === doctorId) {
            row.classList.remove('expanded');
            assignmentRow.classList.remove('show');
            expandedDoctorId = null;
        } else {
            // Contraer cualquier fila previamente expandida
            if (expandedDoctorId !== null) {
                const prevRow = document.getElementById(`doctor-row-${expandedDoctorId}`);
                const prevAssignmentRow = document.getElementById(`sede-assignment-${expandedDoctorId}`);
                if (prevRow) prevRow.classList.remove('expanded');
                if (prevAssignmentRow) prevAssignmentRow.classList.remove('show');
            }
            
            // Expandir la nueva fila
            row.classList.add('expanded');
            assignmentRow.classList.add('show');
            expandedDoctorId = doctorId;
            
            // Renderizar el contenido de sedes
            renderSedesForDoctor(doctorId);
        }
    }
    
    // Renderizar sedes para asignar a un doctor
    function renderSedesForDoctor(doctorId) {
        const container = document.getElementById(`sede-content-${doctorId}`);
        const doctor = doctors.find(d => (d.doctor_id || d.id) === doctorId);
        const doctorName = doctor ? `${doctor.nombre} ${doctor.apellido}` : '';
        
        console.log('=== Renderizando sedes para doctor ===');
        console.log('Doctor ID recibido:', doctorId, 'tipo:', typeof doctorId);
        console.log('Doctor encontrado:', doctor);
        
        // Obtener las sedes ya asignadas a este doctor
        console.log('Total de asignaciones globales:', assignments.length);
        console.log('Buscando asignaciones donde doctor_id ===', doctorId);
        
        const assignedSedeIds = [];
        assignments.forEach(a => {
            console.log(`Comparando: a.doctor_id=${a.doctor_id} (${typeof a.doctor_id}) === doctorId=${doctorId} (${typeof doctorId}) = ${a.doctor_id == doctorId}`);
            // Usar == para comparar sin importar el tipo
            if (a.doctor_id == doctorId) {
                console.log(`  ✓ Match! Agregando sede_id: ${a.sede_id}`);
                assignedSedeIds.push(a.sede_id);
            }
        });
        
        console.log('Sedes asignadas (IDs):', assignedSedeIds);
        console.log('Todas las sedes disponibles:', sedes.length);
        
        let html = `
            <div style="margin-bottom: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #667eea;">
                    🏥 Seleccione las sedes para ${doctorName}
                </h4>
                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                    Marque las sedes que desea asignar a este doctor. Actualmente tiene ${assignedSedeIds.length} sede(s) asignada(s).
                </p>
            </div>
            
            <div class="sede-grid">
        `;
        
        if (sedes.length === 0) {
            html += '<p style="padding: 2rem; text-align: center; color: #999;">No hay sedes disponibles</p>';
        } else {
            sedes.forEach(sede => {
                const sedeId = sede.id;
                // Usar == para comparar sin importar el tipo
                const isAssigned = assignedSedeIds.some(id => id == sedeId);
                const sedeName = sede.nombre || sede.nombre_sede || 'N/A';
                const sedeDir = sede.direccion || 'Sin dirección';
                const sedeTel = sede.telefono || 'Sin teléfono';
                const checkedClass = isAssigned ? 'checked' : '';
                const checkedAttr = isAssigned ? 'checked' : '';
                
                console.log(`Sede ${sedeId} (${sedeName}): isAssigned=${isAssigned}, checked=${checkedAttr}`);
                
                html += `
                    <div class="sede-checkbox-item ${checkedClass}">
                        <input 
                            type="checkbox" 
                            id="sede-${doctorId}-${sede.id}" 
                            value="${sede.id}"
                            ${checkedAttr}
                            onchange="toggleSedeCheckbox(this, ${doctorId}, ${sede.id})"
                        />
                        <label for="sede-${doctorId}-${sede.id}">
                            <div class="sede-info">
                                <span class="sede-name">📍 ${sedeName}</span>
                                <span class="sede-details">📞 ${sedeTel}</span>
                                <span class="sede-details">🏠 ${sedeDir}</span>
                            </div>
                        </label>
                    </div>
                `;
            });
        }
        
        html += `
            </div>
            
            <div class="assignment-actions">
                <button type="button" class="btn-cancel" onclick="toggleDoctorSedes(${doctorId})">
                    Cancelar
                </button>
                <button type="button" class="btn-save-assignments" onclick="saveSedesForDoctor(${doctorId})">
                    💾 Guardar Asignaciones
                </button>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    // Toggle checkbox de sede
    function toggleSedeCheckbox(checkbox, doctorId, sedeId) {
        const item = checkbox.closest('.sede-checkbox-item');
        if (checkbox.checked) {
            item.classList.add('checked');
        } else {
            item.classList.remove('checked');
        }
    }
    
    // Guardar sedes asignadas para un doctor
    async function saveSedesForDoctor(doctorId) {
        const checkboxes = document.querySelectorAll(`#sede-content-${doctorId} input[type="checkbox"]`);
        const selectedSedeIds = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => parseInt(cb.value));
        
        // Obtener las sedes actualmente asignadas usando comparación flexible
        const currentAssignments = assignments
            .filter(a => a.doctor_id == doctorId)  // Usar == en lugar de ===
            .map(a => parseInt(a.sede_id));  // Asegurar que sea número
        
        // Determinar qué sedes agregar y cuáles eliminar
        const sedesToAdd = selectedSedeIds.filter(id => !currentAssignments.includes(id));
        const sedesToRemove = currentAssignments.filter(id => !selectedSedeIds.includes(id));
        
        console.log('=== Guardando asignaciones ===');
        console.log('Doctor ID:', doctorId, 'tipo:', typeof doctorId);
        console.log('Sedes seleccionadas:', selectedSedeIds);
        console.log('Asignaciones actuales:', currentAssignments);
        console.log('Sedes a agregar:', sedesToAdd);
        console.log('Sedes a eliminar:', sedesToRemove);
        
        // Si no hay cambios, cerrar el panel
        if (sedesToAdd.length === 0 && sedesToRemove.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin cambios',
                text: 'No se detectaron cambios en las asignaciones',
                confirmButtonColor: '#3498db'
            });
            toggleDoctorSedes(doctorId);
            return;
        }
        
        try {
            // Mostrar indicador de carga
            Swal.fire({
                title: 'Guardando...',
                text: 'Actualizando asignaciones de sedes',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            let errorsOccurred = false;
            let errorMessages = [];
            let sedesWithSchedules = [];
            
            // Eliminar sedes
            for (const sedeId of sedesToRemove) {
                console.log(`Eliminando asignación: doctor ${doctorId}, sede ${sedeId}`);
                try {
                    const response = await fetch(`/doctor-sede/${doctorId}/${sedeId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    const result = await response.json();
                    
                    if (!result.success) {
                        if (result.has_schedules) {
                            // Si tiene horarios programados, agregar a la lista especial
                            const sedeName = sedes.find(s => s.id == sedeId)?.nombre || 
                                           sedes.find(s => s.id == sedeId)?.nombre_sede || 
                                           `Sede ${sedeId}`;
                            sedesWithSchedules.push({
                                id: sedeId,
                                name: sedeName,
                                count: result.schedule_count
                            });
                        } else {
                            errorsOccurred = true;
                            errorMessages.push(`Error al eliminar sede ${sedeId}: ${result.message}`);
                        }
                    } else {
                        console.log(`✓ Sede ${sedeId} eliminada correctamente`);
                    }
                } catch (error) {
                    errorsOccurred = true;
                    errorMessages.push(`Error al eliminar sede ${sedeId}: ${error.message}`);
                    console.error(`Error al eliminar sede ${sedeId}:`, error);
                }
            }
            
            // Si hay sedes con horarios, mostrar mensaje específico y detener
            if (sedesWithSchedules.length > 0) {
                let message = '<div style="text-align: left; margin: 1rem 0;">';
                message += '<p><strong>No se pueden eliminar las siguientes sedes porque tienen horarios programados:</strong></p>';
                message += '<ul style="margin: 1rem 0; padding-left: 1.5rem;">';
                sedesWithSchedules.forEach(sede => {
                    message += `<li><strong>${sede.name}</strong>: ${sede.count} horario(s) programado(s)</li>`;
                });
                message += '</ul>';
                message += '<p style="color: #d33; font-weight: 600;">Primero elimine los horarios asociados a estas sedes.</p>';
                message += '</div>';
                
                Swal.fire({
                    icon: 'warning',
                    title: '⚠️ Operación no permitida',
                    html: message,
                    confirmButtonColor: '#f39c12',
                    width: '600px'
                });
                
                // No continuar con las demás operaciones
                return;
            }
            
            // Agregar nuevas sedes (solo si no hubo problemas con las eliminaciones)
            for (const sedeId of sedesToAdd) {
                console.log(`Agregando asignación: doctor ${doctorId}, sede ${sedeId}`);
                try {
                    const response = await fetch('/doctor-sede', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            doctor_id: parseInt(doctorId),
                            sede_id: parseInt(sedeId),
                            fecha_inicio: new Date().toISOString().split('T')[0],
                            fecha_fin: null
                        })
                    });
                    const result = await response.json();
                    if (!result.success) {
                        errorsOccurred = true;
                        errorMessages.push(`Error al agregar sede ${sedeId}: ${result.message}`);
                    } else {
                        console.log(`✓ Sede ${sedeId} agregada correctamente`);
                    }
                } catch (error) {
                    errorsOccurred = true;
                    errorMessages.push(`Error al agregar sede ${sedeId}: ${error.message}`);
                    console.error(`Error al agregar sede ${sedeId}:`, error);
                }
            }
            
            // Recargar asignaciones
            await loadAssignments();
            
            // Cerrar el panel expandido
            toggleDoctorSedes(doctorId);
            
            // Actualizar la tabla
            renderDoctorsTable(doctors);
            
            if (errorsOccurred) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Completado con errores',
                    html: 'Algunas operaciones fallaron:<br>' + errorMessages.join('<br>'),
                    confirmButtonColor: '#f39c12'
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: '✅ ¡Éxito!',
                    text: `Se actualizaron las asignaciones correctamente. Agregadas: ${sedesToAdd.length}, Eliminadas: ${sedesToRemove.length}`,
                    confirmButtonColor: '#3498db'
                });
            }
            
        } catch (error) {
            console.error('Error general:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al guardar las asignaciones',
                confirmButtonColor: '#d33'
            });
        }
    }
    
    // Mostrar/ocultar indicador de carga
    function showLoading(type, show) {
        const loadingId = `loading${type.charAt(0).toUpperCase() + type.slice(1)}`;
        const containerId = `${type}TableContainer`;
        
        const loadingElement = document.getElementById(loadingId);
        const containerElement = document.getElementById(containerId);
        
        if (loadingElement) loadingElement.style.display = show ? 'block' : 'none';
        if (containerElement) containerElement.style.display = show ? 'none' : 'block';
    }
    
    // Mostrar error
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#d33'
        });
    }
</script>
