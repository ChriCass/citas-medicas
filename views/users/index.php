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
        flex-wrap: wrap;
    }
    .title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }
    .toolbar {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }
    .field {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 8px;
        padding: 8px 10px;
    }
    .field label {
        font-size: 12px;
        color: var(--muted, #6b7280);
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }
    select, input[type="search"] {
        border: none;
        outline: none;
        font: inherit;
        color: inherit;
        background: transparent;
        min-height: 28px;
    }
    input[type="search"] { min-width: 200px; }
    .table-wrap { 
        width: 100%; 
        overflow: auto; 
        max-height: 70vh;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }
    thead th {
        position: sticky;
        top: 0;
        background: #fafafa;
        z-index: 1;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: var(--muted, #6b7280);
        border-bottom: 1px solid var(--border, #e5e7eb);
        padding: 12px 14px;
        white-space: nowrap;
    }
    tbody td {
        border-bottom: 1px solid var(--border, #e5e7eb);
        padding: 12px 14px;
        font-size: 14px;
        vertical-align: middle;
    }
    tbody tr:hover { background: #fcfcfd; }
    .empty {
        text-align: center;
        color: var(--muted, #6b7280);
        font-size: 14px;
        padding: 40px;
    }
    .loading {
        text-align: center;
        color: var(--muted, #6b7280);
        font-size: 14px;
        padding: 20px;
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
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        margin-right: 8px;
    }
    .btn-primary {
        background: var(--primary, #2563eb);
        color: white;
    }
    .btn-primary:hover {
        background: #1d4ed8;
    }
    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
        margin-right: 4px;
    }
    .btn-edit {
        background: #f59e0b;
        color: white;
    }
    .btn-edit:hover {
        background: #d97706;
    }
    .btn-view {
        background: #3b82f6;
        color: white;
    }
    .btn-view:hover {
        background: #2563eb;
    }
    .btn-delete {
        background: #dc2626;
        color: white;
    }
    .btn-delete:hover {
        background: #b91c1c;
    }
    .actions-cell {
        white-space: nowrap;
    }

    /* Estilos del Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
        animation: fadeIn 0.3s ease;
    }

    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
        padding: 24px 24px 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6b7280;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s;
    }

    .close-modal:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .user-card {
        padding: 24px;
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: white;
        font-weight: 700;
        margin: 0 auto 20px auto;
        text-transform: uppercase;
    }

    .user-name {
        text-align: center;
        font-size: 24px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 8px 0;
    }

    .user-role {
        text-align: center;
        margin-bottom: 24px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .info-section {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
    }

    .info-section h4 {
        margin: 0 0 16px 0;
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    .info-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .info-label {
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        min-width: 100px;
    }

    .info-value {
        font-size: 14px;
        color: #111827;
        text-align: right;
        word-break: break-word;
        flex: 1;
        margin-left: 12px;
    }

    .info-value.empty {
        color: #9ca3af;
        font-style: italic;
    }
</style>

<div class="card">
    <div class="header">
        <h1 class="title">Gestión de Usuarios</h1>
        <div class="toolbar" role="group" aria-label="Filtros y búsqueda">
            <a href="/users/create" class="btn btn-primary">
                ➕ Nuevo Usuario
            </a>
            <div class="field">
                <label for="role">Rol</label>
                <select id="role" name="role" aria-label="Filtrar por rol">
                    <option value="">Todos</option>
                    <option value="paciente">Pacientes</option>
                    <option value="doctor">Doctores</option>
                    <option value="cajero">Cajeros</option>
                    <option value="superadmin">Superadmins</option>
                </select>
            </div>
            <div class="field">
                <label for="q">Buscar</label>
                <input id="q" name="q" type="search" placeholder="Nombre, email, DNI..." aria-label="Búsqueda dinámica" />
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table aria-label="Listado de usuarios">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Email</th>
                    <th>DNI</th>
                    <th>Teléfono</th>
                    <th>Roles</th>
                    <th>Fecha Registro</th>
                    <th width="120">Acciones</th>
                </tr>
            </thead>
            <tbody id="usersTable">
                <tr>
                    <td colspan="8" class="loading">Cargando usuarios...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para ver detalles del usuario -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                👤 Detalles del Usuario
            </h3>
            <button class="close-modal" onclick="closeUserModal()">×</button>
        </div>
        <div class="user-card" id="userCardContent">
            <!-- El contenido se carga dinámicamente -->
        </div>
    </div>
</div>

<script>
let currentUsers = [];
let debounceTimer = null;

// Función para escapar HTML y prevenir XSS
function escapeHtml(text) {
    if (text == null) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cargar usuarios al inicio
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    
    // Event listeners para filtros
    document.getElementById('role').addEventListener('change', function() {
        loadUsers();
    });
    
    document.getElementById('q').addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadUsers();
        }, 300); // Debounce de 300ms
    });
});

async function loadUsers() {
    const role = document.getElementById('role').value;
    const query = document.getElementById('q').value.trim();
    const tbody = document.getElementById('usersTable');
    
    // Mostrar loading
    tbody.innerHTML = '<tr><td colspan="8" class="loading">Cargando usuarios...</td></tr>';
    
    try {
        const params = new URLSearchParams();
        if (role) params.append('role', role);
        if (query) params.append('q', query);
        
        const response = await fetch(`/api/users?${params}`);
        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        currentUsers = data.users || [];
        renderUsers();
        
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        tbody.innerHTML = `<tr><td colspan="8" class="empty">Error al cargar usuarios: ${error.message}</td></tr>`;
    }
}

function renderUsers() {
    const tbody = document.getElementById('usersTable');
    
    if (currentUsers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">No se encontraron usuarios.</td></tr>';
        return;
    }
    
    const rows = currentUsers.map(user => {
        const fullName = [user.nombre, user.apellido].filter(n => n).join(' ') || '-';
        const roles = user.roles ? user.roles.split(', ').map(role => 
            `<span class="role-badge role-${role.toLowerCase()}">${role}</span>`
        ).join(' ') : '-';
        
        return `
            <tr>
                <td>${user.id}</td>
                <td>${escapeHtml(fullName)}</td>
                <td>${escapeHtml(user.email || '-')}</td>
                <td>${escapeHtml(user.dni || '-')}</td>
                <td>${escapeHtml(user.telefono || '-')}</td>
                <td>${roles}</td>
                <td>${user.creado_en || '-'}</td>
                <td class="actions-cell">
                    <button onclick="viewUser(${user.id})" class="btn btn-view btn-sm" title="Ver detalles del usuario">
                        👁️
                    </button>
                    <a href="/users/create?mode=edit&id=${user.id}" class="btn btn-edit btn-sm" title="Editar usuario">
                        ✏️
                    </a>
                    <button onclick="deleteUser(${user.id}, '${escapeHtml(fullName)}')" class="btn btn-delete btn-sm" title="Eliminar usuario">
                        🗑️
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    tbody.innerHTML = rows;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function deleteUser(userId, userName) {
    try {
        // Primero verificar las relaciones del usuario
        const checkResponse = await fetch(`/api/users/${userId}/relationships`);
        const checkResult = await checkResponse.json();
        
        if (!checkResponse.ok) {
            throw new Error('Error al verificar las relaciones del usuario');
        }

        // Si no puede ser eliminado, mostrar información detallada
        if (!checkResult.can_delete) {
            showDeleteWarningModal(userName, checkResult);
            return;
        }

        // Si puede ser eliminado, confirmar normalmente
        if (!confirm(`¿Estás seguro de que quieres eliminar al usuario "${userName}"?\n\nEsta acción no se puede deshacer.`)) {
            return;
        }

        // Proceder con la eliminación
        await performUserDeletion(userId, userName);
        
    } catch (error) {
        console.error('Error al verificar usuario:', error);
        alert('No se pudo verificar el usuario: revise que no tenga citas o turnos activos antes de volverlo a intentar.');
    }
}

// Función para realizar la eliminación real
async function performUserDeletion(userId, userName) {
    try {
        const response = await fetch(`/api/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (response.ok) {
            alert('Usuario eliminado correctamente');
            loadUsers(); // Recargar la lista
        } else {
            // Si el servidor ahora rechaza la eliminación, mostrar el error detallado
            if (result.details && result.suggestions) {
                showDeleteErrorModal(userName, result);
            } else {
                // Mensaje simplificado para cualquier error de eliminación
                alert('No se pudo eliminar el usuario: revise que no tenga citas o turnos activos antes de volverlo a intentar.');
            }
        }
        
    } catch (error) {
        console.error('Error al eliminar usuario:', error);
        alert('No se pudo eliminar el usuario: revise que no tenga citas o turnos activos antes de volverlo a intentar.');
    }
}

// Función para mostrar modal de advertencia
function showDeleteWarningModal(userName, checkResult) {
    const detailsList = checkResult.details.map(detail => `<li>${detail}</li>`).join('');
    const suggestionsList = checkResult.suggestions.map(suggestion => `<li>${suggestion}</li>`).join('');
    
    const modalHtml = `
        <div id="deleteWarningModal" class="modal show">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3 class="modal-title" style="color: #dc2626;">
                        ⚠️ No se puede eliminar
                    </h3>
                    <button class="close-modal" onclick="closeDeleteWarningModal()">×</button>
                </div>
                <div style="padding: 24px;">
                    <p><strong>Usuario:</strong> ${escapeHtml(userName)}</p>
                    <p><strong>Razón:</strong> ${escapeHtml(checkResult.reason)}</p>
                    
                    <div style="margin: 16px 0;">
                        <h4 style="color: #dc2626; margin-bottom: 8px;">Problemas encontrados:</h4>
                        <ul style="margin-left: 20px; color: #374151;">
                            ${detailsList}
                        </ul>
                    </div>
                    
                    ${suggestionsList ? `
                        <div style="margin: 16px 0;">
                            <h4 style="color: #059669; margin-bottom: 8px;">Sugerencias:</h4>
                            <ul style="margin-left: 20px; color: #374151;">
                                ${suggestionsList}
                            </ul>
                        </div>
                    ` : ''}
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button onclick="closeDeleteWarningModal()" class="btn" style="background: #6b7280; color: white; padding: 8px 16px;">
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.body.style.overflow = 'hidden';
}

// Función para mostrar errores detallados de eliminación
function showDeleteErrorModal(userName, result) {
    const detailsList = result.details.map(detail => `<li>${detail}</li>`).join('');
    const suggestionsList = result.suggestions.map(suggestion => `<li>${suggestion}</li>`).join('');
    
    const modalHtml = `
        <div id="deleteErrorModal" class="modal show">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3 class="modal-title" style="color: #dc2626;">
                        ❌ Eliminación Fallida
                    </h3>
                    <button class="close-modal" onclick="closeDeleteErrorModal()">×</button>
                </div>
                <div style="padding: 24px;">
                    <p><strong>Usuario:</strong> ${escapeHtml(userName)}</p>
                    <p style="color: #dc2626;"><strong>${escapeHtml(result.reason)}</strong></p>
                    
                    <div style="margin: 16px 0;">
                        <h4 style="color: #dc2626; margin-bottom: 8px;">Detalles:</h4>
                        <ul style="margin-left: 20px; color: #374151;">
                            ${detailsList}
                        </ul>
                    </div>
                    
                    ${suggestionsList ? `
                        <div style="margin: 16px 0;">
                            <h4 style="color: #059669; margin-bottom: 8px;">Qué hacer:</h4>
                            <ul style="margin-left: 20px; color: #374151;">
                                ${suggestionsList}
                            </ul>
                        </div>
                    ` : ''}
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button onclick="closeDeleteErrorModal()" class="btn" style="background: #6b7280; color: white; padding: 8px 16px;">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.body.style.overflow = 'hidden';
}

// Funciones para cerrar los modales de advertencia
function closeDeleteWarningModal() {
    const modal = document.getElementById('deleteWarningModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

function closeDeleteErrorModal() {
    const modal = document.getElementById('deleteErrorModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Función para ver detalles del usuario
async function viewUser(userId) {
    try {
        const response = await fetch(`/api/users/${userId}`);
        if (!response.ok) {
            throw new Error('Usuario no encontrado');
        }
        
        const data = await response.json();
        const user = data.user;
        
        // Mostrar modal con los datos
        showUserModal(user);
        
    } catch (error) {
        console.error('Error al cargar datos del usuario:', error);
        alert('Error al cargar los datos del usuario: ' + error.message);
    }
}

// Función para mostrar el modal con los datos del usuario
function showUserModal(user) {
    const modal = document.getElementById('userModal');
    const content = document.getElementById('userCardContent');
    
    // Obtener iniciales para el avatar
    const initials = getInitials(user.nombre, user.apellido);
    
    // Construir HTML del contenido
    content.innerHTML = `
        <div class="user-avatar">${initials}</div>
        <h2 class="user-name">${escapeHtml(user.nombre || '')} ${escapeHtml(user.apellido || '')}</h2>
        <div class="user-role">
            ${getRoleBadges(user.roles || '')}
        </div>
        
        <div class="info-grid">
            <!-- Información Personal -->
            <div class="info-section">
                <h4>👤 Información Personal</h4>
                <div class="info-item">
                    <span class="info-label">Nombre</span>
                    <span class="info-value">${escapeHtml(user.nombre || 'No especificado')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Apellido</span>
                    <span class="info-value">${escapeHtml(user.apellido || 'No especificado')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">DNI</span>
                    <span class="info-value">${escapeHtml(user.dni || 'No especificado')}</span>
                </div>
            </div>

            <!-- Información de Contacto -->
            <div class="info-section">
                <h4>📞 Contacto</h4>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value">${escapeHtml(user.email || 'No especificado')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value">${escapeHtml(user.telefono || 'No especificado')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dirección</span>
                    <span class="info-value">${escapeHtml(user.direccion || 'No especificado')}</span>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="info-section">
                <h4>⚙️ Sistema</h4>
                <div class="info-item">
                    <span class="info-label">Roles</span>
                    <span class="info-value">${getRoleBadges(user.roles || 'Sin rol')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Registrado</span>
                    <span class="info-value">${user.creado_en || 'No disponible'}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">ID Usuario</span>
                    <span class="info-value">#${user.id}</span>
                </div>
            </div>

            ${getRoleSpecificSection(user)}
        </div>
    `;
    
    // Mostrar modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevenir scroll del body
}

// Función para cerrar el modal
function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto'; // Restaurar scroll del body
}

// Función para obtener iniciales
function getInitials(nombre, apellido) {
    const n = (nombre || '').charAt(0).toUpperCase();
    const a = (apellido || '').charAt(0).toUpperCase();
    return n + a || 'U';
}

// Función para obtener badges de roles
function getRoleBadges(rolesString) {
    if (!rolesString || rolesString === 'Sin rol') {
        return '<span class="role-badge">Sin rol</span>';
    }
    
    const roles = rolesString.split(', ');
    return roles.map(role => `<span class="role-badge role-${role.toLowerCase()}">${role}</span>`).join(' ');
}

// Función para obtener sección específica del rol
function getRoleSpecificSection(user) {
    if (!user.role_data) {
        return '';
    }

    const roleData = user.role_data;
    const primaryRole = (user.roles || '').split(', ')[0];

    switch(primaryRole?.toLowerCase()) {
        case 'doctor':
            return `
                <div class="info-section">
                    <h4>👨‍⚕️ Información Médica</h4>
                    <div class="info-item">
                        <span class="info-label">Especialidad</span>
                        <span class="info-value">${escapeHtml(roleData.especialidad_nombre || 'No especificada')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">CMP</span>
                        <span class="info-value">${escapeHtml(roleData.cmp || 'No especificado')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Biografía</span>
                        <span class="info-value">${escapeHtml(roleData.biografia || 'No especificada')}</span>
                    </div>
                </div>
            `;

        case 'paciente':
            return `
                <div class="info-section">
                    <h4>🏥 Información Médica</h4>
                    <div class="info-item">
                        <span class="info-label">Tipo Sangre</span>
                        <span class="info-value">${escapeHtml(roleData.tipo_sangre || 'No especificado')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Alergias</span>
                        <span class="info-value">${escapeHtml(roleData.alergias || 'Ninguna conocida')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Condición Crónica</span>
                        <span class="info-value">${escapeHtml(roleData.condicion_cronica || 'Ninguna')}</span>
                    </div>
                </div>
                <div class="info-section">
                    <h4>🚨 Contacto de Emergencia</h4>
                    <div class="info-item">
                        <span class="info-label">Nombre</span>
                        <span class="info-value">${escapeHtml(roleData.contacto_emergencia_nombre || 'No especificado')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teléfono</span>
                        <span class="info-value">${escapeHtml(roleData.contacto_emergencia_telefono || 'No especificado')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Relación</span>
                        <span class="info-value">${escapeHtml(roleData.contacto_emergencia_relacion || 'No especificada')}</span>
                    </div>
                </div>
            `;

        case 'cajero':
            return `
                <div class="info-section">
                    <h4>💰 Sistema de Caja</h4>
                    <div class="info-item">
                        <span class="info-label">Usuario Sistema</span>
                        <span class="info-value">${escapeHtml(roleData.nombre_cajero || 'No especificado')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Estado</span>
                        <span class="info-value">Activo</span>
                    </div>
                </div>
            `;

        case 'superadmin':
            return `
                <div class="info-section">
                    <h4>👑 Administración</h4>
                    <div class="info-item">
                        <span class="info-label">Usuario Sistema</span>
                        <span class="info-value">${escapeHtml(roleData.nombre_admin || 'No especificado')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nivel Acceso</span>
                        <span class="info-value">Completo</span>
                    </div>
                </div>
            `;

        default:
            return '';
    }
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener('click', function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeUserModal();
    }
});

// Cerrar modal con la tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeUserModal();
    }
});
</script>