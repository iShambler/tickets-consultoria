<?php
/**
 * Controlador base
 * Funcionalidad compartida entre controladores
 */

namespace App\Controllers;

class BaseController
{
    /**
     * Renderiza una vista con layout completo (header + sidebar + content + footer)
     */
    protected function renderWithLayout(string $viewFile, array $data = []): void
    {
        extract($data);
        include APP_ROOT . '/app/views/layouts/header.php';
        echo '<div class="container-fluid"><div class="row">';
        include APP_ROOT . '/app/views/layouts/sidebar.php';
        echo '<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">';
        include APP_ROOT . '/app/views/' . $viewFile . '.php';
        echo '</main></div></div>';
        include APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Renderiza una vista standalone (sin sidebar, ej. login/registro)
     */
    protected function renderStandalone(string $viewFile, array $data = []): void
    {
        extract($data);
        include APP_ROOT . '/app/views/' . $viewFile . '.php';
    }

    /**
     * Responde con JSON
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Obtiene datos POST sanitizados
     */
    protected function postData(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Obtiene datos GET sanitizados
     */
    protected function queryData(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Verifica si es petición POST
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
