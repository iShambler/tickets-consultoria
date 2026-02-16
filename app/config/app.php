<?php
/**
 * Configuración general de la aplicación
 */

return [
    'app_name' => 'Sistema de tickets de consultoría',
    'app_url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'Europe/Madrid',
    'locale' => 'es_ES',
    
    // Configuración de sesiones
    'session' => [
        'lifetime' => 7200, // 2 horas
        'cookie_name' => 'ticket_session',
        'secure' => false, // true en producción con HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    
    // Configuración de archivos
    'uploads' => [
        'max_size' => 10485760, // 10MB
        'allowed_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'],
        'path' => __DIR__ . '/../../public/uploads/'
    ],
    
    // Configuración de email
    'mail' => [
        'smtp_host' => getenv('SMTP_HOST') ?: 'localhost',
        'smtp_port' => getenv('SMTP_PORT') ?: 587,
        'smtp_user' => getenv('SMTP_USER') ?: '',
        'smtp_pass' => getenv('SMTP_PASS') ?: '',
        'from_email' => getenv('MAIL_FROM') ?: 'noreply@arelance.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Sistema de tickets'
    ],
    
    // Roles de usuario
    'roles' => [
        'admin' => 1,
        'consultor' => 2,
        'cliente' => 3
    ],
    
    // Estados de ticket
    'ticket_estados' => [
        'nuevo' => 'Nuevo',
        'asignado' => 'Asignado',
        'en_progreso' => 'En progreso',
        'pendiente_cliente' => 'Pendiente de cliente',
        'resuelto' => 'Resuelto',
        'cerrado' => 'Cerrado'
    ],
    
    // Prioridades
    'prioridades' => [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
        'critica' => 'Crítica'
    ]
];
