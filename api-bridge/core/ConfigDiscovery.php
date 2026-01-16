<?php
/**
 * Metin2 Web Panel - Config Discovery
 * Auto-detects database credentials from Metin2 server CONFIG files
 */

class ConfigDiscovery
{
    private const SEARCH_PATHS = [
        '/usr/game',
        '/usr/home/game',
        '/home/game',
        '/home/mt2',
        '/var/game',
        '/root/game',
        '/root/server',
        '/usr/local/game'
    ];

    private const SUB_DIRS = [
        '',
        'auth',
        'db',
        'channel1',
        'channel2',
        'channel3',
        'channel4',
        'game1',
        'game99',
        'g1/auth',
        'g1/db',
        'g1/channel1'
    ];

    private const CONFIG_FILES = ['CONFIG', 'conf.txt', 'Conf.txt', 'db_conf.txt'];

    private array $log = [];
    private ?array $credentials = null;

    /**
     * Discover database credentials from Metin2 CONFIG files
     */
    public function discover(): ?array
    {
        if ($this->credentials) {
            return $this->credentials;
        }

        $this->log[] = "Starting discovery...";

        foreach (self::SEARCH_PATHS as $basePath) {
            if (!is_dir($basePath))
                continue;
            $this->log[] = "Scanning: $basePath";

            foreach (self::SUB_DIRS as $subDir) {
                $dirPath = $subDir ? "$basePath/$subDir" : $basePath;
                if (!is_dir($dirPath))
                    continue;

                foreach (self::CONFIG_FILES as $fileName) {
                    $filePath = "$dirPath/$fileName";
                    if (file_exists($filePath) && is_readable($filePath)) {
                        $this->log[] = "Found: $filePath";
                        $creds = $this->parseConfigFile($filePath);
                        if ($creds) {
                            $this->credentials = $creds;
                            $this->log[] = "SUCCESS: Parsed credentials from $filePath";
                            return $creds;
                        }
                    }
                }
            }
        }

        $this->log[] = "No valid config found";
        return null;
    }

    /**
     * Parse CONFIG file for SQL credentials
     * Supports both PLAYER_SQL and SQL_PLAYER formats
     */
    private function parseConfigFile(string $filePath): ?array
    {
        $content = @file_get_contents($filePath);
        if (!$content)
            return null;

        $result = [
            'account' => null,
            'player' => null,
            'common' => null,
            'log' => null
        ];

        $patterns = [
            // Format: PLAYER_SQL: host user pass db
            'player' => [
                '/^(?:PLAYER_SQL|SQL_PLAYER)\s*[:=]\s*(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/im',
            ],
            'account' => [
                '/^(?:ACCOUNT_SQL|SQL_ACCOUNT)\s*[:=]\s*(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/im',
            ],
            'common' => [
                '/^(?:COMMON_SQL|SQL_COMMON)\s*[:=]\s*(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/im',
            ],
            'log' => [
                '/^(?:LOG_SQL|SQL_LOG)\s*[:=]\s*(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/im',
            ]
        ];

        foreach ($patterns as $dbType => $patternList) {
            foreach ($patternList as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $result[$dbType] = [
                        'host' => $matches[1] === 'localhost' ? '127.0.0.1' : $matches[1],
                        'user' => $matches[2],
                        'pass' => $matches[3],
                        'db' => $matches[4],
                        'port' => 3306
                    ];
                    break;
                }
            }
        }

        // If we found at least player or account, use that as base for missing ones
        $base = $result['player'] ?? $result['account'] ?? $result['common'];
        if (!$base)
            return null;

        // Fill missing with defaults based on found credentials
        foreach (['account', 'player', 'common', 'log'] as $dbType) {
            if (!$result[$dbType]) {
                $result[$dbType] = [
                    'host' => $base['host'],
                    'user' => $base['user'],
                    'pass' => $base['pass'],
                    'db' => $dbType, // Default DB name = type
                    'port' => 3306
                ];
            }
        }

        return $result;
    }

    public function getLog(): array
    {
        return $this->log;
    }
}
