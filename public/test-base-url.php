<?php
require_once __DIR__ . '/../app/bootstrap.php';

echo "<h1>Test de Función base_url()</h1>";
echo "<hr>";

echo "<h2>Variables del servidor:</h2>";
echo "<ul>";
echo "<li><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'NO DEFINIDO') . "</li>";
echo "<li><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'NO DEFINIDO') . "</li>";
echo "<li><strong>HTTPS:</strong> " . ($_SERVER['HTTPS'] ?? 'NO DEFINIDO') . "</li>";
echo "<li><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'NO DEFINIDO') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>Pruebas de base_url():</h2>";
echo "<ul>";
echo "<li><strong>base_url():</strong> " . base_url() . "</li>";
echo "<li><strong>base_url('dashboard.php'):</strong> " . base_url('dashboard.php') . "</li>";
echo "<li><strong>base_url('login.php'):</strong> " . base_url('login.php') . "</li>";
echo "<li><strong>asset('css/style.css'):</strong> " . asset('css/style.css') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>Lo que debería ser:</h2>";
echo "<ul>";
echo "<li><strong>base_url():</strong> http://localhost/ticket-consultoria/public</li>";
echo "<li><strong>base_url('dashboard.php'):</strong> http://localhost/ticket-consultoria/public/dashboard.php</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>Cálculo manual de base URL:</h2>";
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
echo "<p>SCRIPT_NAME: <code>$scriptName</code></p>";

$baseUrl = dirname(dirname($scriptName));
echo "<p>dirname(dirname(SCRIPT_NAME)): <code>$baseUrl</code></p>";

if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
echo "<p>Base URL final: <code>$baseUrl</code></p>";

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fullBaseUrl = $protocol . '://' . $host . $baseUrl;

echo "<p>URL completa: <code>$fullBaseUrl</code></p>";
?>
