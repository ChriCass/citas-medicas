-- ========================================
-- INSERCIÓN DE USUARIOS DEMO
-- ========================================
-- Contraseña para todos: "password"
-- Hash generado: $2y$10$fhsM920ifW.ptgzHy3lYReakN3tKjp0nqgQ4jRE6dMIAaPu5rnTXq

USE med_database_v5;

-- Insertar usuarios demo
INSERT INTO usuarios (nombre, apellido, email, contrasenia, dni, telefono, direccion)
VALUES 
    ('Super', 'Admin Demo', 'super@demo.local', '$2y$10$fhsM920ifW.ptgzHy3lYReakN3tKjp0nqgQ4jRE6dMIAaPu5rnTXq', '99999001', '999-DEMO-001', 'Demo Address 1'),
    ('Doctor', 'Demo', 'doctor@demo.local', '$2y$10$fhsM920ifW.ptgzHy3lYReakN3tKjp0nqgQ4jRE6dMIAaPu5rnTXq', '99999002', '999-DEMO-002', 'Demo Address 2'),
    ('Paciente', 'Demo', 'paciente@demo.local', '$2y$10$fhsM920ifW.ptgzHy3lYReakN3tKjp0nqgQ4jRE6dMIAaPu5rnTXq', '99999003', '999-DEMO-003', 'Demo Address 3'),
    ('Cajero', 'Demo', 'cajero@demo.local', '$2y$10$fhsM920ifW.ptgzHy3lYReakN3tKjp0nqgQ4jRE6dMIAaPu5rnTXq', '99999004', '999-DEMO-004', 'Demo Address 4');

-- Obtener los IDs de los usuarios recién creados y asignar roles
DECLARE @super_id INT, @doctor_id INT, @paciente_id INT, @cajero_id INT;
DECLARE @rol_superadmin INT, @rol_doctor INT, @rol_paciente INT, @rol_cajero INT;

-- Obtener IDs de usuarios
SELECT @super_id = id FROM usuarios WHERE email = 'super@demo.local';
SELECT @doctor_id = id FROM usuarios WHERE email = 'doctor@demo.local';
SELECT @paciente_id = id FROM usuarios WHERE email = 'paciente@demo.local';
SELECT @cajero_id = id FROM usuarios WHERE email = 'cajero@demo.local';

-- Obtener IDs de roles
SELECT @rol_superadmin = id FROM roles WHERE nombre = 'superadmin';
SELECT @rol_doctor = id FROM roles WHERE nombre = 'doctor';
SELECT @rol_paciente = id FROM roles WHERE nombre = 'paciente';
SELECT @rol_cajero = id FROM roles WHERE nombre = 'cajero';

-- Asignar roles
INSERT INTO tiene_roles (usuario_id, rol_id)
VALUES 
    (@super_id, @rol_superadmin),
    (@doctor_id, @rol_doctor),
    (@paciente_id, @rol_paciente),
    (@cajero_id, @rol_cajero);

-- Crear registro en tabla doctores para el usuario doctor demo
INSERT INTO doctores (usuario_id, especialidad_id, cmp, biografia)
VALUES (@doctor_id, 1, 'CMP-DEMO001', 'Doctor demo para pruebas del sistema. Especialista en medicina general.');

-- Crear registro en tabla pacientes para el usuario paciente demo
INSERT INTO pacientes (usuario_id, tipo_sangre, alergias, condicion_cronica, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_relacion)
VALUES (@paciente_id, 'O+', 'Ninguna', 'Ninguna', 'Contacto Demo', '999-DEMO-EMG', 'Familiar');

-- Crear registro en tabla cajeros para el usuario cajero demo
INSERT INTO cajeros (usuario_id, nombre, usuario, contrasenia)
VALUES (@cajero_id, 'Cajero Demo', 'cajero_demo', '$2y$10$fhsM920ifW.ptgzHy3lYReakN3tKjp0nqgQ4jRE6dMIAaPu5rnTXq');

-- Crear registro en tabla superadmins para el usuario superadmin demo
INSERT INTO superadmins (usuario_id, nombre, usuario, contrasenia)
VALUES (@super_id, 'Super Admin Demo', 'super_demo', '$2y$10$fhsM920ifW.ptgzHy3lYReakN3tKjp0nqgQ4jRE6dMIAaPu5rnTXq');

-- Asignar doctor demo a todas las sedes
INSERT INTO doctor_sede (sede_id, doctor_id, fecha_inicio)
SELECT s.id, d.id, '2024-01-01'
FROM sedes s, doctores d
WHERE d.usuario_id = @doctor_id;

-- Crear algunos horarios para el doctor demo
INSERT INTO horarios_medicos (doctor_id, sede_id, fecha, hora_inicio, hora_fin, observaciones)
SELECT d.id, 1, '2025-10-15', '09:00:00', '17:00:00', 'Horario demo - Doctor disponible todo el día'
FROM doctores d
WHERE d.usuario_id = @doctor_id;

-- Mostrar resultados
SELECT 'Usuarios demo creados exitosamente:' AS resultado;
SELECT u.email, r.nombre as rol, u.nombre + ' ' + u.apellido as nombre_completo
FROM usuarios u 
JOIN tiene_roles tr ON u.id = tr.usuario_id 
JOIN roles r ON tr.rol_id = r.id 
WHERE u.email LIKE '%@demo.local'
ORDER BY r.nombre;