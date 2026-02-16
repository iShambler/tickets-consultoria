<?php
use App\Utils\Auth;

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= base_url('dashboard.php') ?>">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
            </li>
            
            <?php if (Auth::isCliente()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'nuevo-ticket.php' ? 'active' : '' ?>" href="<?= base_url('nuevo-ticket.php') ?>">
                        <i class="bi bi-plus-circle"></i> Nuevo ticket
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'mis-tickets.php' ? 'active' : '' ?>" href="<?= base_url('mis-tickets.php') ?>">
                        <i class="bi bi-list-ul"></i> Mis tickets
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if (Auth::isStaff()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'tickets.php' ? 'active' : '' ?>" href="<?= base_url('tickets.php') ?>">
                        <i class="bi bi-list-ul"></i> Todos los tickets
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if (Auth::isAdmin()): ?>
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Administración</span>
                    </h6>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'usuarios.php' ? 'active' : '' ?>" href="<?= base_url('usuarios.php') ?>">
                        <i class="bi bi-people"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'tipos-consultoria.php' ? 'active' : '' ?>" href="<?= base_url('tipos-consultoria.php') ?>">
                        <i class="bi bi-tags"></i> Tipos de consultoría
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'reportes.php' ? 'active' : '' ?>" href="<?= base_url('reportes.php') ?>">
                        <i class="bi bi-graph-up"></i> Reportes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'api-keys.php' ? 'active' : '' ?>" href="<?= base_url('api-keys.php') ?>">
                        <i class="bi bi-key"></i> API Keys
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item mt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Mi cuenta</span>
                </h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'perfil.php' ? 'active' : '' ?>" href="<?= base_url('perfil.php') ?>">
                    <i class="bi bi-person"></i> Mi perfil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('logout.php') ?>">
                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                </a>
            </li>
        </ul>
    </div>
</nav>
