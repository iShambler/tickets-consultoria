<?php /** Vista: Tipos de consultoría - Variables: $tipos */ ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tipos de Consultoría</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Orden</th></tr></thead>
                <tbody>
                    <?php foreach ($tipos as $tipo): ?>
                    <tr>
                        <td><?= $tipo['id'] ?></td>
                        <td><strong><?= e($tipo['nombre']) ?></strong></td>
                        <td><?= e($tipo['descripcion'] ?? '-') ?></td>
                        <td><?= $tipo['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                        <td><?= $tipo['orden'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
