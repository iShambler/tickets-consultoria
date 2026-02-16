<?php /** Vista: Tipos de consultoría - Variables: $tipos, $mensaje, $mensajeTipo */ ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-tags"></i> Tipos de Consultoría</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
        <i class="bi bi-plus-circle"></i> Nuevo tipo
    </button>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?= $mensajeTipo ?> alert-dismissible fade show">
    <?= e($mensaje) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Resumen -->
<div class="row mb-4">
    <?php
    $activos = 0;
    $totalTickets = 0;
    foreach ($tipos as $t) {
        if ($t['activo']) $activos++;
        $totalTickets += (int)$t['tickets_count'];
    }
    ?>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <p class="h4 mb-0"><?= count($tipos) ?></p>
                <small class="text-muted">Total tipos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <p class="h4 mb-0 text-success"><?= $activos ?></p>
                <small class="text-muted">Activos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <p class="h4 mb-0" style="color:var(--gray-400)"><?= count($tipos) - $activos ?></p>
                <small class="text-muted">Inactivos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <p class="h4 mb-0" style="color:var(--primary)"><?= $totalTickets ?></p>
                <small class="text-muted">Tickets asociados</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tipos registrados (<?= count($tipos) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($tipos)): ?>
            <div class="alert alert-info m-3"><i class="bi bi-info-circle"></i> No hay tipos creados. Crea uno para empezar.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="60">Orden</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th width="90">Tickets</th>
                        <th width="90">Estado</th>
                        <th width="180">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo): ?>
                    <tr class="<?= !$tipo['activo'] ? 'table-light text-muted' : '' ?>">
                        <td class="text-center"><span class="badge bg-light text-dark border"><?= $tipo['orden'] ?></span></td>
                        <td>
                            <strong><?= e($tipo['nombre']) ?></strong>
                        </td>
                        <td><small class="text-muted"><?= e($tipo['descripcion'] ?? '-') ?></small></td>
                        <td class="text-center">
                            <?php if ((int)$tipo['tickets_count'] > 0): ?>
                                <span class="badge bg-primary"><?= $tipo['tickets_count'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $tipo['activo']
                                ? '<span class="badge bg-success">Activo</span>'
                                : '<span class="badge bg-secondary">Inactivo</span>' ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <!-- Editar -->
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $tipo['id'] ?>" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <!-- Activar/Desactivar -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $tipo['id'] ?>">
                                    <input type="hidden" name="activo" value="<?= $tipo['activo'] ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-outline-<?= $tipo['activo'] ? 'warning' : 'success' ?>"
                                            title="<?= $tipo['activo'] ? 'Desactivar' : 'Activar' ?>">
                                        <i class="bi bi-<?= $tipo['activo'] ? 'pause-circle' : 'play-circle' ?>"></i>
                                    </button>
                                </form>

                                <!-- Eliminar (solo si 0 tickets) -->
                                <?php if ((int)$tipo['tickets_count'] === 0): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar tipo «<?= e($tipo['nombre']) ?>»?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $tipo['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <button class="btn btn-outline-secondary" disabled title="No se puede eliminar: tiene tickets">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Editar -->
                    <div class="modal fade" id="modalEditar<?= $tipo['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= $tipo['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar tipo</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nombre *</label>
                                            <input type="text" name="nombre" class="form-control" value="<?= e($tipo['nombre']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Descripción</label>
                                            <textarea name="descripcion" class="form-control" rows="2"><?= e($tipo['descripcion'] ?? '') ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Orden</label>
                                            <input type="number" name="orden" class="form-control" value="<?= $tipo['orden'] ?>" min="0">
                                            <small class="text-muted">Menor número = aparece primero</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-check"></i> Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nuevo tipo de consultoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Soporte de redes">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Breve descripción del tipo de consultoría..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" name="orden" class="form-control" value="0" min="0">
                        <small class="text-muted">Menor número = aparece primero en los desplegables</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>
