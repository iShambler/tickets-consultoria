<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Usuario;
use App\Utils\Auth;
use App\Utils\Validator;

// Si ya está autenticado, redirigir al dashboard
if (Auth::check()) {
    redirect('/dashboard.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = Validator::sanitize($_POST);
    
    $validator = new Validator($data);
    $validator->required('nombre')->min('nombre', 3)->max('nombre', 100)
              ->required('email')->email('email')
              ->required('password')->min('password', 6)
              ->required('empresa')->max('empresa', 150);
    
    // Verificar que las contraseñas coincidan
    if (isset($data['password']) && isset($data['password_confirm']) && 
        $data['password'] !== $data['password_confirm']) {
        $validator->errors()['password_confirm'][] = 'Las contraseñas no coinciden';
    }
    
    // Verificar que el email no exista
    if (Usuario::emailExists($data['email'])) {
        $validator->errors()['email'][] = 'Este email ya está registrado';
    }
    
    if ($validator->passes()) {
        $usuario = new Usuario();
        $usuario->setNombre($data['nombre']);
        $usuario->setEmail($data['email']);
        $usuario->setPassword($data['password']);
        $usuario->setEmpresa($data['empresa']);
        $usuario->setTelefono($data['telefono'] ?? null);
        $usuario->setRol(3); // Cliente
        
        if ($usuario->create()) {
            $success = true;
        } else {
            $errors['general'][] = 'Error al crear la cuenta. Inténtalo de nuevo.';
        }
    } else {
        $errors = $validator->errors();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?= config('app_name') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h1 class="text-center mb-4 h3">Crear cuenta</h1>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <h5 class="alert-heading">¡Cuenta creada exitosamente!</h5>
                                <p>Ya puedes <a href="/login.php" class="alert-link">iniciar sesión</a> con tus credenciales.</p>
                            </div>
                        <?php else: ?>
                            
                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= e($errors['general'][0]) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre completo *</label>
                                    <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>" 
                                           id="nombre" name="nombre" value="<?= e($_POST['nombre'] ?? '') ?>" required>
                                    <?php if (isset($errors['nombre'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['nombre'][0]) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                           id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['email'][0]) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="empresa" class="form-label">Empresa *</label>
                                    <input type="text" class="form-control <?= isset($errors['empresa']) ? 'is-invalid' : '' ?>" 
                                           id="empresa" name="empresa" value="<?= e($_POST['empresa'] ?? '') ?>" required>
                                    <?php if (isset($errors['empresa'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['empresa'][0]) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                                           value="<?= e($_POST['telefono'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                           id="password" name="password" required>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['password'][0]) ?></div>
                                    <?php endif; ?>
                                    <small class="form-text text-muted">Mínimo 6 caracteres</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Confirmar contraseña *</label>
                                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                                           id="password_confirm" name="password_confirm" required>
                                    <?php if (isset($errors['password_confirm'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['password_confirm'][0]) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Crear cuenta</button>
                            </form>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <p class="text-center mb-0">
                            <small>¿Ya tienes cuenta? <a href="/login.php">Inicia sesión aquí</a></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
