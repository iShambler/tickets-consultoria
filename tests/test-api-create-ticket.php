<?php
/**
 * Test: Crear ticket vía API
 * 
 * Ejecutar:
 *   C:\xampp99\php\php.exe C:\xampp99\htdocs\ticket-consultoria\tests\test-api-create-ticket.php TU_API_KEY
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/autoload.php';

use App\Utils\ApiAuth;

echo "\n🧪 TEST: Crear ticket vía API\n";
echo str_repeat('=', 50) . "\n\n";

// Configuración
$apiUrl = 'http://localhost/ticket-consultoria/public/api/tickets/create.php';
$apiKey = $argv[1] ?? '';

if (empty($apiKey)) {
    echo "⚠️  No se proporcionó API Key como argumento.\n";
    echo "   Uso: php tests/test-api-create-ticket.php TU_API_KEY\n\n";
    echo "   Generando una key primero...\n\n";
    
    try {
        $result = ApiAuth::createApiKey('test-' . date('YmdHis'), ['tickets.create']);
        $apiKey = $result['api_key'];
        echo "   ✅ Key generada: {$apiKey}\n\n";
    } catch (\Exception $e) {
        echo "   ❌ No se pudo generar key: " . $e->getMessage() . "\n";
        echo "   Ejecuta primero: php docs/GENERAR-API-KEY.php\n";
        exit(1);
    }
}

// Datos de prueba simulando lo que enviaría n8n
$testData = [
    'email' => 'pedro.garcia@empresa-test.com',
    'nombre' => 'Pedro García',
    'titulo' => 'No puedo acceder al servidor de archivos',
    'descripcion' => 'Desde esta mañana no puedo conectarme al servidor de archivos compartido. Me sale error de timeout. Ya he probado a reiniciar el PC y sigue igual.',
    'categoria' => 'red',
    'prioridad' => 'alta',
    'departamento' => 'Contabilidad',
    'urgencia_keywords' => ['no puedo', 'esta mañana'],
    'resumen_ia' => 'Usuario del departamento de Contabilidad no puede acceder al servidor de archivos compartido. Error de timeout persistente tras reinicio.',
    'email_subject' => 'RE: Problema con servidor de archivos',
    'email_body_original' => "Buenos días,\n\nDesde esta mañana no puedo conectarme al servidor de archivos compartido.\nMe sale un error de timeout cada vez que intento abrir \\\\servidor\\compartido.\nYa he probado a reiniciar el PC y sigue igual.\n\nPor favor, ¿podéis echarle un vistazo?\n\nGracias,\nPedro García\nDpto. Contabilidad",
    'metadata' => [
        'email_date' => '2026-02-12T09:15:00Z',
        'ip_origen' => '192.168.1.50'
    ]
];

$jsonData = json_encode($testData, JSON_UNESCAPED_UNICODE);

echo "📤 Enviando petición a: {$apiUrl}\n";
echo "📋 Datos: " . substr($jsonData, 0, 100) . "...\n\n";

// Hacer la petición con curl de PHP
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $jsonData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-KEY: ' . $apiKey
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "📥 HTTP Code: {$httpCode}\n";

if ($curlError) {
    echo "❌ Error CURL: {$curlError}\n";
    echo "\n💡 Asegúrate de que Apache está corriendo en XAMPP.\n";
    exit(1);
}

// Mostrar respuesta formateada
$responseData = json_decode($response, true);

if ($responseData) {
    echo "📦 Respuesta:\n";
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
    
    if ($httpCode === 201 && ($responseData['success'] ?? false)) {
        echo "✅ ¡ÉXITO! Ticket creado correctamente.\n";
        echo "   🎫 ID: " . $responseData['data']['ticket_id'] . "\n";
        echo "   🔢 Número: " . $responseData['data']['ticket_numero'] . "\n";
        echo "   👤 Cliente ID: " . $responseData['data']['cliente_id'] . "\n";
        echo "   📊 Prioridad: " . $responseData['data']['prioridad'] . "\n";
        echo "   📧 Fuente: " . $responseData['data']['fuente'] . "\n";
    } else {
        echo "❌ FALLO: El ticket no se creó.\n";
        echo "   Error: " . ($responseData['error']['message'] ?? 'Desconocido') . "\n";
        echo "   Código: " . ($responseData['error']['code'] ?? 'N/A') . "\n";
    }
} else {
    echo "❌ Respuesta no es JSON válido:\n";
    echo $response . "\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "🏁 Test finalizado.\n\n";
