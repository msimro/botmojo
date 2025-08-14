<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Tools
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Tools;

use BotMojo\Core\AbstractTool;
use BotMojo\Exceptions\BotMojoException;
use PDO;
use PDOException;

/**
 * Database Tool
 *
 * Provides database access and operations for the BotMojo system.
 */
class DatabaseTool extends AbstractTool
{
    /**
     * Required configuration keys
     *
     * @var array<string>
     */
    private const REQUIRED_CONFIG = ['host', 'user', 'password', 'database'];
    
    /**
     * PDO connection instance
     *
     * @var PDO|null
     */
    private ?PDO $connection = null;
    
    /**
     * Validate the configuration
     *
     * Ensure that all required configuration parameters are present.
     *
     * @throws BotMojoException If configuration is invalid
     * @return void
     */
    protected function validateConfig(): void
    {
        foreach (self::REQUIRED_CONFIG as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                throw new BotMojoException(
                    "Missing required configuration: {$key}",
                    ['tool' => 'DatabaseTool']
                );
            }
        }
    }
    
    /**
     * Get database connection
     *
     * Create a new connection if one doesn't exist yet.
     *
     * @throws BotMojoException If connection fails
     * @return PDO The database connection
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    $this->config['host'],
                    $this->config['database']
                );
                
                $this->connection = new PDO(
                    $dsn,
                    $this->config['user'],
                    $this->config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new BotMojoException(
                    "Database connection failed: " . $e->getMessage(),
                    [],
                    0,
                    $e
                );
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Execute a query and return the results
     *
     * @param string        $query  The SQL query to execute
     * @param array<mixed>  $params The parameters to bind to the query
     *
     * @throws BotMojoException If the query fails
     * @return array<array<string, mixed>> The query results
     */
    public function query(string $query, array $params = []): array
    {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new BotMojoException(
                "Database query failed: " . $e->getMessage(),
                ['query' => $query, 'params' => $params],
                0,
                $e
            );
        }
    }
    
    /**
     * Execute a query and return the number of affected rows
     *
     * @param string       $query  The SQL query to execute
     * @param array<mixed> $params The parameters to bind to the query
     *
     * @throws BotMojoException If the query fails
     * @return int The number of affected rows
     */
    public function execute(string $query, array $params = []): int
    {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new BotMojoException(
                "Database execution failed: " . $e->getMessage(),
                ['query' => $query, 'params' => $params],
                0,
                $e
            );
        }
    }
    
    /**
     * Insert a record and return the last insert ID
     *
     * @param string                $table  The table to insert into
     * @param array<string, mixed>  $data   The data to insert
     *
     * @throws BotMojoException If the insert fails
     * @return int The last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute(array_values($data));
            return (int) $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            throw new BotMojoException(
                "Database insert failed: " . $e->getMessage(),
                ['table' => $table, 'data' => $data],
                0,
                $e
            );
        }
    }
    
    /**
     * Update records in a table
     *
     * @param string                $table     The table to update
     * @param array<string, mixed>  $data      The data to update
     * @param string                $condition The WHERE condition
     * @param array<mixed>          $params    The parameters for the condition
     *
     * @throws BotMojoException If the update fails
     * @return int The number of affected rows
     */
    public function update(string $table, array $data, string $condition, array $params = []): int
    {
        $setStatements = [];
        foreach ($data as $column => $value) {
            $setStatements[] = "{$column} = ?";
        }
        
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setStatements),
            $condition
        );
        
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute(array_merge(array_values($data), $params));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new BotMojoException(
                "Database update failed: " . $e->getMessage(),
                ['table' => $table, 'data' => $data, 'condition' => $condition],
                0,
                $e
            );
        }
    }
}
