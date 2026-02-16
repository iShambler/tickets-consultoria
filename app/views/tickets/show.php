<?php
/**
 * Vista: Detalle de ticket
 * Variables: $ticket, $asignables, $comentarios
 */
use App\Utils\Auth;

$esAdminOSistemas = Auth::isAdmin() || Auth::isSistemas();
$esResuelto = in_array($ticket->getEstado(), ['resuelto', 'cerrado']);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 d-inline">Ticket #<?= e($ticket->getNumero()) ?></h1>
        <?php if ($ticket->getFuente() === 'email'): ?>
            <span class="badge bg-primary ms-2"><i class="bi bi-envelope-fill"></i> Email</span>
        <?php elseif ($ticket->getFuente() === 'api'): ?>
            <span class="badge bg-success ms-2"><i class="bi bi-code-slash"></i> API</span>
        <?php endif; ?>
        <span class="badge bg-<?= getEstadoBadgeClass($ticket->getEstado()) ?> ms-1"><?= e(config('ticket_estados')[$ticket->getEstado()]) ?></span>
    </div>
    <a href="<?= base_url(Auth::isStaff() ? 'tickets.php' : 'dashboard.php') ?>" class="btn btn-outline-secondary">
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
            <div class="card-header"><h5 class="mb-0"><?= e($ticket->getTitulo()) ?></h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Descripción:</strong>
                    <div class="mt-2 p-3 bg-light rounded"><?= nl2br(e($ticket->getDescripcion())) ?></div>
                </div>
            </div>
        </div>

        <?php
        $datosIa = $ticket->getDatosIa();
        $recomendacion = $datosIa['recomendacion'] ?? null;
        if ($esAdminOSistemas && $recomendacion && !empty($recomendacion['tickets'])):
        ?>
        <div class="card mb-4 border-info">
            <div class="card-header bg-info bg-opacity-10" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#collapseRecomendacion">
                <h5 class="mb-0">
                    <i class="bi bi-lightbulb text-info"></i> Recomendación IA — Tickets similares resueltos
                    <span class="badge bg-info ms-2"><?= count($recomendacion['tickets']) ?></span>
                    <i class="bi bi-chevron-down float-end"></i>
                </h5>
            </div>
            <div id="collapseRecomendacion" class="collapse show">
                <div class="card-body">
                    <p class="text-muted mb-3"><i class="bi bi-info-circle"></i> <?= e($recomendacion['mensaje']) ?></p>
                    <?php foreach ($recomendacion['tickets'] as $similar): ?>
                    <div class="border rounded p-3 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <a href="<?= base_url('ticket.php?id=' . $similar['ticket_id']) ?>" class="fw-bold text-decoration-none">
                                    #<?= e($similar['numero']) ?>
                                </a>
                                <span class="ms-2"><?= e($similar['titulo']) ?></span>
                            </div>
                            <div>
                                <span class="badge bg-success"><?= e($similar['estado']) ?></span>
                                <?php if (!empty($similar['fecha_resolucion'])): ?>
                                    <small class="text-muted ms-2"><?= formatDate($similar['fecha_resolucion'], 'd/m/Y') ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($similar['comentarios'])): ?>
                            <div class="mt-2 ps-3 border-start border-info border-3">
                                <?php foreach ($similar['comentarios'] as $com): ?>
                                <div class="mb-1">
                                    <small class="text-muted"><strong><?= e($com['autor']) ?></strong> — <?= formatDate($com['fecha'], 'd/m/Y H:i') ?>:</small>
                                    <p class="mb-0 small"><?= nl2br(e($com['texto'])) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($esAdminOSistemas && $ticket->getFuente() === 'email' && $datosIa): ?>
        <div class="card mb-4">
            <div class="card-header" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#collapseIA">
                <h5 class="mb-0"><i class="bi bi-robot"></i> Análisis de IA <i class="bi bi-chevron-down float-end"></i></h5>
            </div>
            <div id="collapseIA" class="collapse">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr><th width="140"><i class="bi bi-tag"></i> Categoría IA:</th><td><span class="badge bg-info"><?= e($datosIa['categoria'] ?? 'N/A') ?></span></td></tr>
                                <tr><th><i class="bi bi-exclamation-triangle"></i> Prioridad IA:</th><td><span class="badge bg-<?= getPrioridadBadgeClass($datosIa['prioridad_sugerida'] ?? 'media') ?>"><?= e($datosIa['prioridad_sugerida'] ?? 'N/A') ?></span></td></tr>
                                <tr><th><i class="bi bi-building"></i> Departamento:</th><td><?= e($datosIa['departamento'] ?? 'N/A') ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($datosIa['urgencia_keywords'])): ?>
                            <p class="mb-1"><strong><i class="bi bi-lightning"></i> Palabras clave:</strong></p>
                            <p><?php foreach ($datosIa['urgencia_keywords'] as $kw): ?><span class="badge bg-warning text-dark me-1"><?= e($kw) ?></span><?php endforeach; ?></p>
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

        <?php if ($esAdminOSistemas && $ticket->getFuente() === 'email' && $ticket->getEmailOriginal()): ?>
        <div class="card mb-4">
            <div class="card-header" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#collapseEmail">
                <h5 class="mb-0"><i class="bi bi-envelope-open"></i> Email original <i class="bi bi-chevron-down float-end"></i></h5>
            </div>
            <div id="collapseEmail" class="collapse">
                <div class="card-body">
                    <pre class="p-3 bg-light rounded mb-0" style="white-space: pre-wrap; font-family: inherit; font-size: 0.9rem;"><?= e($ticket->getEmailOriginal()) ?></pre>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- BOTÓN RESOLVER (solo si NO está resuelto) -->
        <?php if ($esAdminOSistemas && !$esResuelto): ?>
        <div class="card mb-4 border-success">
            <div class="card-header bg-success bg-opacity-10" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#collapseResolver">
                <h5 class="mb-0 text-success"><i class="bi bi-check-circle"></i> Resolver ticket <i class="bi bi-chevron-down float-end"></i></h5>
            </div>
            <div class="collapse" id="collapseResolver">
            <div class="card-body">
                <form method="POST" action="<?= base_url('ticket.php?id=' . $ticket->getId()) ?>" enctype="multipart/form-data" id="formResolver">
                    <input type="hidden" name="action" value="resolve_with_note">
                    <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">
                    <div class="mb-3">
                        <label for="comentario" class="form-label"><strong>Nota de resolución</strong> <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="4" required
                                  placeholder="Describe cómo se resolvió: qué pasos se siguieron, qué se comprobó, configuración aplicada..."></textarea>
                        <small class="text-muted">Esta nota se guardará como referencia para futuras incidencias similares.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted"><i class="bi bi-image"></i> Adjuntar imágenes (opcional)</label>
                        <input type="file" name="imagenes[]" class="form-control" multiple accept="image/*">
                        <small class="text-muted">Capturas de pantalla, fotos del equipo, etc. Máx 10MB por imagen.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted"><i class="bi bi-envelope"></i> Mensaje para el cliente (opcional)</label>
                        <textarea class="form-control" name="mensaje_cliente" rows="2"
                                  placeholder="Se enviará por email al cliente. Si lo dejas vacío se enviará un mensaje genérico."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-check-circle"></i> Resolver y notificar al cliente
                    </button>
                </form>
            </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- NOTAS DE RESOLUCIÓN (solo si está resuelto y hay comentarios) -->
        <?php if ($esAdminOSistemas && $esResuelto): ?>
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill"></i> <strong>Ticket resuelto</strong> el <?= formatDate($ticket->getFechaResolucion()) ?>
        </div>

        <div class="card mb-4" id="comentarios">
            <div class="card-header bg-success bg-opacity-10">
                <h5 class="mb-0"><i class="bi bi-journal-check text-success"></i> Notas de resolución <span class="badge bg-success"><?= count($comentarios) ?></span></h5>
            </div>
            <div class="card-body">
                <?php if (empty($comentarios)): ?>
                    <p class="text-muted text-center my-3">No se registraron notas de resolución.</p>
                <?php else: ?>
                    <?php foreach ($comentarios as $com): ?>
                    <div class="mb-4 border rounded p-3" id="comment-<?= $com['id'] ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?= e($com['usuario_nombre']) ?></strong>
                                <small class="text-muted ms-2"><?= formatDate($com['fecha_creacion'], 'd/m/Y H:i') ?></small>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                    onclick="toggleEdit(<?= $com['id'] ?>)" title="Editar nota">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                        </div>

                        <!-- Vista normal -->
                        <div id="view-<?= $com['id'] ?>">
                            <div class="mb-2"><?= nl2br(e($com['comentario'])) ?></div>
                            <?php if (!empty($com['imagenes'])): ?>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <?php foreach ($com['imagenes'] as $img): ?>
                                <a href="<?= base_url($img['ruta']) ?>" target="_blank">
                                    <img src="<?= base_url($img['ruta']) ?>" alt="<?= e($img['nombre_original']) ?>"
                                         class="rounded border" style="max-height: 120px; max-width: 200px; object-fit: cover;">
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Formulario edición (oculto) -->
                        <div id="edit-<?= $com['id'] ?>" style="display: none;">
                            <form method="POST" action="<?= base_url('ticket.php?id=' . $ticket->getId()) ?>" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="edit_comment">
                                <input type="hidden" name="comentario_id" value="<?= $com['id'] ?>">
                                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">
                                <textarea class="form-control mb-2" name="comentario" rows="4"><?= e($com['comentario']) ?></textarea>
                                <div class="mb-2">
                                    <label class="form-label small text-muted"><i class="bi bi-image"></i> Añadir más imágenes</label>
                                    <input type="file" name="imagenes[]" class="form-control form-control-sm" multiple accept="image/*">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check"></i> Guardar</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleEdit(<?= $com['id'] ?>)">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Añadir nota adicional en ticket resuelto -->
                <div class="border-top pt-3 mt-2">
                    <form method="POST" action="<?= base_url('ticket.php?id=' . $ticket->getId()) ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="comment">
                        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">
                        <div class="mb-2">
                            <label class="form-label"><strong><i class="bi bi-plus-circle"></i> Añadir nota adicional</strong></label>
                            <textarea class="form-control" name="comentario" rows="3" required
                                      placeholder="Información adicional sobre la resolución..."></textarea>
                        </div>
                        <div class="mb-2">
                            <input type="file" name="imagenes[]" class="form-control form-control-sm" multiple accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-outline-success btn-sm"><i class="bi bi-plus"></i> Añadir</button>
                    </form>
                </div>
            </div>
        </div>
        <?php elseif (!$esAdminOSistemas && $esResuelto): ?>
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill"></i> <strong>Ticket resuelto</strong> el <?= formatDate($ticket->getFechaResolucion()) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar derecho -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-info-circle"></i> Información</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="100">Estado:</th><td><span class="badge bg-<?= getEstadoBadgeClass($ticket->getEstado()) ?>"><?= e(config('ticket_estados')[$ticket->getEstado()]) ?></span></td></tr>
                    <tr><th>Prioridad:</th><td><span class="badge bg-<?= getPrioridadBadgeClass($ticket->getPrioridad()) ?>"><?= e(config('prioridades')[$ticket->getPrioridad()]) ?></span></td></tr>
                    <tr><th>Tipo:</th><td><small><?= e($ticket->tipo_consultoria_nombre ?? 'N/A') ?></small></td></tr>
                    <tr><th>Fuente:</th><td>
                        <?php if ($ticket->getFuente() === 'email'): ?><span class="badge bg-primary"><i class="bi bi-envelope"></i> Email</span>
                        <?php elseif ($ticket->getFuente() === 'api'): ?><span class="badge bg-success"><i class="bi bi-code-slash"></i> API</span>
                        <?php else: ?><span class="badge bg-secondary"><i class="bi bi-pencil"></i> Manual</span><?php endif; ?>
                    </td></tr>
                    <tr><th>Tiempo:</th><td><small><?= formatTime($ticket->getTiempoInvertido()) ?></small></td></tr>
                </table>
            </div>
        </div>

        <?php if ($esAdminOSistemas && !$esResuelto): ?>
        <div class="card mb-3 border-primary">
            <div class="card-header bg-primary bg-opacity-10">
                <h6 class="mb-0"><i class="bi bi-person-check"></i> Asignación</h6>
            </div>
            <div class="card-body">
                <?php if (!$ticket->getConsultorId()): ?>
                    <p class="text-muted small mb-2">Este ticket no está asignado.</p>
                    <form method="POST" action="<?= base_url('ticket.php?id=' . $ticket->getId()) ?>">
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">
                        <input type="hidden" name="auto_asignar" value="1">
                        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                            <i class="bi bi-person-plus"></i> Asignármelo
                        </button>
                    </form>
                    <hr class="my-2">
                <?php else: ?>
                    <p class="small mb-2">Asignado a: <strong><?= e($ticket->consultor_nombre ?? 'N/A') ?></strong></p>
                <?php endif; ?>
                <form method="POST" action="<?= base_url('ticket.php?id=' . $ticket->getId()) ?>">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">
                    <div class="mb-2">
                        <select name="consultor_id" class="form-select form-select-sm">
                            <option value="0">— Sin asignar —</option>
                            <?php foreach ($asignables as $u): ?>
                                <option value="<?= $u->getId() ?>" <?= $ticket->getConsultorId() === $u->getId() ? 'selected' : '' ?>>
                                    <?= e($u->getNombre()) ?> (<?= e($u->getRolNombre()) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-arrow-repeat"></i> Cambiar asignación
                    </button>
                </form>
            </div>
        </div>
        <?php elseif ($esAdminOSistemas): ?>
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-person-check"></i> Asignación</h6></div>
            <div class="card-body">
                <p class="small mb-0">Asignado a: <strong><?= e($ticket->consultor_nombre ?? 'Sin asignar') ?></strong></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-person"></i> Cliente</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong><?= e($ticket->cliente_nombre ?? 'N/A') ?></strong></p>
                <?php if ($ticket->cliente_email): ?><p class="mb-1"><small><i class="bi bi-envelope"></i> <?= e($ticket->cliente_email) ?></small></p><?php endif; ?>
                <?php if ($ticket->cliente_empresa): ?><p class="mb-0"><small><i class="bi bi-building"></i> <?= e($ticket->cliente_empresa) ?></small></p><?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-calendar"></i> Fechas</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="100">Creado:</th><td><small><?= formatDate($ticket->getFechaCreacion()) ?></small></td></tr>
                    <?php if ($ticket->getFechaResolucion()): ?><tr><th>Resuelto:</th><td><small><?= formatDate($ticket->getFechaResolucion()) ?></small></td></tr><?php endif; ?>
                    <?php if ($ticket->getFechaCierre()): ?><tr><th>Cerrado:</th><td><small><?= formatDate($ticket->getFechaCierre()) ?></small></td></tr><?php endif; ?>
                </table>
            </div>
        </div>

        <?php if ($esAdminOSistemas && $ticket->getMetadata()): ?>
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-database"></i> Metadata</h6></div>
            <div class="card-body">
                <?php $meta = $ticket->getMetadata(); ?>
                <table class="table table-sm table-borderless mb-0">
                    <?php if (!empty($meta['email_date'])): ?><tr><th>Email:</th><td><small><?= e($meta['email_date']) ?></small></td></tr><?php endif; ?>
                    <?php if (!empty($meta['ip_origen'])): ?><tr><th>IP:</th><td><small><code><?= e($meta['ip_origen']) ?></code></small></td></tr><?php endif; ?>
                    <?php if (!empty($meta['processed_at'])): ?><tr><th>Procesado:</th><td><small><?= formatDate($meta['processed_at']) ?></small></td></tr><?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleEdit(id) {
    const view = document.getElementById('view-' + id);
    const edit = document.getElementById('edit-' + id);
    if (edit.style.display === 'none') {
        view.style.display = 'none';
        edit.style.display = 'block';
    } else {
        view.style.display = 'block';
        edit.style.display = 'none';
    }
}
</script>
