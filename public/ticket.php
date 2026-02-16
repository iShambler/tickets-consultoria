<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Ticket;
use App\Utils\Auth;

Auth::requireAuth();

$ticketId = $_GET['id'] ?? null;

if (!$ticketId) {
    setFlash('danger', 'ID de ticket no especificado');
    redirect('dashboard.php');
}

$ticket = Ticket::findById((int)$ticketId);

if (!$ticket) {
    setFlash('danger', 'Ticket no encontrado');
    redirect('dashboard.php');
}

// Verificar permisos
if (Auth::isCliente() && $ticket->getClienteId() !== Auth::id()) {
    setFlash('danger', 'No tienes permiso para ver este ticket');
    redirect('dashboard.php');
}

$pageTitle = 'Ticket #' . $ticket->getNumero();
include __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../app/views/layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 d-inline">Ticket #<?= e($ticket->getNumero()) ?></h1>
                    <?php if ($ticket->getFuente() === 'email'): ?>
                        <span class="badge bg-primary ms-2"><i class="bi bi-envelope-fill"></i> Creado desde email</span>
                    <?php elseif ($ticket->getFuente() === 'api'): ?>
                        <span class="badge bg-success ms-2"><i class="bi bi-code-slash"></i> Creado desde API</span>
                    <?php endif; ?>
                </div>
                <a href="<?= base_url('dashboard.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
            
            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Columna principal -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><?= e($ticket->getTitulo()) ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Descripción:</strong>
                                <div class="mt-2 p-3 bg-light rounded">
                                    <?= nl2br(e($ticket->getDescripcion())) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($ticket->getFuente() === 'email' && $ticket->getDatosIa()): ?>
                    <!-- Análisis de IA -->
                    <div class="card mb-4">
                        <div class="card-header" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#collapseIA">
                            <h5 class="mb-0">
                                <i class="bi bi-robot"></i> Análisis de IA
                                <i class="bi bi-chevron-down float-end"></i>
                            </h5>
                        </div>
                        <div id="collapseIA" class="collapse show">
                            <div class="card-body">
                                <?php $datosIa = $ticket->getDatosIa(); ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <th width="140"><i class="bi bi-tag"></i> Categoría IA:</th>
                                                <td><span class="badge bg-info"><?= e($datosIa['categoria'] ?? 'N/A') ?></span></td>
                                            </tr>
                                            <tr>
                                                <th><i class="bi bi-exclamation-triangle"></i> Prioridad IA:</th>
                                                <td><span class="badge bg-<?= getPrioridadBadgeClass($datosIa['prioridad_sugerida'] ?? 'media') ?>"><?= e($datosIa['prioridad_sugerida'] ?? 'N/A') ?></span></td>
                                            </tr>
                                            <tr>
                                                <th><i class="bi bi-building"></i> Departamento:</th>
                                                <td><?= e($datosIa['departamento'] ?? 'N/A') ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if (!empty($datosIa['urgencia_keywords'])): ?>
                                        <p class="mb-1"><strong><i class="bi bi-lightning"></i> Palabras clave de urgencia:</strong></p>
                                        <p>
                                            <?php foreach ($datosIa['urgencia_keywords'] as $kw): ?>
                                                <span class="badge bg-warning text-dark me-1"><?= e($kw) ?></span>
                                            <?php endforeach; ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($datosIa['fecha_analisis'])): ?>
                                        <p class="text-muted mb-0"><small><i class="bi bi-clock"></i> Analizado: <?= formatDate($datosIa['fecha_analisis']) ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($datosIa['resumen_ia'])): ?>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <strong><i class="bi bi-chat-quote"></i> Resumen IA:</strong>
                                    <p class="mb-0 mt-1"><?= e($datosIa['resumen_ia']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket->getFuente() === 'email' && $ticket->getEmailOriginal()): ?>
                    <!-- Email Original -->
                    <div class="card mb-4">
                        <div class="card-header" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#collapseEmail">
                            <h5 class="mb-0">
                                <i class="bi bi-envelope-open"></i> Email original
                                <i class="bi bi-chevron-down float-end"></i>
                            </h5>
                        </div>
                        <div id="collapseEmail" class="collapse">
                            <div class="card-body">
                                <pre class="p-3 bg-light rounded mb-0" style="white-space: pre-wrap; font-family: inherit; font-size: 0.9rem;"><?= e($ticket->getEmailOriginal()) ?></pre>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (Auth::isStaff() && $ticket->getEstado() !== 'resuelto' && $ticket->getEstado() !== 'cerrado'): ?>
                    <!-- Botón Resolver -->
                    <div class="card mb-4 border-success">
                        <div class="card-body">
                            <button type="button" class="btn btn-success btn-lg w-100" data-bs-toggle="modal" data-bs-target="#modalResolver">
                                <i class="bi bi-check-circle"></i> Marcar como resuelto y notificar al cliente
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket->getEstado() === 'resuelto'): ?>
                    <div class="alert alert-success mb-4">
                        <i class="bi bi-check-circle-fill"></i> <strong>Ticket resuelto</strong> el <?= formatDate($ticket->getFechaResolucion()) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Comentarios -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Comentarios</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                La funcionalidad de comentarios estará disponible próximamente.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar derecho -->
                <div class="col-md-4">
                    <!-- Información del ticket -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th width="100">Estado:</th>
                                        <td>
                                            <span class="badge bg-<?= getEstadoBadgeClass($ticket->getEstado()) ?>">
                                                <?= e(config('ticket_estados')[$ticket->getEstado()]) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Prioridad:</th>
                                        <td>
                                            <span class="badge bg-<?= getPrioridadBadgeClass($ticket->getPrioridad()) ?>">
                                                <?= e(config('prioridades')[$ticket->getPrioridad()]) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Tipo:</th>
                                        <td>
                                            <small><?= e($ticket->tipo_consultoria_nombre ?? 'N/A') ?></small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Fuente:</th>
                                        <td>
                                            <?php if ($ticket->getFuente() === 'email'): ?>
                                                <span class="badge bg-primary"><i class="bi bi-envelope"></i> Email</span>
                                            <?php elseif ($ticket->getFuente() === 'api'): ?>
                                                <span class="badge bg-success"><i class="bi bi-code-slash"></i> API</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-pencil"></i> Manual</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Tiempo:</th>
                                        <td>
                                            <small><?= formatTime($ticket->getTiempoInvertido()) ?></small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Cliente -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person"></i> Cliente</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong><?= e($ticket->cliente_nombre ?? 'N/A') ?></strong></p>
                            <?php if ($ticket->cliente_email): ?>
                                <p class="mb-1"><small><i class="bi bi-envelope"></i> <?= e($ticket->cliente_email) ?></small></p>
                            <?php endif; ?>
                            <?php if ($ticket->cliente_empresa): ?>
                                <p class="mb-0"><small><i class="bi bi-building"></i> <?= e($ticket->cliente_empresa) ?></small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Fechas -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-calendar"></i> Fechas</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th width="100">Creado:</th>
                                        <td><small><?= formatDate($ticket->getFechaCreacion()) ?></small></td>
                                    </tr>
                                    <?php if ($ticket->getFechaResolucion()): ?>
                                    <tr>
                                        <th>Resuelto:</th>
                                        <td><small><?= formatDate($ticket->getFechaResolucion()) ?></small></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($ticket->getFechaCierre()): ?>
                                    <tr>
                                        <th>Cerrado:</th>
                                        <td><small><?= formatDate($ticket->getFechaCierre()) ?></small></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($ticket->getMetadata()): ?>
                    <!-- Metadata -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-database"></i> Metadata</h6>
                        </div>
                        <div class="card-body">
                            <?php $meta = $ticket->getMetadata(); ?>
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <?php if (!empty($meta['email_date'])): ?>
                                    <tr>
                                        <th>Email:</th>
                                        <td><small><?= e($meta['email_date']) ?></small></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($meta['ip_origen'])): ?>
                                    <tr>
                                        <th>IP:</th>
                                        <td><small><code><?= e($meta['ip_origen']) ?></code></small></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($meta['processed_at'])): ?>
                                    <tr>
                                        <th>Procesado:</th>
                                        <td><small><?= formatDate($meta['processed_at']) ?></small></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php if (Auth::isStaff() && $ticket->getEstado() !== 'resuelto' && $ticket->getEstado() !== 'cerrado'): ?>
<!-- Modal Resolver -->
<div class="modal fade" id="modalResolver" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle"></i> Resolver ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Se marcará el ticket <strong>#<?= e($ticket->getNumero()) ?></strong> como resuelto y se enviará un email a <strong><?= e($ticket->cliente_email ?? 'el cliente') ?></strong>.</p>
                <div class="mb-3">
                    <label class="form-label">Mensaje para el cliente (opcional)</label>
                    <textarea class="form-control" id="mensajeResolucion" rows="4" 
                        placeholder="Ej: Hemos reiniciado el servicio de impresión y verificado que funciona correctamente..."></textarea>
                    <small class="text-muted">Si lo dejas vacío se enviará un mensaje genérico.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnConfirmarResolver">
                    <i class="bi bi-send"></i> Resolver y enviar email
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btnConfirmarResolver').addEventListener('click', function() {
    const btn = this;
    const mensaje = document.getElementById('mensajeResolucion').value;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';
    
    const formData = new FormData();
    formData.append('ticket_id', '<?= $ticket->getId() ?>');
    formData.append('mensaje', mensaje);
    
    fetch('<?= base_url('api/tickets/resolve.php') ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let msg = '\u2705 Ticket resuelto.';
            if (data.email_enviado) {
                msg += ' Email enviado al cliente.';
            } else if (data.warning) {
                msg += ' \u26a0\ufe0f ' + data.warning;
            }
            alert(msg);
            location.reload();
        } else {
            alert('\u274c Error: ' + (data.error || 'Error desconocido'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Resolver y enviar email';
        }
    })
    .catch(err => {
        alert('\u274c Error de conexión: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Resolver y enviar email';
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
