<?php
/**
 * Script para generar API Keys
 * Ejecutar desde línea de comandos:
 *   cd C:\xampp99\htdocs\ticket-consultoria
 *   php docs/GENERAR-API-KEY.php
 * 
 * O desde navegador (solo para admin logueado):
 *   http://localhost/ticket-consultoria/docs/GENERAR-API-KEY.php
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/autoload.php';

use App\Utils\Database;
use App\Utils\ApiAuth;

// Detectar si es CLI o web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><head><title>Generar API Key</title>';
    echo '<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee}';
    echo '.key{background:#16213e;padding:15px;border-radius:8px;margin:10px 0;border-left:4px solid #0f3460}';
    echo '.warn{color:#e94560}h1{color:#0f3460}</style></head><body>';
}

// Nombre de la key
$nombre = $isCli 
    ? ($argv[1] ?? 'n8n-integration-' . date('Ymd'))
    : ($_GET['nombre'] ?? 'n8n-integration-' . date('Ymd'));

try {
    $result = ApiAuth::createApiKey($nombre, ['tickets.create']);
    
    if ($isCli) {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════╗\n";
        echo "║         ✅ API KEY GENERADA EXITOSAMENTE            ║\n";
        echo "╠══════════════════════════════════════════════════════╣\n";
        echo "║                                                      ║\n";
        echo "  ID:     {$result['id']}\n";
        echo "  Nombre: {$result['nombre']}\n";
        echo "  Key:    {$result['api_key']}\n";
        echo "║                                                      ║\n";
        echo "╠══════════════════════════════════════════════════════╣\n";
        echo "║  ⚠️  GUARDA ESTA KEY - NO SE PUEDE RECUPERAR        ║\n";
        echo "╚══════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Ejemplo de uso con curl:\n\n";
        echo "curl -X POST http://localhost/ticket-consultoria/public/api/tickets/create.php \\\n";
        echo "  -H \"X-API-KEY: {$result['api_key']}\" \\\n";
        echo "  -H \"Content-Type: application/json\" \\\n";
        echo "  -d '{\"email\":\"test@test.com\",\"titulo\":\"Test\",\"descripcion\":\"Prueba\"}'\n";
        echo "\n";
    } else {
        echo "<h1>✅ API Key Generada</h1>";
        echo "<div class='key'>";
        echo "<p><strong>ID:</strong> {$result['id']}</p>";
        echo "<p><strong>Nombre:</strong> {$result['nombre']}</p>";
        echo "<p><strong>API Key:</strong> <code>{$result['api_key']}</code></p>";
        echo "</div>";
        echo "<p class='warn'>⚠️ <strong>GUARDA ESTA KEY AHORA</strong> — No se puede recuperar después.</p>";
        echo "<h3>Ejemplo curl:</h3>";
        echo "<div class='key'><pre>curl -X POST http://localhost/ticket-consultoria/public/api/tickets/create.php \\
  -H \"X-API-KEY: {$result['api_key']}\" \\
  -H \"Content-Type: application/json\" \\
  -d '{\"email\":\"test@test.com\",\"titulo\":\"Test\",\"descripcion\":\"Prueba\"}'</pre></div>";
        echo "</body></html>";
    }

} catch (\Exception $e) {
    $msg = "❌ Error: " . $e->getMessage();
    echo $isCli ? "\n{$msg}\n" : "<p class='warn'>{$msg}</p></body></html>";
    exit(1);
}
