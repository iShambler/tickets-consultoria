<?php /** Vista: Mi Perfil - Variables: $usuario */ ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Mi Perfil</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Información Personal</h5></div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><th width="200">Nombre:</th><td><?= e($usuario->getNombre()) ?></td></tr>
                    <tr><th>Email:</th><td><?= e($usuario->getEmail()) ?></td></tr>
                    <tr><th>Empresa:</th><td><?= e($usuario->getEmpresa() ?? 'No especificada') ?></td></tr>
                    <tr><th>Teléfono:</th><td><?= e($usuario->getTelefono() ?? 'No especificado') ?></td></tr>
                    <tr><th>Rol:</th><td><span class="badge bg-secondary"><?= e($usuario->getRolNombre()) ?></span></td></tr>
                </table>
                <div class="alert alert-info mt-3"><i class="bi bi-info-circle"></i> La funcionalidad de edición de perfil estará disponible próximamente.</div>
            </div>
        </div>
    </div>
</div>
