<?php
/**
 * Vista: Gestión de Usuarios
 * Variables: $usuarios, $mensaje, $mensajeTipo, $editando, $errores
 */
use App\Utils\Auth;

$roles = [1 => 'Administrador', 2 => 'Consultor', 3 => 'Cliente', 4 => 'Sistemas'];
$rolBadge = [1 => 'danger', 2 => 'info', 3 => 'secondary', 4 => 'primary'];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-people"></i> Usuarios</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
        <i class="bi bi-person-plus"></i> Nuevo usuario
    </button>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?= $mensajeTipo ?> alert-dismissible fade show">
    <?= e($mensaje) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errores)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <strong>Errores:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errores as $err): ?>
            <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($flash = getFlash()): ?>
<div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Resumen -->
<div class="row mb-4">
    <div class="col-md-2"><div class="card text-center"><div class="card-body py-2"><p class="h4 mb-0"><?= $userStats['total'] ?></p><small class="text-muted">Total</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body py-2"><p class="h4 mb-0 text-success"><?= $userStats['activos'] ?></p><small class="text-muted">Activos</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body py-2"><p class="h4 mb-0 text-danger"><?= $userStats[1] ?></p><small class="text-muted">Admins</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body py-2"><p class="h4 mb-0 text-primary"><?= $userStats[4] ?></p><small class="text-muted">Sistemas</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body py-2"><p class="h4 mb-0 text-info"><?= $userStats[2] ?></p><small class="text-muted">Consultores</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body py-2"><p class="h4 mb-0 text-secondary"><?= $userStats[3] ?></p><small class="text-muted">Clientes</small></div></div></div>
</div>

<?php if ($editando): ?>
<!-- Formulario de edición -->
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-pencil"></i> Editando: <?= e($editando->getNombre()) ?></h5>
        <a href="<?= base_url('usuarios.php') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i> Cancelar</a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('usuarios.php') ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $editando->getId() ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control" value="<?= e($editando->getNombre()) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="<?= e($editando->getEmail()) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Empresa</label>
                    <input type="text" name="empresa" class="form-control" value="<?= e($editando->getEmpresa() ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" value="<?= e($editando->getTelefono() ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rol</label>
                    <select name="rol" class="form-select" <?= $editando->getId() === Auth::id() ? 'disabled' : '' ?>>
                        <?php foreach ($roles as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $editando->getRol() === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($editando->getId() === Auth::id()): ?>
                        <input type="hidden" name="rol" value="<?= $editando->getRol() ?>">
                        <small class="text-muted">No puedes cambiar tu propio rol</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg"></i> Guardar cambios</button>
                <a href="<?= base_url('usuarios.php') ?>" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de usuarios -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Todos los usuarios (<?= $pagUsuarios['total'] ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Empresa</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user):
                        $esSelf = ($user->getId() === Auth::id());
                    ?>
                    <tr class="<?= !$user->isActivo() ? 'table-light text-muted' : '' ?>">
                        <td><?= $user->getId() ?></td>
                        <td>
                            <strong><?= e($user->getNombre()) ?></strong>
                            <?php if ($esSelf): ?>
                                <span class="badge bg-dark ms-1">Tú</span>
                            <?php endif; ?>
                            <?php if ($user->isCreadoAutomaticamente()): ?>
                                <br><small class="text-muted"><i class="bi bi-robot"></i> Auto-creado</small>
                            <?php endif; ?>
                        </td>
                        <td><small><?= e($user->getEmail()) ?></small></td>
                        <td><small><?= e($user->getEmpresa() ?? '-') ?></small></td>
                        <td>
                            <!-- Cambio rápido de rol -->
                            <?php if (!$esSelf): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="id" value="<?= $user->getId() ?>">
                                <select name="rol" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                    <?php foreach ($roles as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $user->getRol() === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <?php else: ?>
                                <span class="badge bg-<?= $rolBadge[$user->getRol()] ?? 'secondary' ?>"><?= e($user->getRolNombre()) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user->isActivo()): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= $user->getUltimoAcceso() ? formatDate($user->getUltimoAcceso(), 'd/m/Y H:i') : 'Nunca' ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <!-- Editar -->
                                <a href="<?= base_url('usuarios.php?edit=' . $user->getId()) ?>" class="btn btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <!-- Resetear contraseña -->
                                <button type="button" class="btn btn-outline-warning" title="Resetear contraseña"
                                        data-bs-toggle="modal" data-bs-target="#modalPassword"
                                        onclick="document.getElementById('reset_user_id').value='<?= $user->getId() ?>'; document.getElementById('reset_user_name').textContent='<?= e($user->getNombre()) ?>';">
                                    <i class="bi bi-key"></i>
                                </button>

                                <!-- Activar/Desactivar -->
                                <?php if (!$esSelf): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $user->getId() ?>">
                                    <input type="hidden" name="activo" value="<?= $user->isActivo() ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-outline-<?= $user->isActivo() ? 'danger' : 'success' ?>"
                                            title="<?= $user->isActivo() ? 'Desactivar' : 'Activar' ?>"
                                            onclick="return confirm('<?= $user->isActivo() ? '¿Desactivar' : '¿Activar' ?> a <?= e($user->getNombre()) ?>?')">
                                        <i class="bi bi-<?= $user->isActivo() ? 'person-x' : 'person-check' ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-3"><?php renderPaginacion($pagUsuarios, 'usuarios.php', $_GET); ?></div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Empresa</label>
                            <input type="text" name="empresa" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select name="rol" class="form-select" required>
                            <?php foreach ($roles as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $val === 3 ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Crear usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Resetear Contraseña -->
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="reset_user_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key"></i> Resetear contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Nueva contraseña para <strong id="reset_user_name"></strong>:</p>
                    <input type="password" name="new_password" class="form-control" minlength="6" required placeholder="Mínimo 6 caracteres">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check"></i> Cambiar</button>
                </div>
            </form>
        </div>
    </div>
</div>
