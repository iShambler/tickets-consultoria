<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico de Login</h1>";
echo "<hr>";

// 1. Verificar archivos principales
echo "<h2>1. Verificación de Archivos</h2>";
$files = [
    '../app/bootstrap.php',
    '../app/utils/Auth.php',
    '../app/utils/Database.php',
    '../app/models/Usuario.php',
    '../app/config/database.php'
];

foreach ($files as $file) {
    echo file_exists(__DIR__ . '/' . $file) 
        ? "✅ $file existe<br>" 
        : "❌ $file NO existe<br>";
}

echo "<hr>";

// 2. Cargar bootstrap y probar conexión
echo "<h2>2. Test de Conexión a Base de Datos</h2>";
try {
    require_once __DIR__ . '/../app/bootstrap.php';
    echo "✅ Bootstrap cargado correctamente<br>";
    
    // Probar conexión a BD usando el método estático correcto
    $connection = \App\Utils\Database::getConnection();
    echo "✅ Conexión a base de datos exitosa<br>";
    
    // Verificar si existe la tabla usuarios
    $stmt = $connection->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabla 'usuarios' existe<br>";
        
        // Contar usuarios
        $stmt = $connection->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch();
        echo "✅ Total de usuarios: " . $result['total'] . "<br>";
        
        // Verificar usuario admin
        $stmt = $connection->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute(['admin@arelance.com']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "✅ Usuario admin encontrado<br>";
            echo "   - ID: " . $admin['id'] . "<br>";
            echo "   - Nombre: " . $admin['nombre'] . "<br>";
            echo "   - Email: " . $admin['email'] . "<br>";
            echo "   - Rol: " . $admin['rol'] . "<br>";
            echo "   - Password hash: " . substr($admin['password'], 0, 30) . "...<br>";
        } else {
            echo "❌ Usuario admin NO encontrado<br>";
        }
    } else {
        echo "❌ Tabla 'usuarios' NO existe<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// 3. Test de password
echo "<h2>3. Test de Verificación de Password</h2>";
$testPassword = 'admin123';
$hashFromDB = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Password a probar: <strong>$testPassword</strong><br>";
echo "Hash esperado: <strong>$hashFromDB</strong><br>";

if (password_verify($testPassword, $hashFromDB)) {
    echo "✅ Password 'admin123' es válido para ese hash<br>";
} else {
    echo "❌ Password 'admin123' NO coincide con el hash<br>";
    
    // Generar nuevo hash
    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
    echo "<br><strong>Nuevo hash generado:</strong><br>";
    echo "<textarea style='width:100%; height:60px;'>$newHash</textarea><br>";
    echo "<small>Copia este hash y ejecútalo en phpMyAdmin:</small><br>";
    echo "<textarea style='width:100%; height:80px;'>UPDATE usuarios SET password = '$newHash' WHERE email = 'admin@arelance.com';</textarea>";
}

echo "<hr>";

// 4. Test de clase Auth
echo "<h2>4. Test de Clase Auth</h2>";
try {
    if (class_exists('App\Utils\Auth')) {
        echo "✅ Clase Auth existe<br>";
        
        // Test de login
        echo "<br><strong>Intentando login con admin@arelance.com / admin123</strong><br>";
        $loginResult = \App\Utils\Auth::login('admin@arelance.com', 'admin123');
        
        if ($loginResult) {
            echo "✅ Login exitoso!<br>";
            $user = \App\Utils\Auth::user();
            if ($user) {
                echo "   - Usuario logueado: " . $user->getNombre() . "<br>";
            }
        } else {
            echo "❌ Login falló<br>";
        }
    } else {
        echo "❌ Clase Auth NO existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Error en Auth: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>✅ Diagnóstico completado</h2>";
echo "<p><a href='login.php'>Volver al Login</a></p>";
?>
