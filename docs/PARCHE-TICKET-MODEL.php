<?php
/**
 * PARCHE PARA MODELO TICKET
 * Añadir estas propiedades y métodos al archivo app/models/Ticket.php
 */

// ============================================
// AÑADIR ESTAS PROPIEDADES DESPUÉS DE $fechaCierre
// ============================================

    private string $fuente = 'manual';
    private ?string $emailMessageId = null;
    private ?string $emailOriginal = null;
    private ?array $datosIa = null;
    private ?array $metadata = null;

// ============================================
// MODIFICAR EL MÉTODO create() PARA INCLUIR LOS NUEVOS CAMPOS
// Reemplazar la línea $data = [ ... ]; con:
// ============================================

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

// ============================================
// MODIFICAR EL MÉTODO hydrate() PARA INCLUIR LOS NUEVOS CAMPOS
// Añadir estas líneas ANTES del return $ticket;
// ============================================

        $ticket->fuente = $data['fuente'] ?? 'manual';
        $ticket->emailMessageId = $data['email_message_id'] ?? null;
        $ticket->emailOriginal = $data['email_original'] ?? null;
        $ticket->datosIa = !empty($data['datos_ia']) ? json_decode($data['datos_ia'], true) : null;
        $ticket->metadata = !empty($data['metadata']) ? json_decode($data['metadata'], true) : null;

// ============================================
// AÑADIR ESTOS GETTERS Y SETTERS AL FINAL, ANTES DEL CIERRE DE LA CLASE
// ============================================

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
