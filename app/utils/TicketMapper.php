<?php
/**
 * Clase TicketMapper
 * Mapeo de categorías de IA a tipos de consultoría
 */

namespace App\Utils;

class TicketMapper
{
    /**
     * Mapeo de categorías a nombres de tipos de consultoría
     */
    private static array $categoriaMap = [
        'hardware' => 'Soporte técnico',
        'software' => 'Soporte técnico',
        'red' => 'Infraestructura',
        'servidor' => 'Infraestructura',
        'infraestructura' => 'Infraestructura',
        'desarrollo' => 'Desarrollo Moodle',
        'formacion' => 'Formación',
        'formación' => 'Formación',
        'otro' => 'Otro'
    ];
    
    /**
     * Convierte una categoría de IA a tipo_consultoria_id
     */
    public static function mapCategoria(string $categoria): ?int
    {
        $categoria = strtolower(trim($categoria));
        
        // Buscar en el mapeo
        $tipoNombre = self::$categoriaMap[$categoria] ?? 'Otro';
        
        // Buscar el ID en la base de datos
        $sql = "SELECT id FROM tipos_consultoria WHERE nombre = ? AND activo = 1 LIMIT 1";
        $result = Database::fetchOne($sql, [$tipoNombre]);
        
        if ($result) {
            return (int) $result['id'];
        }
        
        // Si no existe, buscar "Otro"
        $result = Database::fetchOne("SELECT id FROM tipos_consultoria WHERE nombre = 'Otro' AND activo = 1 LIMIT 1");
        
        return $result ? (int) $result['id'] : null;
    }
    
    /**
     * Normaliza la prioridad
     */
    public static function normalizarPrioridad(string $prioridad): string
    {
        $prioridad = strtolower(trim($prioridad));
        
        $validas = ['baja', 'media', 'alta', 'critica'];
        
        return in_array($prioridad, $validas) ? $prioridad : 'media';
    }
    
    /**
     * Construye el email original en formato legible
     */
    public static function construirEmailOriginal(array $data): string
    {
        $lines = [];
        
        if (!empty($data['email_subject'])) {
            $lines[] = "Asunto: " . $data['email_subject'];
        }
        
        if (!empty($data['email'])) {
            $lines[] = "De: " . ($data['nombre'] ?? '') . " <" . $data['email'] . ">";
        }
        
        if (!empty($data['metadata']['email_date'])) {
            $lines[] = "Fecha: " . $data['metadata']['email_date'];
        }
        
        $lines[] = "";
        $lines[] = "---";
        $lines[] = "";
        
        if (!empty($data['email_body_original'])) {
            $lines[] = $data['email_body_original'];
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Construye los datos de IA para guardar en JSON
     */
    public static function construirDatosIA(array $data): array
    {
        return [
            'categoria' => $data['categoria'] ?? null,
            'prioridad_sugerida' => $data['prioridad'] ?? null,
            'departamento' => $data['departamento'] ?? null,
            'urgencia_keywords' => $data['urgencia_keywords'] ?? [],
            'resumen_ia' => $data['resumen_ia'] ?? null,
            'fecha_analisis' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Construye metadata para guardar en JSON
     */
    public static function construirMetadata(array $data): array
    {
        $metadata = $data['metadata'] ?? [];
        
        return [
            'email_date' => $metadata['email_date'] ?? null,
            'ip_origen' => $metadata['ip_origen'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'api_version' => '1.0',
            'processed_at' => date('Y-m-d H:i:s')
        ];
    }
}
