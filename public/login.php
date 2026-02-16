<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Utils\Auth;
use App\Utils\Validator;

// Si ya está autenticado, redirigir al dashboard
if (Auth::check()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $validator = new Validator(['email' => $email, 'password' => $password]);
    $validator->required('email')->email('email')
              ->required('password');
    
    if ($validator->passes()) {
        if (Auth::login($email, $password)) {
            redirect('dashboard.php');
        } else {
            $error = 'Credenciales incorrectas';
        }
    } else {
        $error = 'Por favor, completa todos los campos correctamente';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - <?= config('app_name') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h1 class="text-center mb-4 h3">Sistema de tickets de consultoría</h1>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= e($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Recordarme
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <p class="text-center mb-0">
                            <small>¿No tienes cuenta? <a href="<?= base_url('registro.php') ?>">Regístrate aquí</a></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
