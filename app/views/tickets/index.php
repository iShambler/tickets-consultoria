<?php
/**
 * Vista: Lista de todos los tickets (staff)
 * Variables: $ticketsPendientes, $pagPendientes, $ticketsEnProgreso, $pagEnProgreso, $ticketsResueltos, $pagResueltos
 */
use App\Utils\Auth;
?>

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
                       placeholder="Número, título o descripción" value="<?= e($_GET['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="prioridad" class="form-label">Prioridad</label>
                <select class="form-select" id="prioridad" name="prioridad">
                    <option value="">Todas</option>
                    <?php foreach (config('prioridades') as $key => $value): ?>
                        <option value="<?= e($key) ?>" <?= ($_GET['prioridad'] ?? '') === $key ? 'selected' : '' ?>><?= e($value) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="<?= base_url('tickets.php') ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle"></i> Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tickets pendientes -->
<div class="card mb-4">
    <div class="card-header bg-warning bg-opacity-10">
        <h5 class="mb-0"><i class="bi bi-exclamation-circle text-warning"></i> Pendientes <span class="badge bg-warning text-dark"><?= $pagPendientes['total'] ?></span></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ticketsPendientes)): ?>
            <div class="alert alert-success m-3"><i class="bi bi-check-circle"></i> No hay tickets pendientes.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Número</th><th>Título</th><th>Cliente</th><th>Prioridad</th><th>Estado</th><th>Consultor</th><th>Tiempo</th><th>Creado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($ticketsPendientes as $ticket): ?>
                            <?php include APP_ROOT . '/app/views/tickets/_row.php'; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-3"><?php renderPaginacion($pagPendientes, 'tickets.php', $_GET, 'pp'); ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Tickets en progreso -->
<div class="card mb-4">
    <div class="card-header bg-primary bg-opacity-10">
        <h5 class="mb-0"><i class="bi bi-gear text-primary"></i> En progreso <span class="badge bg-primary"><?= $pagEnProgreso['total'] ?></span></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ticketsEnProgreso)): ?>
            <div class="alert alert-info m-3"><i class="bi bi-info-circle"></i> No hay tickets en progreso.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Número</th><th>Título</th><th>Cliente</th><th>Prioridad</th><th>Estado</th><th>Consultor</th><th>Tiempo</th><th>Creado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($ticketsEnProgreso as $ticket): ?>
                            <?php include APP_ROOT . '/app/views/tickets/_row.php'; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-3"><?php renderPaginacion($pagEnProgreso, 'tickets.php', $_GET, 'pe'); ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Tickets resueltos / cerrados -->
<div class="card">
    <div class="card-header bg-success bg-opacity-10">
        <h5 class="mb-0"><i class="bi bi-check-circle text-success"></i> Resueltos / Cerrados <span class="badge bg-success"><?= $pagResueltos['total'] ?></span></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ticketsResueltos)): ?>
            <div class="alert alert-info m-3"><i class="bi bi-info-circle"></i> No hay tickets resueltos aún.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Número</th><th>Título</th><th>Cliente</th><th>Prioridad</th><th>Estado</th><th>Consultor</th><th>Tiempo</th><th>Resuelto</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($ticketsResueltos as $ticket): ?>
                            <?php include APP_ROOT . '/app/views/tickets/_row.php'; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-3"><?php renderPaginacion($pagResueltos, 'tickets.php', $_GET, 'pr'); ?></div>
        <?php endif; ?>
    </div>
</div>
