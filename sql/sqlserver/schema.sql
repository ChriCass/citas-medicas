-- SQL Server
IF OBJECT_ID('usuarios','U') IS NULL
BEGIN
  CREATE TABLE usuarios (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(100) NOT NULL,
    email NVARCHAR(150) NOT NULL UNIQUE,
    password NVARCHAR(255) NOT NULL,
    role NVARCHAR(20) NOT NULL DEFAULT 'patient',
    created_at DATETIME2 DEFAULT SYSDATETIME()
  );
END;

IF OBJECT_ID('servicios','U') IS NULL
BEGIN
  CREATE TABLE servicios (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(120) NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 30,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active BIT NOT NULL DEFAULT 1,
    created_at DATETIME2 DEFAULT SYSDATETIME()
  );
END;

IF OBJECT_ID('horarios_atencion','U') IS NULL
BEGIN
  CREATE TABLE horarios_atencion (
    id INT IDENTITY(1,1) PRIMARY KEY,
    weekday TINYINT NOT NULL,
    open_time TIME NOT NULL,
    close_time TIME NOT NULL,
    is_closed BIT NOT NULL DEFAULT 0
  );
END;

IF OBJECT_ID('citas','U') IS NULL
BEGIN
  CREATE TABLE citas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    starts_at DATETIME2 NOT NULL,
    ends_at DATETIME2 NOT NULL,
    status NVARCHAR(20) NOT NULL DEFAULT 'pending',
    notes NVARCHAR(255) NULL,
    created_at DATETIME2 DEFAULT SYSDATETIME()
  );
  CREATE INDEX idx_appt_window ON citas(starts_at, ends_at);
END;

IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name='fk_appt_user')
  ALTER TABLE citas ADD CONSTRAINT fk_appt_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE;
IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name='fk_appt_service')
  ALTER TABLE citas ADD CONSTRAINT fk_appt_service FOREIGN KEY (service_id) REFERENCES servicios(id) ON DELETE NO ACTION;

-- Horario
MERGE horarios_atencion AS t
USING (VALUES (1,'09:00:00','18:00:00',0),(2,'09:00:00','18:00:00',0),(3,'09:00:00','18:00:00',0),(4,'09:00:00','18:00:00',0),(5,'09:00:00','18:00:00',0)) AS s(weekday,open_time,close_time,is_closed)
ON (t.weekday=s.weekday)
WHEN MATCHED THEN UPDATE SET open_time=s.open_time, close_time=s.close_time, is_closed=s.is_closed
WHEN NOT MATCHED THEN INSERT (weekday, open_time, close_time, is_closed) VALUES (s.weekday,s.open_time,s.close_time,s.is_closed);

-- Servicios
MERGE servicios AS t
USING (VALUES ('Consulta',30,0.00,1),('Corte',45,12.00,1),('Mantenimiento',60,25.00,1)) AS s(name,duration_minutes,price,is_active)
ON (t.name=s.name)
WHEN MATCHED THEN UPDATE SET duration_minutes=s.duration_minutes, price=s.price, is_active=s.is_active
WHEN NOT MATCHED THEN INSERT (name,duration_minutes,price,is_active) VALUES (s.name,s.duration_minutes,s.price,s.is_active);

-- Usuarios demo (password: password)
MERGE usuarios AS t
USING (VALUES ('super@demo.local','Super','superadmin'),('doctor@demo.local','Doctor Demo','doctor'),('paciente@demo.local','Paciente Demo','patient'),('cajero@demo.local','Cajero Demo','cashier')) AS s(email,name,role)
ON (t.email=s.email)
WHEN MATCHED THEN UPDATE SET name=s.name, role=s.role
WHEN NOT MATCHED THEN INSERT (name,email,password,role) VALUES (s.name,s.email,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',s.role);
