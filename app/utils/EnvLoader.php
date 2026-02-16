<?php
/**
 * Clase EnvLoader
 * Carga variables de entorno desde archivo .env
 */

namespace App\Utils;

class EnvLoader
{
    /**
     * Carga un archivo .env y lo inyecta en getenv() y $_ENV
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Saltar comentarios
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Parsear KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Quitar comillas si las tiene
                if (preg_match('/^"(.*)"$/', $value, $m)) {
                    $value = $m[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $m)) {
                    $value = $m[1];
                }

                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    /**
     * Obtiene una variable de entorno con valor por defecto
     */
    public static function get(string $key, $default = null): ?string
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Verifica si estamos en entorno de desarrollo
     */
    public static function isDev(): bool
    {
        return self::get('APP_ENV', 'development') === 'development';
    }

    /**
     * Verifica si estamos en entorno de producción
     */
    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'development') === 'production';
    }
}
