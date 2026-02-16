<?php
/**
 * Controlador del Dashboard
 */

namespace App\Controllers;

use App\Models\Ticket;
use App\Utils\Auth;
use App\Utils\Database;
use App\Middleware\AuthMiddleware;

class DashboardController extends BaseController
{
    /**
     * GET /dashboard.php - Muestra el dashboard principal
     */
    public function index(): void
    {
        AuthMiddleware::handle();

        $usuario = Auth::user();

        // Filtros según rol
        $filtros = [];
        if (Auth::isCliente()) {
            $filtros['cliente_id'] = Auth::id();
        } elseif (Auth::isConsultor()) {
            $filtros['consultor_id'] = Auth::id();
        }

        // Filtros de búsqueda
        foreach (['estado', 'prioridad', 'busqueda', 'fuente'] as $filtro) {
            if (!empty($_GET[$filtro])) {
                $filtros[$filtro] = $_GET[$filtro];
            }
        }

        $allTickets = Ticket::getTickets($filtros);
        $pagTickets = paginarArray($allTickets, (int)($_GET['page'] ?? 1), (int)($_GET['per_page'] ?? 10));

        // Estadísticas
        $baseFiltro = Auth::isCliente() ? ['cliente_id' => Auth::id()] : [];
        $estadisticas = [
            'total' => count(Ticket::getTickets($baseFiltro)),
            'nuevos' => count(Ticket::getTickets(array_merge($baseFiltro, ['estado' => 'nuevo']))),
            'en_progreso' => count(Ticket::getTickets(array_merge($baseFiltro, ['estado' => 'en_progreso']))),
            'resueltos' => count(Ticket::getTickets(array_merge($baseFiltro, ['estado' => 'resuelto']))),
        ];

        // Estadísticas por fuente (solo staff)
        $fuenteStats = ['manual' => 0, 'email' => 0, 'api' => 0];
        if (Auth::isStaff()) {
            $fuenteResult = Database::fetchAll("SELECT fuente, COUNT(*) as total FROM tickets GROUP BY fuente");
            foreach ($fuenteResult as $row) {
                $fuenteStats[$row['fuente']] = (int) $row['total'];
            }
        }

        $this->renderWithLayout('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'usuario' => $usuario,
            'tickets' => $pagTickets['items'],
            'pagTickets' => $pagTickets['paginacion'],
            'estadisticas' => $estadisticas,
            'fuenteStats' => $fuenteStats,
            'fuenteActiva' => $_GET['fuente'] ?? '',
        ]);
    }
}
