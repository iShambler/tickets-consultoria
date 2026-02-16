-- ==========================================
-- Migración para Sistema de API de Tickets
-- ==========================================

-- 1. Modificar tabla tickets para soportar tickets por email/API
ALTER TABLE tickets 
ADD COLUMN fuente ENUM('manual', 'email', 'api') DEFAULT 'manual' AFTER estado,
ADD COLUMN email_message_id VARCHAR(255) NULL AFTER fuente,
ADD COLUMN email_original TEXT NULL AFTER email_message_id,
ADD COLUMN datos_ia JSON NULL AFTER email_original,
ADD COLUMN metadata JSON NULL AFTER datos_ia,
ADD INDEX idx_fuente (fuente),
ADD INDEX idx_email_message_id (email_message_id);

-- 2. Crear tabla de API Keys
CREATE TABLE IF NOT EXISTS api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo de la key',
    api_key VARCHAR(64) NOT NULL UNIQUE COMMENT 'Hash SHA256 de la key',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    permisos JSON NULL COMMENT 'Permisos específicos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    INDEX idx_api_key (api_key),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Crear tabla de logs de API
CREATE TABLE IF NOT EXISTS api_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT UNSIGNED NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    request_body TEXT NULL,
    response_code INT NOT NULL,
    response_time DECIMAL(10,3) NOT NULL COMMENT 'Tiempo en segundos',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL,
    INDEX idx_api_key (api_key_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Modificar tabla usuarios para soportar creación automática
ALTER TABLE usuarios 
ADD COLUMN creado_automaticamente TINYINT(1) DEFAULT 0 AFTER activo,
ADD COLUMN departamento VARCHAR(100) NULL AFTER empresa;

-- 5. Insertar API Key de prueba (CAMBIAR EN PRODUCCIÓN)
-- Key original: test_api_key_123456789
-- Hash SHA256: 9d4e1e23bd5b727046a9e3b4b7db57bd8d6ee684b1c9e1d5f1e7c1e8d9e0f1a2
INSERT INTO api_keys (nombre, api_key, activo, permisos) VALUES
('n8n Integration', '9d4e1e23bd5b727046a9e3b4b7db57bd8d6ee684b1c9e1d5f1e7c1e8d9e0f1a2', 1, '["tickets.create"]');

-- ==========================================
-- Verificación de la migración
-- ==========================================

SELECT 'Migración completada exitosamente' as status;

-- Para verificar las nuevas columnas:
SHOW COLUMNS FROM tickets WHERE Field IN ('fuente', 'email_message_id', 'email_original', 'datos_ia', 'metadata');

-- Para verificar las nuevas tablas:
SHOW TABLES LIKE 'api_%';
