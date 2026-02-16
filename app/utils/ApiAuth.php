<?php
/**
 * Clase ApiAuth
 * Gestión de autenticación para API REST
 */

namespace App\Utils;

class ApiAuth
{
    /**
     * Verifica la API Key desde el header
     */
    public static function authenticate(): ?array
    {
        // Obtener API Key del header
        $apiKey = self::getApiKeyFromHeader();
        
        if (!$apiKey) {
            return null;
        }
        
        // Hash de la key para comparar
        $hashedKey = hash('sha256', $apiKey);
        
        // Buscar en base de datos
        $sql = "SELECT * FROM api_keys WHERE api_key = ? AND activo = 1";
        $keyData = Database::fetchOne($sql, [$hashedKey]);
        
        if (!$keyData) {
            return null;
        }
        
        // Actualizar último uso
        Database::update('api_keys', ['last_used_at' => date('Y-m-d H:i:s')], 'id = ?', [$keyData['id']]);
        
        return $keyData;
    }
    
    /**
     * Obtiene la API Key del header
     */
    private static function getApiKeyFromHeader(): ?string
    {
        // Intentar X-API-KEY
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        }
        
        // Intentar Authorization: Bearer
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Verifica si la API Key tiene un permiso específico
     */
    public static function hasPermission(array $keyData, string $permission): bool
    {
        if (empty($keyData['permisos'])) {
            return false;
        }
        
        $permisos = json_decode($keyData['permisos'], true);
        
        if (!is_array($permisos)) {
            return false;
        }
        
        return in_array($permission, $permisos) || in_array('*', $permisos);
    }
    
    /**
     * Genera una nueva API Key
     */
    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Hashea una API Key
     */
    public static function hashApiKey(string $apiKey): string
    {
        return hash('sha256', $apiKey);
    }
    
    /**
     * Crea una nueva API Key en la base de datos
     */
    public static function createApiKey(string $nombre, array $permisos = ['tickets.create']): array
    {
        $apiKey = self::generateApiKey();
        $hashedKey = self::hashApiKey($apiKey);
        
        $data = [
            'nombre' => $nombre,
            'api_key' => $hashedKey,
            'activo' => 1,
            'permisos' => json_encode($permisos)
        ];
        
        $id = Database::insert('api_keys', $data);
        
        return [
            'id' => $id,
            'nombre' => $nombre,
            'api_key' => $apiKey, // Solo se devuelve al crear, NO se guarda en BD
            'permisos' => $permisos
        ];
    }
    
    /**
     * Rate limiting básico
     */
    public static function checkRateLimit(string $ip, int $limitPerMinute = 60): bool
    {
        $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
        
        $sql = "SELECT COUNT(*) as count FROM api_logs 
                WHERE ip_address = ? AND created_at > ?";
        
        $result = Database::fetchOne($sql, [$ip, $oneMinuteAgo]);
        
        return $result['count'] < $limitPerMinute;
    }
}
