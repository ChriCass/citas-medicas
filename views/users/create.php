<!-- Formulario para Crear/Editar Usuario -->
<div class="container" style="max-width: 1600px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Encabezado Principal con Botones -->
    <div class="page-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
        <div>
            <h1 style="margin: 0; display: flex; align-items: center; gap: 1rem; color: #2c3e50;">
                <span id="formIcon">➕</span>
                <span id="formTitleText">Agregar Nuevo Usuario</span>
            </h1>
            <p style="color: #7f8c8d; margin: 0.5rem 0 0 0;" id="formSubtitle">Complete el formulario con la información del usuario</p>
        </div>
        
        <!-- Botones de acción en el header -->
        <div style="display: flex; gap: 1rem;">
            <a href="/users" class="btn btn-secondary" style="text-decoration: none;">
                <span>❌</span> Cancelar
            </a>
            <button type="submit" form="userForm" class="btn btn-primary">
                <span>✓</span> Guardar Usuario
            </button>
        </div>
    </div>

    <form id="userForm" onsubmit="submitUser(event)">
        <input type="hidden" id="userId" name="userId" />
        
        <!-- Contenedor de 2 columnas -->
        <div class="cards-container">
            <!-- CARD 1: Información General del Usuario -->
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.25rem; border-radius: 8px 8px 0 0;">
                    <h2 style="margin: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.5rem;">👤</span>
                        Información General del Usuario
                    </h2>
                </div>
            
            <div class="card-body" style="padding: 1.5rem;">
                <div class="form-section">
                    <!-- Nombre y Apellido -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre(s) <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="nombre" 
                                name="nombre" 
                                class="form-control" 
                                required 
                                pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+"
                                placeholder="Juan Carlos"
                            />
                        </div>
                        
                        <div class="form-group">
                            <label for="apellido">Apellido(s) <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="apellido" 
                                name="apellido" 
                                class="form-control" 
                                required
                                pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+"
                                placeholder="García López"
                            />
                        </div>
                    </div>
                    
                    <!-- DNI y Email -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dni">DNI <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="dni" 
                                name="dni" 
                                class="form-control" 
                                required
                                pattern="[0-9]{8}"
                                maxlength="8"
                                placeholder="12345678"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            />
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                required
                                placeholder="ejemplo@correo.com"
                            />
                        </div>
                    </div>
                    
                    <!-- Teléfono y Contraseña -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Teléfono <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="telefono" 
                                name="telefono" 
                                class="form-control" 
                                required
                                pattern="[0-9]{9}"
                                maxlength="9"
                                placeholder="987654321"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            />
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Contraseña <span id="passwordRequired" class="required">*</span></label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                minlength="6"
                                placeholder="Mínimo 6 caracteres"
                            />
                            <small id="passwordHelp" class="form-text" style="display: none;">
                                Dejar en blanco para mantener actual
                            </small>
                        </div>
                    </div>
                    
                    <!-- Dirección -->
                    <div class="form-group">
                        <label for="direccion">Dirección <span class="required">*</span></label>
                        <textarea 
                            id="direccion" 
                            name="direccion" 
                            class="form-control" 
                            rows="2"
                            required
                            placeholder="Av. Principal 123, Distrito"
                        ></textarea>
                    </div>
                    
                    <!-- Selección de Rol -->
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #2c3e50;">
                            🎭 Rol del Usuario <span class="required">*</span>
                        </label>
                        <div class="role-selector">
                            <label class="role-option">
                                <input type="radio" name="rol" value="paciente" onchange="handleRoleChange()" required>
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
            </div>
        </div>
        
        <!-- CARD 2: Información Específica del Rol -->
        <div id="roleSpecificCard" class="card">
            <div id="roleSpecificHeader" class="card-header" style="background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%); padding: 1.25rem; border-radius: 8px 8px 0 0;">
                <h2 style="margin: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 0.75rem; color: white;">
                    <span id="roleSpecificIcon" style="font-size: 1.5rem;">📋</span>
                    <span id="roleSpecificText">Información Específica del Rol</span>
                </h2>
            </div>
            
            <div class="card-body" style="padding: 2rem;">
                <!-- Placeholder cuando no hay rol seleccionado -->
                <div id="noRolePlaceholder" style="text-align: center; padding: 3rem 2rem; color: #7f8c8d;">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">🎭</div>
                    <h3 style="color: #95a5a6; margin-bottom: 0.5rem;">Seleccione un Rol</h3>
                    <p style="font-size: 0.95rem;">Elija el rol del usuario en la sección izquierda para ver los campos específicos</p>
                </div>
                
                <!-- Campos específicos para PACIENTE -->
                <div id="pacienteFields" class="role-specific-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="numero_historia_clinica">📋 N° Historia Clínica</label>
                            <input type="text" id="numero_historia_clinica" name="numero_historia_clinica" class="form-control" maxlength="20" placeholder="HC-000001" />
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_sangre">🩸 Tipo de Sangre</label>
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
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="alergias">⚠️ Alergias</label>
                            <textarea id="alergias" name="alergias" class="form-control" rows="2" placeholder="Penicilina, Polen..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="condicion_cronica">🏥 Condiciones Crónicas</label>
                            <textarea id="condicion_cronica" name="condicion_cronica" class="form-control" rows="2" placeholder="Diabetes, Hipertensión..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="historial_cirugias">🔪 Historial de Cirugías</label>
                            <textarea id="historial_cirugias" name="historial_cirugias" class="form-control" rows="2" placeholder="Apendicectomía (2018)..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="historico_familiar">👨‍👩‍👧‍👦 Historial Médico Familiar</label>
                            <textarea id="historico_familiar" name="historico_familiar" class="form-control" rows="2" placeholder="Padre con diabetes..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observaciones">📝 Observaciones</label>
                        <textarea id="observaciones" name="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <!-- Contacto de Emergencia -->
                    <h4 class="subsection-title" style="font-size: 1rem; margin: 1.5rem 0 0.75rem 0;">🚨 Contacto de Emergencia</h4>
                        
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contacto_emergencia_nombre">👤 Nombre del Contacto</label>
                            <input type="text" id="contacto_emergencia_nombre" name="contacto_emergencia_nombre" class="form-control" placeholder="Nombre completo" />
                        </div>
                        
                        <div class="form-group">
                            <label for="contacto_emergencia_telefono">📞 Teléfono</label>
                            <input 
                                type="text" 
                                id="contacto_emergencia_telefono" 
                                name="contacto_emergencia_telefono" 
                                class="form-control"
                                pattern="[0-9]{9}"
                                maxlength="9"
                                placeholder="987654321"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contacto_emergencia_relacion">❤️ Relación</label>
                        <input type="text" id="contacto_emergencia_relacion" name="contacto_emergencia_relacion" class="form-control" placeholder="Esposo/a, Hijo/a..." />
                    </div>
                </div>
                
                <!-- Campos específicos para DOCTOR -->
                <div id="doctorFields" class="role-specific-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="especialidad_id">🩺 Especialidad <span class="required">*</span></label>
                            <select id="especialidad_id" name="especialidad_id" class="form-control">
                                <option value="">Seleccione una especialidad</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="cmp">📋 CMP <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="cmp" 
                                name="cmp" 
                                class="form-control"
                                placeholder="12345"
                                maxlength="5"
                                pattern="[0-9]{5}"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 5)"
                            />
                            <small class="form-text">Se guarda como CMP-#####</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="biografia">📝 Biografía Profesional</label>
                        <textarea id="biografia" name="biografia" class="form-control" rows="3" placeholder="Experiencia, logros, áreas de interés..."></textarea>
                    </div>
                </div>
                
                <!-- Campos específicos para CAJERO -->
                <div id="cajeroFields" class="role-specific-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cajero_nombre">👤 Nombre Completo <span class="required">*</span></label>
                            <input type="text" id="cajero_nombre" name="cajero_nombre" class="form-control" placeholder="Nombre para sistema de caja" />
                        </div>
                        
                        <div class="form-group">
                            <label for="cajero_usuario">👨‍💼 Usuario del Sistema <span class="required">*</span></label>
                            <input type="text" id="cajero_usuario" name="cajero_usuario" class="form-control" placeholder="usuario_caja" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="cajero_contrasenia">🔐 Contraseña del Sistema <span class="required">*</span></label>
                        <input type="password" id="cajero_contrasenia" name="cajero_contrasenia" class="form-control" minlength="6" placeholder="Mínimo 6 caracteres" />
                    </div>
                </div>
                
                <!-- Campos específicos para SUPERADMIN -->
                <div id="superadminFields" class="role-specific-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="superadmin_nombre">👤 Nombre Completo <span class="required">*</span></label>
                            <input type="text" id="superadmin_nombre" name="superadmin_nombre" class="form-control" placeholder="Nombre administrativo" />
                        </div>
                        
                        <div class="form-group">
                            <label for="superadmin_usuario">👨‍💼 Usuario del Sistema <span class="required">*</span></label>
                            <input type="text" id="superadmin_usuario" name="superadmin_usuario" class="form-control" placeholder="usuario_admin" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="superadmin_contrasenia">🔐 Contraseña Administrativa <span class="required">*</span></label>
                        <input type="password" id="superadmin_contrasenia" name="superadmin_contrasenia" class="form-control" minlength="6" placeholder="Mínimo 6 caracteres" />
                    </div>
                    
                    <div class="alert alert-warning" style="margin-top: 0.75rem; padding: 0.75rem; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; font-size: 0.9rem;">
                        <strong>⚠️ Advertencia:</strong> Acceso total al sistema. Use credenciales seguras.
                    </div>
                </div>
                </div>
            </div>
        </div>
        </div>
    </form>
</div>

<style>
    .page-header {
        text-align: left;
    }
    
    .container {
        max-width: 1600px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    .cards-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    @media (max-width: 1200px) {
        .cards-container {
            grid-template-columns: 1fr;
        }
    }
    
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: fit-content;
        position: relative;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }
    
    #roleSpecificCard {
        animation: fadeInRight 0.4s ease-out;
    }
    
    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .card-body {
        padding: 2rem;
        background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
    }
    
    .form-section {
        margin-bottom: 2rem;
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 3px solid #3498db;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
    }
    
    .section-title::before {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 3px;
    }
    
    .subsection-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #e74c3c;
        margin: 1.5rem 0 1rem 0;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .section-icon {
        font-size: 1.5rem;
    }
    
    .field-icon {
        margin-right: 0.5rem;
        font-size: 1.1rem;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.4rem;
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
    }
    
    .required {
        color: #e74c3c;
        font-weight: 700;
    }
    
    .form-control {
        width: 100%;
        padding: 0.6rem 0.75rem;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        transform: translateY(-1px);
    }
    
    .form-control:invalid:not(:placeholder-shown) {
        border-color: #e74c3c;
        background-color: #fff5f5;
    }
    
    .form-control:valid:not(:placeholder-shown) {
        border-color: #27ae60;
    }
    
    .form-text {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.85rem;
        color: #6c757d;
        font-style: italic;
    }
    
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
        padding: 1rem 0.75rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        background: white;
        transition: all 0.3s ease;
        min-height: 90px;
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
        font-size: 2rem;
        margin-bottom: 0.25rem;
        display: block;
    }
    
    .role-name {
        font-weight: 600;
        color: #2c3e50;
        text-align: center;
        font-size: 0.85rem;
    }
    
    .role-option input[type="radio"]:checked + .role-card .role-name {
        color: #3498db;
    }
    
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
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    }
    
    .form-actions {
        position: sticky;
        bottom: 0;
        z-index: 100;
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
    
    @media (max-width: 768px) {
        .role-selector {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let especialidades = [];
    // Obtener userId desde PHP si existe
    const userId = <?= isset($userId) ? $userId : 'null' ?>;
    
    // Cargar datos al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        loadEspecialidades();
        
        if (userId) {
            // Modo edición
            document.getElementById('formIcon').textContent = '✏️';
            document.getElementById('formTitleText').textContent = 'Editar Usuario';
            document.getElementById('formSubtitle').textContent = 'Actualice la información del usuario';
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHelp').style.display = 'block';
            document.getElementById('password').required = false;
            document.getElementById('userId').value = userId;
            loadUserData(userId);
        } else {
            // Modo creación - mostrar placeholder hasta que seleccionen un rol
            // No seleccionar ningún rol por defecto
        }
    });
    
    // Cargar especialidades
    async function loadEspecialidades() {
        try {
            const response = await fetch('/api/especialidades');
            const data = await response.json();
            
            if (data.success) {
                especialidades = data.data;
                const select = document.getElementById('especialidad_id');
                select.innerHTML = '<option value="">Seleccione una especialidad</option>';
                
                especialidades.forEach(esp => {
                    const option = document.createElement('option');
                    option.value = esp.id;
                    option.textContent = esp.nombre;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error al cargar especialidades:', error);
        }
    }
    
    // Cargar datos del usuario para edición
    async function loadUserData(id) {
        try {
            const response = await fetch(`/api/users/${id}`);
            const data = await response.json();
            
            if (data.success) {
                const user = data.data.user;
                const roleData = data.data.roleData;
                
                document.getElementById('userId').value = user.id;
                document.getElementById('nombre').value = user.nombre || '';
                document.getElementById('apellido').value = user.apellido || '';
                document.getElementById('dni').value = user.dni || '';
                document.getElementById('email').value = user.email || '';
                document.getElementById('telefono').value = user.telefono || '';
                document.getElementById('direccion').value = user.direccion || '';
                
                // Seleccionar rol
                const rolRadio = document.querySelector(`input[name="rol"][value="${user.rol}"]`);
                if (rolRadio) {
                    rolRadio.checked = true;
                    handleRoleChange();
                }
                
                // Cargar datos específicos del rol
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
                    // Extraer solo los dígitos del CMP si tiene el formato CMP-#####
                    const cmpValue = roleData.cmp || '';
                    document.getElementById('cmp').value = cmpValue.replace('CMP-', '');
                    document.getElementById('biografia').value = roleData.biografia || '';
                } else if (user.rol === 'cajero' && roleData) {
                    document.getElementById('cajero_nombre').value = roleData.nombre || '';
                    document.getElementById('cajero_usuario').value = roleData.usuario || '';
                } else if (user.rol === 'superadmin' && roleData) {
                    document.getElementById('superadmin_nombre').value = roleData.nombre || '';
                    document.getElementById('superadmin_usuario').value = roleData.usuario || '';
                }
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
        const rolSelected = document.querySelector('input[name="rol"]:checked');
        if (!rolSelected) return;
        
        const rol = rolSelected.value;
        
        // Ocultar el placeholder
        const placeholder = document.getElementById('noRolePlaceholder');
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        
        // Ocultar todas las secciones
        document.querySelectorAll('.role-specific-fields').forEach(field => {
            field.style.display = 'none';
            // Limpiar required de los campos ocultos
            field.querySelectorAll('input, select, textarea').forEach(input => {
                input.removeAttribute('required');
            });
        });
        
        const roleSpecificCard = document.getElementById('roleSpecificCard');
        const roleSpecificHeader = document.getElementById('roleSpecificHeader');
        const roleSpecificIcon = document.getElementById('roleSpecificIcon');
        const roleSpecificText = document.getElementById('roleSpecificText');
        
        // Mostrar card específica
        roleSpecificCard.style.display = 'block';
        
        if (rol === 'paciente') {
            roleSpecificHeader.style.background = 'linear-gradient(135deg, #3498db 0%, #2980b9 100%)';
            roleSpecificIcon.textContent = '🏥';
            roleSpecificText.textContent = 'Información Médica del Paciente';
            document.getElementById('pacienteFields').style.display = 'block';
            
        } else if (rol === 'doctor') {
            roleSpecificHeader.style.background = 'linear-gradient(135deg, #1abc9c 0%, #16a085 100%)';
            roleSpecificIcon.textContent = '👨‍⚕️';
            roleSpecificText.textContent = 'Información Profesional del Doctor';
            document.getElementById('doctorFields').style.display = 'block';
            document.getElementById('especialidad_id').required = true;
            document.getElementById('cmp').required = true;
            
        } else if (rol === 'cajero') {
            roleSpecificHeader.style.background = 'linear-gradient(135deg, #e67e22 0%, #d35400 100%)';
            roleSpecificIcon.textContent = '💼';
            roleSpecificText.textContent = 'Información del Cajero';
            document.getElementById('cajeroFields').style.display = 'block';
            document.getElementById('cajero_nombre').required = true;
            document.getElementById('cajero_usuario').required = true;
            if (!userId) {
                document.getElementById('cajero_contrasenia').required = true;
            }
            
        } else if (rol === 'superadmin') {
            roleSpecificHeader.style.background = 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)';
            roleSpecificIcon.textContent = '👑';
            roleSpecificText.textContent = 'Información del Superadministrador';
            document.getElementById('superadminFields').style.display = 'block';
            document.getElementById('superadmin_nombre').required = true;
            document.getElementById('superadmin_usuario').required = true;
            if (!userId) {
                document.getElementById('superadmin_contrasenia').required = true;
            }
        }
    }
    
    // Variable para evitar doble envío
    let isSubmitting = false;
    
    // Enviar formulario
    async function submitUser(event) {
        event.preventDefault();
        
        // Prevenir doble envío
        if (isSubmitting) {
            console.log('Envío ya en progreso, ignorando...');
            return;
        }
        
        isSubmitting = true;
        
        // Deshabilitar el botón de submit
        const submitButton = event.target.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        }
        
        const formData = new FormData(event.target);
        const rolSelected = document.querySelector('input[name="rol"]:checked');
        const rol = rolSelected ? rolSelected.value : '';
        
        const data = {
            nombre: formData.get('nombre'),
            apellido: formData.get('apellido'),
            dni: formData.get('dni'),
            email: formData.get('email'),
            telefono: formData.get('telefono'),
            password: formData.get('password'),
            direccion: formData.get('direccion'),
            rol: rol
        };
        
        // Agregar datos específicos del rol
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
            // Formatear CMP como CMP-##### (5 dígitos)
            const cmpDigits = formData.get('cmp');
            if (cmpDigits && cmpDigits.length === 5) {
                data.cmp = 'CMP-' + cmpDigits;
            } else {
                data.cmp = cmpDigits; // Enviar como está para que el backend valide
            }
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
        
        console.log('Datos a enviar:', data);
        
        try {
            const isEdit = !!userId;
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
                await Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message,
                    confirmButtonColor: '#28a745',
                    timer: 2000,
                    timerProgressBar: true
                });
                window.location.href = '/users';
            } else {
                showError(result.message);
                // Rehabilitar el botón en caso de error
                isSubmitting = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = userId ? '💾 Actualizar Usuario' : '💾 Crear Usuario';
                }
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión con el servidor');
            // Rehabilitar el botón en caso de error
            isSubmitting = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = userId ? '💾 Actualizar Usuario' : '💾 Crear Usuario';
            }
        }
    }
    
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#d33'
        });
    }
</script>