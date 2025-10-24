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
GO

USE med_database_v5;
GO

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
numero_historia_clinica NVARCHAR(20) NULL UNIQUE,
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
pago NVARCHAR(50) DEFAULT 'pendiente',
creado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_cita_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE NO ACTION,
CONSTRAINT fk_cita_doctor FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE NO ACTION,
CONSTRAINT fk_cita_sede FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL,
CONSTRAINT ck_cita_estado CHECK (estado IN ('pendiente', 'confirmado', 'atendido', 'cancelado')),
CONSTRAINT ck_cita_pago CHECK (pago IN ('pendiente', 'pagado', 'rechazado'))
);

CREATE INDEX idx_cita_fecha ON citas(fecha);
CREATE INDEX idx_cita_estado ON citas(estado);
CREATE INDEX idx_cita_pago ON citas(pago);

-- CRUCIAL: Se añade un índice UNIQUE para evitar el doble-booking
-- Asegurando que un doctor no tenga dos citas en el mismo inicio de slot (fecha + hora_inicio)
-- Nota: Para versiones de SQL Server 2008+, se puede usar un índice filtrado
-- CREATE UNIQUE INDEX uq_cita_doctor_slot ON citas (doctor_id, fecha, hora_inicio) WHERE estado != 'cancelada';
-- Para compatibilidad, usamos un índice normal
CREATE UNIQUE INDEX uq_cita_doctor_slot ON citas (doctor_id, fecha, hora_inicio);
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

IF NOT EXISTS (SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID(N'pagos') AND type = N'U')
BEGIN
CREATE TABLE pagos (
id INT IDENTITY(1,1) PRIMARY KEY,
cita_id INT NOT NULL,
cajero_id INT NOT NULL,
monto DECIMAL(10,2) NOT NULL,
metodo_pago NVARCHAR(50) NOT NULL DEFAULT 'efectivo',
estado NVARCHAR(50) NOT NULL DEFAULT 'completado',
fecha_pago DATETIME NOT NULL DEFAULT GETDATE(),
comprobante NVARCHAR(255) NULL,
observaciones NVARCHAR(MAX) NULL,
creado_en DATETIME DEFAULT GETDATE(),
actualizado_en DATETIME DEFAULT GETDATE(),
CONSTRAINT fk_pago_cita FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
CONSTRAINT fk_pago_cajero FOREIGN KEY (cajero_id) REFERENCES cajeros(id) ON DELETE CASCADE,
CONSTRAINT ck_pago_monto CHECK (monto > 0),
CONSTRAINT ck_pago_metodo CHECK (metodo_pago IN ('efectivo', 'tarjeta_debito', 'tarjeta_credito', 'transferencia', 'cheque')),
CONSTRAINT ck_pago_estado CHECK (estado IN ('pendiente', 'completado', 'rechazado'))
);

CREATE INDEX idx_pago_cita ON pagos(cita_id);
CREATE INDEX idx_pago_cajero ON pagos(cajero_id);
CREATE INDEX idx_pago_fecha ON pagos(fecha_pago);
CREATE INDEX idx_pago_estado ON pagos(estado);
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
('Clínica Internacional - Sede San Isidro','Av. Javier Prado Este 4200, San Isidro, Lima','01-234-5678'),
('Clínica Internacional - Sede Miraflores','Av. Angamos Este 1234, Miraflores, Lima','01-234-5679'),
('Clínica Internacional - Sede Surco','Av. Benavides 3456, Surco, Lima','01-234-5680'),
('Clínica Internacional - Sede La Molina','Av. La Molina 7890, La Molina, Lima','01-234-5681'),
('Clínica Internacional - Sede San Borja','Av. Primavera 2345, San Borja, Lima','01-234-5682'),
('Clínica Internacional - Sede Jesús María','Av. Salaverry 1234, Jesús María, Lima','01-234-5683'),
('Clínica Internacional - Sede San Miguel','Av. Universitaria 4567, San Miguel, Lima','01-234-5684'),
('Clínica Internacional - Sede Magdalena','Av. Brasil 8901, Magdalena, Lima','01-234-5685'),
('Clínica Internacional - Sede Independencia','Av. Túpac Amaru 2345, Independencia, Lima','01-234-5686'),
('Clínica Internacional - Sede Villa El Salvador','Av. El Sol 5678, Villa El Salvador, Lima','01-234-5687')
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

INSERT INTO pacientes (usuario_id, numero_historia_clinica, tipo_sangre, alergias, condicion_cronica, historial_cirugias, historico_familiar, observaciones, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_relacion)
SELECT v.* FROM (VALUES
(18,'HC-001','O+','Penicilina','Hipertensión arterial','Apendicectomía (2015)','Padre diabético, madre hipertensa','Paciente controlada, toma medicación diaria','Juan García','999-000-030','Esposo'),
(19,'HC-002','A+','Ninguna','Diabetes tipo 2','Colecistectomía (2018)','Madre diabética','Control glucémico regular','María Martínez','999-000-031','Hija'),
(20,'HC-003','B+','Sulfamidas','Asma bronquial','Ninguna','Hermano asmático','Usa inhalador de rescate','Carlos Vargas','999-000-032','Hermano'),
(21,'HC-004','AB+','Ninguna','Ninguna','Cesárea (2020)','Abuela con cáncer de mama','Embarazo controlado','Pedro Ramírez','999-000-033','Esposo'),
(22,'HC-005','O-','Mariscos','Artritis reumatoide','Artroscopia rodilla (2019)','Madre con artritis','En tratamiento con inmunosupresores','Rosa Jiménez','999-000-034','Hija'),
(23,'HC-006','A-','Ninguna','Ninguna','Ninguna','Ninguna','Paciente sano','Miguel Sánchez','999-000-035','Padre'),
(24,'HC-007','B-','Polen','Migraña','Ninguna','Madre migrañosa','Crisis controladas con medicación','Elena Castro','999-000-036','Madre'),
(25,'HC-008','O+','Ninguna','Hipotiroidismo','Tiroidectomía (2017)','Madre con enfermedad tiroidea','En tratamiento con levotiroxina','Ana Torres','999-000-037','Hermana'),
(26,'HC-009','A+','Ibuprofeno','Gastritis crónica','Ninguna','Padre con úlcera gástrica','Evita AINEs, dieta blanda','Luis Herrera','999-000-038','Esposo'),
(27,'HC-010','B+','Ninguna','Ninguna','Apendicectomía (2019)','Ninguna','Paciente sana, controles regulares','Patricia Díaz','999-000-039','Madre'),
(28,'HC-011','AB+','Polen, ácaros','Rinitis alérgica','Ninguna','Padre alérgico','Uso de antihistamínicos estacionales','Roberto Morales','999-000-040','Padre'),
(29,'HC-012','O-','Ninguna','Obesidad','Cirugía bariátrica (2021)','Familia con sobrepeso','Seguimiento nutricional post-cirugía','Sandra Rojas','999-000-041','Hermana'),
(30,'HC-013','A-','Penicilina','Depresión','Ninguna','Madre con depresión','En tratamiento psiquiátrico','Fernando López','999-000-042','Esposo'),
(31,'HC-014','B-','Ninguna','Ninguna','Colecistectomía (2020)','Ninguna','Paciente sana, sin patologías','Lucía García','999-000-043','Madre'),
(32,'HC-015','O+','Mariscos, frutos secos','Dermatitis atópica','Ninguna','Hermano con eczema','Cuidado especial de la piel','Diego Martínez','999-000-044','Padre'),
(33,'HC-016','A+','Ninguna','Ninguna','Ninguna','Ninguna','Paciente sana, deportista','Valeria Silva','999-000-045','Madre'),
(34,'HC-017','B+','Polen','Asma leve','Ninguna','Padre asmático','Inhalador preventivo','Andrés Vargas','999-000-046','Hermano'),
(35,'HC-018','AB-','Ninguna','Ninguna','Cesárea (2022)','Madre con diabetes gestacional','Control post-parto','Natalia Ramírez','999-000-047','Esposo'),
(36,'HC-019','O-','Ninguna','Ninguna','Ninguna','Ninguna','Paciente sano, joven','Sebastián Castro','999-000-048','Padre'),
(37,'HC-020','A-','Ninguna','Ninguna','Ninguna','Ninguna','Paciente sana, controles preventivos','Gabriela Jiménez','999-000-049','Madre')
) v(usuario_id,numero_historia_clinica,tipo_sangre,alergias,condicion_cronica,historial_cirugias,historico_familiar,observaciones,contacto_emergencia_nombre,contacto_emergencia_telefono,contacto_emergencia_relacion)
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
(3,5,'2025-10-27','09:00:00','13:00:00'),(3,5,'2025-10-29','09:00:00','13:00:00'),(3,5,'2025-10-31','09:00:00','13:00:00'),

-- Dr. Carlos (doctor_id=4) - Cardiología
(4,1,'2025-10-13','08:00:00','12:00:00'),(4,1,'2025-10-15','08:00:00','12:00:00'),(4,1,'2025-10-17','08:00:00','12:00:00'),
(4,3,'2025-10-14','14:00:00','18:00:00'),(4,3,'2025-10-16','14:00:00','18:00:00'),
(4,1,'2025-10-20','08:00:00','12:00:00'),(4,1,'2025-10-22','08:00:00','12:00:00'),(4,1,'2025-10-24','08:00:00','12:00:00'),
(4,3,'2025-10-21','14:00:00','18:00:00'),(4,3,'2025-10-23','14:00:00','18:00:00'),
(4,1,'2025-10-27','08:00:00','12:00:00'),(4,1,'2025-10-29','08:00:00','12:00:00'),(4,1,'2025-10-31','08:00:00','12:00:00'),
(4,3,'2025-10-28','14:00:00','18:00:00'),(4,3,'2025-10-30','14:00:00','18:00:00'),

-- Dra. Ana (doctor_id=5) - Dermatología
(5,2,'2025-10-13','09:00:00','13:00:00'),(5,2,'2025-10-15','09:00:00','13:00:00'),(5,2,'2025-10-17','09:00:00','13:00:00'),
(5,4,'2025-10-14','15:00:00','19:00:00'),(5,4,'2025-10-16','15:00:00','19:00:00'),
(5,2,'2025-10-20','09:00:00','13:00:00'),(5,2,'2025-10-22','09:00:00','13:00:00'),(5,2,'2025-10-24','09:00:00','13:00:00'),
(5,4,'2025-10-21','15:00:00','19:00:00'),(5,4,'2025-10-23','15:00:00','19:00:00'),
(5,2,'2025-10-27','09:00:00','13:00:00'),(5,2,'2025-10-29','09:00:00','13:00:00'),(5,2,'2025-10-31','09:00:00','13:00:00'),
(5,4,'2025-10-28','15:00:00','19:00:00'),(5,4,'2025-10-30','15:00:00','19:00:00'),

-- Dr. Luis (doctor_id=6) - Pediatría
(6,1,'2025-10-13','08:30:00','12:30:00'),(6,1,'2025-10-14','08:30:00','12:30:00'),(6,1,'2025-10-15','08:30:00','12:30:00'),
(6,5,'2025-10-16','14:30:00','18:30:00'),(6,5,'2025-10-17','14:30:00','18:30:00'),
(6,1,'2025-10-20','08:30:00','12:30:00'),(6,1,'2025-10-21','08:30:00','12:30:00'),(6,1,'2025-10-22','08:30:00','12:30:00'),
(6,5,'2025-10-23','14:30:00','18:30:00'),(6,5,'2025-10-24','14:30:00','18:30:00'),
(6,1,'2025-10-27','08:30:00','12:30:00'),(6,1,'2025-10-28','08:30:00','12:30:00'),(6,1,'2025-10-29','08:30:00','12:30:00'),
(6,5,'2025-10-30','14:30:00','18:30:00'),(6,5,'2025-10-31','14:30:00','18:30:00'),

-- Dra. Carmen (doctor_id=7) - Ginecología
(7,2,'2025-10-13','10:00:00','14:00:00'),(7,2,'2025-10-15','10:00:00','14:00:00'),(7,2,'2025-10-17','10:00:00','14:00:00'),
(7,6,'2025-10-14','16:00:00','20:00:00'),(7,6,'2025-10-16','16:00:00','20:00:00'),
(7,2,'2025-10-20','10:00:00','14:00:00'),(7,2,'2025-10-22','10:00:00','14:00:00'),(7,2,'2025-10-24','10:00:00','14:00:00'),
(7,6,'2025-10-21','16:00:00','20:00:00'),(7,6,'2025-10-23','16:00:00','20:00:00'),
(7,2,'2025-10-27','10:00:00','14:00:00'),(7,2,'2025-10-29','10:00:00','14:00:00'),(7,2,'2025-10-31','10:00:00','14:00:00'),
(7,6,'2025-10-28','16:00:00','20:00:00'),(7,6,'2025-10-30','16:00:00','20:00:00'),

-- Dr. Miguel (doctor_id=8) - Traumatología
(8,3,'2025-10-13','08:00:00','12:00:00'),(8,3,'2025-10-14','08:00:00','12:00:00'),(8,3,'2025-10-15','08:00:00','12:00:00'),
(8,7,'2025-10-16','14:00:00','18:00:00'),(8,7,'2025-10-17','14:00:00','18:00:00'),
(8,3,'2025-10-20','08:00:00','12:00:00'),(8,3,'2025-10-21','08:00:00','12:00:00'),(8,3,'2025-10-22','08:00:00','12:00:00'),
(8,7,'2025-10-23','14:00:00','18:00:00'),(8,7,'2025-10-24','14:00:00','18:00:00'),
(8,3,'2025-10-27','08:00:00','12:00:00'),(8,3,'2025-10-28','08:00:00','12:00:00'),(8,3,'2025-10-29','08:00:00','12:00:00'),
(8,7,'2025-10-30','14:00:00','18:00:00'),(8,7,'2025-10-31','14:00:00','18:00:00'),

-- Dra. Patricia (doctor_id=9) - Neurología
(9,4,'2025-10-13','09:30:00','13:30:00'),(9,4,'2025-10-15','09:30:00','13:30:00'),(9,4,'2025-10-17','09:30:00','13:30:00'),
(9,8,'2025-10-14','15:30:00','19:30:00'),(9,8,'2025-10-16','15:30:00','19:30:00'),
(9,4,'2025-10-20','09:30:00','13:30:00'),(9,4,'2025-10-22','09:30:00','13:30:00'),(9,4,'2025-10-24','09:30:00','13:30:00'),
(9,8,'2025-10-21','15:30:00','19:30:00'),(9,8,'2025-10-23','15:30:00','19:30:00'),
(9,4,'2025-10-27','09:30:00','13:30:00'),(9,4,'2025-10-29','09:30:00','13:30:00'),(9,4,'2025-10-31','09:30:00','13:30:00'),
(9,8,'2025-10-28','15:30:00','19:30:00'),(9,8,'2025-10-30','15:30:00','19:30:00'),

-- Dra. Rosa (doctor_id=10) - Oftalmología
(10,1,'2025-10-13','08:00:00','12:00:00'),(10,1,'2025-10-14','08:00:00','12:00:00'),(10,1,'2025-10-15','08:00:00','12:00:00'),
(10,2,'2025-10-16','14:00:00','18:00:00'),(10,2,'2025-10-17','14:00:00','18:00:00'),
(10,1,'2025-10-20','08:00:00','12:00:00'),(10,1,'2025-10-21','08:00:00','12:00:00'),(10,1,'2025-10-22','08:00:00','12:00:00'),
(10,2,'2025-10-23','14:00:00','18:00:00'),(10,2,'2025-10-24','14:00:00','18:00:00'),
(10,1,'2025-10-27','08:00:00','12:00:00'),(10,1,'2025-10-28','08:00:00','12:00:00'),(10,1,'2025-10-29','08:00:00','12:00:00'),
(10,2,'2025-10-30','14:00:00','18:00:00'),(10,2,'2025-10-31','14:00:00','18:00:00'),

-- Dr. Fernando (doctor_id=11) - Psiquiatría
(11,3,'2025-10-13','10:00:00','14:00:00'),(11,3,'2025-10-15','10:00:00','14:00:00'),(11,3,'2025-10-17','10:00:00','14:00:00'),
(11,4,'2025-10-14','16:00:00','20:00:00'),(11,4,'2025-10-16','16:00:00','20:00:00'),
(11,3,'2025-10-20','10:00:00','14:00:00'),(11,3,'2025-10-22','10:00:00','14:00:00'),(11,3,'2025-10-24','10:00:00','14:00:00'),
(11,4,'2025-10-21','16:00:00','20:00:00'),(11,4,'2025-10-23','16:00:00','20:00:00'),
(11,3,'2025-10-27','10:00:00','14:00:00'),(11,3,'2025-10-29','10:00:00','14:00:00'),(11,3,'2025-10-31','10:00:00','14:00:00'),
(11,4,'2025-10-28','16:00:00','20:00:00'),(11,4,'2025-10-30','16:00:00','20:00:00'),

-- Dr. Antonio (doctor_id=12) - Urología
(12,5,'2025-10-13','08:30:00','12:30:00'),(12,5,'2025-10-14','08:30:00','12:30:00'),(12,5,'2025-10-15','08:30:00','12:30:00'),
(12,6,'2025-10-16','14:30:00','18:30:00'),(12,6,'2025-10-17','14:30:00','18:30:00'),
(12,5,'2025-10-20','08:30:00','12:30:00'),(12,5,'2025-10-21','08:30:00','12:30:00'),(12,5,'2025-10-22','08:30:00','12:30:00'),
(12,6,'2025-10-23','14:30:00','18:30:00'),(12,6,'2025-10-24','14:30:00','18:30:00'),
(12,5,'2025-10-27','08:30:00','12:30:00'),(12,5,'2025-10-28','08:30:00','12:30:00'),(12,5,'2025-10-29','08:30:00','12:30:00'),
(12,6,'2025-10-30','14:30:00','18:30:00'),(12,6,'2025-10-31','14:30:00','18:30:00'),

-- Dr. Jorge (doctor_id=13) - Medicina General
(13,7,'2025-10-13','09:00:00','13:00:00'),(13,7,'2025-10-14','09:00:00','13:00:00'),(13,7,'2025-10-15','09:00:00','13:00:00'),
(13,8,'2025-10-16','15:00:00','19:00:00'),(13,8,'2025-10-17','15:00:00','19:00:00'),
(13,7,'2025-10-20','09:00:00','13:00:00'),(13,7,'2025-10-21','09:00:00','13:00:00'),(13,7,'2025-10-22','09:00:00','13:00:00'),
(13,8,'2025-10-23','15:00:00','19:00:00'),(13,8,'2025-10-24','15:00:00','19:00:00'),
(13,7,'2025-10-27','09:00:00','13:00:00'),(13,7,'2025-10-28','09:00:00','13:00:00'),(13,7,'2025-10-29','09:00:00','13:00:00'),
(13,8,'2025-10-30','15:00:00','19:00:00'),(13,8,'2025-10-31','15:00:00','19:00:00'),

-- Dra. Isabel (doctor_id=14) - Cardiología
(14,1,'2025-10-13','10:30:00','14:30:00'),(14,1,'2025-10-15','10:30:00','14:30:00'),(14,1,'2025-10-17','10:30:00','14:30:00'),
(14,2,'2025-10-14','16:30:00','20:30:00'),(14,2,'2025-10-16','16:30:00','20:30:00'),
(14,1,'2025-10-20','10:30:00','14:30:00'),(14,1,'2025-10-22','10:30:00','14:30:00'),(14,1,'2025-10-24','10:30:00','14:30:00'),
(14,2,'2025-10-21','16:30:00','20:30:00'),(14,2,'2025-10-23','16:30:00','20:30:00'),
(14,1,'2025-10-27','10:30:00','14:30:00'),(14,1,'2025-10-29','10:30:00','14:30:00'),(14,1,'2025-10-31','10:30:00','14:30:00'),
(14,2,'2025-10-28','16:30:00','20:30:00'),(14,2,'2025-10-30','16:30:00','20:30:00'),

-- Dr. Manuel (doctor_id=15) - Dermatología
(15,3,'2025-10-13','08:00:00','12:00:00'),(15,3,'2025-10-14','08:00:00','12:00:00'),(15,3,'2025-10-15','08:00:00','12:00:00'),
(15,4,'2025-10-16','14:00:00','18:00:00'),(15,4,'2025-10-17','14:00:00','18:00:00'),
(15,3,'2025-10-20','08:00:00','12:00:00'),(15,3,'2025-10-21','08:00:00','12:00:00'),(15,3,'2025-10-22','08:00:00','12:00:00'),
(15,4,'2025-10-23','14:00:00','18:00:00'),(15,4,'2025-10-24','14:00:00','18:00:00'),
(15,3,'2025-10-27','08:00:00','12:00:00'),(15,3,'2025-10-28','08:00:00','12:00:00'),(15,3,'2025-10-29','08:00:00','12:00:00'),
(15,4,'2025-10-30','14:00:00','18:00:00'),(15,4,'2025-10-31','14:00:00','18:00:00')
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

-- ===== DATOS DE PAGOS DE EJEMPLO =====
GO

-- Insertar algunos pagos de ejemplo para citas existentes
INSERT INTO pagos (cita_id, cajero_id, monto, metodo_pago, estado, fecha_pago, observaciones)
SELECT v.* FROM (VALUES
-- Pagos para las primeras 10 citas como ejemplo
(1, 1, 150.00, 'efectivo', 'completado', '2025-10-13 10:30:00', 'Pago en efectivo - consulta general'),
(2, 1, 200.00, 'tarjeta_debito', 'completado', '2025-10-13 11:00:00', 'Pago con tarjeta de débito'),
(3, 1, 180.00, 'efectivo', 'completado', '2025-10-13 11:30:00', 'Pago en efectivo - control'),
(4, 1, 220.00, 'tarjeta_credito', 'completado', '2025-10-13 12:00:00', 'Pago con tarjeta de crédito'),
(5, 1, 160.00, 'efectivo', 'completado', '2025-10-13 12:30:00', 'Pago en efectivo - seguimiento'),
(6, 1, 190.00, 'transferencia', 'completado', '2025-10-13 13:00:00', 'Transferencia bancaria'),
(7, 1, 170.00, 'efectivo', 'completado', '2025-10-13 13:30:00', 'Pago en efectivo - evaluación'),
(8, 1, 210.00, 'cheque', 'completado', '2025-10-13 14:00:00', 'Pago con cheque'),
(9, 1, 155.00, 'efectivo', 'completado', '2025-10-13 14:30:00', 'Pago en efectivo - consulta'),
(10, 1, 185.00, 'tarjeta_debito', 'completado', '2025-10-13 15:00:00', 'Pago con tarjeta de débito')
) v(cita_id, cajero_id, monto, metodo_pago, estado, fecha_pago, observaciones)
WHERE NOT EXISTS (SELECT 1 FROM pagos p WHERE p.cita_id = v.cita_id);

-- Actualizar el estado de pago de las citas que tienen pagos registrados
UPDATE citas 
SET pago = 'pagado' 
WHERE id IN (SELECT cita_id FROM pagos WHERE estado = 'completado');
GO

-- ===== TRIGGERS =====

-- Trigger para actualizar automáticamente el campo actualizado_en en la tabla pagos
IF NOT EXISTS (SELECT 1 FROM sys.triggers WHERE name = 'tr_pagos_actualizado_en')
BEGIN
EXEC('
CREATE TRIGGER tr_pagos_actualizado_en
ON pagos
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE pagos 
    SET actualizado_en = GETDATE()
    FROM pagos p
    INNER JOIN inserted i ON p.id = i.id;
END
');
END;

-- Trigger para actualizar automáticamente el estado de pago de la cita cuando se registra un pago
IF NOT EXISTS (SELECT 1 FROM sys.triggers WHERE name = 'tr_pagos_actualizar_cita')
BEGIN
EXEC('
CREATE TRIGGER tr_pagos_actualizar_cita
ON pagos
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE citas 
    SET pago = ''pagado''
    FROM citas c
    INNER JOIN inserted i ON c.id = i.cita_id
    WHERE i.estado = ''completado'';
END
');
END;
GO

-- ===== VISTAS ÚTILES PARA EL MÓDULO DE PAGOS =====

-- Vista para reportes de pagos con información completa
IF NOT EXISTS (SELECT 1 FROM sys.views WHERE name = 'v_pagos_completos')
BEGIN
EXEC('
CREATE VIEW v_pagos_completos AS
SELECT 
    p.id AS pago_id,
    p.monto,
    p.metodo_pago,
    p.estado AS estado_pago,
    p.fecha_pago,
    p.observaciones,
    p.comprobante,
    c.id AS cita_id,
    c.fecha AS fecha_cita,
    c.hora_inicio,
    c.hora_fin,
    c.razon,
    c.estado AS estado_cita,
    c.pago AS estado_pago_cita,
    -- Información del paciente
    u_pac.nombre AS paciente_nombre,
    u_pac.apellido AS paciente_apellido,
    u_pac.dni AS paciente_dni,
    u_pac.email AS paciente_email,
    u_pac.telefono AS paciente_telefono,
    -- Información del doctor
    u_doc.nombre AS doctor_nombre,
    u_doc.apellido AS doctor_apellido,
    esp.nombre AS especialidad_nombre,
    -- Información de la sede
    s.nombre_sede,
    s.direccion AS sede_direccion,
    s.telefono AS sede_telefono,
    -- Información del cajero
    u_caj.nombre AS cajero_nombre,
    u_caj.apellido AS cajero_apellido
FROM pagos p
INNER JOIN citas c ON p.cita_id = c.id
INNER JOIN pacientes pac ON c.paciente_id = pac.id
INNER JOIN usuarios u_pac ON pac.usuario_id = u_pac.id
INNER JOIN doctores doc ON c.doctor_id = doc.id
INNER JOIN usuarios u_doc ON doc.usuario_id = u_doc.id
LEFT JOIN especialidades esp ON doc.especialidad_id = esp.id
LEFT JOIN sedes s ON c.sede_id = s.id
INNER JOIN cajeros caj ON p.cajero_id = caj.id
INNER JOIN usuarios u_caj ON caj.usuario_id = u_caj.id
');
END;
GO

-- Vista para estadísticas de pagos por cajero
IF NOT EXISTS (SELECT 1 FROM sys.views WHERE name = 'v_estadisticas_pagos_cajero')
BEGIN
EXEC('
CREATE VIEW v_estadisticas_pagos_cajero AS
SELECT 
    caj.id AS cajero_id,
    u_caj.nombre + '' '' + u_caj.apellido AS cajero_nombre,
    COUNT(p.id) AS total_pagos,
    SUM(p.monto) AS monto_total,
    AVG(p.monto) AS monto_promedio,
    MIN(p.fecha_pago) AS primer_pago,
    MAX(p.fecha_pago) AS ultimo_pago,
    COUNT(CASE WHEN p.metodo_pago = ''efectivo'' THEN 1 END) AS pagos_efectivo,
    COUNT(CASE WHEN p.metodo_pago = ''tarjeta_debito'' THEN 1 END) AS pagos_tarjeta_debito,
    COUNT(CASE WHEN p.metodo_pago = ''tarjeta_credito'' THEN 1 END) AS pagos_tarjeta_credito,
    COUNT(CASE WHEN p.metodo_pago = ''transferencia'' THEN 1 END) AS pagos_transferencia,
    COUNT(CASE WHEN p.metodo_pago = ''cheque'' THEN 1 END) AS pagos_cheque
FROM cajeros caj
INNER JOIN usuarios u_caj ON caj.usuario_id = u_caj.id
LEFT JOIN pagos p ON caj.id = p.cajero_id
WHERE p.estado = ''completado''
GROUP BY caj.id, u_caj.nombre, u_caj.apellido
');
END;
GO

-- Vista para citas pendientes de pago
IF NOT EXISTS (SELECT 1 FROM sys.views WHERE name = 'v_citas_pendientes_pago')
BEGIN
EXEC('
CREATE VIEW v_citas_pendientes_pago AS
SELECT 
    c.id AS cita_id,
    c.fecha,
    c.hora_inicio,
    c.hora_fin,
    c.razon,
    c.estado,
    c.pago,
    -- Información del paciente
    u_pac.nombre AS paciente_nombre,
    u_pac.apellido AS paciente_apellido,
    u_pac.dni AS paciente_dni,
    u_pac.email AS paciente_email,
    u_pac.telefono AS paciente_telefono,
    -- Información del doctor
    u_doc.nombre AS doctor_nombre,
    u_doc.apellido AS doctor_apellido,
    esp.nombre AS especialidad_nombre,
    -- Información de la sede
    s.nombre_sede,
    s.direccion AS sede_direccion
FROM citas c
INNER JOIN pacientes pac ON c.paciente_id = pac.id
INNER JOIN usuarios u_pac ON pac.usuario_id = u_pac.id
INNER JOIN doctores doc ON c.doctor_id = doc.id
INNER JOIN usuarios u_doc ON doc.usuario_id = u_doc.id
LEFT JOIN especialidades esp ON doc.especialidad_id = esp.id
LEFT JOIN sedes s ON c.sede_id = s.id
WHERE c.estado = ''atendido'' 
AND c.pago = ''pendiente''
AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.cita_id = c.id)
');
END;
GO

-- ============================================
-- INSERTAR USUARIOS DEMO CON @demo.local
-- ============================================

-- Insertar usuarios demo
INSERT INTO usuarios (nombre, apellido, email, contrasenia, dni, telefono, direccion)
SELECT v.* FROM (VALUES
('Super','Demo','super@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99999999','999-999-001','Av. Demo 100, Lima'),
('Doctor','Demo','doctor@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99999998','999-999-002','Av. Demo 200, Lima'),
('Paciente','Demo','paciente@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99999997','999-999-003','Av. Demo 300, Lima'),
('Cajero','Demo','cajero@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','99999996','999-999-004','Av. Demo 400, Lima')
) v(nombre,apellido,email,contrasenia,dni,telefono,direccion)
WHERE NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.email = v.email);

-- Asignar roles a usuarios demo (asumiendo que serán los últimos IDs insertados)
DECLARE @super_id INT, @doctor_id INT, @paciente_id INT, @cajero_id INT;

SELECT @super_id = id FROM usuarios WHERE email = 'super@demo.local';
SELECT @doctor_id = id FROM usuarios WHERE email = 'doctor@demo.local';
SELECT @paciente_id = id FROM usuarios WHERE email = 'paciente@demo.local';
SELECT @cajero_id = id FROM usuarios WHERE email = 'cajero@demo.local';

-- Insertar relaciones en tiene_roles
INSERT INTO tiene_roles (usuario_id, rol_id)
SELECT v.usuario_id, v.rol_id FROM (VALUES
(@super_id,1), -- Superadmin
(@doctor_id,2), -- Doctor
(@paciente_id,3), -- Paciente
(@cajero_id,4) -- Cajero
) v(usuario_id,rol_id)
WHERE v.usuario_id IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM tiene_roles tr WHERE tr.usuario_id=v.usuario_id AND tr.rol_id=v.rol_id);

-- Insertar en tabla superadmins
INSERT INTO superadmins (usuario_id, nivel_acceso, departamento, observaciones)
SELECT @super_id, 'total', 'Administración', 'Usuario demo para pruebas de superadmin'
WHERE @super_id IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM superadmins sa WHERE sa.usuario_id = @super_id);

-- Insertar en tabla doctores
INSERT INTO doctores (usuario_id, especialidad_id, cmp, biografia)
SELECT @doctor_id, 1, 'CMP-DEMO', 'Doctor demo para pruebas del sistema'
WHERE @doctor_id IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM doctores d WHERE d.usuario_id = @doctor_id);

-- Insertar en tabla pacientes  
INSERT INTO pacientes (usuario_id, numero_historia_clinica, tipo_sangre, alergias, condicion_cronica, historial_cirugias, historico_familiar, observaciones, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_relacion)
SELECT @paciente_id, 'HC-DEMO', 'O+', 'Ninguna', 'Ninguna', 'Ninguna', 'Ninguna', 'Paciente demo para pruebas', 'Contacto Demo', '999-999-000', 'Familiar'
WHERE @paciente_id IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM pacientes p WHERE p.usuario_id = @paciente_id);

-- Insertar en tabla cajeros
INSERT INTO cajeros (usuario_id, numero_caja, sucursal, observaciones)
SELECT @cajero_id, 'CAJA-DEMO', 'Sucursal Demo', 'Cajero demo para pruebas del sistema'
WHERE @cajero_id IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM cajeros c WHERE c.usuario_id = @cajero_id);
