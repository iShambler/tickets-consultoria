<?php
/**
 * Controlador de Autenticación
 * Login, Logout, Registro
 */

namespace App\Controllers;

use App\Models\Usuario;
use App\Utils\Auth;
use App\Utils\Validator;
use App\Middleware\AuthMiddleware;

class AuthController extends BaseController
{
    /**
     * GET /login.php - Muestra formulario de login
     * POST /login.php - Procesa el login
     */
    public function login(): void
    {
        AuthMiddleware::guest();

        $error = '';

        if ($this->isPost()) {
            $email = $this->postData('email', '');
            $password = $this->postData('password', '');

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

        $this->renderStandalone('auth/login', [
            'error' => $error,
            'pageTitle' => 'Iniciar sesión'
        ]);
    }

    /**
     * GET /logout.php - Cierra sesión
     */
    public function logout(): void
    {
        Auth::logout();
        redirect('login.php');
    }

    /**
     * GET /registro.php - Muestra formulario de registro
     * POST /registro.php - Procesa el registro
     */
    public function registro(): void
    {
        AuthMiddleware::guest();

        $errors = [];
        $success = false;

        if ($this->isPost()) {
            $data = Validator::sanitize($_POST);

            $validator = new Validator($data);
            $validator->required('nombre')->min('nombre', 3)->max('nombre', 100)
                      ->required('email')->email('email')
                      ->required('password')->min('password', 6)
                      ->required('empresa')->max('empresa', 150);

            // Verificar contraseñas
            if (isset($data['password'], $data['password_confirm'])
                && $data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'][] = 'Las contraseñas no coinciden';
            }

            // Verificar email único
            if (!empty($data['email']) && Usuario::emailExists($data['email'])) {
                $errors['email'][] = 'Este email ya está registrado';
            }

            if ($validator->passes() && empty($errors)) {
                $usuario = new Usuario();
                $usuario->setNombre($data['nombre']);
                $usuario->setEmail($data['email']);
                $usuario->setPassword($data['password']);
                $usuario->setEmpresa($data['empresa']);
                $usuario->setTelefono($data['telefono'] ?? null);
                $usuario->setRol(3);

                if ($usuario->create()) {
                    $success = true;
                } else {
                    $errors['general'][] = 'Error al crear la cuenta. Inténtalo de nuevo.';
                }
            } else {
                $errors = array_merge($errors, $validator->errors());
            }
        }

        $this->renderStandalone('auth/registro', [
            'errors' => $errors,
            'success' => $success,
            'pageTitle' => 'Registro'
        ]);
    }
}
