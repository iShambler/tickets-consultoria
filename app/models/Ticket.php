<?php
/**
 * Modelo Ticket
 * Gestión de tickets de consultoría
 */

namespace App\Models;

use App\Utils\Database;

class Ticket
{
    private ?int $id = null;
    private ?string $numero = null;
    private int $clienteId;
    private ?int $tipoConsultoriaId = null;
    private ?int $consultorId = null;
    private string $titulo;
    private string $descripcion;
    private string $prioridad = 'media';
    private string $estado = 'nuevo';
    private float $tiempoInvertido = 0.00;
    private ?string $fechaCreacion = null;
    private ?string $fechaResolucion = null;
    private ?string $fechaCierre = null;
    
    // Campos para tickets desde email/API
    private string $fuente = 'manual';
    private ?string $emailMessageId = null;
    private ?string $emailOriginal = null;
    private ?array $datosIa = null;
    private ?array $metadata = null;
    
    // Propiedades dinámicas de JOINs
    public ?string $cliente_nombre = null;
    public ?string $cliente_email = null;
    public ?string $cliente_empresa = null;
    public ?string $consultor_nombre = null;
    public ?string $tipo_consultoria_nombre = null;
    
    /**
     * Crea un nuevo ticket
     */
    public function create(): bool
    {
        $data = [
            'cliente_id' => $this->clienteId,
            'tipo_consultoria_id' => $this->tipoConsultoriaId,
            'consultor_id' => $this->consultorId,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'prioridad' => $this->prioridad,
            'estado' => $this->estado,
            'fuente' => $this->fuente,
            'email_message_id' => $this->emailMessageId,
            'email_original' => $this->emailOriginal,
            'datos_ia' => $this->datosIa ? json_encode($this->datosIa) : null,
            'metadata' => $this->metadata ? json_encode($this->metadata) : null
        ];
        
        try {
            Database::beginTransaction();
            
            $this->id = Database::insert('tickets', $data);
            
            // Obtener el número generado por el trigger
            $result = Database::fetchOne("SELECT numero FROM tickets WHERE id = ?", [$this->id]);
            $this->numero = $result['numero'];
            
            // Registrar en historial
            $this->registrarHistorial(null, 'estado', null, $this->estado);
            
            Database::commit();
            return true;
            
        } catch (\Exception $e) {
            Database::rollback();
            error_log("Error al crear ticket: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza un ticket existente
     */
    public function update(int $usuarioId): bool
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            Database::beginTransaction();
            
            // Obtener valores anteriores para historial
            $ticketAnterior = self::findById($this->id);
            
            $data = [
                'tipo_consultoria_id' => $this->tipoConsultoriaId,
                'consultor_id' => $this->consultorId,
                'titulo' => $this->titulo,
                'descripcion' => $this->descripcion,
                'prioridad' => $this->prioridad,
                'estado' => $this->estado
            ];
            
            // Si se marca como resuelto, registrar fecha
            if ($this->estado === 'resuelto' && $ticketAnterior->estado !== 'resuelto') {
                $data['fecha_resolucion'] = date('Y-m-d H:i:s');
            }
            
            // Si se cierra, registrar fecha
            if ($this->estado === 'cerrado' && $ticketAnterior->estado !== 'cerrado') {
                $data['fecha_cierre'] = date('Y-m-d H:i:s');
            }
            
            Database::update('tickets', $data, 'id = ?', [$this->id]);
            
            // Registrar cambios en historial
            $this->registrarCambios($ticketAnterior, $usuarioId);
            
            Database::commit();
            return true;
            
        } catch (\Exception $e) {
            Database::rollback();
            error_log("Error al actualizar ticket: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Añade un comentario al ticket
     */
    public function addComentario(int $usuarioId, string $comentario, bool $esInterno = false): bool
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            $data = [
                'ticket_id' => $this->id,
                'usuario_id' => $usuarioId,
                'comentario' => $comentario,
                'es_interno' => $esInterno ? 1 : 0
            ];
            
            Database::insert('ticket_comentarios', $data);
            return true;
            
        } catch (\Exception $e) {
            error_log("Error al añadir comentario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Añade tiempo invertido al ticket
     */
    public function addTiempo(int $consultorId, float $horas, string $descripcion = ''): bool
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            Database::beginTransaction();
            
            // Insertar registro de tiempo
            $data = [
                'ticket_id' => $this->id,
                'consultor_id' => $consultorId,
                'horas' => $horas,
                'descripcion' => $descripcion
            ];
            
            Database::insert('ticket_tiempo', $data);
            
            // Actualizar total en ticket
            $sql = "UPDATE tickets SET tiempo_invertido = tiempo_invertido + ? WHERE id = ?";
            Database::query($sql, [$horas, $this->id]);
            
            $this->tiempoInvertido += $horas;
            
            Database::commit();
            return true;
            
        } catch (\Exception $e) {
            Database::rollback();
            error_log("Error al añadir tiempo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Añade un archivo adjunto al ticket
     */
    public function addArchivo(int $usuarioId, string $nombreOriginal, string $nombreArchivo, string $ruta, string $tipoMime, int $tamano): bool
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            $data = [
                'ticket_id' => $this->id,
                'usuario_id' => $usuarioId,
                'nombre_original' => $nombreOriginal,
                'nombre_archivo' => $nombreArchivo,
                'ruta' => $ruta,
                'tipo_mime' => $tipoMime,
                'tamano' => $tamano
            ];
            
            Database::insert('ticket_archivos', $data);
            return true;
            
        } catch (\Exception $e) {
            error_log("Error al añadir archivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene los comentarios del ticket
     */
    public function getComentarios(bool $incluirInternos = false): array
    {
        if ($this->id === null) {
            return [];
        }
        
        $sql = "SELECT c.*, u.nombre as usuario_nombre, u.rol as usuario_rol 
                FROM ticket_comentarios c 
                INNER JOIN usuarios u ON c.usuario_id = u.id 
                WHERE c.ticket_id = ?";
        
        $params = [$this->id];
        
        if (!$incluirInternos) {
            $sql .= " AND c.es_interno = 0";
        }
        
        $sql .= " ORDER BY c.fecha_creacion ASC";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtiene los archivos adjuntos del ticket
     */
    public function getArchivos(): array
    {
        if ($this->id === null) {
            return [];
        }
        
        $sql = "SELECT a.*, u.nombre as usuario_nombre 
                FROM ticket_archivos a 
                INNER JOIN usuarios u ON a.usuario_id = u.id 
                WHERE a.ticket_id = ? 
                ORDER BY a.fecha_subida DESC";
        
        return Database::fetchAll($sql, [$this->id]);
    }
    
    /**
     * Obtiene el historial de tiempos
     */
    public function getTiempos(): array
    {
        if ($this->id === null) {
            return [];
        }
        
        $sql = "SELECT t.*, u.nombre as consultor_nombre 
                FROM ticket_tiempo t 
                INNER JOIN usuarios u ON t.consultor_id = u.id 
                WHERE t.ticket_id = ? 
                ORDER BY t.fecha_registro DESC";
        
        return Database::fetchAll($sql, [$this->id]);
    }
    
    /**
     * Obtiene el historial de cambios
     */
    public function getHistorial(): array
    {
        if ($this->id === null) {
            return [];
        }
        
        $sql = "SELECT h.*, u.nombre as usuario_nombre 
                FROM ticket_historial h 
                INNER JOIN usuarios u ON h.usuario_id = u.id 
                WHERE h.ticket_id = ? 
                ORDER BY h.fecha_cambio DESC";
        
        return Database::fetchAll($sql, [$this->id]);
    }
    
    /**
     * Busca un ticket por ID con información relacionada
     */
    public static function findById(int $id): ?self
    {
        $sql = "SELECT t.*, 
                       c.nombre as cliente_nombre, c.email as cliente_email, c.empresa as cliente_empresa,
                       con.nombre as consultor_nombre,
                       tc.nombre as tipo_consultoria_nombre
                FROM tickets t
                INNER JOIN usuarios c ON t.cliente_id = c.id
                LEFT JOIN usuarios con ON t.consultor_id = con.id
                LEFT JOIN tipos_consultoria tc ON t.tipo_consultoria_id = tc.id
                WHERE t.id = ?";
        
        $data = Database::fetchOne($sql, [$id]);
        
        return $data ? self::hydrate($data) : null;
    }
    
    /**
     * Busca un ticket por número
     */
    public static function findByNumero(string $numero): ?self
    {
        $sql = "SELECT t.*, 
                       c.nombre as cliente_nombre, c.email as cliente_email, c.empresa as cliente_empresa,
                       con.nombre as consultor_nombre,
                       tc.nombre as tipo_consultoria_nombre
                FROM tickets t
                INNER JOIN usuarios c ON t.cliente_id = c.id
                LEFT JOIN usuarios con ON t.consultor_id = con.id
                LEFT JOIN tipos_consultoria tc ON t.tipo_consultoria_id = tc.id
                WHERE t.numero = ?";
        
        $data = Database::fetchOne($sql, [$numero]);
        
        return $data ? self::hydrate($data) : null;
    }
    
    /**
     * Obtiene tickets con filtros
     */
    public static function getTickets(array $filtros = []): array
    {
        $sql = "SELECT t.*, 
                       c.nombre as cliente_nombre, c.empresa as cliente_empresa,
                       con.nombre as consultor_nombre,
                       tc.nombre as tipo_consultoria_nombre
                FROM tickets t
                INNER JOIN usuarios c ON t.cliente_id = c.id
                LEFT JOIN usuarios con ON t.consultor_id = con.id
                LEFT JOIN tipos_consultoria tc ON t.tipo_consultoria_id = tc.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['cliente_id'])) {
            $sql .= " AND t.cliente_id = ?";
            $params[] = $filtros['cliente_id'];
        }
        
        if (!empty($filtros['consultor_id'])) {
            $sql .= " AND t.consultor_id = ?";
            $params[] = $filtros['consultor_id'];
        }
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['prioridad'])) {
            $sql .= " AND t.prioridad = ?";
            $params[] = $filtros['prioridad'];
        }
        
        if (!empty($filtros['tipo_consultoria_id'])) {
            $sql .= " AND t.tipo_consultoria_id = ?";
            $params[] = $filtros['tipo_consultoria_id'];
        }
        
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (t.numero LIKE ? OR t.titulo LIKE ? OR t.descripcion LIKE ?)";
            $busqueda = '%' . $filtros['busqueda'] . '%';
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }
        
        if (!empty($filtros['fuente'])) {
            $sql .= " AND t.fuente = ?";
            $params[] = $filtros['fuente'];
        }
        
        $sql .= " ORDER BY 
                  CASE t.prioridad 
                    WHEN 'critica' THEN 1 
                    WHEN 'alta' THEN 2 
                    WHEN 'media' THEN 3 
                    WHEN 'baja' THEN 4 
                  END,
                  t.fecha_creacion DESC";
        
        $results = Database::fetchAll($sql, $params);
        
        return array_map([self::class, 'hydrate'], $results);
    }
    
    /**
     * Registra cambios en el historial
     */
    private function registrarCambios(self $anterior, int $usuarioId): void
    {
        $campos = [
            'estado' => [$anterior->estado, $this->estado],
            'prioridad' => [$anterior->prioridad, $this->prioridad],
            'consultor_id' => [$anterior->consultorId, $this->consultorId],
            'tipo_consultoria_id' => [$anterior->tipoConsultoriaId, $this->tipoConsultoriaId]
        ];
        
        foreach ($campos as $campo => $valores) {
            if ($valores[0] != $valores[1]) {
                $this->registrarHistorial($usuarioId, $campo, $valores[0], $valores[1]);
            }
        }
    }
    
    /**
     * Registra una entrada en el historial
     */
    private function registrarHistorial(?int $usuarioId, string $campo, $valorAnterior, $valorNuevo): void
    {
        // Si no hay usuario, usar el cliente del ticket (para creación)
        if ($usuarioId === null) {
            $usuarioId = $this->clienteId;
        }
        
        $data = [
            'ticket_id' => $this->id,
            'usuario_id' => $usuarioId,
            'campo' => $campo,
            'valor_anterior' => $valorAnterior,
            'valor_nuevo' => $valorNuevo
        ];
        
        Database::insert('ticket_historial', $data);
    }
    
    /**
     * Hidrata un objeto Ticket desde un array
     */
    private static function hydrate(array $data): self
    {
        $ticket = new self();
        $ticket->id = (int) $data['id'];
        $ticket->numero = $data['numero'];
        $ticket->clienteId = (int) $data['cliente_id'];
        $ticket->tipoConsultoriaId = $data['tipo_consultoria_id'] ? (int) $data['tipo_consultoria_id'] : null;
        $ticket->consultorId = $data['consultor_id'] ? (int) $data['consultor_id'] : null;
        $ticket->titulo = $data['titulo'];
        $ticket->descripcion = $data['descripcion'];
        $ticket->prioridad = $data['prioridad'];
        $ticket->estado = $data['estado'];
        $ticket->tiempoInvertido = (float) $data['tiempo_invertido'];
        $ticket->fechaCreacion = $data['fecha_creacion'];
        $ticket->fechaResolucion = $data['fecha_resolucion'];
        $ticket->fechaCierre = $data['fecha_cierre'];
        
        // Campos email/API
        $ticket->fuente = $data['fuente'] ?? 'manual';
        $ticket->emailMessageId = $data['email_message_id'] ?? null;
        $ticket->emailOriginal = $data['email_original'] ?? null;
        $ticket->datosIa = !empty($data['datos_ia']) ? json_decode($data['datos_ia'], true) : null;
        $ticket->metadata = !empty($data['metadata']) ? json_decode($data['metadata'], true) : null;
        
        // Propiedades dinámicas de JOINs
        $ticket->cliente_nombre = $data['cliente_nombre'] ?? null;
        $ticket->cliente_email = $data['cliente_email'] ?? null;
        $ticket->cliente_empresa = $data['cliente_empresa'] ?? null;
        $ticket->consultor_nombre = $data['consultor_nombre'] ?? null;
        $ticket->tipo_consultoria_nombre = $data['tipo_consultoria_nombre'] ?? null;
        
        return $ticket;
    }
    
    // Getters y Setters
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getNumero(): ?string
    {
        return $this->numero;
    }
    
    public function getClienteId(): int
    {
        return $this->clienteId;
    }
    
    public function setClienteId(int $clienteId): void
    {
        $this->clienteId = $clienteId;
    }
    
    public function getTipoConsultoriaId(): ?int
    {
        return $this->tipoConsultoriaId;
    }
    
    public function setTipoConsultoriaId(?int $tipoConsultoriaId): void
    {
        $this->tipoConsultoriaId = $tipoConsultoriaId;
    }
    
    public function getConsultorId(): ?int
    {
        return $this->consultorId;
    }
    
    public function setConsultorId(?int $consultorId): void
    {
        $this->consultorId = $consultorId;
    }
    
    public function getTitulo(): string
    {
        return $this->titulo;
    }
    
    public function setTitulo(string $titulo): void
    {
        $this->titulo = $titulo;
    }
    
    public function getDescripcion(): string
    {
        return $this->descripcion;
    }
    
    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }
    
    public function getPrioridad(): string
    {
        return $this->prioridad;
    }
    
    public function setPrioridad(string $prioridad): void
    {
        $this->prioridad = $prioridad;
    }
    
    public function getEstado(): string
    {
        return $this->estado;
    }
    
    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }
    
    public function getTiempoInvertido(): float
    {
        return $this->tiempoInvertido;
    }
    
    public function getFechaCreacion(): ?string
    {
        return $this->fechaCreacion;
    }
    
    public function getFechaResolucion(): ?string
    {
        return $this->fechaResolucion;
    }
    
    public function getFechaCierre(): ?string
    {
        return $this->fechaCierre;
    }
    
    // Getters y Setters para campos email/API
    
    public function getFuente(): string
    {
        return $this->fuente;
    }
    
    public function setFuente(string $fuente): void
    {
        $this->fuente = $fuente;
    }
    
    public function getEmailMessageId(): ?string
    {
        return $this->emailMessageId;
    }
    
    public function setEmailMessageId(?string $emailMessageId): void
    {
        $this->emailMessageId = $emailMessageId;
    }
    
    public function getEmailOriginal(): ?string
    {
        return $this->emailOriginal;
    }
    
    public function setEmailOriginal(?string $emailOriginal): void
    {
        $this->emailOriginal = $emailOriginal;
    }
    
    public function getDatosIa(): ?array
    {
        return $this->datosIa;
    }
    
    public function setDatosIa(?array $datosIa): void
    {
        $this->datosIa = $datosIa;
    }
    
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
    
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }
}
