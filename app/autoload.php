<?php
/**
 * Autoloader PSR-4
 * Carga automática de clases
 */

spl_autoload_register(function ($class) {
    // Prefijo del namespace del proyecto
    $prefix = 'App\\';
    
    // Directorio base para el namespace
    $baseDir = __DIR__ . '/';
    
    // Verificar si la clase usa el prefijo del namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, mover a la siguiente autoloader registrado
        return;
    }
    
    // Obtener el nombre relativo de la clase
    $relativeClass = substr($class, $len);
    
    // Reemplazar el prefijo del namespace con el directorio base
    // Reemplazar los separadores de namespace con separadores de directorio
    // Añadir .php al final
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Si el archivo existe, cargarlo
    if (file_exists($file)) {
        require $file;
    }
});
