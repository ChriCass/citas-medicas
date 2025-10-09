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

CREATE DATABASE med_database_v5;


USE med_database_v5;

-- ===== TABLAS =====

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'roles') AND type = N'U')
BEGIN
CREATE TABLE roles (
id INT IDENTITY(1,1) PRIMARY KEY,
nombre NVARCHAR(50) NOT NULL UNIQUE,
descripcion NVARCHAR(255) NULL
);
END;

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

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'tiene_roles') AND type = N'U')
BEGIN
CREATE TABLE tiene_roles (
id INT IDENTITY(1,1) PRIMARY KEY,
usuario_id INT NOT NULL,
rol_id INT NOT NULL,
creado_en DATETIME DEFAULT GETDATE(),
-- Se añade una restricción UNIQUE para asegurar que un usuario solo tenga un rol una vez
CONSTRAINT uq_tr_user_role UNIQUE (usuario_id, rol_id),
CONSTRAINT fk_tr_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
CONSTRAINT fk_tr_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE
);
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'especialidades') AND type = N'U')
BEGIN
CREATE TABLE especialidades (
id INT IDENTITY(1,1) PRIMARY KEY,
nombre NVARCHAR(150) NOT NULL UNIQUE, -- Aseguramos que los nombres de especialidad sean únicos
descripcion NVARCHAR(MAX) NULL
);
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'sedes') AND type = N'U')
BEGIN
CREATE TABLE sedes (
id INT IDENTITY(1,1) PRIMARY KEY,
nombre_sede NVARCHAR(150) NOT NULL UNIQUE, -- Aseguramos que los nombres de sede sean únicos
direccion NVARCHAR(255) NULL,
telefono NVARCHAR(50) NULL
);
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'doctores') AND type = N'U')
BEGIN
CREATE TABLE doctores (
id INT IDENTITY(1,1) PRIMARY KEY,
usuario_id INT NOT NULL UNIQUE, -- Un usuario solo puede ser un doctor
especialidad_id INT NULL,
cmp NVARCHAR(100) NULL UNIQUE, -- Se añade UNIQUE al CMP (Colegiatura Médica del Perú)
biografia NVARCHAR(MAX) NULL,
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_doctor_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
CONSTRAINT fk_doctor_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE SET NULL
);
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'pacientes') AND type = N'U')
BEGIN
CREATE TABLE pacientes (
id INT IDENTITY(1,1) PRIMARY KEY,
usuario_id INT NOT NULL UNIQUE, -- Un usuario solo puede ser un paciente
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

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'horarios_medicos') AND type = N'U')
BEGIN
CREATE TABLE horarios_medicos (
id INT IDENTITY(1,1) PRIMARY KEY,
doctor_id INT NOT NULL,
sede_id INT NULL,
fecha DATE NOT NULL,
hora_inicio TIME NOT NULL,
hora_fin TIME NOT NULL,
activo BIT DEFAULT 1,
observaciones NVARCHAR(255) NULL,
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_hm_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE CASCADE,
CONSTRAINT fk_hm_sede FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL,
CONSTRAINT uq_hm_doctor_fecha_hora UNIQUE (doctor_id, sede_id, fecha, hora_inicio, hora_fin)
);
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'diagnosticos') AND type = N'U')
BEGIN
CREATE TABLE diagnosticos (
id INT IDENTITY(1,1) PRIMARY KEY,
codigo NVARCHAR(50) NOT NULL UNIQUE,
nombre_enfermedad NVARCHAR(255) NOT NULL,
descripcion NVARCHAR(MAX) NULL
);
END;

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

-- CRUCIAL: Se añade un índice UNIQUE filtrado para evitar el doble-booking
-- Solo aplica a citas que no estén canceladas, asegurando que un doctor no tenga
-- dos citas en el mismo inicio de slot (fecha + hora_inicio).
CREATE UNIQUE INDEX uq_cita_doctor_slot ON citas (doctor_id, fecha, hora_inicio) WHERE estado != 'cancelada';
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'consultas') AND type = N'U')
BEGIN
CREATE TABLE consultas (
id INT IDENTITY(1,1) PRIMARY KEY,
cita_id INT NOT NULL UNIQUE, -- Una consulta corresponde a una única cita
diagnostico_id INT NULL,
observaciones NVARCHAR(MAX) NULL,
receta NVARCHAR(MAX) NULL,
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_consulta_cita FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
CONSTRAINT fk_consulta_diag FOREIGN KEY (diagnostico_id) REFERENCES diagnosticos(id) ON DELETE SET NULL
);
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'calendario') AND type = N'U')
BEGIN
CREATE TABLE calendario (
id INT IDENTITY(1,1) PRIMARY KEY,
doctor_id INT NULL,
cita_id INT NULL UNIQUE, -- Un registro de calendario corresponde a una única cita
fecha DATE NOT NULL,
hora_inicio TIME NOT NULL,
hora_fin TIME NOT NULL,
estado NVARCHAR(80) DEFAULT 'activo',
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_calendario_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE SET NULL,
CONSTRAINT fk_calendario_cita FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL
);
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'cajeros') AND type = N'U')
BEGIN
CREATE TABLE cajeros (
id INT IDENTITY(1,1) PRIMARY KEY,
usuario_id INT NOT NULL UNIQUE, -- 1:1 con usuario
nombre NVARCHAR(150) NULL,
usuario NVARCHAR(100) NULL,
contrasenia NVARCHAR(255) NULL,
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_cajero_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
END;

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'superadmins') AND type = N'U')
BEGIN
CREATE TABLE superadmins (
id INT IDENTITY(1,1) PRIMARY KEY,
usuario_id INT NOT NULL UNIQUE, -- 1:1 con usuario
nombre NVARCHAR(150) NULL,
usuario NVARCHAR(100) NULL,
contrasenia NVARCHAR(255) NULL,
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_superadmin_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
END;

-- ===== SEEDS IDEMPOTENTES =====

INSERT INTO roles (nombre, descripcion)
SELECT v.nombre, v.descripcion FROM (VALUES
('superadmin','Administrador del sistema'),('doctor','Médico'),('paciente','Paciente'),('cajero','Cajero/Recepción')
) v(nombre,descripcion)
WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.nombre = v.nombre);

INSERT INTO especialidades (nombre, descripcion)
SELECT v.nombre, v.descripcion FROM (VALUES
('Medicina General','Atención médica general y preventiva'),('Cardiología','Especialidad en enfermedades del corazón y sistema cardiovascular'),('Dermatología','Especialidad en enfermedades de la piel, pelo y uñas'),('Pediatría','Especialidad en medicina infantil y adolescente'),('Ginecología','Especialidad en salud femenina y reproductiva'),('Traumatología','Especialidad en lesiones del sistema musculoesquelético'),('Neurología','Especialidad en enfermedades del sistema nervioso'),('Oftalmología','Especialidad en enfermedades de los ojos'),('Psiquiatría','Especialidad en salud mental'),('Urología','Especialidad en el sistema urinario y reproductor masculino')
) v(nombre,descripcion)
WHERE NOT EXISTS (SELECT 1 FROM especialidades e WHERE e.nombre = v.nombre);

INSERT INTO sedes (nombre_sede, direccion, telefono)
SELECT v.nombre_sede, v.direccion, v.telefono FROM (VALUES
('Clínica San José - Sede Central','Av. Javier Prado Este 4200, San Isidro, Lima','01-234-5678'),
('Clínica San José - Sede Norte','Av. Túpac Amaru 1234, Independencia, Lima','01-234-5679'),
('Clínica San José - Sede Sur','Av. El Sol 567, Villa El Salvador, Lima','01-234-5680'),
('Clínica San José - Sede Este','Av. La Molina 890, La Molina, Lima','01-234-5681'),
('Clínica San José - Sede Oeste','Av. Universitaria 2345, San Miguel, Lima','01-234-5682')
) v(nombre_sede,direccion,telefono)
WHERE NOT EXISTS (SELECT 1 FROM sedes s WHERE s.nombre_sede = v.nombre_sede);

INSERT INTO usuarios (nombre, apellido, email, contrasenia, dni, telefono, direccion)
SELECT v.* FROM (VALUES
('Super','Admin','super@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','12345678','999-000-001','Av. Principal 123, Lima'),
('Carlos','Mendoza','admin@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','87654321','999-000-002','Av. Principal 123, Lima'),
('Dr. Juan Carlos','Pérez García','jperez@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','11223344','999-000-010','Av. Javier Prado 100, San Isidro'),
('Dra. María Elena','Rodríguez López','mrodriguez@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','22334455','999-000-011','Av. Angamos 200, Miraflores'),
('Dr. Roberto','Silva Martínez','rsilva@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','33445566','999-000-012','Av. Arequipa 300, Lima'),
('Dra. Ana Patricia','González Vega','agonzalez@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44556677','999-000-013','Av. Benavides 400, Surco'),
('Dr. Luis Fernando','Herrera Díaz','lherrera@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','55667788','999-000-014','Av. Primavera 500, San Borja'),
('Dr. Carlos Alberto','Mendoza Torres','cmendoza@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','66778899','999-000-015','Av. Larco 600, Miraflores'),
('Dra. Patricia','Vargas Flores','pvargas@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','77889900','999-000-016','Av. Salaverry 700, Jesús María'),
('Dr. Miguel Ángel','Ramírez Castro','mramirez@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','88990011','999-000-017','Av. Brasil 800, Magdalena'),
('Dra. Carmen','Jiménez Morales','cjimenez@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99001122','999-000-018','Av. Universitaria 900, San Miguel'),
('Dr. Fernando','Sánchez Rojas','fsanchez@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','00112233','999-000-019','Av. Túpac Amaru 1000, Independencia'),
('Dra. Lucía','Castro Díaz','lcastro@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','11223344','999-000-020','Av. El Sol 1100, Villa El Salvador'),
('Dr. Antonio','López Vega','alopez@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','22334455','999-000-021','Av. La Molina 1200, La Molina'),
('Dra. Isabel','Martínez Herrera','imartinez@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','33445566','999-000-022','Av. Primavera 1300, San Borja'),
('Dr. Rafael','García Torres','rgarcia@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44556677','999-000-023','Av. Javier Prado 1400, San Isidro'),
('Dra. Elena','Ruiz Flores','eruiz@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','55667788','999-000-024','Av. Angamos 1500, Miraflores'),
('María','García López','mgarcia@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','66778899','999-000-020','Jr. Los Olivos 100, San Miguel'),
('José','Martínez Ruiz','jmartinez@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','77889900','999-000-021','Av. Túpac Amaru 200, Independencia'),
('Carmen','Vargas Flores','cvargas@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','88990011','999-000-022','Av. El Sol 300, Villa El Salvador'),
('Pedro','Ramírez Torres','pramirez@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99001122','999-000-023','Av. La Molina 400, La Molina'),
('Rosa','Jiménez Castro','rjimenez@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','00112233','999-000-024','Av. Universitaria 500, San Miguel'),
('Miguel','Sánchez Morales','msanchez@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','11223344','999-000-025','Jr. Las Flores 600, Callao'),
('Elena','Castro Rojas','ecastro@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','22334455','999-000-026','Av. Brasil 700, Magdalena'),
('Ana','Torres Mendoza','atorres@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','33445566','999-000-027','Av. Javier Prado 800, San Isidro'),
('Luis','Herrera Vega','lherrera@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44556677','999-000-028','Av. Angamos 900, Miraflores'),
('Patricia','Díaz Flores','pdiaz@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','55667788','999-000-029','Av. Arequipa 1000, Lima'),
('Roberto','Morales Castro','rmorales@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','66778899','999-000-030','Av. Benavides 1100, Surco'),
('Sandra','Rojas Jiménez','srojas@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','77889900','999-000-031','Av. Primavera 1200, San Borja'),
('Fernando','López Sánchez','flopez@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','88990011','999-000-032','Av. Larco 1300, Miraflores'),
('Lucía','García Ruiz','lgarcia@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99001122','999-000-033','Av. Salaverry 1400, Jesús María'),
('Diego','Martínez Herrera','dmartinez@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','00112233','999-000-034','Av. Brasil 1500, Magdalena'),
('Valeria','Silva Torres','vsilva@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','11223344','999-000-035','Av. Universitaria 1600, San Miguel'),
('Andrés','Vargas Flores','avargas@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','22334455','999-000-036','Av. Túpac Amaru 1700, Independencia'),
('Natalia','Ramírez Díaz','nramirez@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','33445566','999-000-037','Av. El Sol 1800, Villa El Salvador'),
('Sebastián','Castro Morales','scastro@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44556677','999-000-038','Av. La Molina 1900, La Molina'),
('Gabriela','Jiménez Rojas','gjimenez@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','55667788','999-000-039','Av. Primavera 2000, San Borja'),
('Carlos','López','cajero@clinicasanjose.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','44332211','999-000-004','Av. Principal 123, Lima')
) v(nombre,apellido,email,contrasenia,dni,telefono,direccion)
WHERE NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.email = v.email);

INSERT INTO tiene_roles (usuario_id, rol_id)
SELECT v.usuario_id, v.rol_id FROM (VALUES
(1,1),(2,1),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),
(18,3),(19,3),(20,3),(21,3),(22,3),(23,3),(24,3),(25,3),(26,3),(27,3),(28,3),(29,3),(30,3),(31,3),(32,3),(33,3),(34,3),(35,3),(36,3),
(37,4)
) v(usuario_id,rol_id)
WHERE NOT EXISTS (SELECT 1 FROM tiene_roles tr WHERE tr.usuario_id=v.usuario_id AND tr.rol_id=v.rol_id);

INSERT INTO doctores (usuario_id, especialidad_id, cmp, biografia)
SELECT v.* FROM (VALUES
(3,1,'CMP-12345','Médico general con 15 años de experiencia. Especialista en medicina preventiva y atención integral del adulto.'),
(4,2,'CMP-23456','Cardiólogo con 12 años de experiencia. Especialista en ecocardiografía y cateterismo cardíaco.'),
(5,3,'CMP-34567','Dermatóloga con 10 años de experiencia. Especialista en dermatología estética y cirugía dermatológica.'),
(6,4,'CMP-45678','Pediatra con 18 años de experiencia. Especialista en neonatología y medicina del adolescente.'),
(7,5,'CMP-56789','Ginecóloga con 14 años de experiencia. Especialista en ginecología oncológica y cirugía laparoscópica.'),
(8,6,'CMP-67890','Traumatólogo con 16 años de experiencia. Especialista en cirugía de columna y articulaciones.'),
(9,7,'CMP-78901','Neuróloga con 13 años de experiencia. Especialista en epilepsia y trastornos del movimiento.'),
(10,8,'CMP-89012','Oftalmóloga con 11 años de experiencia. Especialista en cirugía refractiva y retina.'),
(11,9,'CMP-90123','Psiquiatra con 9 años de experiencia. Especialista en trastornos del estado de ánimo y ansiedad.'),
(12,10,'CMP-01234','Urólogo con 17 años de experiencia. Especialista en cirugía robótica y oncología urológica.'),
(13,1,'CMP-12346','Médico general con 8 años de experiencia. Especialista en medicina familiar y comunitaria.'),
(14,2,'CMP-23457','Cardióloga con 14 años de experiencia. Especialista en arritmias y marcapasos.'),
(15,3,'CMP-34568','Dermatólogo con 12 años de experiencia. Especialista en dermatología pediátrica y alérgica.'),
(16,4,'CMP-45679','Pediatra con 20 años de experiencia. Especialista en cardiología pediátrica y neonatología.'),
(17,5,'CMP-56790','Ginecólogo con 16 años de experiencia. Especialista en reproducción asistida y endocrinología ginecológica.')
) v(usuario_id,especialidad_id,cmp,biografia)
WHERE NOT EXISTS (SELECT 1 FROM doctores d WHERE d.usuario_id = v.usuario_id);

INSERT INTO pacientes (usuario_id, tipo_sangre, alergias, condicion_cronica, historial_cirugias, historico_familiar, observaciones, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_relacion)
SELECT v.* FROM (VALUES
(18,'O+','Penicilina','Hipertensión arterial','Apendicectomía (2015)','Padre diabético, madre hipertensa','Paciente controlada, toma medicación diaria','Juan García','999-000-030','Esposo'),
(19,'A+','Ninguna','Diabetes tipo 2','Colecistectomía (2018)','Madre diabética','Control glucémico regular','María Martínez','999-000-031','Hija'),
(20,'B+','Sulfamidas','Asma bronquial','Ninguna','Hermano asmático','Usa inhalador de rescate','Carlos Vargas','999-000-032','Hermano'),
(21,'AB+','Ninguna','Ninguna','Cesárea (2020)','Abuela con cáncer de mama','Embarazo controlado','Pedro Ramírez','999-000-033','Esposo'),
(22,'O-','Mariscos','Artritis reumatoide','Artroscopia rodilla (2019)','Madre con artritis','En tratamiento con inmunosupresores','Rosa Jiménez','999-000-034','Hija'),
(23,'A-','Ninguna','Ninguna','Ninguna','Ninguna','Paciente sano','Miguel Sánchez','999-000-035','Padre'),
(24,'B-','Polen','Migraña','Ninguna','Madre migrañosa','Crisis controladas con medicación','Elena Castro','999-000-036','Madre'),
(25,'O+','Ninguna','Hipotiroidismo','Tiroidectomía (2017)','Madre con enfermedad tiroidea','En tratamiento con levotiroxina','Ana Torres','999-000-037','Hermana'),
(26,'A+','Ibuprofeno','Gastritis crónica','Ninguna','Padre con úlcera gástrica','Evita AINEs, dieta blanda','Luis Herrera','999-000-038','Esposo'),
(27,'B+','Ninguna','Ninguna','Apendicectomía (2019)','Ninguna','Paciente sana, controles regulares','Patricia Díaz','999-000-039','Madre'),
(28,'AB+','Polen, ácaros','Rinitis alérgica','Ninguna','Padre alérgico','Uso de antihistamínicos estacionales','Roberto Morales','999-000-040','Padre'),
(29,'O-','Ninguna','Obesidad','Cirugía bariátrica (2021)','Familia con sobrepeso','Seguimiento nutricional post-cirugía','Sandra Rojas','999-000-041','Hermana'),
(30,'A-','Penicilina','Depresión','Ninguna','Madre con depresión','En tratamiento psiquiátrico','Fernando López','999-000-042','Esposo'),
(31,'B-','Ninguna','Ninguna','Colecistectomía (2020)','Ninguna','Paciente sana, sin patologías','Lucía García','999-000-043','Madre'),
(32,'O+','Mariscos, frutos secos','Dermatitis atópica','Ninguna','Hermano con eczema','Cuidado especial de la piel','Diego Martínez','999-000-044','Padre'),
(33,'A+','Ninguna','Ninguna','Ninguna','Ninguna','Paciente sana, deportista','Valeria Silva','999-000-045','Madre'),
(34,'B+','Polen','Asma leve','Ninguna','Padre asmático','Inhalador preventivo','Andrés Vargas','999-000-046','Hermano'),
(35,'AB-','Ninguna','Ninguna','Cesárea (2022)','Madre con diabetes gestacional','Control post-parto','Natalia Ramírez','999-000-047','Esposo'),
(36,'O-','Ninguna','Ninguna','Ninguna','Ninguna','Paciente sano, joven','Sebastián Castro','999-000-048','Padre'),
(37,'A-','Ninguna','Ninguna','Ninguna','Ninguna','Paciente sana, controles preventivos','Gabriela Jiménez','999-000-049','Madre')
) v(usuario_id,tipo_sangre,alergias,condicion_cronica,historial_cirugias,historico_familiar,observaciones,contacto_emergencia_nombre,contacto_emergencia_telefono,contacto_emergencia_relacion)
WHERE NOT EXISTS (SELECT 1 FROM pacientes p WHERE p.usuario_id = v.usuario_id);

INSERT INTO cajeros (usuario_id, nombre, usuario, contrasenia)
SELECT 37,'Carlos López','cajero','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE NOT EXISTS (SELECT 1 FROM cajeros c WHERE c.usuario_id=37);

INSERT INTO superadmins (usuario_id, nombre, usuario, contrasenia)
SELECT v.* FROM (VALUES
(1,'Super Admin','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2,'Carlos Mendoza','carlos','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
) v(usuario_id,nombre,usuario,contrasenia)
WHERE NOT EXISTS (SELECT 1 FROM superadmins s WHERE s.usuario_id=v.usuario_id);

INSERT INTO doctor_sede (sede_id, doctor_id, fecha_inicio)
SELECT v.* FROM (VALUES
(1,1,'2024-01-01'),(1,2,'2024-01-01'),(1,5,'2024-01-01'),(1,8,'2024-01-01'),(1,10,'2024-01-01'),(1,12,'2024-01-01'),(1,14,'2024-01-01'),(1,15,'2024-01-01'),
(2,1,'2024-01-01'),(2,2,'2024-01-01'),(2,3,'2024-01-01'),(2,6,'2024-01-01'),(2,9,'2024-01-01'),(2,11,'2024-01-01'),(2,13,'2024-01-01'),(2,15,'2024-01-01'),
(3,4,'2024-01-01'),(3,7,'2024-01-01'),(3,8,'2024-01-01'),(3,9,'2024-01-01'),(3,11,'2024-01-01'),(3,13,'2024-01-01'),(3,15,'2024-01-01'),
(4,5,'2024-01-01'),(4,6,'2024-01-01'),(4,7,'2024-01-01'),(4,10,'2024-01-01'),(4,12,'2024-01-01'),(4,14,'2024-01-01'),(4,15,'2024-01-01'),
(5,3,'2024-01-01'),(5,8,'2024-01-01'),(5,9,'2024-01-01'),(5,10,'2024-01-01'),(5,11,'2024-01-01'),(5,13,'2024-01-01'),(5,14,'2024-01-01'),(5,15,'2024-01-01')
) v(sede_id,doctor_id,fecha_inicio)
WHERE NOT EXISTS (SELECT 1 FROM doctor_sede ds WHERE ds.sede_id=v.sede_id AND ds.doctor_id=v.doctor_id);

INSERT INTO horarios_medicos (doctor_id, sede_id, fecha, hora_inicio, hora_fin)
SELECT v.* FROM (VALUES
-- Dr. Juan Carlos (doctor_id=1) - Semana del 13 al 31 de octubre 2025
(1,1,'2025-10-13','08:00:00','12:00:00'),(1,1,'2025-10-13','14:00:00','18:00:00'), -- Lunes
(1,1,'2025-10-14','08:00:00','12:00:00'),(1,1,'2025-10-14','14:00:00','18:00:00'), -- Martes
(1,1,'2025-10-15','08:00:00','12:00:00'),(1,1,'2025-10-15','14:00:00','18:00:00'), -- Miércoles
(1,1,'2025-10-16','08:00:00','12:00:00'),(1,1,'2025-10-16','14:00:00','18:00:00'), -- Jueves
(1,1,'2025-10-17','08:00:00','12:00:00'),(1,1,'2025-10-17','14:00:00','18:00:00'), -- Viernes
(1,2,'2025-10-13','09:00:00','13:00:00'),(1,2,'2025-10-15','09:00:00','13:00:00'),(1,2,'2025-10-17','09:00:00','13:00:00'),
-- Semana siguiente
(1,1,'2025-10-20','08:00:00','12:00:00'),(1,1,'2025-10-20','14:00:00','18:00:00'), -- Lunes
(1,1,'2025-10-21','08:00:00','12:00:00'),(1,1,'2025-10-21','14:00:00','18:00:00'), -- Martes
(1,1,'2025-10-22','08:00:00','12:00:00'),(1,1,'2025-10-22','14:00:00','18:00:00'), -- Miércoles
(1,1,'2025-10-23','08:00:00','12:00:00'),(1,1,'2025-10-23','14:00:00','18:00:00'), -- Jueves
(1,1,'2025-10-24','08:00:00','12:00:00'),(1,1,'2025-10-24','14:00:00','18:00:00'), -- Viernes
(1,2,'2025-10-20','09:00:00','13:00:00'),(1,2,'2025-10-22','09:00:00','13:00:00'),(1,2,'2025-10-24','09:00:00','13:00:00'),
-- Última semana
(1,1,'2025-10-27','08:00:00','12:00:00'),(1,1,'2025-10-27','14:00:00','18:00:00'), -- Lunes
(1,1,'2025-10-28','08:00:00','12:00:00'),(1,1,'2025-10-28','14:00:00','18:00:00'), -- Martes
(1,1,'2025-10-29','08:00:00','12:00:00'),(1,1,'2025-10-29','14:00:00','18:00:00'), -- Miércoles
(1,1,'2025-10-30','08:00:00','12:00:00'),(1,1,'2025-10-30','14:00:00','18:00:00'), -- Jueves
(1,1,'2025-10-31','08:00:00','12:00:00'),(1,1,'2025-10-31','14:00:00','18:00:00'), -- Viernes
(1,2,'2025-10-27','09:00:00','13:00:00'),(1,2,'2025-10-29','09:00:00','13:00:00'),(1,2,'2025-10-31','09:00:00','13:00:00'),

-- Dra. María Elena (doctor_id=2) - Patrón similar
(2,1,'2025-10-13','09:00:00','13:00:00'),(2,1,'2025-10-13','15:00:00','19:00:00'), -- Lunes
(2,1,'2025-10-15','09:00:00','13:00:00'),(2,1,'2025-10-15','15:00:00','19:00:00'), -- Miércoles  
(2,1,'2025-10-17','09:00:00','13:00:00'),(2,1,'2025-10-17','15:00:00','19:00:00'), -- Viernes
(2,2,'2025-10-14','10:00:00','14:00:00'),(2,2,'2025-10-16','10:00:00','14:00:00'), -- Martes, Jueves
(2,1,'2025-10-20','09:00:00','13:00:00'),(2,1,'2025-10-20','15:00:00','19:00:00'),
(2,1,'2025-10-22','09:00:00','13:00:00'),(2,1,'2025-10-22','15:00:00','19:00:00'),
(2,1,'2025-10-24','09:00:00','13:00:00'),(2,1,'2025-10-24','15:00:00','19:00:00'),
(2,2,'2025-10-21','10:00:00','14:00:00'),(2,2,'2025-10-23','10:00:00','14:00:00'),
(2,1,'2025-10-27','09:00:00','13:00:00'),(2,1,'2025-10-27','15:00:00','19:00:00'),
(2,1,'2025-10-29','09:00:00','13:00:00'),(2,1,'2025-10-29','15:00:00','19:00:00'),
(2,1,'2025-10-31','09:00:00','13:00:00'),(2,1,'2025-10-31','15:00:00','19:00:00'),
(2,2,'2025-10-28','10:00:00','14:00:00'),(2,2,'2025-10-30','10:00:00','14:00:00'),

-- Dr. Roberto (doctor_id=3) - Patrón similar
(3,2,'2025-10-14','08:30:00','12:30:00'),(3,2,'2025-10-14','14:30:00','18:30:00'), -- Martes
(3,2,'2025-10-16','08:30:00','12:30:00'),(3,2,'2025-10-16','14:30:00','18:30:00'), -- Jueves
(3,2,'2025-10-18','08:30:00','12:30:00'), -- Sábado
(3,5,'2025-10-13','09:00:00','13:00:00'),(3,5,'2025-10-15','09:00:00','13:00:00'),(3,5,'2025-10-17','09:00:00','13:00:00'),
(3,2,'2025-10-21','08:30:00','12:30:00'),(3,2,'2025-10-21','14:30:00','18:30:00'),
(3,2,'2025-10-23','08:30:00','12:30:00'),(3,2,'2025-10-23','14:30:00','18:30:00'),
(3,2,'2025-10-25','08:30:00','12:30:00'),
(3,5,'2025-10-20','09:00:00','13:00:00'),(3,5,'2025-10-22','09:00:00','13:00:00'),(3,5,'2025-10-24','09:00:00','13:00:00'),
(3,2,'2025-10-28','08:30:00','12:30:00'),(3,2,'2025-10-28','14:30:00','18:30:00'),
(3,2,'2025-10-30','08:30:00','12:30:00'),(3,2,'2025-10-30','14:30:00','18:30:00'),
(3,5,'2025-10-27','09:00:00','13:00:00'),(3,5,'2025-10-29','09:00:00','13:00:00'),(3,5,'2025-10-31','09:00:00','13:00:00')
) v(doctor_id,sede_id,fecha,hora_inicio,hora_fin)
WHERE NOT EXISTS (SELECT 1 FROM horarios_medicos hm WHERE hm.doctor_id=v.doctor_id AND hm.sede_id=v.sede_id AND hm.fecha=v.fecha AND hm.hora_inicio=v.hora_inicio AND hm.hora_fin=v.hora_fin);

INSERT INTO diagnosticos (codigo, nombre_enfermedad, descripcion)
SELECT v.* FROM (VALUES
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
) v(codigo,nombre_enfermedad,descripcion)
WHERE NOT EXISTS (SELECT 1 FROM diagnosticos d WHERE d.codigo=v.codigo);

-- ===== GENERADOR DE CITAS SIMPLIFICADO =====

DECLARE @start_date DATE = '2025-10-13';
DECLARE @end_date   DATE = '2025-10-31';
DECLARE @slot_minutes INT = 30;
DECLARE @max_slots_per_day INT = 3;

-- Primero eliminamos citas existentes en el rango de fechas para evitar duplicados
DELETE FROM calendario WHERE fecha BETWEEN @start_date AND @end_date;
DELETE FROM citas WHERE fecha BETWEEN @start_date AND @end_date;

;WITH nums AS (
SELECT 0 AS n
UNION ALL SELECT n+1 FROM nums WHERE n < 31
),
pacientes_cte AS (
SELECT id AS paciente_id FROM pacientes
),
-- Ahora simplemente obtenemos los horarios directamente por fecha
slots AS (
SELECT hm.doctor_id, hm.sede_id, hm.fecha,
CAST(DATEADD(MINUTE, n.n*@slot_minutes, CAST('1900-01-01 ' + CAST(hm.hora_inicio AS VARCHAR(8)) AS DATETIME)) AS TIME) AS hora_inicio,
CAST(DATEADD(MINUTE, (n.n+1) * @slot_minutes, CAST('1900-01-01 ' + CAST(hm.hora_inicio AS VARCHAR(8)) AS DATETIME)) AS TIME) AS hora_fin
FROM horarios_medicos hm
JOIN nums n ON DATEADD(MINUTE, (n.n+1) * @slot_minutes, CAST('1900-01-01 ' + CAST(hm.hora_inicio AS VARCHAR(8)) AS DATETIME)) <= CAST('1900-01-01 ' + CAST(hm.hora_fin AS VARCHAR(8)) AS DATETIME)
WHERE hm.fecha BETWEEN @start_date AND @end_date AND hm.activo = 1
),
slots_limit AS (
SELECT s.*, ROW_NUMBER() OVER (PARTITION BY s.doctor_id, s.fecha ORDER BY s.hora_inicio) AS rn
FROM slots s
),
slots_filtrados AS (
SELECT * FROM slots_limit WHERE rn <= @max_slots_per_day
),
slots_enumerados AS (
SELECT s.*, ROW_NUMBER() OVER (ORDER BY s.fecha, s.doctor_id, s.hora_inicio) AS slot_num
FROM slots_filtrados s
),
pacientes_enum AS (
SELECT p.paciente_id, ROW_NUMBER() OVER (ORDER BY p.paciente_id) AS pnum
FROM pacientes_cte p
),
pac_count AS (
SELECT COUNT(*) AS cnt FROM pacientes_cte
),
slots_con_paciente AS (
SELECT se.doctor_id, se.sede_id, se.fecha, se.hora_inicio, se.hora_fin,
pe.paciente_id,
((se.slot_num - 1) % pc.cnt) + 1 AS pmod
FROM slots_enumerados se
CROSS JOIN pac_count pc
JOIN pacientes_enum pe ON pe.pnum = (((se.slot_num - 1) % pc.cnt) + 1)
)
INSERT INTO citas (paciente_id, doctor_id, sede_id, fecha, hora_inicio, hora_fin, razon, estado)
SELECT scp.paciente_id, scp.doctor_id, scp.sede_id, scp.fecha, scp.hora_inicio, scp.hora_fin,
CASE (scp.doctor_id % 5)
WHEN 0 THEN N'Chequeo general'
WHEN 1 THEN N'Control de tratamiento'
WHEN 2 THEN N'Consulta de seguimiento'
WHEN 3 THEN N'Evaluación inicial'
ELSE N'Consulta'
END,
N'pendiente'
FROM slots_con_paciente scp
OPTION (MAXRECURSION 32767);

-- Se insertan los registros en el calendario
INSERT INTO calendario (doctor_id, cita_id, fecha, hora_inicio, hora_fin, estado)
SELECT c.doctor_id, c.id, c.fecha, c.hora_inicio, c.hora_fin, N'activo'
FROM citas c
WHERE c.fecha BETWEEN @start_date AND @end_date
AND NOT EXISTS (SELECT 1 FROM calendario cal WHERE cal.cita_id = c.id);
