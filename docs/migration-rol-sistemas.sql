-- Migración: Añadir rol de Sistemas (rol=4)
-- Ejecutar en phpMyAdmin o consola MySQL

-- El campo 'rol' ya es TINYINT UNSIGNED, soporta valor 4 sin cambios de esquema.
-- Solo necesitas crear usuarios con rol=4.

-- Ejemplo: crear un usuario de sistemas
-- (cambiar datos según necesidad, contraseña: sistemas123)
INSERT INTO usuarios (nombre, email, password, rol, empresa, activo) VALUES
('Usuario Sistemas', 'sistemas@arelance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 'Arelance', 1);

-- Si quieres cambiar un usuario existente a rol sistemas:
-- UPDATE usuarios SET rol = 4 WHERE email = 'usuario@ejemplo.com';

-- Referencia de roles:
-- 1 = Administrador
-- 2 = Consultor  
-- 3 = Cliente
-- 4 = Sistemas
