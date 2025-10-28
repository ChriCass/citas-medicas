<style>
    .card {
        background: var(--card, #ffffff);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border, #e5e7eb);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }
    .form-container {
        padding: 20px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group label {
        font-size: 14px;
        font-weight: 600;
        color: var(--fg, #1f2937);
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 10px 12px;
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary, #2563eb);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    .form-group .required {
        color: #dc2626;
    }
    .form-group small {
        color: var(--muted, #6b7280);
        font-size: 12px;
    }
    .form-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        padding-top: 16px;
        border-top: 1px solid var(--border, #e5e7eb);
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-primary {
        background: var(--primary, #2563eb);
        color: white;
    }
    .btn-primary:hover {
        background: #1d4ed8;
    }
    .btn-secondary {
        background: var(--muted, #6b7280);
        color: white;
    }
    .btn-secondary:hover {
        background: #4b5563;
    }
    .btn-danger {
        background: #dc2626;
        color: white;
    }
    .btn-danger:hover {
        background: #b91c1c;
    }
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 14px;
    }
    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .alert-error {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    .checkbox-group,
    .radio-group {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 8px;
    }
    .checkbox-item,
    .radio-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .checkbox-item input[type="checkbox"],
    .radio-item input[type="radio"] {
        margin: 0;
        width: auto;
    }
    .role-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        background: #e5e7eb;
        color: #374151;
    }
    .role-superadmin { background: #fef3c7; color: #92400e; }
    .role-doctor { background: #dbeafe; color: #1e40af; }
    .role-paciente { background: #dcfce7; color: #166534; }
    .role-cajero { background: #fce7f3; color: #be185d; }
    
    /* Campos específicos de roles */
    .role-specific-fields {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .role-specific-fields h3 {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 18px;
        font-weight: 600;
    }
    
    .role-specific-fields h4 {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
        font-weight: 500;
    }
    
    /* Estilos para modo edición */
    .edit-mode .title::before {
        content: "✏️ ";
    }
    .delete-mode .title::before {
        content: "🗑️ ";
    }
    .delete-mode .form-container {
        background: #fef2f2;
    }
</style>

<div class="card" id="userForm">
    <div class="header">
        <h1 class="title" id="formTitle">Crear Nuevo Usuario</h1>
        <a href="/users" class="btn btn-secondary">← Volver a la Lista</a>
    </div>

    <div class="form-container">
        <!-- Alertas -->
        <div id="alerts"></div>

        <form id="userFormElement" method="POST">
            <input type="hidden" id="userId" name="userId" value="">
            <input type="hidden" name="_method" id="httpMethod" value="POST">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

            <div class="form-grid">
                <!-- Información Personal -->
                <div class="form-group">
                    <label for="nombre">Nombre <span class="required">*</span></label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido <span class="required">*</span></label>
                    <input type="text" id="apellido" name="apellido" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required maxlength="150">
                    <small>Debe ser un email válido y único</small>
                </div>

                <div class="form-group" id="passwordGroup">
                    <label for="password">Contraseña <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Mínimo 6 caracteres. Déjalo vacío en edición para mantener la actual</small>
                </div>

                <!-- Información de Contacto -->
                <div class="form-group">
                    <label for="dni">DNI/Documento <span class="required">*</span></label>
                    <input type="text" id="dni" name="dni" required maxlength="8" minlength="8" pattern="\d{8}" placeholder="12345678">
                    <small>Exactamente 8 dígitos</small>
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono <span class="required">*</span></label>
                    <input type="tel" id="telefono" name="telefono" required maxlength="9" minlength="9" pattern="\d{9}" placeholder="987654321">
                    <small>Exactamente 9 dígitos</small>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="direccion">Dirección <span class="required">*</span></label>
                    <textarea id="direccion" name="direccion" rows="2" required maxlength="255"></textarea>
                </div>

                <!-- Rol (solo uno) -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Rol del Usuario <span class="required">*</span></label>
                    <small>Selecciona el rol principal para este usuario</small>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="role_paciente" name="role" value="paciente" required onchange="toggleRoleFields(this.value)">
                            <label for="role_paciente">
                                <span class="role-badge role-paciente">Paciente</span>
                            </label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="role_doctor" name="role" value="doctor" required onchange="toggleRoleFields(this.value)">
                            <label for="role_doctor">
                                <span class="role-badge role-doctor">Doctor</span>
                            </label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="role_cajero" name="role" value="cajero" required onchange="toggleRoleFields(this.value)">
                            <label for="role_cajero">
                                <span class="role-badge role-cajero">Cajero</span>
                            </label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="role_superadmin" name="role" value="superadmin" required onchange="toggleRoleFields(this.value)">
                            <label for="role_superadmin">
                                <span class="role-badge role-superadmin">SuperAdmin</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Campos específicos por rol -->
                
                <!-- Campos para Doctor -->
                <div id="doctorFields" class="role-specific-fields" style="display: none; grid-column: 1 / -1;">
                    <h3 style="margin: 20px 0 15px 0; color: #1e40af; border-bottom: 2px solid #dbeafe; padding-bottom: 8px;">
                        👨‍⚕️ Información del Doctor
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="especialidad_id">Especialidad <span class="required">*</span></label>
                            <select name="especialidad_id" id="especialidad_id">
                                <option value="">Seleccionar especialidad...</option>
                                <!-- Se cargarán dinámicamente -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cmp">CMP (Colegiatura) <span class="required">*</span></label>
                            <input type="text" name="cmp" id="cmp" placeholder="CMP-12345" maxlength="9" minlength="9" pattern="CMP-\d{5}">
                            <small>Formato: CMP-##### (exactamente 5 dígitos)</small>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="biografia">Biografía Profesional</label>
                            <textarea name="biografia" id="biografia" rows="4" placeholder="Especialidades, experiencia, certificaciones, formación académica..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Campos para Paciente -->
                <div id="pacienteFields" class="role-specific-fields" style="display: none; grid-column: 1 / -1;">
                    <h3 style="margin: 20px 0 15px 0; color: #166534; border-bottom: 2px solid #dcfce7; padding-bottom: 8px;">
                        🏥 Información Médica del Paciente
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="tipo_sangre">Tipo de Sangre</label>
                            <select name="tipo_sangre" id="tipo_sangre">
                                <option value="">Seleccionar...</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="alergias">Alergias</label>
                            <input type="text" name="alergias" id="alergias" placeholder="Ejemplo: Penicilina, mariscos, polen..." maxlength="255">
                        </div>
                        <div class="form-group">
                            <label for="condicion_cronica">Condición Crónica</label>
                            <input type="text" name="condicion_cronica" id="condicion_cronica" placeholder="Ejemplo: Diabetes, hipertensión..." maxlength="255">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="historial_cirugias">Historial de Cirugías</label>
                            <textarea name="historial_cirugias" id="historial_cirugias" rows="3" placeholder="Detalle de cirugías previas, fechas, resultados..."></textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="historico_familiar">Historial Familiar</label>
                            <textarea name="historico_familiar" id="historico_familiar" rows="3" placeholder="Enfermedades familiares relevantes (diabetes, cáncer, cardiopatías, etc.)..."></textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="observaciones">Observaciones Médicas</label>
                            <textarea name="observaciones" id="observaciones" rows="3" placeholder="Observaciones adicionales, notas médicas importantes..."></textarea>
                        </div>
                    </div>

                    <h4 style="margin: 20px 0 15px 0; color: #dc2626;">🚨 Contacto de Emergencia</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="contacto_emergencia_nombre">Nombre del Contacto</label>
                            <input type="text" name="contacto_emergencia_nombre" id="contacto_emergencia_nombre" placeholder="Nombre completo" maxlength="150">
                        </div>
                        <div class="form-group">
                            <label for="contacto_emergencia_telefono">Teléfono de Emergencia</label>
                            <input type="text" name="contacto_emergencia_telefono" id="contacto_emergencia_telefono" placeholder="Número de emergencia" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="contacto_emergencia_relacion">Relación</label>
                            <input type="text" name="contacto_emergencia_relacion" id="contacto_emergencia_relacion" placeholder="Ejemplo: Esposo/a, hijo/a, hermano/a..." maxlength="100">
                        </div>
                    </div>
                </div>

                <!-- Campos para Cajero -->
                <div id="cajeroFields" class="role-specific-fields" style="display: none; grid-column: 1 / -1;">
                    <h3 style="margin: 20px 0 15px 0; color: #be185d; border-bottom: 2px solid #fce7f3; padding-bottom: 8px;">
                        💰 Información del Cajero
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre_cajero">Nombre de Usuario <span class="required">*</span></label>
                            <input type="text" name="nombre_cajero" id="nombre_cajero" placeholder="Usuario para el sistema de caja" maxlength="100">
                            <small>Este será el usuario para acceder al sistema de caja</small>
                        </div>
                        <div class="form-group">
                            <label for="contrasenia_cajero">Contraseña del Sistema <span class="required">*</span></label>
                            <input type="password" name="contrasenia_cajero" id="contrasenia_cajero" placeholder="Contraseña específica para caja" minlength="6">
                            <small>Contraseña específica para el módulo de caja</small>
                        </div>
                    </div>
                </div>

                <!-- Campos para Super Admin -->
                <div id="superadminFields" class="role-specific-fields" style="display: none; grid-column: 1 / -1;">
                    <h3 style="margin: 20px 0 15px 0; color: #92400e; border-bottom: 2px solid #fef3c7; padding-bottom: 8px;">
                        👑 Información del Super Administrador
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre_admin">Nombre de Usuario <span class="required">*</span></label>
                            <input type="text" name="nombre_admin" id="nombre_admin" placeholder="Usuario administrativo" maxlength="100">
                            <small>Usuario para acceso administrativo completo</small>
                        </div>
                        <div class="form-group">
                            <label for="contrasenia_admin">Contraseña del Sistema <span class="required">*</span></label>
                            <input type="password" name="contrasenia_admin" id="contrasenia_admin" placeholder="Contraseña administrativa" minlength="6">
                            <small>Contraseña para funciones administrativas</small>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    ➕ Crear Usuario
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    🔄 Limpiar
                </button>
                <button type="button" class="btn btn-danger" id="deleteBtn" style="display: none;" onclick="confirmDelete()">
                    🗑️ Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentMode = 'create'; // 'create', 'edit', 'delete'
let currentUserId = null;

// Función para mostrar/ocultar campos específicos según el rol
function toggleRoleFields(selectedRole) {
    console.log('Rol seleccionado:', selectedRole);
    
    // Ocultar todos los campos específicos
    const roleFields = document.querySelectorAll('.role-specific-fields');
    roleFields.forEach(field => {
        field.style.display = 'none';
    });
    
    // Remover required de todos los campos específicos
    removeRequiredFromRoleFields();
    
    // Mostrar campos del rol seleccionado
    const selectedFields = document.getElementById(selectedRole + 'Fields');
    if (selectedFields) {
        selectedFields.style.display = 'block';
        
        // Agregar required a campos obligatorios del rol seleccionado
        addRequiredToRoleFields(selectedRole);
    }
}

// Remover atributo required de campos específicos de roles
function removeRequiredFromRoleFields() {
    const roleSpecificInputs = document.querySelectorAll('.role-specific-fields input, .role-specific-fields select');
    roleSpecificInputs.forEach(input => {
        input.removeAttribute('required');
    });
}

// Agregar required a campos obligatorios del rol seleccionado
function addRequiredToRoleFields(role) {
    switch(role) {
        case 'doctor':
            ['especialidad_id', 'cmp'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) field.setAttribute('required', 'required');
            });
            break;
        case 'cajero':
            ['nombre_cajero', 'contrasenia_cajero'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) field.setAttribute('required', 'required');
            });
            break;
        case 'superadmin':
            ['nombre_admin', 'contrasenia_admin'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) field.setAttribute('required', 'required');
            });
            break;
        // Paciente no tiene campos obligatorios específicos
    }
}

// Cargar especialidades médicas
async function loadEspecialidades() {
    try {
        const response = await fetch('/api/especialidades');
        if (!response.ok) {
            throw new Error('No se pudieron cargar las especialidades');
        }
        
        const data = await response.json();
        const select = document.getElementById('especialidad_id');
        
        // Limpiar opciones existentes (excepto la primera)
        select.innerHTML = '<option value="">Seleccionar especialidad...</option>';
        
        // Agregar opciones
        data.especialidades.forEach(especialidad => {
            const option = document.createElement('option');
            option.value = especialidad.id;
            option.textContent = especialidad.nombre;
            select.appendChild(option);
        });
        
    } catch (error) {
        console.error('Error al cargar especialidades:', error);
        showAlert('Error al cargar especialidades: ' + error.message, 'error');
    }
}

// Inicializar formulario
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const mode = urlParams.get('mode') || 'create';
    const userId = urlParams.get('id');
    
    // Cargar especialidades al inicializar
    loadEspecialidades();
    
    if (mode && userId) {
        initializeForm(mode, userId);
    } else {
        initializeForm('create');
    }
    
    // Event listener para el formulario
    document.getElementById('userFormElement').addEventListener('submit', handleSubmit);
    
    // Agregar validaciones en tiempo real
    addRealTimeValidations();
});

function initializeForm(mode, userId = null) {
    currentMode = mode;
    currentUserId = userId;
    
    const formCard = document.getElementById('userForm');
    const formTitle = document.getElementById('formTitle');
    const submitBtn = document.getElementById('submitBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const passwordGroup = document.getElementById('passwordGroup');
    const httpMethod = document.getElementById('httpMethod');
    
    // Limpiar clases previas
    formCard.className = 'card';
    
    switch (mode) {
        case 'edit':
            formCard.classList.add('edit-mode');
            formTitle.textContent = 'Editar Usuario';
            submitBtn.innerHTML = '💾 Actualizar Usuario';
            deleteBtn.style.display = 'inline-flex';
            httpMethod.value = 'PUT';
            
            // En edición, hacer contraseña opcional
            const passwordInput = document.getElementById('password');
            passwordInput.required = false;
            passwordGroup.querySelector('small').textContent = 'Déjalo vacío para mantener la contraseña actual';
            
            loadUserData(userId);
            break;
            
        case 'delete':
            formCard.classList.add('delete-mode');
            formTitle.textContent = 'Eliminar Usuario';
            submitBtn.innerHTML = '🗑️ Confirmar Eliminación';
            submitBtn.className = 'btn btn-danger';
            deleteBtn.style.display = 'none';
            httpMethod.value = 'DELETE';
            
            // Deshabilitar todos los campos
            disableAllFields();
            loadUserData(userId);
            break;
            
        default: // create
            formTitle.textContent = 'Crear Nuevo Usuario';
            submitBtn.innerHTML = '➕ Crear Usuario';
            deleteBtn.style.display = 'none';
            httpMethod.value = 'POST';
            break;
    }
}

async function loadUserData(userId) {
    try {
        console.log(`Cargando datos del usuario ${userId}`);
        const response = await fetch(`/api/users/${userId}`);
        if (!response.ok) {
            throw new Error('Usuario no encontrado');
        }
        
        const data = await response.json();
        const user = data.user;
        
        console.log('Datos del usuario cargados:', user);
        
        // Llenar campos básicos
        document.getElementById('userId').value = user.id;
        document.getElementById('nombre').value = user.nombre || '';
        document.getElementById('apellido').value = user.apellido || '';
        document.getElementById('email').value = user.email || '';
        document.getElementById('dni').value = user.dni || '';
        document.getElementById('telefono').value = user.telefono || '';
        document.getElementById('direccion').value = user.direccion || '';
        
        // Verificar que los valores se asignaron correctamente
        console.log('Valores asignados:');
        console.log('- Nombre:', document.getElementById('nombre').value);
        console.log('- Email:', document.getElementById('email').value);
        
        // Marcar rol (solo uno)
        const userRoles = user.roles ? user.roles.split(', ') : [];
        console.log('Roles del usuario:', userRoles);
        
        // Seleccionar el primer rol como rol principal
        const primaryRole = userRoles[0] || '';
        
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.checked = radio.value === primaryRole;
            console.log(`Rol ${radio.value}: ${radio.checked}`);
        });

        // Mostrar campos específicos del rol
        if (primaryRole) {
            toggleRoleFields(primaryRole);
            
            // Cargar datos específicos del rol
            if (user.role_data) {
                console.log('Cargando datos específicos del rol:', user.role_data);
                loadRoleSpecificData(primaryRole, user.role_data);
            }
        }
        
    } catch (error) {
        console.error('Error al cargar datos:', error);
        showAlert('Error al cargar los datos del usuario: ' + error.message, 'error');
    }
}

// Función para cargar datos específicos según el rol
function loadRoleSpecificData(role, roleData) {
    try {
        switch(role) {
            case 'doctor':
                if (roleData.especialidad_id) {
                    document.getElementById('especialidad_id').value = roleData.especialidad_id;
                }
                if (roleData.cmp) {
                    document.getElementById('cmp').value = roleData.cmp;
                }
                if (roleData.biografia) {
                    document.getElementById('biografia').value = roleData.biografia;
                }
                break;

            case 'paciente':
                const pacienteFields = [
                    'tipo_sangre', 'alergias', 'condicion_cronica', 'historial_cirugias',
                    'historico_familiar', 'observaciones', 'contacto_emergencia_nombre',
                    'contacto_emergencia_telefono', 'contacto_emergencia_relacion'
                ];
                
                pacienteFields.forEach(field => {
                    if (roleData[field] && document.getElementById(field)) {
                        document.getElementById(field).value = roleData[field];
                    }
                });
                break;

            case 'cajero':
                if (roleData.nombre_cajero && document.getElementById('nombre_cajero')) {
                    document.getElementById('nombre_cajero').value = roleData.nombre_cajero;
                }
                // No cargamos la contraseña por seguridad
                break;

            case 'superadmin':
                if (roleData.nombre_admin && document.getElementById('nombre_admin')) {
                    document.getElementById('nombre_admin').value = roleData.nombre_admin;
                }
                // No cargamos la contraseña por seguridad
                break;
        }
        
        console.log(`Datos específicos del rol ${role} cargados correctamente`);
        
    } catch (error) {
        console.error(`Error al cargar datos específicos del rol ${role}:`, error);
    }
}

function disableAllFields() {
    const inputs = document.querySelectorAll('#userFormElement input, #userFormElement select, #userFormElement textarea');
    inputs.forEach(input => {
        if (input.type !== 'hidden' && input.type !== 'submit') {
            input.disabled = true;
        }
    });
}

async function handleSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = document.getElementById('submitBtn');
    
    // Debug: Mostrar datos que se van a enviar
    console.log('=== DEBUG: Datos del formulario ===');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Validar rol (ahora es un solo campo)
    const role = formData.get('role');
    if (!role && currentMode !== 'delete') {
        showAlert('Debes seleccionar un rol para el usuario', 'error');
        return;
    }

    // Validaciones de formato específicas
    if (currentMode !== 'delete') {
        const formatValidation = validateFormats(formData);
        if (formatValidation !== true) {
            showAlert(formatValidation, 'error');
            return;
        }

        // Validaciones específicas del rol
        const roleValidation = validateRoleSpecific(formData, role);
        if (roleValidation !== true) {
            showAlert(roleValidation, 'error');
            return;
        }
    }
    
    // Verificar campos obligatorios manualmente
    const requiredFields = {
        'nombre': 'Nombre',
        'apellido': 'Apellido', 
        'email': 'Email',
        'dni': 'DNI/Documento',
        'telefono': 'Teléfono',
        'direccion': 'Dirección'
    };
    
    // En modo creación, contraseña también es obligatoria
    if (currentMode === 'create') {
        requiredFields['password'] = 'Contraseña';
    }

    // Agregar campos obligatorios específicos del rol
    if (role && currentMode !== 'delete') {
        switch(role) {
            case 'doctor':
                requiredFields['especialidad_id'] = 'Especialidad';
                requiredFields['cmp'] = 'CMP';
                break;
            case 'cajero':
                requiredFields['nombre_cajero'] = 'Nombre de usuario (cajero)';
                // Solo requerir contraseña en modo creación
                if (currentMode === 'create') {
                    requiredFields['contrasenia_cajero'] = 'Contraseña del sistema (cajero)';
                }
                break;
            case 'superadmin':
                requiredFields['nombre_admin'] = 'Nombre de usuario (admin)';
                // Solo requerir contraseña en modo creación
                if (currentMode === 'create') {
                    requiredFields['contrasenia_admin'] = 'Contraseña del sistema (admin)';
                }
                break;
            // Paciente no tiene campos específicos obligatorios
        }
    }
    
    const missingFields = [];
    for (const [field, label] of Object.entries(requiredFields)) {
        const value = formData.get(field);
        if (!value || value.trim() === '') {
            missingFields.push(label);
        }
    }
    
    if (missingFields.length > 0 && currentMode !== 'delete') {
        showAlert(`Los siguientes campos son obligatorios: ${missingFields.join(', ')}`, 'error');
        return;
    }
    
    // Deshabilitar botón durante envío
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '⏳ Procesando...';
    
    try {
        let url = '/api/users';
        let method = 'POST';
        
        if (currentMode === 'edit') {
            url += `/${currentUserId}`;
            method = 'PUT';
            // Agregar campo para indicar que es una actualización
            formData.append('_method', 'PUT');
        } else if (currentMode === 'delete') {
            url += `/${currentUserId}`;
            method = 'DELETE';
        }
        
        // Para PUT y DELETE, necesitamos usar POST con un campo _method
        const requestOptions = {
            method: (method === 'PUT' || method === 'DELETE') ? 'POST' : method,
            body: formData
        };
        
        // Para DELETE, enviar el método en el body
        if (currentMode === 'delete') {
            formData.append('_method', 'DELETE');
        }
        
        console.log(`Enviando ${requestOptions.method} a ${url}`);
        
        const response = await fetch(url, requestOptions);
        
        const result = await response.json();
        
        if (response.ok) {
            let message = '';
            switch (currentMode) {
                case 'create':
                    message = 'Usuario creado correctamente';
                    break;
                case 'edit':
                    message = 'Usuario actualizado correctamente';
                    break;
                case 'delete':
                    message = 'Usuario eliminado correctamente';
                    break;
            }
            
            showAlert(message, 'success');
            
            // Redirigir después de 2 segundos
            setTimeout(() => {
                window.location.href = '/users';
            }, 2000);
            
        } else {
            throw new Error(result.error || 'Error al procesar la solicitud');
        }
        
    } catch (error) {
        console.error('Error completo:', error);
        showAlert('Error: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

function resetForm() {
    if (currentMode === 'create') {
        document.getElementById('userFormElement').reset();
    } else {
        // En modo edición, recargar datos originales
        if (currentUserId) {
            loadUserData(currentUserId);
        }
    }
}

function confirmDelete() {
    if (confirm('¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.')) {
        // Cambiar a modo eliminación
        const url = new URL(window.location);
        url.searchParams.set('mode', 'delete');
        window.location.href = url.toString();
    }
}

function showAlert(message, type) {
    const alertsContainer = document.getElementById('alerts');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    alertsContainer.innerHTML = '';
    alertsContainer.appendChild(alert);
    
    // Auto-ocultar después de 5 segundos para mensajes de éxito
    if (type === 'success') {
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    // Scroll hacia arriba para mostrar la alerta
    alertsContainer.scrollIntoView({ behavior: 'smooth' });
}

// Validaciones de formato en JavaScript
function validateFormats(formData) {
    const dni = formData.get('dni')?.trim();
    const telefono = formData.get('telefono')?.trim();
    const email = formData.get('email')?.trim();

    // Validar DNI: exactamente 8 dígitos
    if (dni && !/^\d{8}$/.test(dni)) {
        return 'El DNI debe tener exactamente 8 dígitos numéricos';
    }

    // Validar teléfono: exactamente 9 dígitos
    if (telefono && !/^\d{9}$/.test(telefono)) {
        return 'El teléfono debe tener exactamente 9 dígitos numéricos';
    }

    // Validar email con regex más estricta
    if (email && !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
        return 'El formato del email no es válido';
    }

    return true;
}

// Validaciones específicas del rol
function validateRoleSpecific(formData, role) {
    switch(role) {
        case 'doctor':
            const cmp = formData.get('cmp')?.trim();
            if (cmp && !/^CMP-\d{5}$/.test(cmp)) {
                return 'El CMP debe tener el formato CMP-##### con exactamente 5 dígitos (ejemplo: CMP-12345)';
            }
            break;
        
        case 'cajero':
            const nombreCajero = formData.get('nombre_cajero')?.trim();
            if (currentMode === 'create' && (!nombreCajero || nombreCajero.length < 3)) {
                return 'El nombre de usuario del cajero debe tener al menos 3 caracteres';
            }
            break;
            
        case 'superadmin':
            const nombreAdmin = formData.get('nombre_admin')?.trim();
            if (currentMode === 'create' && (!nombreAdmin || nombreAdmin.length < 3)) {
                return 'El nombre de usuario del administrador debe tener al menos 3 caracteres';
            }
            break;
    }
    
    return true;
}

// Agregar validaciones en tiempo real
function addRealTimeValidations() {
    // Validación DNI - solo números, máximo 8
    document.getElementById('dni').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remover no-dígitos
        if (value.length > 8) value = value.slice(0, 8);
        e.target.value = value;
        
        // Cambiar color del borde según validez
        if (value.length === 8) {
            e.target.style.borderColor = '#10b981'; // Verde
        } else if (value.length > 0) {
            e.target.style.borderColor = '#f59e0b'; // Amarillo
        } else {
            e.target.style.borderColor = '#d1d5db'; // Gris por defecto
        }
    });

    // Validación teléfono - solo números, máximo 9
    document.getElementById('telefono').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remover no-dígitos
        if (value.length > 9) value = value.slice(0, 9);
        e.target.value = value;
        
        // Cambiar color del borde según validez
        if (value.length === 9) {
            e.target.style.borderColor = '#10b981'; // Verde
        } else if (value.length > 0) {
            e.target.style.borderColor = '#f59e0b'; // Amarillo
        } else {
            e.target.style.borderColor = '#d1d5db'; // Gris por defecto
        }
    });

    // Validación CMP en tiempo real
    document.getElementById('cmp').addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase();
        
        // Si no empieza con CMP-, agregar automáticamente
        if (value && !value.startsWith('CMP-')) {
            // Si es solo números, agregar CMP-
            if (/^\d+$/.test(value)) {
                value = 'CMP-' + value;
            }
        }
        
        // Limitar a CMP- + 5 dígitos
        if (value.startsWith('CMP-')) {
            const numbers = value.substring(4).replace(/\D/g, '');
            value = 'CMP-' + numbers.slice(0, 5);
        }
        
        e.target.value = value;
        
        // Cambiar color del borde según validez
        if (/^CMP-\d{5}$/.test(value)) {
            e.target.style.borderColor = '#10b981'; // Verde
        } else if (value.length > 0) {
            e.target.style.borderColor = '#f59e0b'; // Amarillo
        } else {
            e.target.style.borderColor = '#d1d5db'; // Gris por defecto
        }
    });
}
</script>