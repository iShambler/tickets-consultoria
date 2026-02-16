-- Base de datos para sistema de tickets de consultoría
-- Ejecutar este script para crear la estructura inicial

CREATE DATABASE IF NOT EXISTS ticket_consultoria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ticket_consultoria;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    empresa VARCHAR(150),
    telefono VARCHAR(20),
    rol TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '1=admin, 2=consultor, 3=cliente',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tipos de consultoría
CREATE TABLE tipos_consultoria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT UNSIGNED DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tickets
CREATE TABLE tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE COMMENT 'Formato: TICK-YYYY-NNNN',
    cliente_id INT UNSIGNED NOT NULL,
    tipo_consultoria_id INT UNSIGNED,
    consultor_id INT UNSIGNED NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    prioridad ENUM('baja', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
    estado ENUM('nuevo', 'asignado', 'en_progreso', 'pendiente_cliente', 'resuelto', 'cerrado') NOT NULL DEFAULT 'nuevo',
    tiempo_invertido DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Horas invertidas',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_resolucion TIMESTAMP NULL,
    fecha_cierre TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_consultoria_id) REFERENCES tipos_consultoria(id) ON DELETE SET NULL,
    FOREIGN KEY (consultor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_numero (numero),
    INDEX idx_cliente (cliente_id),
    INDEX idx_consultor (consultor_id),
    INDEX idx_estado (estado),
    INDEX idx_prioridad (prioridad),
    INDEX idx_fecha_creacion (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de comentarios/historial
CREATE TABLE ticket_comentarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    comentario TEXT NOT NULL,
    es_interno TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=visible solo para consultores/admin, 0=visible para cliente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de archivos adjuntos
CREATE TABLE ticket_archivos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    tipo_mime VARCHAR(100),
    tamano INT UNSIGNED NOT NULL COMMENT 'Bytes',
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de registro de tiempo
CREATE TABLE ticket_tiempo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    consultor_id INT UNSIGNED NOT NULL,
    horas DECIMAL(10,2) NOT NULL,
    descripcion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (consultor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_consultor (consultor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de cambios de estado
CREATE TABLE ticket_historial (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    campo VARCHAR(50) NOT NULL COMMENT 'estado, prioridad, consultor_id, etc',
    valor_anterior VARCHAR(255),
    valor_nuevo VARCHAR(255),
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_fecha (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar tipos de consultoría por defecto
INSERT INTO tipos_consultoria (nombre, descripcion, orden) VALUES
('Desarrollo Moodle', 'Desarrollo de plugins y personalizaciones en Moodle', 1),
('Soporte técnico', 'Soporte y resolución de incidencias técnicas', 2),
('Formación', 'Capacitación y formación en plataformas', 3),
('Consultoría estratégica', 'Asesoramiento estratégico en tecnología educativa', 4),
('Infraestructura', 'Configuración y mantenimiento de servidores', 5),
('Otro', 'Otras consultas no categorizadas', 99);

-- Insertar usuario administrador por defecto
-- Contraseña: admin123 (cambiar en producción)
INSERT INTO usuarios (nombre, email, password, rol, empresa) VALUES
('Administrador', 'admin@arelance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Arelance');

-- Trigger para generar número de ticket automáticamente
DELIMITER //
CREATE TRIGGER before_ticket_insert
BEFORE INSERT ON tickets
FOR EACH ROW
BEGIN
    DECLARE next_num INT;
    DECLARE current_year VARCHAR(4);
    
    SET current_year = YEAR(CURRENT_TIMESTAMP);
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero, -4) AS UNSIGNED)), 0) + 1 
    INTO next_num
    FROM tickets 
    WHERE numero LIKE CONCAT('TICK-', current_year, '-%');
    
    SET NEW.numero = CONCAT('TICK-', current_year, '-', LPAD(next_num, 4, '0'));
END//
DELIMITER ;
