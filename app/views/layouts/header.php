<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= config('app_name') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= filemtime(APP_ROOT . '/public/css/style.css') ?>">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-corp sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= base_url('dashboard.php') ?>">
                <i class="bi bi-ticket-perforated"></i> <?= config('app_name') ?>
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <span class="user-info d-none d-sm-inline">
                    <i class="bi bi-person-circle"></i> 
                    <?= e(\App\Utils\Auth::user()->getNombre()) ?>
                    <span class="badge ms-1"><?= e(\App\Utils\Auth::user()->getRolNombre()) ?></span>
                </span>
                <a href="<?= base_url('logout.php') ?>" class="btn btn-sm btn-logout btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>
