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
use PDOStatement;

/**
 * Database Tool
 *
 * Provides database access and operations for the BotMojo system.
 * Features:
 * - Connection pooling
 * - Prepared statement caching
 * - Transaction support
 * - Query logging in debug mode
 * - Connection health checks
 * - Automatic reconnection
 */
class DatabaseTool extends AbstractTool
{
    /**
     * Required configuration keys
     *
     * @var array<string>
     */
    private const REQUIRED_CONFIG = [
        'host',
        'user',
        'password',
        'database',
        'charset',
        'port'
    ];

    /**
     * Default configuration values
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_CONFIG = [
        'charset' => 'utf8mb4',
        'port' => 3306,
        'timeout' => 5,
        'persistent' => false,
        'ssl' => false,
        'retry_attempts' => 3,
        'retry_delay' => 1
    ];
    
    /**
     * PDO connection instance
     *
     * @var PDO|null
     */
    private ?PDO $connection = null;

    /**
     * Prepared statement cache
     *
     * @var array<string, PDOStatement>
     */
    private array $statementCache = [];

    /**
     * Last query execution time
     *
     * @var float
     */
    private float $lastQueryTime = 0.0;

    /**
     * Whether we're currently in a transaction
     *
     * @var bool
     */
    private bool $inTransaction = false;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Configuration parameters
     */
    public function __construct(array $config = [])
    {
        // Merge with default config
        $config = array_merge(self::DEFAULT_CONFIG, $config);
        parent::__construct($config);
    }

    /**
     * Initialize the tool
     *
     * @param array<string, mixed> $config Configuration parameters
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->validateConfig();
    }

    /**
     * Validate the configuration
     *
     * @throws BotMojoException If configuration is invalid
     */
    protected function validateConfig(): void
    {
        $missing = [];
        foreach (self::REQUIRED_CONFIG as $key) {
            if (!isset($this->config[$key]) && !isset(self::DEFAULT_CONFIG[$key])) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new BotMojoException(
                'Missing required database configuration',
                500,
                null,
                ['missing' => $missing]
            );
        }
    }
    
    /**
     * Get database connection with retry logic
     *
     * @throws BotMojoException If connection fails after retries
     * @return PDO The database connection
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null || !$this->isConnectionAlive()) {
            $attempts = 0;
            $lastException = null;

            while ($attempts < $this->config['retry_attempts']) {
                try {
                    $this->connection = $this->createConnection();
                    return $this->connection;
                } catch (PDOException $e) {
                    $lastException = $e;
                    $attempts++;
                    if ($attempts < $this->config['retry_attempts']) {
                        sleep($this->config['retry_delay']);
                    }
                }
            }

            throw new BotMojoException(
                'Database connection failed after retries',
                500,
                $lastException,
                [
                    'attempts' => $attempts,
                    'host' => $this->config['host'],
                    'database' => $this->config['database']
                ]
            );
        }
        
        return $this->connection;
    }

    /**
     * Create a new database connection
     *
     * @throws PDOException If connection fails
     * @return PDO
     */
    private function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => $this->config['persistent'],
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']}"
        ];

        if ($this->config['ssl']) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $this->config['ssl_ca'] ?? null;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        return new PDO($dsn, $this->config['user'], $this->config['password'], $options);
    }

    /**
     * Check if the current connection is alive
     *
     * @return bool
     */
    private function isConnectionAlive(): bool
    {
        if ($this->connection === null) {
            return false;
        }

        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * Begin a transaction
     *
     * @throws BotMojoException If transaction fails to start
     */
    public function beginTransaction(): void
    {
        if ($this->inTransaction) {
            throw new BotMojoException('Transaction already in progress', 400);
        }

        try {
            $this->getConnection()->beginTransaction();
            $this->inTransaction = true;
        } catch (PDOException $e) {
            throw new BotMojoException(
                'Failed to start transaction',
                500,
                $e,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Commit the current transaction
     *
     * @throws BotMojoException If commit fails
     */
    public function commit(): void
    {
        if (!$this->inTransaction) {
            throw new BotMojoException('No transaction in progress', 400);
        }

        try {
            $this->getConnection()->commit();
            $this->inTransaction = false;
        } catch (PDOException $e) {
            throw new BotMojoException(
                'Failed to commit transaction',
                500,
                $e,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Rollback the current transaction
     *
     * @throws BotMojoException If rollback fails
     */
    public function rollback(): void
    {
        if (!$this->inTransaction) {
            throw new BotMojoException('No transaction in progress', 400);
        }

        try {
            $this->getConnection()->rollBack();
            $this->inTransaction = false;
        } catch (PDOException $e) {
            throw new BotMojoException(
                'Failed to rollback transaction',
                500,
                $e,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Execute a query and return the results
     *
     * @param string       $query  The SQL query to execute
     * @param array<mixed> $params The parameters to bind to the query
     * @param bool         $useCache Whether to use prepared statement cache
     *
     * @throws BotMojoException If the query fails
     * @return array<array<string, mixed>> The query results
     */
    public function query(string $query, array $params = [], bool $useCache = true): array
    {
        $start = microtime(true);
        try {
            $stmt = $this->prepareStatement($query, $useCache);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            $this->lastQueryTime = microtime(true) - $start;
            $this->logQuery($query, $params, $this->lastQueryTime);
            
            return $result;
        } catch (PDOException $e) {
            throw new BotMojoException(
                'Query execution failed',
                500,
                $e,
                [
                    'query' => $query,
                    'params' => $params,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Execute a query and return a single row
     *
     * @param string       $query  The SQL query to execute
     * @param array<mixed> $params The parameters to bind to the query
     * @param bool         $useCache Whether to use prepared statement cache
     *
     * @throws BotMojoException If the query fails
     * @return array<string, mixed>|null The query result or null if no rows
     */
    public function queryOne(string $query, array $params = [], bool $useCache = true): ?array
    {
        $results = $this->query($query, $params, $useCache);
        return $results[0] ?? null;
    }

    /**
     * Execute a query and return a single value
     *
     * @param string       $query  The SQL query to execute
     * @param array<mixed> $params The parameters to bind to the query
     * @param bool         $useCache Whether to use prepared statement cache
     *
     * @throws BotMojoException If the query fails
     * @return mixed The query result or null if no value
     */
    public function queryValue(string $query, array $params = [], bool $useCache = true): mixed
    {
        $result = $this->queryOne($query, $params, $useCache);
        return $result ? reset($result) : null;
    }

    /**
     * Insert a record and return the last insert ID
     *
     * @param string               $table The table to insert into
     * @param array<string, mixed> $data  The data to insert
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
            $this->escapeIdentifier($table),
            implode(', ', array_map([$this, 'escapeIdentifier'], $columns)),
            implode(', ', $placeholders)
        );
        
        try {
            $stmt = $this->prepareStatement($query);
            $stmt->execute(array_values($data));
            return (int) $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            throw new BotMojoException(
                'Insert operation failed',
                500,
                $e,
                [
                    'table' => $table,
                    'data' => $data,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Update records in a table
     *
     * @param string               $table     The table to update
     * @param array<string, mixed> $data      The data to update
     * @param string               $condition The WHERE condition
     * @param array<mixed>         $params    The parameters for the condition
     *
     * @throws BotMojoException If the update fails
     * @return int The number of affected rows
     */
    public function update(string $table, array $data, string $condition, array $params = []): int
    {
        $setStatements = [];
        foreach ($data as $column => $value) {
            $setStatements[] = $this->escapeIdentifier($column) . " = ?";
        }
        
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $this->escapeIdentifier($table),
            implode(', ', $setStatements),
            $condition
        );
        
        try {
            $stmt = $this->prepareStatement($query);
            $stmt->execute(array_merge(array_values($data), $params));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new BotMojoException(
                'Update operation failed',
                500,
                $e,
                [
                    'table' => $table,
                    'data' => $data,
                    'condition' => $condition,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Delete records from a table
     *
     * @param string       $table     The table to delete from
     * @param string       $condition The WHERE condition
     * @param array<mixed> $params    The parameters for the condition
     *
     * @throws BotMojoException If the delete fails
     * @return int The number of affected rows
     */
    public function delete(string $table, string $condition, array $params = []): int
    {
        $query = sprintf(
            "DELETE FROM %s WHERE %s",
            $this->escapeIdentifier($table),
            $condition
        );
        
        try {
            $stmt = $this->prepareStatement($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new BotMojoException(
                'Delete operation failed',
                500,
                $e,
                [
                    'table' => $table,
                    'condition' => $condition,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Prepare a statement with optional caching
     *
     * @param string $query    The SQL query
     * @param bool   $useCache Whether to use statement cache
     *
     * @return PDOStatement
     */
    private function prepareStatement(string $query, bool $useCache = true): PDOStatement
    {
        if ($useCache && isset($this->statementCache[$query])) {
            return $this->statementCache[$query];
        }

        $stmt = $this->getConnection()->prepare($query);
        
        if ($useCache) {
            $this->statementCache[$query] = $stmt;
        }
        
        return $stmt;
    }

    /**
     * Escape a SQL identifier (table or column name)
     *
     * @param string $identifier The identifier to escape
     *
     * @return string The escaped identifier
     */
    private function escapeIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Log a query execution
     *
     * @param string       $query    The SQL query
     * @param array<mixed> $params   The query parameters
     * @param float       $duration The query execution time
     */
    private function logQuery(string $query, array $params, float $duration): void
    {
        if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
            return;
        }

        $context = [
            'query' => $query,
            'params' => $params,
            'duration' => sprintf('%.4f', $duration),
            'connection' => [
                'host' => $this->config['host'],
                'database' => $this->config['database']
            ]
        ];

        error_log(sprintf(
            "[DatabaseTool] Query executed in %.4fs: %s",
            $duration,
            json_encode($context, JSON_PRETTY_PRINT)
        ));
    }

    /**
     * Get the last query execution time
     *
     * @return float The execution time in seconds
     */
    public function getLastQueryTime(): float
    {
        return $this->lastQueryTime;
    }

    /**
     * Clear the prepared statement cache
     */
    public function clearStatementCache(): void
    {
        $this->statementCache = [];
    }
}
