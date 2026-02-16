<?php
/**
 * Clase Auth
 * Gestión de autenticación y sesiones
 */

namespace App\Utils;

use App\Models\Usuario;

class Auth
{
    /**
     * Inicia la sesión si no está iniciada
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../config/app.php';
            
            session_set_cookie_params([
                'lifetime' => $config['session']['lifetime'],
                'path' => '/',
                'domain' => '',
                'secure' => $config['session']['secure'],
                'httponly' => $config['session']['httponly'],
                'samesite' => $config['session']['samesite']
            ]);
            
            session_name($config['session']['cookie_name']);
            session_start();
        }
    }
    
    /**
     * Intenta autenticar un usuario
     */
    public static function login(string $email, string $password): bool
    {
        $usuario = Usuario::login($email, $password);
        
        if ($usuario) {
            self::startSession();
            
            $_SESSION['user_id'] = $usuario->getId();
            $_SESSION['user_nombre'] = $usuario->getNombre();
            $_SESSION['user_email'] = $usuario->getEmail();
            $_SESSION['user_rol'] = $usuario->getRol();
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Cierra la sesión del usuario
     */
    public static function logout(): void
    {
        self::startSession();
        
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    /**
     * Verifica si hay un usuario autenticado
     */
    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Obtiene el ID del usuario autenticado
     */
    public static function id(): ?int
    {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obtiene el usuario autenticado
     */
    public static function user(): ?Usuario
    {
        $id = self::id();
        return $id ? Usuario::findById($id) : null;
    }
    
    /**
     * Verifica si el usuario tiene un rol específico
     */
    public static function hasRole(int $rol): bool
    {
        self::startSession();
        return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === $rol;
    }
    
    /**
     * Verifica si el usuario es administrador
     */
    public static function isAdmin(): bool
    {
        return self::hasRole(1);
    }
    
    /**
     * Verifica si el usuario es consultor
     */
    public static function isConsultor(): bool
    {
        return self::hasRole(2);
    }
    
    /**
     * Verifica si el usuario es cliente
     */
    public static function isCliente(): bool
    {
        return self::hasRole(3);
    }
    
    /**
     * Verifica si el usuario es de sistemas
     */
    public static function isSistemas(): bool
    {
        return self::hasRole(4);
    }
    
    /**
     * Verifica si el usuario es staff (admin, consultor o sistemas)
     */
    public static function isStaff(): bool
    {
        return self::isAdmin() || self::isConsultor() || self::isSistemas();
    }
    
    /**
     * Requiere autenticación, redirige si no está autenticado
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    /**
     * Requiere un rol específico
     */
    public static function requireRole(int $rol): void
    {
        self::requireAuth();
        
        if (!self::hasRole($rol)) {
            header('HTTP/1.1 403 Forbidden');
            die('Acceso denegado');
        }
    }
    
    /**
     * Requiere ser staff (admin o consultor)
     */
    public static function requireStaff(): void
    {
        self::requireAuth();
        
        if (!self::isStaff()) {
            header('HTTP/1.1 403 Forbidden');
            die('Acceso denegado');
        }
    }
    
    /**
     * Verifica el tiempo de inactividad de la sesión
     */
    public static function checkTimeout(int $maxInactivity = 7200): bool
    {
        self::startSession();
        
        if (isset($_SESSION['login_time'])) {
            $inactiveTime = time() - $_SESSION['login_time'];
            
            if ($inactiveTime > $maxInactivity) {
                self::logout();
                return false;
            }
            
            $_SESSION['login_time'] = time();
        }
        
        return true;
    }
    
    /**
     * Genera un token CSRF
     */
    public static function generateCsrfToken(): string
    {
        self::startSession();
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verifica un token CSRF
     */
    public static function verifyCsrfToken(string $token): bool
    {
        self::startSession();
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
