<?php
/**
 * Bootstrap
 * Inicialización de la aplicación
 */

// Mostrar errores en desarrollo (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir constantes
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_PATH', APP_ROOT . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Cargar autoloader
require_once APP_ROOT . '/app/autoload.php';

// Cargar configuración
$appConfig = require APP_ROOT . '/app/config/app.php';

// Configurar zona horaria
date_default_timezone_set($appConfig['timezone']);

// Configurar locale
setlocale(LC_TIME, $appConfig['locale']);

// Iniciar sesión
\App\Utils\Auth::startSession();

// Función helper para obtener la URL base
function base_url(string $path = ''): string
{
    // Detectar automáticamente la base URL
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Solo quitar 1 nivel (el nombre del archivo) para mantener /public
    $baseUrl = dirname($scriptName);
    
    // Si estamos en la raíz, no agregar nada
    if ($baseUrl === '/' || $baseUrl === '\\') {
        $baseUrl = '';
    }
    
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    return $protocol . '://' . $host . $baseUrl . '/' . ltrim($path, '/');
}

// Función helper para obtener asset URL (CSS, JS, imágenes)
function asset(string $path): string
{
    return base_url(ltrim($path, '/'));
}

// Función helper para cargar vistas
function view(string $view, array $data = []): void
{
    extract($data);
    $viewFile = APP_ROOT . '/app/views/' . $view . '.php';
    
    if (file_exists($viewFile)) {
        require $viewFile;
    } else {
        die("Vista no encontrada: {$view}");
    }
}

// Función helper para redireccionar
function redirect(string $url): void
{
    // Si la URL no empieza con http, agregar base_url
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = base_url($url);
    }
    header("Location: {$url}");
    exit;
}

// Función helper para obtener la configuración
function config(string $key, $default = null)
{
    static $config = null;
    
    if ($config === null) {
        $config = require APP_ROOT . '/app/config/app.php';
    }
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

// Función helper para escapar HTML
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Función helper para formatear fechas
function formatDate(?string $date, string $format = 'd/m/Y H:i'): string
{
    if (!$date) {
        return '-';
    }
    
    $dt = new DateTime($date);
    return $dt->format($format);
}

// Función helper para formatear tiempo
function formatTime(float $hours): string
{
    $h = floor($hours);
    $m = round(($hours - $h) * 60);
    
    return sprintf('%dh %02dm', $h, $m);
}

// Función helper para obtener clase de badge según prioridad
function getPrioridadBadgeClass(string $prioridad): string
{
    return match($prioridad) {
        'critica' => 'danger',
        'alta' => 'warning',
        'media' => 'primary',
        'baja' => 'secondary',
        default => 'secondary'
    };
}

// Función helper para obtener clase de badge según estado
function getEstadoBadgeClass(string $estado): string
{
    return match($estado) {
        'nuevo' => 'info',
        'asignado' => 'primary',
        'en_progreso' => 'warning',
        'pendiente_cliente' => 'secondary',
        'resuelto' => 'success',
        'cerrado' => 'dark',
        default => 'secondary'
    };
}

// Función helper para mensajes flash
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function hasFlash(): bool
{
    return isset($_SESSION['flash']);
}
