<?php
/**
 * Vista: Dashboard principal
 * Variables disponibles: $usuario, $tickets, $estadisticas, $fuenteStats, $fuenteActiva
 */
use App\Utils\Auth;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <?php if (Auth::isCliente()): ?>
        <a href="<?= base_url('nuevo-ticket.php') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo ticket
        </a>
    <?php endif; ?>
</div>

<?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total</h6>
                        <p class="display-6 mb-0"><?= $estadisticas['total'] ?></p>
                    </div>
                    <i class="bi bi-ticket-perforated" style="font-size: 2.5rem; opacity: 0.5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Nuevos</h6>
                        <p class="display-6 mb-0"><?= $estadisticas['nuevos'] ?></p>
                    </div>
                    <i class="bi bi-inbox" style="font-size: 2.5rem; opacity: 0.5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">En progreso</h6>
                        <p class="display-6 mb-0"><?= $estadisticas['en_progreso'] ?></p>
                    </div>
                    <i class="bi bi-gear" style="font-size: 2.5rem; opacity: 0.5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Resueltos</h6>
                        <p class="display-6 mb-0"><?= $estadisticas['resueltos'] ?></p>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (Auth::isStaff()): ?>
<!-- Pestañas por fuente -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $fuenteActiva === '' ? 'active' : '' ?>" href="<?= base_url('dashboard.php') ?>">
            <i class="bi bi-grid"></i> Todos
            <span class="badge bg-secondary ms-1"><?= $estadisticas['total'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $fuenteActiva === 'manual' ? 'active' : '' ?>" href="<?= base_url('dashboard.php?fuente=manual') ?>">
            <i class="bi bi-pencil-square"></i> Manuales
            <span class="badge bg-secondary ms-1"><?= $fuenteStats['manual'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $fuenteActiva === 'email' ? 'active' : '' ?>" href="<?= base_url('dashboard.php?fuente=email') ?>">
            <i class="bi bi-envelope"></i> Por Email
            <span class="badge bg-secondary ms-1"><?= $fuenteStats['email'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $fuenteActiva === 'api' ? 'active' : '' ?>" href="<?= base_url('dashboard.php?fuente=api') ?>">
            <i class="bi bi-code-slash"></i> Por API
            <span class="badge bg-secondary ms-1"><?= $fuenteStats['api'] ?></span>
        </a>
    </li>
</ul>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <?php if (!empty($fuenteActiva)): ?>
                <input type="hidden" name="fuente" value="<?= e($fuenteActiva) ?>">
            <?php endif; ?>
            <div class="col-md-4">
                <label for="busqueda" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="busqueda" name="busqueda"
                       placeholder="Número, título o descripción"
                       value="<?= e($_GET['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach (config('ticket_estados') as $key => $value): ?>
                        <option value="<?= e($key) ?>" <?= ($_GET['estado'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= e($value) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de tickets -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Tickets (<?= $pagTickets['total'] ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="alert alert-info m-3" role="alert">
                <i class="bi bi-info-circle"></i> No hay tickets que mostrar.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Título</th>
                            <?php if (!Auth::isCliente()): ?>
                                <th>Cliente</th>
                            <?php endif; ?>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Consultor</th>
                            <th>Tiempo</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <?php include APP_ROOT . '/app/views/tickets/_row.php'; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-3"><?php renderPaginacion($pagTickets, 'dashboard.php', $_GET); ?></div>
        <?php endif; ?>
    </div>
</div>
