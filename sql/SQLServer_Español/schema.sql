-- ========================================
-- Reset de BD: si existe, eliminar y recrear
-- ========================================
USE master;
GO

IF DB_ID(N'med_database_v5') IS NOT NULL
BEGIN
    ALTER DATABASE [med_database_v5] SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE [med_database_v5];
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.databases WHERE name = N'med_database_v5')
BEGIN
CREATE DATABASE med_database_v5;
END;

-- Cambiar de base de datos (en algunos clientes debe ejecutarse en una sentencia aparte)
USE med_database_v5;

-- ========================================
-- TABLAS (crear en orden de dependencias)
-- ========================================

-- ROLES
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'roles') AND type = N'U')
BEGIN
CREATE TABLE roles (
id INT IDENTITY(1,1) PRIMARY KEY,
nombre NVARCHAR(50) NOT NULL UNIQUE,
descripcion NVARCHAR(255) NULL
);
END;

-- USUARIOS
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'usuarios') AND type = N'U')
BEGIN
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
END;

-- RELACIÓN usuarios <-> roles
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'tiene_roles') AND type = N'U')
BEGIN
CREATE TABLE tiene_roles (
id INT IDENTITY(1,1) PRIMARY KEY,
usuario_id INT NOT NULL,
rol_id INT NOT NULL,
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_tr_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
CONSTRAINT fk_tr_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE
);
END;

-- ESPECIALIDADES
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'especialidades') AND type = N'U')
BEGIN
CREATE TABLE especialidades (
id INT IDENTITY(1,1) PRIMARY KEY,
nombre NVARCHAR(150) NOT NULL,
descripcion NVARCHAR(MAX) NULL
);
END;

-- SEDES
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'sedes') AND type = N'U')
BEGIN
CREATE TABLE sedes (
id INT IDENTITY(1,1) PRIMARY KEY,
nombre_sede NVARCHAR(150) NOT NULL,
direccion NVARCHAR(255) NULL,
telefono NVARCHAR(50) NULL
);
END;

-- DOCTORES
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'doctores') AND type = N'U')
BEGIN
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
END;

-- PACIENTES
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'pacientes') AND type = N'U')
BEGIN
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
END;

-- DOCTOR_SEDE (relación m:n)
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'doctor_sede') AND type = N'U')
BEGIN
CREATE TABLE doctor_sede (
sede_id INT NOT NULL,
doctor_id INT NOT NULL,
fecha_inicio DATE NULL,
fecha_fin DATE NULL,
PRIMARY KEY (sede_id, doctor_id),
CONSTRAINT fk_ds_sede FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE CASCADE,
CONSTRAINT fk_ds_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE CASCADE
);
END;

-- HORARIOS
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'horarios') AND type = N'U')
BEGIN
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
END;

-- DIAGNOSTICOS
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'diagnosticos') AND type = N'U')
BEGIN
CREATE TABLE diagnosticos (
id INT IDENTITY(1,1) PRIMARY KEY,
codigo NVARCHAR(50) NOT NULL UNIQUE,
nombre_enfermedad NVARCHAR(255) NOT NULL,
descripcion NVARCHAR(MAX) NULL
);
END;

-- CITAS
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'citas') AND type = N'U')
BEGIN
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
CONSTRAINT fk_cita_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE NO ACTION,
CONSTRAINT fk_cita_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE NO ACTION,
CONSTRAINT fk_cita_sede FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
);
CREATE INDEX idx_cita_fecha ON citas(fecha);
END;

-- CONSULTAS
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'consultas') AND type = N'U')
BEGIN
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
END;

-- CALENDARIO
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'calendario') AND type = N'U')
BEGIN
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
END;

-- CAJEROS
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'cajeros') AND type = N'U')
BEGIN
CREATE TABLE cajeros (
id INT IDENTITY(1,1) PRIMARY KEY,
usuario_id INT NOT NULL,
nombre NVARCHAR(150) NULL,
usuario NVARCHAR(100) NULL,
contrasenia NVARCHAR(255) NULL,
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_cajero_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
END;

-- SUPERADMIN
IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'superadmins') AND type = N'U')
BEGIN
CREATE TABLE superadmins (
id INT IDENTITY(1,1) PRIMARY KEY,
usuario_id INT NOT NULL,
nombre NVARCHAR(150) NULL,
usuario NVARCHAR(100) NULL,
contrasenia NVARCHAR(255) NULL,
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_superadmin_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
END;

-- ========================================
-- SEEDS IDEMPOTENTES
-- ========================================

-- ROLES
INSERT INTO roles (nombre, descripcion)
SELECT v.nombre, v.descripcion
FROM (VALUES
('superadmin', 'Administrador del sistema'),
('doctor', 'Médico'),
('paciente', 'Paciente'),
('cajero', 'Cajero/Recepción')
) AS v(nombre, descripcion)
WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.nombre = v.nombre);

-- ESPECIALIDADES
INSERT INTO especialidades (nombre, descripcion)
SELECT v.nombre, v.descripcion
FROM (VALUES
('Medicina General', 'Atención médica general y preventiva'),
('Cardiología', 'Especialidad en enfermedades del corazón y sistema cardiovascular'),
('Dermatología', 'Especialidad en enfermedades de la piel, pelo y uñas'),
('Pediatría', 'Especialidad en medicina infantil y adolescente'),
('Ginecología', 'Especialidad en salud femenina y reproductiva'),
('Traumatología', 'Especialidad en lesiones del sistema musculoesquelético'),
('Neurología', 'Especialidad en enfermedades del sistema nervioso'),
('Oftalmología', 'Especialidad en enfermedades de los ojos'),
('Psiquiatría', 'Especialidad en salud mental'),
('Urología', 'Especialidad en el sistema urinario y reproductor masculino')
) AS v(nombre, descripcion)
WHERE NOT EXISTS (SELECT 1 FROM especialidades e WHERE e.nombre = v.nombre);

-- SEDES
INSERT INTO sedes (nombre_sede, direccion, telefono)
SELECT v.nombre_sede, v.direccion, v.telefono
FROM (VALUES
('Clínica San José - Sede Central', 'Av. Javier Prado Este 4200, San Isidro, Lima', '01-234-5678'),
('Clínica San José - Sede Norte', 'Av. Túpac Amaru 1234, Independencia, Lima', '01-234-5679'),
('Clínica San José - Sede Sur', 'Av. El Sol 567, Villa El Salvador, Lima', '01-234-5680'),
('Clínica San José - Sede Este', 'Av. La Molina 890, La Molina, Lima', '01-234-5681'),
('Clínica San José - Sede Oeste', 'Av. Universitaria 2345, San Miguel, Lima', '01-234-5682')
) AS v(nombre_sede, direccion, telefono)
WHERE NOT EXISTS (SELECT 1 FROM sedes s WHERE s.nombre_sede = v.nombre_sede);

-- USUARIOS (hash "password" de ejemplo)
INSERT INTO usuarios (nombre, apellido, email, contrasenia, dni, telefono, direccion)
SELECT v.nombre, v.apellido, v.email, v.contrasenia, v.dni, v.telefono, v.direccion
FROM (VALUES
('Super','Admin','[super@clinicasanjose.com](mailto:super@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','12345678','999-000-001','Av. Principal 123, Lima'),
('Carlos','Mendoza','[admin@clinicasanjose.com](mailto:admin@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','87654321','999-000-002','Av. Principal 123, Lima'),
('Dr. Juan Carlos','Pérez García','[jperez@clinicasanjose.com](mailto:jperez@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','11223344','999-000-010','Av. Javier Prado 100, San Isidro'),
('Dra. María Elena','Rodríguez López','[mrodriguez@clinicasanjose.com](mailto:mrodriguez@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','22334455','999-000-011','Av. Angamos 200, Miraflores'),
('Dr. Roberto','Silva Martínez','[rsilva@clinicasanjose.com](mailto:rsilva@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','33445566','999-000-012','Av. Arequipa 300, Lima'),
('Dra. Ana Patricia','González Vega','[agonzalez@clinicasanjose.com](mailto:agonzalez@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44556677','999-000-013','Av. Benavides 400, Surco'),
('Dr. Luis Fernando','Herrera Díaz','[lherrera@clinicasanjose.com](mailto:lherrera@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','55667788','999-000-014','Av. Primavera 500, San Borja'),
('Dr. Carlos Alberto','Mendoza Torres','[cmendoza@clinicasanjose.com](mailto:cmendoza@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','66778899','999-000-015','Av. Larco 600, Miraflores'),
('Dra. Patricia','Vargas Flores','[pvargas@clinicasanjose.com](mailto:pvargas@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','77889900','999-000-016','Av. Salaverry 700, Jesús María'),
('Dr. Miguel Ángel','Ramírez Castro','[mramirez@clinicasanjose.com](mailto:mramirez@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','88990011','999-000-017','Av. Brasil 800, Magdalena'),
('Dra. Carmen','Jiménez Morales','[cjimenez@clinicasanjose.com](mailto:cjimenez@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99001122','999-000-018','Av. Universitaria 900, San Miguel'),
('Dr. Fernando','Sánchez Rojas','[fsanchez@clinicasanjose.com](mailto:fsanchez@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','00112233','999-000-019','Av. Túpac Amaru 1000, Independencia'),
('Dra. Lucía','Castro Díaz','[lcastro@clinicasanjose.com](mailto:lcastro@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','11223344','999-000-020','Av. El Sol 1100, Villa El Salvador'),
('Dr. Antonio','López Vega','[alopez@clinicasanjose.com](mailto:alopez@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','22334455','999-000-021','Av. La Molina 1200, La Molina'),
('Dra. Isabel','Martínez Herrera','[imartinez@clinicasanjose.com](mailto:imartinez@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','33445566','999-000-022','Av. Primavera 1300, San Borja'),
('Dr. Rafael','García Torres','[rgarcia@clinicasanjose.com](mailto:rgarcia@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44556677','999-000-023','Av. Javier Prado 1400, San Isidro'),
('Dra. Elena','Ruiz Flores','[eruiz@clinicasanjose.com](mailto:eruiz@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','55667788','999-000-024','Av. Angamos 1500, Miraflores'),
('María','García López','[mgarcia@email.com](mailto:mgarcia@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','66778899','999-000-020','Jr. Los Olivos 100, San Miguel'),
('José','Martínez Ruiz','[jmartinez@email.com](mailto:jmartinez@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','77889900','999-000-021','Av. Túpac Amaru 200, Independencia'),
('Carmen','Vargas Flores','[cvargas@email.com](mailto:cvargas@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','88990011','999-000-022','Av. El Sol 300, Villa El Salvador'),
('Pedro','Ramírez Torres','[pramirez@email.com](mailto:pramirez@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99001122','999-000-023','Av. La Molina 400, La Molina'),
('Rosa','Jiménez Castro','[rjimenez@email.com](mailto:rjimenez@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','00112233','999-000-024','Av. Universitaria 500, San Miguel'),
('Miguel','Sánchez Morales','[msanchez@email.com](mailto:msanchez@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','11223344','999-000-025','Jr. Las Flores 600, Callao'),
('Elena','Castro Rojas','[ecastro@email.com](mailto:ecastro@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','22334455','999-000-026','Av. Brasil 700, Magdalena'),
('Ana','Torres Mendoza','[atorres@email.com](mailto:atorres@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','33445566','999-000-027','Av. Javier Prado 800, San Isidro'),
('Luis','Herrera Vega','[lherrera@email.com](mailto:lherrera@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44556677','999-000-028','Av. Angamos 900, Miraflores'),
('Patricia','Díaz Flores','[pdiaz@email.com](mailto:pdiaz@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','55667788','999-000-029','Av. Arequipa 1000, Lima'),
('Roberto','Morales Castro','[rmorales@email.com](mailto:rmorales@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','66778899','999-000-030','Av. Benavides 1100, Surco'),
('Sandra','Rojas Jiménez','[srojas@email.com](mailto:srojas@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','77889900','999-000-031','Av. Primavera 1200, San Borja'),
('Fernando','López Sánchez','[flopez@email.com](mailto:flopez@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','88990011','999-000-032','Av. Larco 1300, Miraflores'),
('Lucía','García Ruiz','[lgarcia@email.com](mailto:lgarcia@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99001122','999-000-033','Av. Salaverry 1400, Jesús María'),
('Diego','Martínez Herrera','[dmartinez@email.com](mailto:dmartinez@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','00112233','999-000-034','Av. Brasil 1500, Magdalena'),
('Valeria','Silva Torres','[vsilva@email.com](mailto:vsilva@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','11223344','999-000-035','Av. Universitaria 1600, San Miguel'),
('Andrés','Vargas Flores','[avargas@email.com](mailto:avargas@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','22334455','999-000-036','Av. Túpac Amaru 1700, Independencia'),
('Natalia','Ramírez Díaz','[nramirez@email.com](mailto:nramirez@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','33445566','999-000-037','Av. El Sol 1800, Villa El Salvador'),
('Sebastián','Castro Morales','[scastro@email.com](mailto:scastro@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44556677','999-000-038','Av. La Molina 1900, La Molina'),
('Gabriela','Jiménez Rojas','[gjimenez@email.com](mailto:gjimenez@email.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','55667788','999-000-039','Av. Primavera 2000, San Borja'),
('Carlos','López','[cajero@clinicasanjose.com](mailto:cajero@clinicasanjose.com)','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44332211','999-000-004','Av. Principal 123, Lima')
) AS v(nombre, apellido, email, contrasenia, dni, telefono, direccion)
WHERE NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.email = v.email);

-- ROLES de usuarios
INSERT INTO tiene_roles (usuario_id, rol_id)
SELECT v.usuario_id, v.rol_id
FROM (VALUES
(1,1),(2,1),
(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),
(18,3),(19,3),(20,3),(21,3),(22,3),(23,3),(24,3),(25,3),(26,3),(27,3),(28,3),(29,3),(30,3),(31,3),(32,3),(33,3),(34,3),(35,3),(36,3),
(37,4)
) AS v(usuario_id, rol_id)
WHERE NOT EXISTS (
SELECT 1 FROM tiene_roles tr WHERE tr.usuario_id = v.usuario_id AND tr.rol_id = v.rol_id
);

-- DOCTORES (IDs 1..15)
INSERT INTO doctores (usuario_id, especialidad_id, cmp, biografia)
SELECT v.usuario_id, v.especialidad_id, v.cmp, v.biografia
FROM (VALUES
(3, 1, 'CMP-12345', 'Médico general con 15 años de experiencia. Especialista en medicina preventiva y atención integral del adulto.'),
(4, 2, 'CMP-23456', 'Cardiólogo con 12 años de experiencia. Especialista en ecocardiografía y cateterismo cardíaco.'),
(5, 3, 'CMP-34567', 'Dermatóloga con 10 años de experiencia. Especialista en dermatología estética y cirugía dermatológica.'),
(6, 4, 'CMP-45678', 'Pediatra con 18 años de experiencia. Especialista en neonatología y medicina del adolescente.'),
(7, 5, 'CMP-56789', 'Ginecóloga con 14 años de experiencia. Especialista en ginecología oncológica y cirugía laparoscópica.'),
(8, 6, 'CMP-67890', 'Traumatólogo con 16 años de experiencia. Especialista en cirugía de columna y articulaciones.'),
(9, 7, 'CMP-78901', 'Neuróloga con 13 años de experiencia. Especialista en epilepsia y trastornos del movimiento.'),
(10, 8, 'CMP-89012', 'Oftalmóloga con 11 años de experiencia. Especialista en cirugía refractiva y retina.'),
(11, 9, 'CMP-90123', 'Psiquiatra con 9 años de experiencia. Especialista en trastornos del estado de ánimo y ansiedad.'),
(12,10, 'CMP-01234', 'Urólogo con 17 años de experiencia. Especialista en cirugía robótica y oncología urológica.'),
(13, 1, 'CMP-12346', 'Médico general con 8 años de experiencia. Especialista en medicina familiar y comunitaria.'),
(14, 2, 'CMP-23457', 'Cardióloga con 14 años de experiencia. Especialista en arritmias y marcapasos.'),
(15, 3, 'CMP-34568', 'Dermatólogo con 12 años de experiencia. Especialista en dermatología pediátrica y alérgica.'),
(16, 4, 'CMP-45679', 'Pediatra con 20 años de experiencia. Especialista en cardiología pediátrica y neonatología.'),
(17, 5, 'CMP-56790', 'Ginecólogo con 16 años de experiencia. Especialista en reproducción asistida y endocrinología ginecológica.')
) AS v(usuario_id, especialidad_id, cmp, biografia)
WHERE NOT EXISTS (SELECT 1 FROM doctores d WHERE d.usuario_id = v.usuario_id);

-- PACIENTES
INSERT INTO pacientes (usuario_id, tipo_sangre, alergias, condicion_cronica, historial_cirugias, historico_familiar, observaciones, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_relacion)
SELECT v.* FROM (VALUES
(18, 'O+', 'Penicilina', 'Hipertensión arterial', 'Apendicectomía (2015)', 'Padre diabético, madre hipertensa', 'Paciente controlada, toma medicación diaria', 'Juan García', '999-000-030', 'Esposo'),
(19, 'A+', 'Ninguna', 'Diabetes tipo 2', 'Colecistectomía (2018)', 'Madre diabética', 'Control glucémico regular', 'María Martínez', '999-000-031', 'Hija'),
(20, 'B+', 'Sulfamidas', 'Asma bronquial', 'Ninguna', 'Hermano asmático', 'Usa inhalador de rescate', 'Carlos Vargas', '999-000-032', 'Hermano'),
(21, 'AB+', 'Ninguna', 'Ninguna', 'Cesárea (2020)', 'Abuela con cáncer de mama', 'Embarazo controlado', 'Pedro Ramírez', '999-000-033', 'Esposo'),
(22, 'O-', 'Mariscos', 'Artritis reumatoide', 'Artroscopia rodilla (2019)', 'Madre con artritis', 'En tratamiento con inmunosupresores', 'Rosa Jiménez', '999-000-034', 'Hija'),
(23, 'A-', 'Ninguna', 'Ninguna', 'Ninguna', 'Ninguna', 'Paciente sano', 'Miguel Sánchez', '999-000-035', 'Padre'),
(24, 'B-', 'Polen', 'Migraña', 'Ninguna', 'Madre migrañosa', 'Crisis controladas con medicación', 'Elena Castro', '999-000-036', 'Madre'),
(25, 'O+', 'Ninguna', 'Hipotiroidismo', 'Tiroidectomía (2017)', 'Madre con enfermedad tiroidea', 'En tratamiento con levotiroxina', 'Ana Torres', '999-000-037', 'Hermana'),
(26, 'A+', 'Ibuprofeno', 'Gastritis crónica', 'Ninguna', 'Padre con úlcera gástrica', 'Evita AINEs, dieta blanda', 'Luis Herrera', '999-000-038', 'Esposo'),
(27, 'B+', 'Ninguna', 'Ninguna', 'Apendicectomía (2019)', 'Ninguna', 'Paciente sana, controles regulares', 'Patricia Díaz', '999-000-039', 'Madre'),
(28, 'AB+', 'Polen, ácaros', 'Rinitis alérgica', 'Ninguna', 'Padre alérgico', 'Uso de antihistamínicos estacionales', 'Roberto Morales', '999-000-040', 'Padre'),
(29, 'O-', 'Ninguna', 'Obesidad', 'Cirugía bariátrica (2021)', 'Familia con sobrepeso', 'Seguimiento nutricional post-cirugía', 'Sandra Rojas', '999-000-041', 'Hermana'),
(30, 'A-', 'Penicilina', 'Depresión', 'Ninguna', 'Madre con depresión', 'En tratamiento psiquiátrico', 'Fernando López', '999-000-042', 'Esposo'),
(31, 'B-', 'Ninguna', 'Ninguna', 'Colecistectomía (2020)', 'Ninguna', 'Paciente sana, sin patologías', 'Lucía García', '999-000-043', 'Madre'),
(32, 'O+', 'Mariscos, frutos secos', 'Dermatitis atópica', 'Ninguna', 'Hermano con eczema', 'Cuidado especial de la piel', 'Diego Martínez', '999-000-044', 'Padre'),
(33, 'A+', 'Ninguna', 'Ninguna', 'Ninguna', 'Ninguna', 'Paciente sana, deportista', 'Valeria Silva', '999-000-045', 'Madre'),
(34, 'B+', 'Polen', 'Asma leve', 'Ninguna', 'Padre asmático', 'Inhalador preventivo', 'Andrés Vargas', '999-000-046', 'Hermano'),
(35, 'AB-', 'Ninguna', 'Ninguna', 'Cesárea (2022)', 'Madre con diabetes gestacional', 'Control post-parto', 'Natalia Ramírez', '999-000-047', 'Esposo'),
(36, 'O-', 'Ninguna', 'Ninguna', 'Ninguna', 'Ninguna', 'Paciente sano, joven', 'Sebastián Castro', '999-000-048', 'Padre'),
(37, 'A-', 'Ninguna', 'Ninguna', 'Ninguna', 'Ninguna', 'Paciente sana, controles preventivos', 'Gabriela Jiménez', '999-000-049', 'Madre')
) AS v(usuario_id, tipo_sangre, alergias, condicion_cronica, historial_cirugias, historico_familiar, observaciones, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_relacion)
WHERE NOT EXISTS (SELECT 1 FROM pacientes p WHERE p.usuario_id = v.usuario_id);

-- CAJERO
INSERT INTO cajeros (usuario_id, nombre, usuario, contrasenia)
SELECT 37, 'Carlos López', 'cajero', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE NOT EXISTS (SELECT 1 FROM cajeros c WHERE c.usuario_id = 37);

-- SUPERADMINS
INSERT INTO superadmins (usuario_id, nombre, usuario, contrasenia)
SELECT v.usuario_id, v.nombre, v.usuario, v.contrasenia
FROM (VALUES
(1, 'Super Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'Carlos Mendoza', 'carlos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
) AS v(usuario_id, nombre, usuario, contrasenia)
WHERE NOT EXISTS (SELECT 1 FROM superadmins s WHERE s.usuario_id = v.usuario_id);

-- DOCTOR_SEDE (sin duplicados, sin IDs inexistentes)
INSERT INTO doctor_sede (sede_id, doctor_id, fecha_inicio)
SELECT v.sede_id, v.doctor_id, v.fecha_inicio
FROM (VALUES
(1, 1, '2024-01-01'),(1, 2, '2024-01-01'),(1, 5, '2024-01-01'),(1, 8, '2024-01-01'),(1,10, '2024-01-01'),(1,12, '2024-01-01'),(1,14, '2024-01-01'),(1,15, '2024-01-01'),
(2, 1, '2024-01-01'),(2, 2, '2024-01-01'),(2, 3, '2024-01-01'),(2, 6, '2024-01-01'),(2, 9, '2024-01-01'),(2,11, '2024-01-01'),(2,13, '2024-01-01'),(2,15, '2024-01-01'),
(3, 4, '2024-01-01'),(3, 7, '2024-01-01'),(3, 8, '2024-01-01'),(3, 9, '2024-01-01'),(3,11, '2024-01-01'),(3,13, '2024-01-01'),(3,15, '2024-01-01'),
(4, 5, '2024-01-01'),(4, 6, '2024-01-01'),(4, 7, '2024-01-01'),(4,10, '2024-01-01'),(4,12, '2024-01-01'),(4,14, '2024-01-01'),(4,15, '2024-01-01'),
(5, 3, '2024-01-01'),(5, 8, '2024-01-01'),(5, 9, '2024-01-01'),(5,10, '2024-01-01'),(5,11, '2024-01-01'),(5,13, '2024-01-01'),(5,14, '2024-01-01'),(5,15, '2024-01-01')
) AS v(sede_id, doctor_id, fecha_inicio)
WHERE NOT EXISTS (
SELECT 1 FROM doctor_sede ds WHERE ds.sede_id = v.sede_id AND ds.doctor_id = v.doctor_id
);

-- HORARIOS (idempotente)
INSERT INTO horarios (doctor_id, sede_id, dia_semana, hora_inicio, hora_fin)
SELECT v.* FROM (VALUES
(1,1,1,'08:00:00','12:00:00'),(1,1,1,'14:00:00','18:00:00'),(1,1,2,'08:00:00','12:00:00'),(1,1,2,'14:00:00','18:00:00'),(1,1,3,'08:00:00','12:00:00'),(1,1,3,'14:00:00','18:00:00'),(1,1,4,'08:00:00','12:00:00'),(1,1,4,'14:00:00','18:00:00'),(1,1,5,'08:00:00','12:00:00'),(1,1,5,'14:00:00','18:00:00'),
(1,2,1,'09:00:00','13:00:00'),(1,2,3,'09:00:00','13:00:00'),(1,2,5,'09:00:00','13:00:00'),
(2,1,1,'09:00:00','13:00:00'),(2,1,1,'15:00:00','19:00:00'),(2,1,3,'09:00:00','13:00:00'),(2,1,3,'15:00:00','19:00:00'),(2,1,5,'09:00:00','13:00:00'),(2,1,5,'15:00:00','19:00:00'),
(2,2,2,'10:00:00','14:00:00'),(2,2,4,'10:00:00','14:00:00'),
(3,2,2,'08:30:00','12:30:00'),(3,2,2,'14:30:00','18:30:00'),(3,2,4,'08:30:00','12:30:00'),(3,2,4,'14:30:00','18:30:00'),(3,2,6,'08:30:00','12:30:00'),
(3,5,1,'09:00:00','13:00:00'),(3,5,3,'09:00:00','13:00:00'),(3,5,5,'09:00:00','13:00:00'),
(4,3,1,'09:00:00','13:00:00'),(4,3,1,'15:00:00','19:00:00'),(4,3,3,'09:00:00','13:00:00'),(4,3,3,'15:00:00','19:00:00'),(4,3,5,'09:00:00','13:00:00'),(4,3,5,'15:00:00','19:00:00'),
(4,4,2,'08:00:00','12:00:00'),(4,4,4,'08:00:00','12:00:00'),(4,4,6,'08:00:00','12:00:00'),
(5,4,2,'08:00:00','12:00:00'),(5,4,2,'14:00:00','18:00:00'),(5,4,4,'08:00:00','12:00:00'),(5,4,4,'14:00:00','18:00:00'),(5,4,6,'08:00:00','12:00:00'),
(5,1,1,'10:00:00','14:00:00'),(5,1,3,'10:00:00','14:00:00'),(5,1,5,'10:00:00','14:00:00'),
(8,1,2,'08:00:00','12:00:00'),(8,1,2,'14:00:00','18:00:00'),(8,1,4,'08:00:00','12:00:00'),(8,1,4,'14:00:00','18:00:00'),(8,1,6,'08:00:00','12:00:00'),
(8,2,1,'09:00:00','13:00:00'),(8,2,3,'09:00:00','13:00:00'),(8,2,5,'09:00:00','13:00:00'),
(8,3,2,'10:00:00','14:00:00'),(8,3,4,'10:00:00','14:00:00'),
(9,1,1,'09:00:00','13:00:00'),(9,1,1,'15:00:00','19:00:00'),(9,1,3,'09:00:00','13:00:00'),(9,1,3,'15:00:00','19:00:00'),(9,1,5,'09:00:00','13:00:00'),(9,1,5,'15:00:00','19:00:00'),
(9,2,2,'10:00:00','14:00:00'),(9,2,4,'10:00:00','14:00:00'),
(9,3,1,'11:00:00','15:00:00'),(9,3,3,'11:00:00','15:00:00'),(9,3,5,'11:00:00','15:00:00'),
(10,1,2,'08:30:00','12:30:00'),(10,1,2,'14:30:00','18:30:00'),(10,1,4,'08:30:00','12:30:00'),(10,1,4,'14:30:00','18:30:00'),(10,1,6,'08:30:00','12:30:00'),
(10,4,1,'09:00:00','13:00:00'),(10,4,3,'09:00:00','13:00:00'),(10,4,5,'09:00:00','13:00:00'),
(10,5,2,'10:00:00','14:00:00'),(10,5,4,'10:00:00','14:00:00'),
(11,2,1,'09:00:00','13:00:00'),(11,2,1,'15:00:00','19:00:00'),(11,2,3,'09:00:00','13:00:00'),(11,2,3,'15:00:00','19:00:00'),(11,2,5,'09:00:00','13:00:00'),(11,2,5,'15:00:00','19:00:00'),
(11,3,2,'10:00:00','14:00:00'),(11,3,4,'10:00:00','14:00:00'),
(11,5,1,'11:00:00','15:00:00'),(11,5,3,'11:00:00','15:00:00'),(11,5,5,'11:00:00','15:00:00')
) AS v(doctor_id, sede_id, dia_semana, hora_inicio, hora_fin)
WHERE NOT EXISTS (
SELECT 1 FROM horarios h
WHERE h.doctor_id = v.doctor_id AND h.sede_id = v.sede_id
AND h.dia_semana = v.dia_semana AND h.hora_inicio = v.hora_inicio AND h.hora_fin = v.hora_fin
);

-- DIAGNOSTICOS (sin códigos repetidos)
INSERT INTO diagnosticos (codigo, nombre_enfermedad, descripcion)
SELECT v.codigo, v.nombre_enfermedad, v.descripcion
FROM (VALUES
('J06.9','Infección aguda de vías respiratorias superiores, no especificada','Cuadro catarral alto'),
('I10','Hipertensión esencial (primaria)','HTA sin causa secundaria'),
('E11.9','Diabetes mellitus tipo 2 sin complicaciones','DM2 control/seguimiento'),
('J45.9','Asma no especificada','Asma leve/moderada'),
('K21.9','Enfermedad por reflujo gastroesofágico s/e','ERGE'),
('F32.9','Episodio depresivo s/e','Depresión'),
('M54.5','Lumbalgia','Dolor lumbar'),
('L20.9','Dermatitis atópica s/e','Eccema atópico'),
('N39.0','Infección urinaria','ITU'),
('H10.9','Conjuntivitis s/e','Inflamación ocular')
) AS v(codigo, nombre_enfermedad, descripcion)
WHERE NOT EXISTS (SELECT 1 FROM diagnosticos d WHERE d.codigo = v.codigo);
