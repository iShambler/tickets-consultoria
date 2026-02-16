<?php
/**
 * Bootstrap
 * Inicialización de la aplicación
 */

// Definir constantes
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_PATH', APP_ROOT . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Cargar autoloader
require_once APP_ROOT . '/app/autoload.php';

// Cargar helpers
require_once APP_ROOT . '/app/helpers/pagination.php';

// Cargar variables de entorno
\App\Utils\EnvLoader::load(APP_ROOT . '/.env');

// Configurar errores según entorno
if (\App\Utils\EnvLoader::isDev()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Cargar configuración
$appConfig = require APP_ROOT . '/app/config/app.php';

// Configurar zona horaria y locale
date_default_timezone_set($appConfig['timezone']);
setlocale(LC_TIME, $appConfig['locale']);

// Iniciar sesión (solo si no es API)
$isApiRequest = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/tickets/create.php');
if (!$isApiRequest) {
    \App\Utils\Auth::startSession();
}

// =============================================
// Funciones helper globales
// =============================================

function base_url(string $path = ''): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseUrl = dirname($scriptName);

    if ($baseUrl === '/' || $baseUrl === '\\') {
        $baseUrl = '';
    }

    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $protocol . '://' . $host . $baseUrl . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return base_url(ltrim($path, '/'));
}

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

function redirect(string $url): void
{
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = base_url($url);
    }
    header("Location: {$url}");
    exit;
}

function config(string $key, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require APP_ROOT . '/app/config/app.php';
    }
    $keys = explode('.', $key);
    $value = $config;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return $default;
        $value = $value[$k];
    }
    return $value;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatDate(?string $date, string $format = 'd/m/Y H:i'): string
{
    if (!$date) return '-';
    return (new DateTime($date))->format($format);
}

function formatTime(float $hours): string
{
    $h = floor($hours);
    $m = round(($hours - $h) * 60);
    return sprintf('%dh %02dm', $h, $m);
}

function getPrioridadBadgeClass(string $prioridad): string
{
    return match($prioridad) {
        'critica' => 'danger', 'alta' => 'warning', 'media' => 'primary', 'baja' => 'secondary', default => 'secondary'
    };
}

function getEstadoBadgeClass(string $estado): string
{
    return match($estado) {
        'nuevo' => 'info', 'asignado' => 'primary', 'en_progreso' => 'warning',
        'pendiente_cliente' => 'secondary', 'resuelto' => 'success', 'cerrado' => 'dark', default => 'secondary'
    };
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
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

/**
 * Genera campo hidden CSRF para formularios
 */
function csrf_field(): string
{
    return \App\Middleware\CsrfMiddleware::field();
}
