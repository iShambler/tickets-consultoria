<?php
/**
 * Clase Router
 * Router centralizado simple basado en archivo => controlador
 */

namespace App\Utils;

class Router
{
    private static array $routes = [];

    /**
     * Registra una ruta GET
     */
    public static function get(string $path, string $controller, string $method): void
    {
        self::$routes['GET'][$path] = [$controller, $method];
    }

    /**
     * Registra una ruta POST
     */
    public static function post(string $path, string $controller, string $method): void
    {
        self::$routes['POST'][$path] = [$controller, $method];
    }

    /**
     * Registra una ruta para GET y POST
     */
    public static function any(string $path, string $controller, string $method): void
    {
        self::get($path, $controller, $method);
        self::post($path, $controller, $method);
    }

    /**
     * Despacha la petición al controlador correspondiente
     */
    public static function dispatch(string $path, string $httpMethod = null): void
    {
        $httpMethod = $httpMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (isset(self::$routes[$httpMethod][$path])) {
            [$controllerClass, $method] = self::$routes[$httpMethod][$path];

            if (!class_exists($controllerClass)) {
                http_response_code(500);
                die("Controlador no encontrado: {$controllerClass}");
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $method)) {
                http_response_code(500);
                die("Método no encontrado: {$controllerClass}::{$method}");
            }

            $controller->$method();
            return;
        }

        // Si no hay ruta registrada, 404
        http_response_code(404);
        die('Página no encontrada');
    }

    /**
     * Obtiene todas las rutas registradas (para debug)
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}
