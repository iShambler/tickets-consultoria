<?php
/**
 * Script para corregir todas las rutas en los archivos del proyecto
 */

echo "<h1>Corrección Masiva de Rutas</h1>";
echo "<hr>";

$projectRoot = dirname(__DIR__);
$filesFixed = 0;
$errorsCount = 0;

// Archivos a corregir
$filesToFix = [
    $projectRoot . '/public/dashboard.php',
    $projectRoot . '/public/logout.php',
    $projectRoot . '/public/registro.php',
    $projectRoot . '/app/views/layouts/header.php',
    $projectRoot . '/app/views/layouts/sidebar.php',
];

echo "<h2>Archivos a corregir:</h2><ul>";

foreach ($filesToFix as $file) {
    if (file_exists($file)) {
        echo "<li>📄 " . basename($file) . "... ";
        
        try {
            $content = file_get_contents($file);
            $originalContent = $content;
            
            // Corregir rutas en href y action
            $content = preg_replace('/href="\/([^"]+)"/', 'href="<?= base_url(\'$1\') ?>"', $content);
            $content = preg_replace('/action="\/([^"]+)"/', 'action="<?= base_url(\'$1\') ?>"', $content);
            
            // Corregir rutas en redirect() que empiezan con /
            $content = preg_replace('/redirect\(\'\/([^\']+)\'\)/', 'redirect(\'$1\')', $content);
            $content = preg_replace('/redirect\("\/([^"]+)"\)/', 'redirect("$1")', $content);
            
            // Guardar solo si hubo cambios
            if ($content !== $originalContent) {
                file_put_contents($file, $content);
                echo "<span style='color: green;'>✅ Corregido</span>";
                $filesFixed++;
            } else {
                echo "<span style='color: gray;'>⏭️ Sin cambios</span>";
            }
            
        } catch (Exception $e) {
            echo "<span style='color: red;'>❌ Error: " . $e->getMessage() . "</span>";
            $errorsCount++;
        }
        
        echo "</li>";
    } else {
        echo "<li>❌ " . basename($file) . " (no existe)</li>";
    }
}

echo "</ul>";

echo "<hr>";
echo "<div style='background: " . ($errorsCount > 0 ? '#fff3cd' : '#d4edda') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2 style='margin: 0;'>Resumen:</h2>";
echo "<p>✅ Archivos corregidos: <strong>$filesFixed</strong></p>";
if ($errorsCount > 0) {
    echo "<p>❌ Errores: <strong>$errorsCount</strong></p>";
}
echo "</div>";

if ($errorsCount == 0) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ ¡Corrección completada exitosamente!</strong></p>";
    echo "<p><a href='login.php' style='display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver al Login</a></p>";
}
?>
