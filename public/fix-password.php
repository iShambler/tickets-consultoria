<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/bootstrap.php';

echo "<h1>Actualizar Password del Administrador</h1>";
echo "<hr>";

try {
    // Generar nuevo hash para admin123
    $newPassword = 'admin123';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    echo "<p>Generando nuevo hash para password: <strong>$newPassword</strong></p>";
    echo "<p>Nuevo hash: <code>$newHash</code></p>";
    
    // Actualizar en base de datos
    $connection = \App\Utils\Database::getConnection();
    $stmt = $connection->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
    $result = $stmt->execute([$newHash, 'admin@arelance.com']);
    
    if ($result) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h2 style='color: #155724; margin: 0;'>✅ Password actualizado exitosamente!</h2>";
        echo "</div>";
        
        // Verificar que funciona
        echo "<h2>Verificación</h2>";
        $stmt = $connection->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute(['admin@arelance.com']);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($newPassword, $admin['password'])) {
            echo "<p style='color: green;'>✅ Verificación exitosa: El password 'admin123' ahora funciona correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ Algo salió mal en la verificación</p>";
        }
        
        echo "<hr>";
        echo "<h2>¡Listo para usar!</h2>";
        echo "<p><strong>Credenciales de acceso:</strong></p>";
        echo "<ul>";
        echo "<li>📧 Email: <strong>admin@arelance.com</strong></li>";
        echo "<li>🔑 Contraseña: <strong>admin123</strong></li>";
        echo "</ul>";
        
        echo "<p><a href='login.php' style='display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Error al actualizar el password</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
