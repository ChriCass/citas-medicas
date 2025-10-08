-- ========================================
-- Base de datos médica optimizada (med_database_v5)
-- Versión para SQL Server
-- ========================================

IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = N'med_database_v5')
    CREATE DATABASE med_database_v5;
GO

USE med_database_v5;
GO

-- ========================================
-- ROLES
-- ========================================
CREATE TABLE roles (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre NVARCHAR(50) NOT NULL UNIQUE,
    descripcion NVARCHAR(255) NULL
);
GO

-- ========================================
-- USUARIOS
-- ========================================
CREATE TABLE usuarios (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre NVARCHAR(100) NOT NULL,
    apellido NVARCHAR(100) NULL,
    email NVARCHAR(150) NOT NULL UNIQUE,
    contrasenia NVARCHAR(255) NOT NULL,
    dni NVARCHAR(50) NULL,
    telefono NVARCHAR(50) NULL,
    direccion NVARCHAR(255) NULL,
    creado_en DATETIME DEFAULT GETDATE()
);
GO

-- ========================================
-- RELACIÓN usuarios <-> roles
-- ========================================
CREATE TABLE tiene_roles (
    id INT IDENTITY(1,1) PRIMARY KEY,
    usuario_id INT NOT NULL,
    rol_id INT NOT NULL,
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_tr_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_tr_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE
);
GO

-- ========================================
-- PACIENTES
-- ========================================
CREATE TABLE pacientes (
    id INT IDENTITY(1,1) PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_sangre NVARCHAR(10) NULL,
    alergias NVARCHAR(MAX) NULL,
    condicion_cronica NVARCHAR(MAX) NULL,
    historial_cirugias NVARCHAR(MAX) NULL,
    historico_familiar NVARCHAR(MAX) NULL,
    observaciones NVARCHAR(MAX) NULL,
    contacto_emergencia_nombre NVARCHAR(150) NULL,
    contacto_emergencia_telefono NVARCHAR(50) NULL,
    contacto_emergencia_relacion NVARCHAR(100) NULL,
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_paciente_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
GO

-- ========================================
-- ESPECIALIDADES
-- ========================================
CREATE TABLE especialidades (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre NVARCHAR(150) NOT NULL,
    descripcion NVARCHAR(MAX) NULL
);
GO

-- ========================================
-- DOCTORES
-- ========================================
CREATE TABLE doctores (
    id INT IDENTITY(1,1) PRIMARY KEY,
    usuario_id INT NOT NULL,
    especialidad_id INT NULL,
    cmp NVARCHAR(100) NULL,
    biografia NVARCHAR(MAX) NULL,
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_doctor_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_doctor_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE SET NULL
);
GO

-- ========================================
-- SEDES
-- ========================================
CREATE TABLE sedes (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre_sede NVARCHAR(150) NOT NULL,
    direccion NVARCHAR(255) NULL,
    telefono NVARCHAR(50) NULL
);
GO

-- ========================================
-- DOCTOR_SEDE (relación m:n)
-- ========================================
CREATE TABLE doctor_sede (
    sede_id INT NOT NULL,
    doctor_id INT NOT NULL,
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    PRIMARY KEY (sede_id, doctor_id),
    CONSTRAINT fk_ds_sede FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ds_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE CASCADE
);
GO

-- ========================================
-- HORARIOS
-- ========================================
CREATE TABLE horarios (
    id INT IDENTITY(1,1) PRIMARY KEY,
    doctor_id INT NOT NULL,
    sede_id INT NULL,
    dia_semana TINYINT NOT NULL, -- 0=domingo..6=sábado
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_horario_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE CASCADE,
    CONSTRAINT fk_horario_sede FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
);
GO

-- ========================================
-- DIAGNOSTICOS
-- ========================================
CREATE TABLE diagnosticos (
    id INT IDENTITY(1,1) PRIMARY KEY,
    codigo NVARCHAR(50) NOT NULL UNIQUE,
    nombre_enfermedad NVARCHAR(255) NOT NULL,
    descripcion NVARCHAR(MAX) NULL
);
GO

-- ========================================
-- CITAS
-- ========================================
CREATE TABLE citas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    paciente_id INT NOT NULL,
    doctor_id INT NOT NULL,
    sede_id INT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    razon NVARCHAR(255) NULL,
    estado NVARCHAR(50) DEFAULT 'pendiente',
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_cita_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    CONSTRAINT fk_cita_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE CASCADE,
    CONSTRAINT fk_cita_sede FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
);
GO

CREATE INDEX idx_cita_fecha ON citas(fecha);
GO

-- ========================================
-- CONSULTAS
-- ========================================
CREATE TABLE consultas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    cita_id INT NOT NULL,
    diagnostico_id INT NULL,
    observaciones NVARCHAR(MAX) NULL,
    receta NVARCHAR(MAX) NULL,
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_consulta_cita FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    CONSTRAINT fk_consulta_diag FOREIGN KEY (diagnostico_id) REFERENCES diagnosticos(id) ON DELETE SET NULL
);
GO

-- ========================================
-- CALENDARIO
-- ========================================
CREATE TABLE calendario (
    id INT IDENTITY(1,1) PRIMARY KEY,
    doctor_id INT NULL,
    cita_id INT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    estado NVARCHAR(80) DEFAULT 'activo',
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_calendario_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE SET NULL,
    CONSTRAINT fk_calendario_cita FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL
);
GO

-- ========================================
-- CAJEROS
-- ========================================
CREATE TABLE cajeros (
    id INT IDENTITY(1,1) PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre NVARCHAR(150) NULL,
    usuario NVARCHAR(100) NULL,
    contrasenia NVARCHAR(255) NULL,
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_cajero_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
GO

-- ========================================
-- SUPERADMIN
-- ========================================
CREATE TABLE superadmins (
    id INT IDENTITY(1,1) PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre NVARCHAR(150) NULL,
    usuario NVARCHAR(100) NULL,
    contrasenia NVARCHAR(255) NULL,
    creado_en DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_superadmin_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
GO

-- ========================================
-- DATOS INICIALES
-- ========================================

-- Roles
INSERT INTO roles (nombre, descripcion) VALUES 
('superadmin', 'Administrador del sistema'),
('doctor', 'Médico'),
('paciente', 'Paciente'),
('cajero', 'Cajero/Recepción');

-- Especialidades
INSERT INTO especialidades (nombre, descripcion) VALUES 
('Medicina General', 'Atención médica general'),
('Cardiología', 'Especialidad en enfermedades del corazón'),
('Dermatología', 'Especialidad en enfermedades de la piel'),
('Pediatría', 'Especialidad en medicina infantil'),
('Ginecología', 'Especialidad en salud femenina');

-- Sedes
INSERT INTO sedes (nombre_sede, direccion, telefono) VALUES 
('Sede Central', 'Av. Principal 123, Lima', '555-0100'),
('Sede Norte', 'Calle Norte 456, Lima', '555-0200'),
('Sede Sur', 'Av. Sur 789, Lima', '555-0300');

-- Usuarios demo (contraseña: password)
INSERT INTO usuarios (nombre, apellido, email, contrasenia, dni, telefono) VALUES 
('Super', 'Admin', 'super@demo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '12345678', '999-000-001'),
('Dr. Juan', 'Pérez', 'doctor@demo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '87654321', '999-000-002'),
('María', 'García', 'paciente@demo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11223344', '999-000-003'),
('Carlos', 'López', 'cajero@demo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '44332211', '999-000-004');

-- Asignar roles
INSERT INTO tiene_roles (usuario_id, rol_id) VALUES 
(1, 1), -- super admin
(2, 2), -- doctor
(3, 3), -- paciente
(4, 4); -- cajero

-- Crear perfil de doctor
INSERT INTO doctores (usuario_id, especialidad_id, cmp, biografia) VALUES 
(2, 1, 'CMP-12345', 'Médico general con 10 años de experiencia');

-- Crear perfil de paciente
INSERT INTO pacientes (usuario_id, tipo_sangre, alergias, contacto_emergencia_nombre, contacto_emergencia_telefono) VALUES 
(3, 'O+', 'Ninguna conocida', 'Ana García', '999-000-005');

-- Crear perfil de cajero
INSERT INTO cajeros (usuario_id, nombre, usuario, contrasenia) VALUES 
(4, 'Carlos López', 'cajero', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Crear perfil de superadmin
INSERT INTO superadmins (usuario_id, nombre, usuario, contrasenia) VALUES 
(1, 'Super Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Asignar doctor a sede
INSERT INTO doctor_sede (sede_id, doctor_id, fecha_inicio) VALUES 
(1, 1, GETDATE());

-- Crear horarios de ejemplo
INSERT INTO horarios (doctor_id, sede_id, dia_semana, hora_inicio, hora_fin) VALUES 
(1, 1, 1, '09:00:00', '17:00:00'), -- Lunes
(1, 1, 2, '09:00:00', '17:00:00'), -- Martes
(1, 1, 3, '09:00:00', '17:00:00'), -- Miércoles
(1, 1, 4, '09:00:00', '17:00:00'), -- Jueves
(1, 1, 5, '09:00:00', '17:00:00'); -- Viernes
