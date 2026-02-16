<?php
/**
 * Resolver ticket y notificar al cliente vía n8n/Gmail
 * POST /api/tickets/resolve.php
 */

require_once __DIR__ . '/../../../app/bootstrap.php';

use App\Models\Ticket;
use App\Utils\Auth;
use App\Utils\Database;

// Requiere login (es una acción del técnico desde la web)
Auth::requireAuth();
Auth::requireStaff();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    if (!$ticketId) {
        throw new \RuntimeException('ID de ticket no proporcionado');
    }
    
    $ticket = Ticket::findById($ticketId);
    
    if (!$ticket) {
        throw new \RuntimeException('Ticket no encontrado');
    }
    
    // Obtener datos del cliente
    $cliente = Database::fetchOne("SELECT nombre, email FROM usuarios WHERE id = ?", [$ticket->getClienteId()]);
    
    if (!$cliente || empty($cliente['email'])) {
        throw new \RuntimeException('No se encontró el email del cliente');
    }
    
    // Marcar ticket como resuelto
    $ticket->setEstado('resuelto');
    $ticket->update(Auth::id());
    
    // Llamar a n8n webhook para enviar email
    $webhookUrl = 'https://n8n.arelance.com/webhook/ticket-resuelto';
    
    $payload = [
        'ticket_id' => $ticket->getId(),
        'ticket_numero' => $ticket->getNumero(),
        'ticket_titulo' => $ticket->getTitulo(),
        'cliente_nombre' => $cliente['nombre'],
        'cliente_email' => $cliente['email'],
        'mensaje' => $mensaje ?: 'Su incidencia ha sido revisada y resuelta por nuestro equipo técnico.',
        'resuelto_por' => Auth::user()->getNombre(),
        'fecha_resolucion' => date('c')
    ];
    
    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Error llamando a n8n webhook: " . $curlError);
        echo json_encode([
            'success' => true,
            'ticket_resuelto' => true,
            'email_enviado' => false,
            'warning' => 'Ticket resuelto pero no se pudo enviar el email: ' . $curlError
        ]);
        exit;
    }
    
    if ($httpCode >= 400) {
        error_log("n8n webhook respondió con código: " . $httpCode);
        echo json_encode([
            'success' => true,
            'ticket_resuelto' => true,
            'email_enviado' => false,
            'warning' => 'Ticket resuelto pero n8n respondió con error (código ' . $httpCode . ')'
        ]);
        exit;
    }
    
    // Todo OK
    echo json_encode([
        'success' => true,
        'ticket_resuelto' => true,
        'email_enviado' => true,
        'message' => 'Ticket resuelto y email enviado a ' . $cliente['email']
    ]);
    
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
