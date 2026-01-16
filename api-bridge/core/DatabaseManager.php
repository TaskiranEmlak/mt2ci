<?php
/**
 * Metin2 Web Panel - Database Manager
 * Singleton pattern with multi-database support
 * 
 * Supports: account, player, common, log databases
 */

require_once __DIR__ . '/ConfigDiscovery.php';

class DatabaseManager
{
    private static ?DatabaseManager $instance = null;

    private array $connections = [];
    private array $config = [];
    private array $discoveryLog = [];

    private function __construct()
    {
        $this->initializeConfig();
    }

    public static function getInstance(): DatabaseManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize configuration (auto-discovery or manual)
     */
    private function initializeConfig(): void
    {
        // Try to load manual config first
        $manualConfigPath = __DIR__ . '/../config.php';
        if (file_exists($manualConfigPath)) {
            $manualConfig = require $manualConfigPath;
            if (!empty($manualConfig['databases'])) {
                $this->config = $manualConfig['databases'];
                $this->discoveryLog[] = "Using manual config.php";
                return;
            }
        }

        // Fallback to auto-discovery
        $discovery = new ConfigDiscovery();
        $discovered = $discovery->discover();
        $this->discoveryLog = $discovery->getLog();

        if ($discovered) {
            $this->config = $discovered;
            $this->discoveryLog[] = "Using auto-discovered config";
        }
    }

    /**
     * Get PDO connection for specified database
     */
    public function getConnection(string $dbType = 'account'): ?PDO
    {
        // Return cached connection if exists
        if (isset($this->connections[$dbType])) {
            return $this->connections[$dbType];
        }

        // Check if config exists for this DB type
        if (!isset($this->config[$dbType])) {
            throw new Exception("No configuration found for database: $dbType");
        }

        $cfg = $this->config[$dbType];

        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
            $cfg['host'],
            $cfg['port'] ?? 3306,
            $cfg['db']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        $this->connections[$dbType] = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
        return $this->connections[$dbType];
    }

    /**
     * Shorthand for account database
     */
    public function account(): PDO
    {
        return $this->getConnection('account');
    }

    /**
     * Shorthand for player database
     */
    public function player(): PDO
    {
        return $this->getConnection('player');
    }

    /**
     * Shorthand for common database
     */
    public function common(): PDO
    {
        return $this->getConnection('common');
    }

    /**
     * Shorthand for log database
     */
    public function log(): PDO
    {
        return $this->getConnection('log');
    }

    /**
     * Execute SELECT query with prepared statement
     */
    public function select(string $dbType, string $query, array $params = []): array
    {
        $stmt = $this->getConnection($dbType)->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute SELECT query and return single row
     */
    public function selectOne(string $dbType, string $query, array $params = []): ?array
    {
        $stmt = $this->getConnection($dbType)->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute INSERT/UPDATE/DELETE query
     */
    public function execute(string $dbType, string $query, array $params = []): int
    {
        $stmt = $this->getConnection($dbType)->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Check if table exists
     */
    public function tableExists(string $dbType, string $table): bool
    {
        try {
            $this->getConnection($dbType)->query("SELECT 1 FROM `$table` LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get table columns
     */
    public function getColumns(string $dbType, string $table): array
    {
        try {
            $stmt = $this->getConnection($dbType)->query("DESCRIBE `$table`");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Check if connected to all databases
     */
    public function isConnected(): bool
    {
        try {
            $this->getConnection('account');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get discovery/connection log
     */
    public function getLog(): array
    {
        return $this->discoveryLog;
    }

    /**
     * Get current config (for debugging)
     */
    public function getConfig(): array
    {
        // Hide passwords
        $safe = [];
        foreach ($this->config as $db => $cfg) {
            $safe[$db] = [
                'host' => $cfg['host'] ?? null,
                'db' => $cfg['db'] ?? null,
                'user' => $cfg['user'] ?? null,
                'pass' => '***'
            ];
        }
        return $safe;
    }
}
