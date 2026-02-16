<?php
/**
 * Middleware AuthMiddleware
 * Verifica que el usuario esté autenticado
 */

namespace App\Middleware;

use App\Utils\Auth;

class AuthMiddleware
{
    /**
     * Requiere que el usuario esté autenticado
     */
    public static function handle(): void
    {
        if (!Auth::check()) {
            redirect('login.php');
        }
    }

    /**
     * Requiere rol de administrador (solo admin puro)
     */
    public static function requireAdmin(): void
    {
        self::handle();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            die('Acceso denegado: se requiere rol de administrador');
        }
    }

    /**
     * Requiere rol de administrador o sistemas
     * (para vistas de gestión: usuarios, reportes, tipos, api-keys, etc.)
     */
    public static function requireAdminOrSistemas(): void
    {
        self::handle();
        if (!Auth::isAdmin() && !Auth::isSistemas()) {
            http_response_code(403);
            die('Acceso denegado: se requiere rol de administrador o sistemas');
        }
    }

    /**
     * Requiere rol de staff (admin, consultor o sistemas)
     */
    public static function requireStaff(): void
    {
        self::handle();
        if (!Auth::isStaff()) {
            http_response_code(403);
            die('Acceso denegado: se requiere rol de staff');
        }
    }

    /**
     * Redirige si ya está autenticado (para login/registro)
     */
    public static function guest(): void
    {
        if (Auth::check()) {
            redirect('dashboard.php');
        }
    }
}
