CREATE DATABASE php_scaffoldm;
GO

USE php_scaffoldm;
GO

-- Tabla: usuarios
CREATE TABLE usuarios (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'patient',
  created_at DATETIME NOT NULL DEFAULT GETDATE()
);
GO

-- Tabla: ubicaciones
CREATE TABLE ubicaciones (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  address VARCHAR(255) NULL,
  is_active BIT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT GETDATE()
);
GO

-- Tabla: servicios
CREATE TABLE servicios (
  id INT IDENTITY(1,1) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  duration_minutes INT NOT NULL DEFAULT 30,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_active BIT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT GETDATE()
);
GO

-- Tabla: citas
CREATE TABLE citas (
  id INT IDENTITY(1,1) PRIMARY KEY,
  user_id INT NOT NULL,
  location_id INT NULL,
  doctor_id INT NULL,
  service_id INT NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT GETDATE(),
  CONSTRAINT fk_appt_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_service FOREIGN KEY (service_id) REFERENCES servicios(id),
  -- Evitamos multiple cascade paths: cambiamos a NO ACTION
  CONSTRAINT fk_appt_doctor FOREIGN KEY (doctor_id) REFERENCES usuarios(id) ON DELETE NO ACTION,
  CONSTRAINT fk_appt_location FOREIGN KEY (location_id) REFERENCES ubicaciones(id) ON DELETE SET NULL
);
GO

-- Tabla: horarios_atencion
CREATE TABLE horarios_atencion (
  id INT IDENTITY(1,1) PRIMARY KEY,
  weekday TINYINT NOT NULL,
  open_time TIME NOT NULL,
  close_time TIME NOT NULL,
  is_closed BIT NOT NULL DEFAULT 0
);
GO

-- Tabla: horarios_doctores
CREATE TABLE horarios_doctores (
  id INT IDENTITY(1,1) PRIMARY KEY,
  doctor_id INT NOT NULL,
  location_id INT NOT NULL,
  weekday TINYINT NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_active BIT NOT NULL DEFAULT 1,
  CONSTRAINT fk_ds_doctor FOREIGN KEY (doctor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_ds_location FOREIGN KEY (location_id) REFERENCES ubicaciones(id) ON DELETE CASCADE
);
GO

-- Datos para usuarios
INSERT INTO usuarios (name, email, password, role, created_at) VALUES
('Super', 'super@demo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', GETDATE()),
('Doctor Demo', 'doctor@demo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', GETDATE()),
('Paciente Demo', 'paciente@demo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', GETDATE()),
('Cajero Demo', 'cajero@demo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', GETDATE());
GO

-- Datos para ubicaciones
INSERT INTO ubicaciones (name, address, is_active, created_at) VALUES
('Sede Miraflores', 'Av. Arequipa 3450, Miraflores - Lima', 1, GETDATE()),
('Sede San Isidro', 'Av. Javier Prado Oeste 950, San Isidro - Lima', 1, GETDATE()),
('Sede Surco', 'Av. Caminos del Inca 1545, Santiago de Surco - Lima', 1, GETDATE()),
('Sede San Borja', 'Av. San Borja Norte 1123, San Borja - Lima', 1, GETDATE()),
('Sede La Molina', 'Av. La Molina 1235, La Molina - Lima', 1, GETDATE()),
('Sede Magdalena', 'Av. Brasil 3050, Magdalena del Mar - Lima', 1, GETDATE()),
('Sede Jesús María', 'Av. Salaverry 2020, Jesús María - Lima', 1, GETDATE()),
('Sede San Miguel', 'Av. La Marina 2680, San Miguel - Lima', 1, GETDATE()),
('Sede Pueblo Libre', 'Av. Simón Bolívar 1150, Pueblo Libre - Lima', 1, GETDATE()),
('Sede Barranco', 'Av. República de Panamá 3080, Barranco - Lima', 1, GETDATE()),
('Sede Lince', 'Av. Arequipa 2150, Lince - Lima', 1, GETDATE()),
('Sede Cercado de Lima', 'Jr. de la Unión 600, Cercado de Lima - Lima', 1, GETDATE()),
('Sede Los Olivos', 'Av. Universitaria 3200, Los Olivos - Lima', 1, GETDATE()),
('Sede Chorrillos', 'Av. Huaylas 2500, Chorrillos - Lima', 1, GETDATE()),
('Sede San Juan de Lurigancho', 'Av. Próceres de la Independencia 1850, SJL - Lima', 1, GETDATE()),
('Sede San Juan de Miraflores', 'Av. Los Héroes 650, San Juan de Miraflores - Lima', 1, GETDATE()),
('Sede Villa El Salvador', 'Av. Pachacútec 4200, Villa El Salvador - Lima', 1, GETDATE()),
('Sede Ate', 'Carretera Central 6800, Ate - Lima', 1, GETDATE()),
('Sede Independencia', 'Av. Túpac Amaru 1200, Independencia - Lima', 1, GETDATE());
GO

-- Datos para servicios
INSERT INTO servicios (name, duration_minutes, price, is_active, created_at) VALUES
('Consulta', 30, 0.00, 1, GETDATE()),
('Corte', 45, 12.00, 1, GETDATE()),
('Mantenimiento', 60, 25.00, 1, GETDATE());
GO

-- Datos para citas
INSERT INTO citas (user_id, location_id, doctor_id, service_id, starts_at, ends_at, status, payment_status, notes, created_at) VALUES
(1, NULL, NULL, 1, '2025-09-29 09:00:00', '2025-09-29 09:30:00', 'confirmed', 'unpaid', '', '2025-09-28 05:19:43'),
(3, NULL, 2, 1, '2025-09-29 12:00:00', '2025-09-29 12:30:00', 'attended', 'paid', '', '2025-09-28 05:52:15'),
(3, 1, 2, 1, '2025-09-29 10:00:00', '2025-09-29 10:15:00', 'pending', 'unpaid', '', '2025-09-28 17:52:32'),
(3, 1, 2, 1, '2025-09-29 09:00:00', '2025-09-29 09:15:00', 'pending', 'unpaid', '', '2025-09-28 18:05:26'),
(3, 6, 2, 1, '2025-09-29 01:00:00', '2025-09-29 01:15:00', 'pending', 'unpaid', '', '2025-09-28 19:18:23'),
(3, 6, 2, 1, '2025-09-29 12:30:00', '2025-09-29 12:45:00', 'pending', 'unpaid', '', '2025-09-29 14:30:48');
GO

-- Datos para horarios_atencion
INSERT INTO horarios_atencion (weekday, open_time, close_time, is_closed) VALUES
(1, '09:00:00', '18:00:00', 0),
(2, '09:00:00', '18:00:00', 0),
(3, '09:00:00', '18:00:00', 0),
(4, '09:00:00', '18:00:00', 0),
(5, '09:00:00', '18:00:00', 0);
GO

-- Datos para horarios_doctores
INSERT INTO horarios_doctores (doctor_id, location_id, weekday, start_time, end_time, is_active) VALUES
(2, 1, 1, '09:00:00', '13:00:00', 1),
(2, 1, 3, '09:00:00', '13:00:00', 1),
(2, 1, 5, '09:00:00', '13:00:00', 1),
(2, 1, 1, '14:00:00', '18:00:00', 1),
(2, 1, 3, '14:00:00', '18:00:00', 1),
(2, 1, 5, '14:00:00', '18:00:00', 1),
(2, 2, 2, '09:00:00', '13:00:00', 1),
(2, 2, 4, '09:00:00', '13:00:00', 1),
(2, 2, 2, '14:00:00', '18:00:00', 1),
(2, 2, 4, '14:00:00', '18:00:00', 1),
(2, 6, 3, '15:00:00', '20:00:00', 1),
(2, 6, 1, '01:00:00', '20:00:00', 1),
(2, 10, 1, '15:00:00', '16:00:00', 1);
GO
