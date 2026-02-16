<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Ticket;
use App\Utils\Auth;

Auth::requireAuth();
Auth::requireStaff();

// Obtener filtros
$filtros = [];
if (!empty($_GET['prioridad'])) {
    $filtros['prioridad'] = $_GET['prioridad'];
}
if (!empty($_GET['busqueda'])) {
    $filtros['busqueda'] = $_GET['busqueda'];
}
if (!empty($_GET['consultor'])) {
    $filtros['consultor_id'] = $_GET['consultor'];
}

// Tickets activos (todo menos resuelto y cerrado)
$todosTickets = Ticket::getTickets($filtros);
$ticketsActivos = [];
$ticketsResueltos = [];

foreach ($todosTickets as $ticket) {
    if (in_array($ticket->getEstado(), ['resuelto', 'cerrado'])) {
        $ticketsResueltos[] = $ticket;
    } else {
        $ticketsActivos[] = $ticket;
    }
}

$pageTitle = 'Todos los Tickets';
include __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../app/views/layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Todos los Tickets</h1>
            </div>
            
            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="busqueda" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                   placeholder="Número, título o descripción" 
                                   value="<?= e($_GET['busqueda'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="prioridad" class="form-label">Prioridad</label>
                            <select class="form-select" id="prioridad" name="prioridad">
                                <option value="">Todas</option>
                                <?php foreach (config('prioridades') as $key => $value): ?>
                                    <option value="<?= e($key) ?>" <?= ($_GET['prioridad'] ?? '') === $key ? 'selected' : '' ?>>
                                        <?= e($value) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-secondary w-100">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="<?= base_url('tickets.php') ?>" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- TICKETS ACTIVOS -->
            <div class="card mb-4">
                <div class="card-header bg-warning bg-opacity-10">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-circle text-warning"></i> 
                        Tickets activos 
                        <span class="badge bg-warning text-dark"><?= count($ticketsActivos) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($ticketsActivos)): ?>
                        <div class="alert alert-success m-3">
                            <i class="bi bi-check-circle"></i> No hay tickets pendientes. ¡Todo al día!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Título</th>
                                        <th>Cliente</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Consultor</th>
                                        <th>Tiempo</th>
                                        <th>Creado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ticketsActivos as $ticket): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($ticket->getNumero()) ?></strong>
                                                <?php if ($ticket->getFuente() === 'email'): ?>
                                                    <br><span class="badge bg-light text-dark border" style="font-size: 0.65rem"><i class="bi bi-envelope-fill text-primary"></i> Email</span>
                                                <?php elseif ($ticket->getFuente() === 'api'): ?>
                                                    <br><span class="badge bg-light text-dark border" style="font-size: 0.65rem"><i class="bi bi-code-slash text-success"></i> API</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($ticket->getTitulo()) ?></td>
                                            <td>
                                                <small>
                                                    <?= e($ticket->cliente_nombre ?? '') ?><br>
                                                    <span class="text-muted"><?= e($ticket->cliente_empresa ?? '') ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getPrioridadBadgeClass($ticket->getPrioridad()) ?>">
                                                    <?= e(config('prioridades')[$ticket->getPrioridad()]) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getEstadoBadgeClass($ticket->getEstado()) ?>">
                                                    <?= e(config('ticket_estados')[$ticket->getEstado()]) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?= e($ticket->consultor_nombre ?? 'Sin asignar') ?></small>
                                            </td>
                                            <td>
                                                <small><?= formatTime($ticket->getTiempoInvertido()) ?></small>
                                            </td>
                                            <td>
                                                <small><?= formatDate($ticket->getFechaCreacion(), 'd/m/Y') ?></small>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('ticket.php?id=' . $ticket->getId()) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TICKETS RESUELTOS / CERRADOS -->
            <div class="card">
                <div class="card-header bg-success bg-opacity-10">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle text-success"></i> 
                        Resueltos / Cerrados
                        <span class="badge bg-success"><?= count($ticketsResueltos) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($ticketsResueltos)): ?>
                        <div class="alert alert-info m-3">
                            <i class="bi bi-info-circle"></i> No hay tickets resueltos aún.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Título</th>
                                        <th>Cliente</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Consultor</th>
                                        <th>Tiempo</th>
                                        <th>Resuelto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ticketsResueltos as $ticket): ?>
                                        <tr class="table-light">
                                            <td>
                                                <strong><?= e($ticket->getNumero()) ?></strong>
                                                <?php if ($ticket->getFuente() === 'email'): ?>
                                                    <br><span class="badge bg-light text-dark border" style="font-size: 0.65rem"><i class="bi bi-envelope-fill text-primary"></i> Email</span>
                                                <?php elseif ($ticket->getFuente() === 'api'): ?>
                                                    <br><span class="badge bg-light text-dark border" style="font-size: 0.65rem"><i class="bi bi-code-slash text-success"></i> API</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($ticket->getTitulo()) ?></td>
                                            <td>
                                                <small>
                                                    <?= e($ticket->cliente_nombre ?? '') ?><br>
                                                    <span class="text-muted"><?= e($ticket->cliente_empresa ?? '') ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getPrioridadBadgeClass($ticket->getPrioridad()) ?>">
                                                    <?= e(config('prioridades')[$ticket->getPrioridad()]) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getEstadoBadgeClass($ticket->getEstado()) ?>">
                                                    <?= e(config('ticket_estados')[$ticket->getEstado()]) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?= e($ticket->consultor_nombre ?? 'Sin asignar') ?></small>
                                            </td>
                                            <td>
                                                <small><?= formatTime($ticket->getTiempoInvertido()) ?></small>
                                            </td>
                                            <td>
                                                <small><?= formatDate($ticket->getFechaResolucion() ?? $ticket->getFechaCierre(), 'd/m/Y') ?></small>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('ticket.php?id=' . $ticket->getId()) ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
