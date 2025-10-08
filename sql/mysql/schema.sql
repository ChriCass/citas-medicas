-- MySQL / MariaDB
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'patient',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS servicios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS horarios_atencion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  weekday TINYINT UNSIGNED NOT NULL,
  open_time TIME NOT NULL,
  close_time TIME NOT NULL,
  is_closed TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS citas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appt_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_service FOREIGN KEY (service_id) REFERENCES servicios(id) ON DELETE RESTRICT,
  INDEX idx_appt_window (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Horario (L-V 9-18)
INSERT INTO horarios_atencion (weekday, open_time, close_time, is_closed) VALUES
(1,'09:00:00','18:00:00',0),(2,'09:00:00','18:00:00',0),(3,'09:00:00','18:00:00',0),(4,'09:00:00','18:00:00',0),(5,'09:00:00','18:00:00',0)
ON DUPLICATE KEY UPDATE open_time=VALUES(open_time), close_time=VALUES(close_time), is_closed=VALUES(is_closed);

-- Servicios
INSERT INTO servicios (name,duration_minutes,price,is_active) VALUES
('Consulta',30,0.00,1),('Corte',45,12.00,1),('Mantenimiento',60,25.00,1)
ON DUPLICATE KEY UPDATE duration_minutes=VALUES(duration_minutes), price=VALUES(price), is_active=VALUES(is_active);

-- Usuarios demo (password: password)
INSERT INTO usuarios (name,email,password,role) VALUES
('Super','super@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','superadmin'),
('Doctor Demo','doctor@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','doctor'),
('Paciente Demo','paciente@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','patient'),
('Cajero Demo','cajero@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cashier')
ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role);
