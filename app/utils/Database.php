<?php
/**
 * Clase Database
 * Maneja la conexión a la base de datos usando PDO
 */

namespace App\Utils;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private static ?self $instance = null;
    
    private function __construct()
    {
        // Constructor privado para patrón Singleton
    }
    
    /**
     * Obtiene la instancia única de la clase
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Obtiene la conexión PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $config = require __DIR__ . '/../config/database.php';
                
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    $config['host'],
                    $config['database'],
                    $config['charset']
                );
                
                self::$connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
                
            } catch (PDOException $e) {
                error_log("Error de conexión a base de datos: " . $e->getMessage());
                throw new \RuntimeException("No se pudo conectar a la base de datos");
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Ejecuta una consulta preparada
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Error en consulta SQL: " . $e->getMessage());
            throw new \RuntimeException("Error al ejecutar la consulta");
        }
    }
    
    /**
     * Obtiene una sola fila
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        
        return $result !== false ? $result : null;
    }
    
    /**
     * Obtiene todas las filas
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Inserta un registro y retorna el ID
     */
    public static function insert(string $table, array $data): int
    {
        $fields = array_keys($data);
        $values = array_values($data);
        
        $fieldList = implode(', ', $fields);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        
        $sql = "INSERT INTO {$table} ({$fieldList}) VALUES ({$placeholders})";
        
        self::query($sql, $values);
        
        return (int) self::getConnection()->lastInsertId();
    }
    
    /**
     * Actualiza un registro
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $setParts[] = "{$field} = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $stmt = self::query($sql, array_merge($values, $whereParams));
        
        return $stmt->rowCount();
    }
    
    /**
     * Elimina registros
     */
    public static function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $whereParams);
        
        return $stmt->rowCount();
    }
    
    /**
     * Inicia una transacción
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Confirma una transacción
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }
    
    /**
     * Revierte una transacción
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }
    
    /**
     * Cierra la conexión
     */
    public static function close(): void
    {
        self::$connection = null;
    }
}
