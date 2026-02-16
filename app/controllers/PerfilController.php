<?php
/**
 * Controlador del Perfil de usuario
 */

namespace App\Controllers;

use App\Utils\Auth;
use App\Middleware\AuthMiddleware;

class PerfilController extends BaseController
{
    /**
     * GET /perfil.php - Muestra perfil del usuario
     */
    public function index(): void
    {
        AuthMiddleware::handle();

        $usuario = Auth::user();

        $this->renderWithLayout('perfil/index', [
            'pageTitle' => 'Mi Perfil',
            'usuario' => $usuario,
        ]);
    }
}
