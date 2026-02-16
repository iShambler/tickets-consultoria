<?php
/**
 * Modelo Usuario
 * Gestión de usuarios del sistema
 */

namespace App\Models;

use App\Utils\Database;

class Usuario
{
    private ?int $id = null;
    private string $nombre;
    private string $email;
    private string $password;
    private ?string $empresa = null;
    private ?string $departamento = null;
    private ?string $telefono = null;
    private int $rol = 3; // Cliente por defecto
    private bool $activo = true;
    private bool $creadoAutomaticamente = false;
    
    /**
     * Crea un nuevo usuario en la base de datos
     */
    public function create(): bool
    {
        $data = [
            'nombre' => $this->nombre,
            'email' => $this->email,
            'password' => password_hash($this->password, PASSWORD_DEFAULT),
            'empresa' => $this->empresa,
            'departamento' => $this->departamento,
            'telefono' => $this->telefono,
            'rol' => $this->rol,
            'activo' => $this->activo ? 1 : 0,
            'creado_automaticamente' => $this->creadoAutomaticamente ? 1 : 0
        ];
        
        try {
            $this->id = Database::insert('usuarios', $data);
            return true;
        } catch (\Exception $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza un usuario existente
     */
    public function update(): bool
    {
        if ($this->id === null) {
            return false;
        }
        
        $data = [
            'nombre' => $this->nombre,
            'email' => $this->email,
            'empresa' => $this->empresa,
            'telefono' => $this->telefono,
            'rol' => $this->rol,
            'activo' => $this->activo ? 1 : 0
        ];
        
        try {
            Database::update('usuarios', $data, 'id = ?', [$this->id]);
            return true;
        } catch (\Exception $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza la contraseña del usuario
     */
    public function updatePassword(string $newPassword): bool
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            $data = ['password' => password_hash($newPassword, PASSWORD_DEFAULT)];
            Database::update('usuarios', $data, 'id = ?', [$this->id]);
            return true;
        } catch (\Exception $e) {
            error_log("Error al actualizar contraseña: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un usuario (soft delete desactivando)
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            Database::update('usuarios', ['activo' => 0], 'id = ?', [$this->id]);
            return true;
        } catch (\Exception $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca un usuario por ID
     */
    public static function findById(int $id): ?self
    {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $data = Database::fetchOne($sql, [$id]);
        
        return $data ? self::hydrate($data) : null;
    }
    
    /**
     * Busca un usuario por email
     */
    public static function findByEmail(string $email): ?self
    {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $data = Database::fetchOne($sql, [$email]);
        
        return $data ? self::hydrate($data) : null;
    }
    
    /**
     * Obtiene todos los usuarios activos
     */
    public static function getAll(int $rol = null, bool $soloActivos = true): array
    {
        $sql = "SELECT * FROM usuarios WHERE 1=1";
        $params = [];
        
        if ($soloActivos) {
            $sql .= " AND activo = 1";
        }
        
        if ($rol !== null) {
            $sql .= " AND rol = ?";
            $params[] = $rol;
        }
        
        $sql .= " ORDER BY nombre ASC";
        
        $results = Database::fetchAll($sql, $params);
        
        return array_map([self::class, 'hydrate'], $results);
    }
    
    /**
     * Obtiene todos los consultores
     */
    public static function getConsultores(): array
    {
        return self::getAll(2);
    }
    
    /**
     * Verifica las credenciales de login
     */
    public static function login(string $email, string $password): ?self
    {
        $usuario = self::findByEmail($email);
        
        if ($usuario && $usuario->activo && password_verify($password, $usuario->password)) {
            // Actualizar último acceso
            Database::update('usuarios', ['ultimo_acceso' => date('Y-m-d H:i:s')], 'id = ?', [$usuario->id]);
            return $usuario;
        }
        
        return null;
    }
    
    /**
     * Crea un usuario automáticamente desde un email (para tickets vía API/n8n)
     * Si el email ya existe, devuelve el usuario existente
     */
    public static function createFromEmail(string $email, string $nombre = '', ?string $empresa = null, ?string $departamento = null): self
    {
        // Si ya existe, devolver el existente
        $existing = self::findByEmail($email);
        if ($existing) {
            return $existing;
        }
        
        // Si no viene nombre, usar la parte antes del @ del email
        if (empty($nombre)) {
            $nombre = ucfirst(explode('@', $email)[0]);
        }
        
        // Generar password aleatorio (el usuario podrá cambiarlo después)
        $randomPassword = bin2hex(random_bytes(16));
        
        $usuario = new self();
        $usuario->setNombre($nombre);
        $usuario->setEmail($email);
        $usuario->setPassword($randomPassword);
        $usuario->setEmpresa($empresa);
        $usuario->setDepartamento($departamento);
        $usuario->setRol(3); // Cliente
        $usuario->setActivo(true);
        $usuario->setCreadoAutomaticamente(true);
        
        if (!$usuario->create()) {
            throw new \RuntimeException("No se pudo crear el usuario automático para: {$email}");
        }
        
        error_log("Usuario creado automáticamente desde email: {$email} (ID: {$usuario->getId()})");
        
        return $usuario;
    }
    
    /**
     * Verifica si el email ya existe
     */
    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM usuarios WHERE email = ?";
        $params = [$email];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = Database::fetchOne($sql, $params);
        
        return $result['count'] > 0;
    }
    
    /**
     * Hidrata un objeto Usuario desde un array
     */
    private static function hydrate(array $data): self
    {
        $usuario = new self();
        $usuario->id = (int) $data['id'];
        $usuario->nombre = $data['nombre'];
        $usuario->email = $data['email'];
        $usuario->password = $data['password'];
        $usuario->empresa = $data['empresa'];
        $usuario->departamento = $data['departamento'] ?? null;
        $usuario->telefono = $data['telefono'];
        $usuario->rol = (int) $data['rol'];
        $usuario->activo = (bool) $data['activo'];
        $usuario->creadoAutomaticamente = (bool) ($data['creado_automaticamente'] ?? false);
        
        return $usuario;
    }
    
    /**
     * Verifica si el usuario es administrador
     */
    public function isAdmin(): bool
    {
        return $this->rol === 1;
    }
    
    /**
     * Verifica si el usuario es consultor
     */
    public function isConsultor(): bool
    {
        return $this->rol === 2;
    }
    
    /**
     * Verifica si el usuario es cliente
     */
    public function isCliente(): bool
    {
        return $this->rol === 3;
    }
    
    /**
     * Obtiene el nombre del rol
     */
    public function getRolNombre(): string
    {
        return match($this->rol) {
            1 => 'Administrador',
            2 => 'Consultor',
            3 => 'Cliente',
            default => 'Desconocido'
        };
    }
    
    // Getters y Setters
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getNombre(): string
    {
        return $this->nombre;
    }
    
    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
    
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
    
    public function getEmpresa(): ?string
    {
        return $this->empresa;
    }
    
    public function setEmpresa(?string $empresa): void
    {
        $this->empresa = $empresa;
    }
    
    public function getTelefono(): ?string
    {
        return $this->telefono;
    }
    
    public function setTelefono(?string $telefono): void
    {
        $this->telefono = $telefono;
    }
    
    public function getRol(): int
    {
        return $this->rol;
    }
    
    public function setRol(int $rol): void
    {
        $this->rol = $rol;
    }
    
    public function isActivo(): bool
    {
        return $this->activo;
    }
    
    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }
    
    public function getDepartamento(): ?string
    {
        return $this->departamento;
    }
    
    public function setDepartamento(?string $departamento): void
    {
        $this->departamento = $departamento;
    }
    
    public function isCreadoAutomaticamente(): bool
    {
        return $this->creadoAutomaticamente;
    }
    
    public function setCreadoAutomaticamente(bool $creadoAutomaticamente): void
    {
        $this->creadoAutomaticamente = $creadoAutomaticamente;
    }
}
