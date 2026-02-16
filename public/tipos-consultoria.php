<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Utils\Auth;
use App\Utils\Database;

Auth::requireAuth();
Auth::requireRole(1); // Solo administradores

// Obtener tipos de consultoría
$sql = "SELECT * FROM tipos_consultoria ORDER BY orden ASC, nombre ASC";
$tipos = Database::fetchAll($sql);

$pageTitle = 'Tipos de Consultoría';
include __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../app/views/layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Tipos de Consultoría</h1>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Orden</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tipos as $tipo): ?>
                                    <tr>
                                        <td><?= $tipo['id'] ?></td>
                                        <td><strong><?= e($tipo['nombre']) ?></strong></td>
                                        <td><?= e($tipo['descripcion'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($tipo['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $tipo['orden'] ?></td>
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
