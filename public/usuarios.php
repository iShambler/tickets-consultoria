<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Utils\Auth;
use App\Models\Usuario;

Auth::requireAuth();
Auth::requireRole(1); // Solo administradores

$usuarios = Usuario::getAll(null, false); // Incluir inactivos

$pageTitle = 'Usuarios';
include __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../app/views/layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Usuarios</h1>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                Módulo de gestión de usuarios. Total: <strong><?= count($usuarios) ?></strong> usuarios
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Empresa</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td><?= $user->getId() ?></td>
                                        <td><?= e($user->getNombre()) ?></td>
                                        <td><?= e($user->getEmail()) ?></td>
                                        <td><?= e($user->getEmpresa() ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= e($user->getRolNombre()) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($user->isActivo()): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
