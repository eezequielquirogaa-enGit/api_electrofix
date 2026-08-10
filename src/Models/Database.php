<?php
 
namespace App\Models;
 
use PDO;
use PDOException;
 
/**
 * Clase Base de Acceso a Datos
 *
 * Encapsula la conexión mediante PDO y proporciona
 * métodos auxiliares para ejecutar sentencias SQL.
 */
class Database
{
    protected PDO $connection;
 
    public function __construct()
    {
        // Acceso a variables de entorno inyectadas por phpdotenv
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'api_electrofix';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
    
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new PDOException("Error de conexión: " . $e->getMessage());
        }
    }
 
    /** Ejecuta una consulta preparada y retorna todos los registros */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
 
    /** Ejecuta una consulta preparada y retorna un único registro */
    public function fetchOne(string $query, array $params = []): array
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ? $result : [];
    }
 
    /** Ejecuta una sentencia de inserción, actualización o eliminación */
    public function execute(string $query, array $params = []): bool
    {
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }
}