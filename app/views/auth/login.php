<?php use App\Utils\Auth; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - <?= config('app_name') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= time() ?>">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center position-relative"
                                 style="width:64px; height:64px; background:linear-gradient(135deg,#143554,#2a4d7a); border-radius:16px; color:#fff; font-size:1.8rem; margin-bottom:1rem;">
                                <i class="bi bi-ticket-perforated"></i>
                                <span style="position:absolute; bottom:-3px; right:-3px; width:16px; height:16px; background:#F05726; border-radius:5px;"></span>
                            </div>
                            <h1 style="color:#143554; font-weight:700; font-size:1.5rem;">Sistema de Tickets</h1>
                            <p class="text-muted mb-0" style="font-size:0.9rem;">Consultoría IT — Arelance</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle"></i> <?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= e($_POST['email'] ?? '') ?>" required autofocus
                                           placeholder="tu@email.com">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required
                                           placeholder="••••••••">
                                </div>
                            </div>

                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label text-muted" for="remember" style="font-size:0.85rem">Recordarme</label>
                            </div>

                            <button type="submit" class="btn w-100"
                                    style="background:#F05726; border-color:#F05726; color:#fff; padding:0.75rem; font-weight:600; font-size:1rem; border-radius:10px;">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                            </button>
                        </form>

                        <hr class="my-4">

                        <p class="text-center mb-0">
                            <small class="text-muted">¿No tienes cuenta? <a href="<?= base_url('registro.php') ?>" style="color:#F05726; font-weight:500;">Regístrate aquí</a></small>
                        </p>
                    </div>
                </div>
                <p class="text-center mt-3" style="color:rgba(255,255,255,0.5); font-size:0.75rem;">&copy; <?= date('Y') ?> Arelance</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
