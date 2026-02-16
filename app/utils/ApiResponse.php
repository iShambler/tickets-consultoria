<?php
/**
 * Clase ApiResponse
 * Respuestas estandarizadas para API REST
 */

namespace App\Utils;

class ApiResponse
{
    /**
     * Envía una respuesta JSON exitosa
     */
    public static function success($data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        exit;
    }
    
    /**
     * Envía una respuesta JSON de error
     */
    public static function error(string $message, string $code = 'ERROR', int $statusCode = 400, array $details = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code
            ],
            'timestamp' => date('c')
        ];
        
        if (!empty($details)) {
            $response['error']['details'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        exit;
    }
    
    /**
     * Respuesta de no autorizado
     */
    public static function unauthorized(string $message = 'API Key inválida o no proporcionada'): void
    {
        self::error($message, 'UNAUTHORIZED', 401);
    }
    
    /**
     * Respuesta de prohibido
     */
    public static function forbidden(string $message = 'No tienes permisos para esta acción'): void
    {
        self::error($message, 'FORBIDDEN', 403);
    }
    
    /**
     * Respuesta de no encontrado
     */
    public static function notFound(string $message = 'Recurso no encontrado'): void
    {
        self::error($message, 'NOT_FOUND', 404);
    }
    
    /**
     * Respuesta de validación fallida
     */
    public static function validationError(string $message, array $errors = []): void
    {
        self::error($message, 'VALIDATION_ERROR', 422, $errors);
    }
    
    /**
     * Respuesta de error del servidor
     */
    public static function serverError(string $message = 'Error interno del servidor'): void
    {
        self::error($message, 'SERVER_ERROR', 500);
    }
    
    /**
     * Respuesta de rate limit excedido
     */
    public static function tooManyRequests(string $message = 'Demasiadas peticiones. Intenta más tarde.'): void
    {
        self::error($message, 'RATE_LIMIT_EXCEEDED', 429);
    }
    
    /**
     * Registra una petición en los logs
     */
    public static function logRequest(?int $apiKeyId, string $endpoint, string $method, string $ip, ?string $requestBody, int $responseCode, float $responseTime, ?string $errorMessage = null): void
    {
        try {
            $data = [
                'api_key_id' => $apiKeyId,
                'endpoint' => $endpoint,
                'method' => $method,
                'ip_address' => $ip,
                'request_body' => $requestBody,
                'response_code' => $responseCode,
                'response_time' => $responseTime,
                'error_message' => $errorMessage
            ];
            
            Database::insert('api_logs', $data);
        } catch (\Exception $e) {
            error_log("Error logging API request: " . $e->getMessage());
        }
    }
}
