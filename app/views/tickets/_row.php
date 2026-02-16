<?php
/**
 * Partial: Fila de ticket en tabla
 * Variables disponibles: $ticket
 */
use App\Utils\Auth;
?>
<tr>
    <td>
        <strong><?= e($ticket->getNumero()) ?></strong>
        <?php if ($ticket->getFuente() === 'email'): ?>
            <br><span class="badge bg-light text-dark border" style="font-size: 0.65rem"><i class="bi bi-envelope-fill text-primary"></i> Email</span>
        <?php elseif ($ticket->getFuente() === 'api'): ?>
            <br><span class="badge bg-light text-dark border" style="font-size: 0.65rem"><i class="bi bi-code-slash text-success"></i> API</span>
        <?php endif; ?>
    </td>
    <td>
        <?= e($ticket->getTitulo()) ?>
        <?php if ($ticket->getDatosIa()): ?>
            <br><small class="text-muted"><i class="bi bi-robot"></i> <?= e($ticket->getDatosIa()['resumen_ia'] ?? '') ?></small>
        <?php endif; ?>
    </td>
    <?php if (!Auth::isCliente()): ?>
        <td>
            <small>
                <?= e($ticket->cliente_nombre ?? '') ?><br>
                <span class="text-muted"><?= e($ticket->cliente_empresa ?? '') ?></span>
            </small>
        </td>
    <?php endif; ?>
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
