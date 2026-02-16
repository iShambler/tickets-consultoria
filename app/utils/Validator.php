<?php
/**
 * Clase Validator
 * Validación de datos de entrada
 */

namespace App\Utils;

class Validator
{
    private array $errors = [];
    private array $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * Valida campo requerido
     */
    public function required(string $field, string $message = null): self
    {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field][] = $message ?? "El campo {$field} es obligatorio";
        }
        
        return $this;
    }
    
    /**
     * Valida email
     */
    public function email(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "El email no es válido";
        }
        
        return $this;
    }
    
    /**
     * Valida longitud mínima
     */
    public function min(string $field, int $min, string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field][] = $message ?? "El campo debe tener al menos {$min} caracteres";
        }
        
        return $this;
    }
    
    /**
     * Valida longitud máxima
     */
    public function max(string $field, int $max, string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field][] = $message ?? "El campo no puede exceder {$max} caracteres";
        }
        
        return $this;
    }
    
    /**
     * Valida que el valor esté en una lista
     */
    public function in(string $field, array $values, string $message = null): self
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values, true)) {
            $this->errors[$field][] = $message ?? "El valor seleccionado no es válido";
        }
        
        return $this;
    }
    
    /**
     * Valida número entero
     */
    public function integer(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = $message ?? "El campo debe ser un número entero";
        }
        
        return $this;
    }
    
    /**
     * Valida número decimal
     */
    public function numeric(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = $message ?? "El campo debe ser un número";
        }
        
        return $this;
    }
    
    /**
     * Valida formato de fecha
     */
    public function date(string $field, string $format = 'Y-m-d', string $message = null): self
    {
        if (isset($this->data[$field])) {
            $date = \DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field][] = $message ?? "El formato de fecha no es válido";
            }
        }
        
        return $this;
    }
    
    /**
     * Valida expresión regular
     */
    public function regex(string $field, string $pattern, string $message = null): self
    {
        if (isset($this->data[$field]) && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field][] = $message ?? "El formato del campo no es válido";
        }
        
        return $this;
    }
    
    /**
     * Valida archivo subido
     */
    public function file(string $field, array $allowedTypes = [], int $maxSize = 0, string $message = null): self
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return $this;
        }
        
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field][] = "Error al subir el archivo";
            return $this;
        }
        
        // Validar tipo
        if (!empty($allowedTypes)) {
            $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes, true)) {
                $this->errors[$field][] = $message ?? "Tipo de archivo no permitido";
                return $this;
            }
        }
        
        // Validar tamaño
        if ($maxSize > 0 && $_FILES[$field]['size'] > $maxSize) {
            $maxMB = round($maxSize / 1048576, 2);
            $this->errors[$field][] = "El archivo no puede exceder {$maxMB}MB";
        }
        
        return $this;
    }
    
    /**
     * Verifica si hay errores
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Verifica si la validación pasó
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * Obtiene los errores
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * Obtiene el primer error de un campo
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Limpia y sanitiza los datos
     */
    public static function sanitize(array $data): array
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $cleaned[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } elseif (is_array($value)) {
                $cleaned[$key] = self::sanitize($value);
            } else {
                $cleaned[$key] = $value;
            }
        }
        
        return $cleaned;
    }
}
