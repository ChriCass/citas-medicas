<!-- Sección de filtros y búsqueda -->
<div class="card mb-4" style="padding: 1rem;">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h2 style="margin: 0;">Gestión de Usuarios</h2>
            <div style="display: flex; gap: 0.5rem;">
                <a href="/users/headquarters" class="btn" style="text-decoration: none; background-color: #17a2b8; color: white;">
                    🏢 Asignación de Sedes
                </a>
                <a href="/users/create" class="btn primary" style="text-decoration: none;">
                    ➕ Agregar Usuario
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <!-- Filtro por rol -->
            <div style="flex: 0 1 200px;">
                <label for="roleFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Filtrar por Rol:</label>
                <select id="roleFilter" class="form-control" onchange="loadUsers()">
                    <option value="">Todos los roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars($role['nombre']) ?>">
                            <?= htmlspecialchars(ucfirst($role['nombre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Barra de búsqueda -->
            <div style="flex: 1 1 300px;">
                <label for="searchInput" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Buscar:</label>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="form-control" 
                    placeholder="Buscar por nombre, email, DNI, teléfono o dirección..."
                    onkeyup="handleSearch(event)"
                />
            </div>
        </div>
    </div>
</div>

<!-- Tabla de usuarios -->
<div class="card">
    <div class="card-body">
        <div id="loadingIndicator" style="text-align: center; padding: 2rem; display: none;">
            <div style="display: inline-block; width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 1rem;">Cargando usuarios...</p>
        </div>
        
        <div id="usersTableContainer">
            <!-- La tabla se cargará aquí dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal para crear/editar usuario -->
<div id="userModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3 id="modalTitle">Agregar Usuario</h3>
            <button type="button" class="modal-close" onclick="closeUserModal()">&times;</button>
        </div>
        <form id="userForm" onsubmit="saveUser(event)">
            <div class="modal-body">
                <input type="hidden" id="userId" name="userId" />
                
                <!-- SECCIÓN 1: Información General + Selección de Rol -->
                <div class="form-section">
                    <h4 style="margin-bottom: 1.5rem; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">
                        <span class="section-icon">👤</span>
                        Información General del Usuario
                    </h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre <span style="color: red;">*</span></label>
                            <input type="text" id="nombre" name="nombre" class="form-control" required />
                        </div>
                        
                        <div class="form-group">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido" class="form-control" />
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dni">DNI</label>
                            <input type="text" id="dni" name="dni" class="form-control" maxlength="8" pattern="[0-9]{8}" />
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span style="color: red;">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required />
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" />
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Contraseña <span id="passwordRequired" style="color: red;">*</span></label>
                            <input type="password" id="password" name="password" class="form-control" minlength="6" />
                            <small id="passwordHelp" style="color: #6c757d; display: none;">Dejar en blanco para mantener la actual</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <textarea id="direccion" name="direccion" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <!-- Selección de Rol con Radio Buttons -->
                    <div class="form-group" style="margin-top: 2rem;">
                        <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: #2c3e50;">
                            <span class="section-icon">🎭</span>
                            Rol del Usuario <span style="color: red;">*</span>
                        </label>
                        <div class="role-selector">
                            <label class="role-option">
                                <input type="radio" name="rol" value="paciente" onchange="handleRoleChange()">
                                <span class="role-card">
                                    <span class="role-icon">🏥</span>
                                    <span class="role-name">Paciente</span>
                                </span>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="rol" value="doctor" onchange="handleRoleChange()">
                                <span class="role-card">
                                    <span class="role-icon">👨‍⚕️</span>
                                    <span class="role-name">Doctor</span>
                                </span>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="rol" value="cajero" onchange="handleRoleChange()">
                                <span class="role-card">
                                    <span class="role-icon">💼</span>
                                    <span class="role-name">Cajero</span>
                                </span>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="rol" value="superadmin" onchange="handleRoleChange()">
                                <span class="role-card">
                                    <span class="role-icon">👑</span>
                                    <span class="role-name">Superadmin</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 2: Información Específica del Rol -->
                <div id="roleSpecificSection" style="display: none; margin-top: 2rem;">
                    <h4 id="roleSpecificTitle" style="margin-bottom: 1.5rem; color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 0.5rem;">
                        <span id="roleSpecificIcon" class="section-icon">📋</span>
                        <span id="roleSpecificText">Información Específica</span>
                    </h4>
                    
                    <!-- Campos específicos para PACIENTE -->
                    <div id="pacienteFields" class="role-specific-fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="numero_historia_clinica">
                                    <span class="field-icon">📋</span>
                                    Número de Historia Clínica
                                </label>
                                <input type="text" id="numero_historia_clinica" name="numero_historia_clinica" class="form-control" maxlength="20" />
                                <small style="color: #6c757d;">Debe ser único en el sistema</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="tipo_sangre">
                                    <span class="field-icon">🩸</span>
                                    Tipo de Sangre
                                </label>
                                <select id="tipo_sangre" name="tipo_sangre" class="form-control">
                                    <option value="">Seleccionar...</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="alergias">
                                <span class="field-icon">⚠️</span>
                                Alergias
                            </label>
                            <textarea id="alergias" name="alergias" class="form-control" rows="2" placeholder="Ej: Penicilina, Polen, Mariscos..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="condicion_cronica">
                                <span class="field-icon">🏥</span>
                                Condiciones Crónicas
                            </label>
                            <textarea id="condicion_cronica" name="condicion_cronica" class="form-control" rows="2" placeholder="Ej: Diabetes, Hipertensión, Asma..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="historial_cirugias">
                                <span class="field-icon">🔪</span>
                                Historial de Cirugías
                            </label>
                            <textarea id="historial_cirugias" name="historial_cirugias" class="form-control" rows="2" placeholder="Ej: Apendicectomía (2018), Cesárea (2020)..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="historico_familiar">
                                <span class="field-icon">👨‍👩‍👧‍👦</span>
                                Historial Médico Familiar
                            </label>
                            <textarea id="historico_familiar" name="historico_familiar" class="form-control" rows="2" placeholder="Ej: Padre con diabetes, madre con hipertensión..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="observaciones">
                                <span class="field-icon">📝</span>
                                Observaciones Adicionales
                            </label>
                            <textarea id="observaciones" name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <!-- Contacto de Emergencia -->
                        <h5 style="margin: 1.5rem 0 1rem 0; color: #e74c3c; border-bottom: 1px solid #e0e0e0; padding-bottom: 0.5rem;">
                            <span class="field-icon">🚨</span>
                            Contacto de Emergencia
                        </h5>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contacto_emergencia_nombre">
                                    <span class="field-icon">👤</span>
                                    Nombre del Contacto
                                </label>
                                <input type="text" id="contacto_emergencia_nombre" name="contacto_emergencia_nombre" class="form-control" />
                            </div>
                            
                            <div class="form-group">
                                <label for="contacto_emergencia_telefono">
                                    <span class="field-icon">📞</span>
                                    Teléfono de Emergencia
                                </label>
                                <input type="tel" id="contacto_emergencia_telefono" name="contacto_emergencia_telefono" class="form-control" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="contacto_emergencia_relacion">
                                <span class="field-icon">❤️</span>
                                Relación con el Paciente
                            </label>
                            <input type="text" id="contacto_emergencia_relacion" name="contacto_emergencia_relacion" class="form-control" placeholder="Ej: Esposo/a, Hijo/a, Padre/Madre..." />
                        </div>
                    </div>
                    
                    <!-- Campos específicos para DOCTOR -->
                    <div id="doctorFields" class="role-specific-fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="especialidad_id">
                                    <span class="field-icon">🩺</span>
                                    Especialidad <span style="color: red;">*</span>
                                </label>
                                <select id="especialidad_id" name="especialidad_id" class="form-control">
                                    <option value="">Seleccione una especialidad</option>
                                    <!-- Se llenará dinámicamente -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="cmp">
                                    <span class="field-icon">📋</span>
                                    CMP (Colegio Médico del Perú)
                                </label>
                                <input type="text" id="cmp" name="cmp" class="form-control" placeholder="Ej: 12345" />
                                <small style="color: #6c757d;">Debe ser único en el sistema</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="biografia">
                                <span class="field-icon">📝</span>
                                Biografía Profesional
                            </label>
                            <textarea id="biografia" name="biografia" class="form-control" rows="4" placeholder="Experiencia, logros, áreas de interés..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Campos específicos para CAJERO -->
                    <div id="cajeroFields" class="role-specific-fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cajero_nombre">
                                    <span class="field-icon">👤</span>
                                    Nombre Completo (Registro Interno)
                                </label>
                                <input type="text" id="cajero_nombre" name="cajero_nombre" class="form-control" />
                                <small style="color: #6c757d;">Nombre para el sistema de caja</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="cajero_usuario">
                                    <span class="field-icon">👨‍💼</span>
                                    Usuario del Sistema
                                </label>
                                <input type="text" id="cajero_usuario" name="cajero_usuario" class="form-control" />
                                <small style="color: #6c757d;">Usuario para acceso al módulo de caja</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cajero_contrasenia">
                                <span class="field-icon">🔐</span>
                                Contraseña del Sistema de Caja
                            </label>
                            <input type="password" id="cajero_contrasenia" name="cajero_contrasenia" class="form-control" minlength="6" />
                            <small style="color: #6c757d;">Contraseña específica para el módulo de caja</small>
                        </div>
                    </div>
                    
                    <!-- Campos específicos para SUPERADMIN -->
                    <div id="superadminFields" class="role-specific-fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="superadmin_nombre">
                                    <span class="field-icon">👤</span>
                                    Nombre Completo (Registro Interno)
                                </label>
                                <input type="text" id="superadmin_nombre" name="superadmin_nombre" class="form-control" />
                                <small style="color: #6c757d;">Nombre para el sistema administrativo</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="superadmin_usuario">
                                    <span class="field-icon">👨‍💼</span>
                                    Usuario del Sistema
                                </label>
                                <input type="text" id="superadmin_usuario" name="superadmin_usuario" class="form-control" />
                                <small style="color: #6c757d;">Usuario para acceso administrativo</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="superadmin_contrasenia">
                                <span class="field-icon">🔐</span>
                                Contraseña Administrativa
                            </label>
                            <input type="password" id="superadmin_contrasenia" name="superadmin_contrasenia" class="form-control" minlength="6" />
                            <small style="color: #6c757d;">Contraseña con privilegios de administrador</small>
                        </div>
                        
                        <div class="alert alert-warning" style="margin-top: 1rem; padding: 1rem; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                            <strong>⚠️ Advertencia:</strong> Este usuario tendrá acceso total al sistema. Asegúrese de usar credenciales seguras.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeUserModal()">Cancelar</button>
                <button type="submit" class="btn primary">
                    <span class="btn-icon">✓</span> Guardar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para ver detalles del usuario -->
<div id="viewModal" class="modal" style="display: none;">
    <div class="modal-content view-modal-content" style="max-width: 800px;">
        <div class="modal-header view-modal-header">
            <div>
                <h3 style="margin: 0;">Detalles del Usuario</h3>
                <p id="viewUserRole" style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;"></p>
            </div>
            <button type="button" class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body view-modal-body" id="viewModalContent">
            <!-- El contenido se cargará dinámicamente -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn primary" onclick="closeViewModal()">Cerrar</button>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
    
    .form-section {
        margin-bottom: 1.5rem;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
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
    
    .badge-doctor {
        background-color: #e3f2fd;
        color: #1976d2;
    }
    
    .badge-paciente {
        background-color: #f3e5f5;
        color: #7b1fa2;
    }
    
    .badge-cajero {
        background-color: #fff3e0;
        color: #f57c00;
    }
    
    .badge-superadmin {
        background-color: #ffebee;
        color: #c62828;
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
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .btn-view {
        background-color: #2196f3;
        color: white;
    }
    
    .btn-view:hover {
        background-color: #1976d2;
    }
    
    .btn-edit {
        background-color: #ff9800;
        color: white;
    }
    
    .btn-edit:hover {
        background-color: #f57c00;
    }
    
    .btn-delete {
        background-color: #f44336;
        color: white;
    }
    
    .btn-delete:hover {
        background-color: #d32f2f;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .detail-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .detail-label {
        font-weight: 600;
        color: #2c3e50;
        min-width: 150px;
    }
    
    .detail-value {
        color: #555;
    }
    
    /* Estilos mejorados para el modal de visualización */
    .view-modal-content {
        background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
    }
    
    .view-modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 1.5rem;
        border-bottom: none;
    }
    
    .view-modal-header h3 {
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .view-modal-header .modal-close {
        color: white;
        opacity: 0.9;
    }
    
    .view-modal-header .modal-close:hover {
        opacity: 1;
        transform: scale(1.1);
    }
    
    .view-modal-body {
        padding: 0;
        max-height: calc(90vh - 180px);
        overflow-y: auto;
    }
    
    .user-section {
        background: white;
        margin: 1.5rem;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-left: 4px solid;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .user-section:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }
    
    .user-section.personal {
        border-left-color: #667eea;
    }
    
    .user-section.contact {
        border-left-color: #28a745;
    }
    
    .user-section.system {
        border-left-color: #ffc107;
    }
    
    .user-section.medical {
        border-left-color: #17a2b8;
    }
    
    .user-section.work {
        border-left-color: #fd7e14;
    }
    
    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .section-icon {
        font-size: 1.5rem;
        margin-right: 0.75rem;
        opacity: 0.8;
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    
    .info-value {
        font-size: 1rem;
        color: #2c3e50;
        font-weight: 500;
        word-break: break-word;
    }
    
    .info-value.empty {
        color: #adb5bd;
        font-style: italic;
    }
    
    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .role-badge-large {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .user-section {
            margin: 1rem;
            padding: 1rem;
        }
    }
    
    /* Estilos para el selector de roles con radio buttons */
    .role-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
        margin-top: 0.5rem;
    }
    
    .role-option {
        position: relative;
        cursor: pointer;
    }
    
    .role-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .role-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem 1rem;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        background: white;
        transition: all 0.3s ease;
        min-height: 120px;
    }
    
    .role-option input[type="radio"]:checked + .role-card {
        border-color: #3498db;
        background: linear-gradient(135deg, #667eea15, #764ba215);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        transform: translateY(-2px);
    }
    
    .role-card:hover {
        border-color: #3498db;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
    }
    
    .role-icon {
        font-size: 3rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .role-name {
        font-weight: 600;
        color: #2c3e50;
        text-align: center;
        font-size: 0.95rem;
    }
    
    .role-option input[type="radio"]:checked + .role-card .role-name {
        color: #3498db;
    }
    
    /* Estilos para secciones de campos específicos */
    .role-specific-fields {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .field-icon {
        margin-right: 0.5rem;
        font-size: 1.1rem;
    }
    
    .section-icon {
        margin-right: 0.5rem;
        font-size: 1.2rem;
    }
    
    .btn-icon {
        margin-right: 0.5rem;
    }
    
    .alert-warning {
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.9;
        }
    }
    
    /* Estilos para SweetAlert2 personalizado */
    .swal-wide {
        width: 600px !important;
    }
    
    .btn-delete-confirm {
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
    }
    
    .btn-cancel-confirm {
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
    }
</style>

<script>
    let especialidades = [];
    
    // Cargar especialidades al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        loadEspecialidades();
        loadUsers();
    });
    
    // Cargar especialidades desde la API
    async function loadEspecialidades() {
        try {
            const response = await fetch('/api/especialidades');
            const data = await response.json();
            
            if (data.success) {
                especialidades = data.data;
                updateEspecialidadesSelect();
            }
        } catch (error) {
            console.error('Error al cargar especialidades:', error);
        }
    }
    
    // Actualizar el select de especialidades
    function updateEspecialidadesSelect() {
        const select = document.getElementById('especialidad_id');
        select.innerHTML = '<option value="">Seleccione una especialidad</option>';
        
        especialidades.forEach(esp => {
            const option = document.createElement('option');
            option.value = esp.id;
            option.textContent = esp.nombre;
            select.appendChild(option);
        });
    }
    
    // Cargar usuarios
    async function loadUsers() {
        const roleFilter = document.getElementById('roleFilter').value;
        const search = document.getElementById('searchInput').value;
        
        showLoading(true);
        
        try {
            const params = new URLSearchParams();
            if (roleFilter) params.append('role', roleFilter);
            if (search) params.append('search', search);
            
            const response = await fetch(`/api/users?${params.toString()}`);
            const data = await response.json();
            
            if (data.success) {
                renderUsersTable(data.data);
            } else {
                showError('Error al cargar usuarios');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
        } finally {
            showLoading(false);
        }
    }
    
    // Renderizar tabla de usuarios
    function renderUsersTable(users) {
        const container = document.getElementById('usersTableContainer');
        
        if (users.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 2rem; color: #999;">No se encontraron usuarios</p>';
            return;
        }
        
        let html = `
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>DNI</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        users.forEach(user => {
            const fullName = `${user.nombre || ''} ${user.apellido || ''}`.trim();
            const roleBadge = getBadgeClass(user.rol);
            
            html += `
                <tr>
                    <td>${user.id}</td>
                    <td>${fullName || '-'}</td>
                    <td>${user.email || '-'}</td>
                    <td><span class="badge ${roleBadge}">${user.rol || '-'}</span></td>
                    <td>${user.dni || '-'}</td>
                    <td>${user.telefono || '-'}</td>
                    <td>${user.direccion || '-'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon btn-view" onclick="viewUser(${user.id})" title="Ver detalles">
                                👁️
                            </button>
                            <a href="/users/${user.id}/edit" class="btn-icon btn-edit" title="Editar" style="text-decoration: none; display: inline-block; padding: 0.5rem; border-radius: 4px;">
                                ✏️
                            </a>
                            <button class="btn-icon btn-delete" onclick="confirmDelete(${user.id}, '${fullName}')" title="Eliminar">
                                🗑️
                            </button>
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
    
    // Obtener clase de badge según el rol
    function getBadgeClass(rol) {
        switch (rol) {
            case 'doctor':
                return 'badge-doctor';
            case 'paciente':
                return 'badge-paciente';
            case 'cajero':
                return 'badge-cajero';
            case 'superadmin':
                return 'badge-superadmin';
            default:
                return '';
        }
    }
    
    // Manejar búsqueda con debounce
    let searchTimeout;
    function handleSearch(event) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadUsers();
        }, 500);
    }
    
    // Abrir modal de creación
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Agregar Usuario';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('passwordHelp').style.display = 'none';
        document.getElementById('password').required = true;
        
        // Ocultar todas las secciones específicas de roles
        document.getElementById('roleSpecificSection').style.display = 'none';
        document.querySelectorAll('.role-specific-fields').forEach(field => {
            field.style.display = 'none';
        });
        
        // Seleccionar por defecto el rol de paciente
        const pacienteRadio = document.querySelector('input[name="rol"][value="paciente"]');
        if (pacienteRadio) {
            pacienteRadio.checked = true;
            handleRoleChange();
        }
        
        document.getElementById('userModal').style.display = 'flex';
    }
    
    // Ver detalles del usuario
    async function viewUser(id) {
        try {
            const response = await fetch(`/api/users/${id}`);
            const data = await response.json();
            
            if (data.success) {
                const user = data.data.user;
                const roleData = data.data.roleData;
                
                // Obtener iniciales para el avatar
                const initials = (user.nombre.charAt(0) + user.apellido.charAt(0)).toUpperCase();
                
                // Actualizar el rol en el header
                document.getElementById('viewUserRole').innerHTML = `
                    <span class="badge ${getBadgeClass(user.rol)}" style="font-size: 0.9rem;">
                        ${user.rol.toUpperCase()}
                    </span>
                `;
                
                let html = `
                    <!-- Sección de Información Personal -->
                    <div class="user-section personal">
                        <div class="section-header">
                            <span class="section-icon">👤</span>
                            <h4 class="section-title">Información Personal</h4>
                        </div>
                        <div style="display: flex; align-items: start; gap: 2rem; margin-bottom: 1.5rem;">
                            <div class="user-avatar">${initials}</div>
                            <div style="flex: 1;">
                                <h3 style="margin: 0; color: #2c3e50; font-size: 1.5rem;">
                                    ${user.nombre} ${user.apellido}
                                </h3>
                                <p style="margin: 0.5rem 0; color: #6c757d;">ID: #${user.id}</p>
                            </div>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Nombre</span>
                                <span class="info-value">${user.nombre || '<span class="empty">No especificado</span>'}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Apellido</span>
                                <span class="info-value">${user.apellido || '<span class="empty">No especificado</span>'}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">DNI</span>
                                <span class="info-value">${user.dni || '<span class="empty">No especificado</span>'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Contacto -->
                    <div class="user-section contact">
                        <div class="section-header">
                            <span class="section-icon">📧</span>
                            <h4 class="section-title">Información de Contacto</h4>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">📧 Email</span>
                                <span class="info-value">${user.email || '<span class="empty">No especificado</span>'}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">📞 Teléfono</span>
                                <span class="info-value">${user.telefono || '<span class="empty">No especificado</span>'}</span>
                            </div>
                            <div class="info-item" style="grid-column: 1 / -1;">
                                <span class="info-label">📍 Dirección</span>
                                <span class="info-value">${user.direccion || '<span class="empty">No especificado</span>'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección del Sistema -->
                    <div class="user-section system">
                        <div class="section-header">
                            <span class="section-icon">⚙️</span>
                            <h4 class="section-title">Información del Sistema</h4>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">ID de Usuario</span>
                                <span class="info-value">#${user.id}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Rol en el Sistema</span>
                                <span class="info-value">
                                    <span class="badge ${getBadgeClass(user.rol)}">${user.rol}</span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Fecha de Registro</span>
                                <span class="info-value">${user.creado_en ? new Date(user.creado_en).toLocaleDateString('es-PE') : '<span class="empty">No disponible</span>'}</span>
                            </div>
                        </div>
                    </div>
                `;
                
                // Información específica según el rol
                if (user.rol === 'doctor' && roleData) {
                    html += `
                        <!-- Sección de Información Médica (Doctor) -->
                        <div class="user-section medical">
                            <div class="section-header">
                                <span class="section-icon">🏥</span>
                                <h4 class="section-title">Información Médica Profesional</h4>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">🩺 Especialidad</span>
                                    <span class="info-value">${roleData.especialidad_nombre || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">📋 CMP (Colegio Médico)</span>
                                    <span class="info-value">${roleData.cmp || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">📅 Fecha de Registro</span>
                                    <span class="info-value">${roleData.creado_en ? new Date(roleData.creado_en).toLocaleDateString('es-PE') : '<span class="empty">No disponible</span>'}</span>
                                </div>
                                ${roleData.biografia ? `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">📝 Biografía Profesional</span>
                                    <span class="info-value">${roleData.biografia}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                } else if (user.rol === 'paciente' && roleData) {
                    html += `
                        <!-- Sección de Información Médica del Paciente -->
                        <div class="user-section medical">
                            <div class="section-header">
                                <span class="section-icon">🏥</span>
                                <h4 class="section-title">Información Médica del Paciente</h4>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">📋 Número de Historia Clínica</span>
                                    <span class="info-value">${roleData.numero_historia_clinica || '<span class="empty">No asignado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">🩸 Tipo de Sangre</span>
                                    <span class="info-value">${roleData.tipo_sangre || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">� Fecha de Registro</span>
                                    <span class="info-value">${roleData.creado_en ? new Date(roleData.creado_en).toLocaleDateString('es-PE') : '<span class="empty">No disponible</span>'}</span>
                                </div>
                                ${roleData.alergias ? `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">⚠️ Alergias</span>
                                    <span class="info-value">${roleData.alergias}</span>
                                </div>
                                ` : ''}
                                ${roleData.condicion_cronica ? `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">🏥 Condiciones Crónicas</span>
                                    <span class="info-value">${roleData.condicion_cronica}</span>
                                </div>
                                ` : ''}
                                ${roleData.historial_cirugias ? `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">🔪 Historial de Cirugías</span>
                                    <span class="info-value">${roleData.historial_cirugias}</span>
                                </div>
                                ` : ''}
                                ${roleData.historico_familiar ? `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">👨‍👩‍👧‍👦 Historial Familiar</span>
                                    <span class="info-value">${roleData.historico_familiar}</span>
                                </div>
                                ` : ''}
                                ${roleData.observaciones ? `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">📝 Observaciones</span>
                                    <span class="info-value">${roleData.observaciones}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        <!-- Sección de Contacto de Emergencia -->
                        ${roleData.contacto_emergencia_nombre || roleData.contacto_emergencia_telefono || roleData.contacto_emergencia_relacion ? `
                        <div class="user-section contact">
                            <div class="section-header">
                                <span class="section-icon">🚨</span>
                                <h4 class="section-title">Contacto de Emergencia</h4>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">👤 Nombre del Contacto</span>
                                    <span class="info-value">${roleData.contacto_emergencia_nombre || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">📞 Teléfono de Emergencia</span>
                                    <span class="info-value">${roleData.contacto_emergencia_telefono || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">❤️ Relación</span>
                                    <span class="info-value">${roleData.contacto_emergencia_relacion || '<span class="empty">No especificado</span>'}</span>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    `;
                } else if (user.rol === 'cajero' && roleData) {
                    html += `
                        <!-- Sección de Información de Trabajo (Cajero) -->
                        <div class="user-section work">
                            <div class="section-header">
                                <span class="section-icon">💼</span>
                                <h4 class="section-title">Información de Trabajo</h4>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">👤 Nombre Registrado</span>
                                    <span class="info-value">${roleData.nombre || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">👨‍💼 Usuario del Sistema</span>
                                    <span class="info-value">${roleData.usuario || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">📅 Fecha de Registro</span>
                                    <span class="info-value">${roleData.creado_en ? new Date(roleData.creado_en).toLocaleDateString('es-PE') : '<span class="empty">No disponible</span>'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (user.rol === 'superadmin' && roleData) {
                    html += `
                        <!-- Sección de Administrador -->
                        <div class="user-section work">
                            <div class="section-header">
                                <span class="section-icon">👑</span>
                                <h4 class="section-title">Información de Administrador</h4>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">👤 Nombre Registrado</span>
                                    <span class="info-value">${roleData.nombre || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">👨‍💼 Usuario del Sistema</span>
                                    <span class="info-value">${roleData.usuario || '<span class="empty">No especificado</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">📅 Fecha de Registro</span>
                                    <span class="info-value">${roleData.creado_en ? new Date(roleData.creado_en).toLocaleDateString('es-PE') : '<span class="empty">No disponible</span>'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">🔐 Nivel de Acceso</span>
                                    <span class="info-value">
                                        <span class="badge badge-superadmin">Acceso Total al Sistema</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                document.getElementById('viewModalContent').innerHTML = html;
                document.getElementById('viewModal').style.display = 'flex';
            } else {
                showError('Error al cargar los detalles del usuario');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
        }
    }
    
    // Editar usuario
    async function editUser(id) {
        try {
            const response = await fetch(`/api/users/${id}`);
            const data = await response.json();
            
            if (data.success) {
                const user = data.data.user;
                const roleData = data.data.roleData;
                
                // Configurar el modal para edición
                document.getElementById('modalTitle').textContent = 'Editar Usuario';
                document.getElementById('userId').value = user.id;
                
                // Llenar campos generales
                document.getElementById('nombre').value = user.nombre || '';
                document.getElementById('apellido').value = user.apellido || '';
                document.getElementById('email').value = user.email || '';
                document.getElementById('dni').value = user.dni || '';
                document.getElementById('telefono').value = user.telefono || '';
                document.getElementById('direccion').value = user.direccion || '';
                document.getElementById('password').value = '';
                document.getElementById('passwordRequired').style.display = 'none';
                document.getElementById('passwordHelp').style.display = 'block';
                document.getElementById('password').required = false;
                
                // Seleccionar el rol correcto en el radio button
                const rolRadio = document.querySelector(`input[name="rol"][value="${user.rol}"]`);
                if (rolRadio) {
                    rolRadio.checked = true;
                    handleRoleChange(); // Mostrar los campos específicos del rol
                }
                
                // Cargar datos específicos según el rol
                if (user.rol === 'paciente' && roleData) {
                    document.getElementById('numero_historia_clinica').value = roleData.numero_historia_clinica || '';
                    document.getElementById('tipo_sangre').value = roleData.tipo_sangre || '';
                    document.getElementById('alergias').value = roleData.alergias || '';
                    document.getElementById('condicion_cronica').value = roleData.condicion_cronica || '';
                    document.getElementById('historial_cirugias').value = roleData.historial_cirugias || '';
                    document.getElementById('historico_familiar').value = roleData.historico_familiar || '';
                    document.getElementById('observaciones').value = roleData.observaciones || '';
                    document.getElementById('contacto_emergencia_nombre').value = roleData.contacto_emergencia_nombre || '';
                    document.getElementById('contacto_emergencia_telefono').value = roleData.contacto_emergencia_telefono || '';
                    document.getElementById('contacto_emergencia_relacion').value = roleData.contacto_emergencia_relacion || '';
                    
                } else if (user.rol === 'doctor' && roleData) {
                    document.getElementById('especialidad_id').value = roleData.especialidad_id || '';
                    document.getElementById('cmp').value = roleData.cmp || '';
                    document.getElementById('biografia').value = roleData.biografia || '';
                    
                } else if (user.rol === 'cajero' && roleData) {
                    document.getElementById('cajero_nombre').value = roleData.nombre || '';
                    document.getElementById('cajero_usuario').value = roleData.usuario || '';
                    // No cargamos la contraseña por seguridad
                    document.getElementById('cajero_contrasenia').value = '';
                    
                } else if (user.rol === 'superadmin' && roleData) {
                    document.getElementById('superadmin_nombre').value = roleData.nombre || '';
                    document.getElementById('superadmin_usuario').value = roleData.usuario || '';
                    // No cargamos la contraseña por seguridad
                    document.getElementById('superadmin_contrasenia').value = '';
                }
                
                document.getElementById('userModal').style.display = 'flex';
            } else {
                showError('Error al cargar los datos del usuario');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
        }
    }
    
    // Manejar cambio de rol
    function handleRoleChange() {
        // Obtener el rol seleccionado del radio button
        const rolSelected = document.querySelector('input[name="rol"]:checked');
        if (!rolSelected) return;
        
        const rol = rolSelected.value;
        
        // Ocultar todas las secciones específicas de roles
        const allRoleFields = document.querySelectorAll('.role-specific-fields');
        allRoleFields.forEach(field => field.style.display = 'none');
        
        // Obtener los elementos
        const roleSpecificSection = document.getElementById('roleSpecificSection');
        const roleSpecificTitle = document.getElementById('roleSpecificTitle');
        const roleSpecificIcon = document.getElementById('roleSpecificIcon');
        const roleSpecificText = document.getElementById('roleSpecificText');
        
        // Mostrar la sección específica del rol seleccionado
        if (rol === 'paciente') {
            roleSpecificSection.style.display = 'block';
            roleSpecificIcon.textContent = '🏥';
            roleSpecificText.textContent = 'Información Médica del Paciente';
            document.getElementById('pacienteFields').style.display = 'block';
            
        } else if (rol === 'doctor') {
            roleSpecificSection.style.display = 'block';
            roleSpecificIcon.textContent = '👨‍⚕️';
            roleSpecificText.textContent = 'Información Profesional del Doctor';
            document.getElementById('doctorFields').style.display = 'block';
            document.getElementById('especialidad_id').required = true;
            
        } else if (rol === 'cajero') {
            roleSpecificSection.style.display = 'block';
            roleSpecificIcon.textContent = '💼';
            roleSpecificText.textContent = 'Información del Cajero';
            document.getElementById('cajeroFields').style.display = 'block';
            
        } else if (rol === 'superadmin') {
            roleSpecificSection.style.display = 'block';
            roleSpecificIcon.textContent = '👑';
            roleSpecificText.textContent = 'Información del Superadministrador';
            document.getElementById('superadminFields').style.display = 'block';
        }
    }
    
    // Variable para evitar doble envío
    let isSubmittingUser = false;
    
    // Guardar usuario
    async function saveUser(event) {
        event.preventDefault();
        
        // Prevenir doble envío
        if (isSubmittingUser) {
            console.log('Envío ya en progreso, ignorando...');
            return;
        }
        
        isSubmittingUser = true;
        
        // Deshabilitar el botón de submit
        const submitButton = event.target.querySelector('button[type="submit"]');
        const originalButtonText = submitButton ? submitButton.innerHTML : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        }
        
        const formData = new FormData(event.target);
        const userId = document.getElementById('userId').value;
        const isEdit = userId !== '';
        
        // Obtener el rol del radio button
        const rolSelected = document.querySelector('input[name="rol"]:checked');
        const rol = rolSelected ? rolSelected.value : '';
        
        const data = {
            nombre: formData.get('nombre'),
            apellido: formData.get('apellido'),
            email: formData.get('email'),
            password: formData.get('password'),
            dni: formData.get('dni'),
            telefono: formData.get('telefono'),
            direccion: formData.get('direccion'),
            rol: rol
        };
        
        // Agregar campos específicos del rol
        if (rol === 'paciente') {
            data.numero_historia_clinica = formData.get('numero_historia_clinica');
            data.tipo_sangre = formData.get('tipo_sangre');
            data.alergias = formData.get('alergias');
            data.condicion_cronica = formData.get('condicion_cronica');
            data.historial_cirugias = formData.get('historial_cirugias');
            data.historico_familiar = formData.get('historico_familiar');
            data.observaciones = formData.get('observaciones');
            data.contacto_emergencia_nombre = formData.get('contacto_emergencia_nombre');
            data.contacto_emergencia_telefono = formData.get('contacto_emergencia_telefono');
            data.contacto_emergencia_relacion = formData.get('contacto_emergencia_relacion');
            
        } else if (rol === 'doctor') {
            data.especialidad_id = formData.get('especialidad_id');
            data.cmp = formData.get('cmp');
            data.biografia = formData.get('biografia');
            
        } else if (rol === 'cajero') {
            data.cajero_nombre = formData.get('cajero_nombre');
            data.cajero_usuario = formData.get('cajero_usuario');
            data.cajero_contrasenia = formData.get('cajero_contrasenia');
            
        } else if (rol === 'superadmin') {
            data.superadmin_nombre = formData.get('superadmin_nombre');
            data.superadmin_usuario = formData.get('superadmin_usuario');
            data.superadmin_contrasenia = formData.get('superadmin_contrasenia');
        }
        
        // Debug: mostrar los datos que se van a enviar
        console.log('Datos a enviar:', data);
        
        try {
            const url = isEdit ? `/api/users/${userId}` : '/api/users';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message,
                    confirmButtonColor: '#28a745'
                });
                closeUserModal();
                loadUsers();
            } else {
                showError(result.message);
                // Rehabilitar el botón en caso de error
                isSubmittingUser = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
            // Rehabilitar el botón en caso de error
            isSubmittingUser = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        } finally {
            // Asegurar que se resetee la bandera después de un tiempo
            setTimeout(() => {
                isSubmittingUser = false;
            }, 1000);
        }
    }
    
    // Confirmar eliminación
    async function confirmDelete(id, name) {
        // Primero verificar si tiene relaciones
        try {
            const response = await fetch(`/api/users/${id}/relationships`);
            const data = await response.json();
            
            if (data.success) {
                const rel = data.data;
                let warnings = [];
                
                if (rel.citas > 0) {
                    warnings.push(`<li><strong>${rel.citas}</strong> cita(s) médica(s)</li>`);
                }
                if (rel.pagos > 0) {
                    warnings.push(`<li><strong>${rel.pagos}</strong> pago(s) registrado(s)</li>`);
                }
                if (rel.horarios > 0) {
                    warnings.push(`<li><strong>${rel.horarios}</strong> horario(s) programado(s)</li>`);
                }
                
                if (warnings.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: '❌ No se puede eliminar',
                        html: `
                            <div style="text-align: left;">
                                <p>El usuario <strong>${name}</strong> tiene los siguientes registros asociados:</p>
                                <ul style="margin-top: 1rem; padding-left: 2rem;">
                                    ${warnings.join('')}
                                </ul>
                                <p style="margin-top: 1.5rem; color: #e74c3c; font-weight: 600;">
                                    ⚠️ Debe eliminar primero estos registros antes de eliminar al usuario.
                                </p>
                            </div>
                        `,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'swal-wide'
                        }
                    });
                    return;
                }
            }
        } catch (error) {
            console.error('Error al verificar relaciones:', error);
            showError('Error al verificar las relaciones del usuario');
            return;
        }
        
        // Si no tiene relaciones, confirmar eliminación
        Swal.fire({
            icon: 'warning',
            title: '⚠️ ¿Está seguro?',
            html: `
                <div style="text-align: center;">
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                        Se eliminará permanentemente al usuario:
                    </p>
                    <p style="font-size: 1.3rem; font-weight: 700; color: #e74c3c; margin-bottom: 1rem;">
                        ${name}
                    </p>
                    <p style="color: #666; font-size: 0.95rem;">
                        Esta acción <strong>NO</strong> se puede deshacer.
                    </p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '🗑️ Sí, eliminar',
            cancelButtonText: '❌ Cancelar',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn-delete-confirm',
                cancelButton: 'btn-cancel-confirm'
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                await deleteUser(id, name);
            }
        });
    }
    
    // Eliminar usuario
    async function deleteUser(id, name) {
        try {
            const response = await fetch(`/api/users/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ _method: 'DELETE' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '✅ ¡Eliminado con éxito!',
                    html: `
                        <p>El usuario <strong>${name}</strong> ha sido eliminado correctamente.</p>
                    `,
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    timerProgressBar: true
                });
                loadUsers();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '❌ Error al eliminar',
                    text: data.message,
                    confirmButtonColor: '#d33'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: '❌ Error de conexión',
                text: 'No se pudo conectar con el servidor. Por favor, intente nuevamente.',
                confirmButtonColor: '#d33'
            });
        }
    }
    
    // Cerrar modal de usuario
    function closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
        document.getElementById('userForm').reset();
    }
    
    // Cerrar modal de ver detalles
    function closeViewModal() {
        document.getElementById('viewModal').style.display = 'none';
    }
    
    // Mostrar/ocultar indicador de carga
    function showLoading(show) {
        document.getElementById('loadingIndicator').style.display = show ? 'block' : 'none';
        document.getElementById('usersTableContainer').style.display = show ? 'none' : 'block';
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
    
    // Cerrar modales al hacer clic fuera de ellos
    window.onclick = function(event) {
        const userModal = document.getElementById('userModal');
        const viewModal = document.getElementById('viewModal');
        
        if (event.target === userModal) {
            closeUserModal();
        }
        if (event.target === viewModal) {
            closeViewModal();
        }
    }
</script>
