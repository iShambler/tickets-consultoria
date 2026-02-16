<?php
/**
 * API Endpoint: Crear Ticket
 * POST /api/tickets/create.php
 * 
 * Recibe tickets desde n8n procesados por IA
 * Requiere: Header X-API-KEY con key válida
 */

// No iniciar sesión para API - cargar solo lo necesario
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/app/autoload.php';

// Cargar config de BD
date_default_timezone_set('Europe/Madrid');

use App\Utils\Database;
use App\Utils\ApiAuth;
use App\Utils\ApiResponse;
use App\Utils\TicketMapper;
use App\Models\Ticket;
use App\Models\Usuario;

// Headers CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Tiempo de inicio para medir rendimiento
$startTime = microtime(true);
$apiKeyId = null;
$requestBody = null;

try {
    // =========================================
    // 1. VERIFICAR MÉTODO HTTP
    // =========================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Método no permitido. Usa POST.', 'METHOD_NOT_ALLOWED', 405);
    }

    // =========================================
    // 2. AUTENTICACIÓN - Verificar API Key
    // =========================================
    $apiKeyData = ApiAuth::authenticate();
    
    if (!$apiKeyData) {
        $responseTime = microtime(true) - $startTime;
        ApiResponse::logRequest(null, '/api/tickets/create', 'POST', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', null, 401, $responseTime, 'API Key inválida');
        ApiResponse::unauthorized();
    }
    
    $apiKeyId = (int) $apiKeyData['id'];

    // =========================================
    // 3. VERIFICAR PERMISOS
    // =========================================
    if (!ApiAuth::hasPermission($apiKeyData, 'tickets.create')) {
        ApiResponse::forbidden('La API Key no tiene permiso para crear tickets');
    }

    // =========================================
    // 4. RATE LIMITING
    // =========================================
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    if (!ApiAuth::checkRateLimit($clientIp, 60)) {
        ApiResponse::tooManyRequests();
    }

    // =========================================
    // 5. PARSEAR Y VALIDAR JSON
    // =========================================
    $rawBody = file_get_contents('php://input');
    $requestBody = $rawBody;
    
    if (empty($rawBody)) {
        ApiResponse::error('El cuerpo de la petición está vacío', 'EMPTY_BODY', 400);
    }
    
    $data = json_decode($rawBody, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        ApiResponse::error('JSON inválido: ' . json_last_error_msg(), 'INVALID_JSON', 400);
    }

    // =========================================
    // 6. VALIDAR CAMPOS OBLIGATORIOS
    // =========================================
    $requiredFields = ['email', 'titulo', 'descripcion'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        ApiResponse::validationError(
            'Faltan campos obligatorios: ' . implode(', ', $missingFields),
            ['missing_fields' => $missingFields]
        );
    }

    // =========================================
    // 7. SANITIZAR INPUTS
    // =========================================
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::validationError('El email proporcionado no es válido');
    }
    
    // Decodificar MIME encoded-word (=?UTF-8?Q?...?= o =?UTF-8?B?...?=) de asuntos de email
    $nombre = htmlspecialchars(trim($data['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
    $tituloRaw = trim($data['titulo']);
    if (preg_match('/=\?[^?]+\?[BQbq]\?/', $tituloRaw)) {
        $tituloRaw = mb_decode_mimeheader($tituloRaw);
    }
    $titulo = htmlspecialchars($tituloRaw, ENT_QUOTES, 'UTF-8');
    $descripcion = htmlspecialchars(trim($data['descripcion']), ENT_QUOTES, 'UTF-8');
    $categoria = strtolower(trim($data['categoria'] ?? 'otro'));
    $prioridad = TicketMapper::normalizarPrioridad($data['prioridad'] ?? 'media');
    $departamento = htmlspecialchars(trim($data['departamento'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Validar longitudes
    if (strlen($titulo) > 255) {
        ApiResponse::validationError('El título no puede exceder 255 caracteres');
    }
    
    if (strlen($descripcion) > 65535) {
        ApiResponse::validationError('La descripción es demasiado larga');
    }

    // =========================================
    // 8. CREAR/OBTENER USUARIO
    // =========================================
    $usuario = Usuario::createFromEmail($email, $nombre, null, $departamento ?: null);
    
    if (!$usuario || !$usuario->getId()) {
        ApiResponse::serverError('No se pudo crear/obtener el usuario');
    }

    // =========================================
    // 9. MAPEAR CATEGORÍA A TIPO CONSULTORÍA
    // =========================================
    $tipoConsultoriaId = TicketMapper::mapCategoria($categoria);

    // =========================================
    // 10. CONSTRUIR DATOS EXTRA
    // =========================================
    $emailOriginal = TicketMapper::construirEmailOriginal($data);
    $datosIa = TicketMapper::construirDatosIA($data);
    $metadata = TicketMapper::construirMetadata($data);

    // =========================================
    // 11. BUSCAR TICKETS SIMILARES RESUELTOS
    // =========================================
    $ticketsSimilares = Ticket::findSimilarResolved($titulo, $descripcion, $categoria, 5);
    
    $recomendacion = null;
    if (!empty($ticketsSimilares)) {
        $recomendacion = [];
        $recomendacion['mensaje'] = 'Se encontraron ' . count($ticketsSimilares) . ' ticket(s) similares ya resueltos que podrían ayudar a resolver esta incidencia.';
        $recomendacion['tickets'] = [];
        
        foreach ($ticketsSimilares as $similar) {
            $entry = [
                'ticket_id' => $similar['id'],
                'numero' => $similar['numero'],
                'titulo' => $similar['titulo'],
                'estado' => $similar['estado'],
                'fecha_resolucion' => $similar['fecha_resolucion'],
                'relevancia' => $similar['relevancia'] ?? 0,
            ];
            // Incluir comentarios de resolución si los hay
            if (!empty($similar['comentarios_resolucion'])) {
                $entry['comentarios'] = array_map(function($c) {
                    return [
                        'autor' => $c['autor'],
                        'texto' => mb_substr($c['comentario'], 0, 500),
                        'fecha' => $c['fecha_creacion']
                    ];
                }, $similar['comentarios_resolucion']);
            }
            $recomendacion['tickets'][] = $entry;
        }
        
        // Añadir recomendación a datos_ia
        $datosIa['recomendacion'] = $recomendacion;
    }

    // =========================================
    // 12. CREAR TICKET
    // =========================================
    $ticket = new Ticket();
    $ticket->setClienteId($usuario->getId());
    $ticket->setTipoConsultoriaId($tipoConsultoriaId);
    $ticket->setTitulo($titulo);
    $ticket->setDescripcion($descripcion);
    $ticket->setPrioridad($prioridad);
    $ticket->setEstado('nuevo');
    $ticket->setFuente('email');
    $ticket->setEmailMessageId($data['email_message_id'] ?? null);
    $ticket->setEmailOriginal($emailOriginal);
    $ticket->setDatosIa($datosIa);
    $ticket->setMetadata($metadata);
    
    if (!$ticket->create()) {
        ApiResponse::serverError('No se pudo crear el ticket');
    }

    // =========================================
    // 13. LOG Y RESPUESTA EXITOSA
    // =========================================
    $responseTime = microtime(true) - $startTime;
    
    ApiResponse::logRequest(
        $apiKeyId,
        '/api/tickets/create',
        'POST',
        $clientIp,
        $requestBody,
        201,
        $responseTime
    );
    
    $responseData = [
        'ticket_id' => $ticket->getId(),
        'ticket_numero' => $ticket->getNumero(),
        'cliente_id' => $usuario->getId(),
        'cliente_nuevo' => !Usuario::findByEmail($email) ? false : true,
        'tipo_consultoria_id' => $tipoConsultoriaId,
        'prioridad' => $prioridad,
        'estado' => 'nuevo',
        'fuente' => 'email',
        'created_at' => date('c'),
        'tickets_similares' => !empty($ticketsSimilares) ? count($ticketsSimilares) : 0,
    ];
    
    if (!empty($recomendacion)) {
        $responseData['recomendacion'] = $recomendacion;
    }
    
    ApiResponse::success($responseData, 201);

} catch (\Exception $e) {
    $responseTime = microtime(true) - $startTime;
    
    ApiResponse::logRequest(
        $apiKeyId,
        '/api/tickets/create',
        'POST',
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        $requestBody,
        500,
        $responseTime,
        $e->getMessage()
    );
    
    error_log("API Error creating ticket: " . $e->getMessage());
    ApiResponse::serverError('Error interno: ' . $e->getMessage());
}
