<?php
/**
 * Middleware CsrfMiddleware
 * Protección contra ataques CSRF
 */

namespace App\Middleware;

use App\Utils\Auth;

class CsrfMiddleware
{
    /**
     * Verifica el token CSRF en peticiones POST
     */
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? '';

            if (!Auth::verifyCsrfToken($token)) {
                http_response_code(403);
                die('Token CSRF inválido. Recarga la página e inténtalo de nuevo.');
            }
        }
    }

    /**
     * Genera un campo hidden con el token CSRF para formularios
     */
    public static function field(): string
    {
        $token = Auth::generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
