<?php
/**
 * Metin2 Web Panel - Auth Manager
 * Handles login, password verification, and session management
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class AuthManager
{
    private DatabaseManager $db;
    private ?array $currentUser = null;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Login user with username and password
     */
    public function login(string $username, string $password): array
    {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Kullanıcı adı ve şifre gerekli'];
        }

        // Find account
        $account = $this->db->selectOne(
            'account',
            "SELECT * FROM account WHERE login = ?",
            [$username]
        );

        if (!$account) {
            return ['success' => false, 'error' => 'Geçersiz kullanıcı adı veya şifre'];
        }

        // Check ban status
        $status = $account['status'] ?? 'OK';
        if ($status !== 'OK') {
            $banDate = $account['availDt'] ?? null;
            $message = 'Hesabınız engellenmiş';
            if ($banDate) {
                $message .= ". Açılış: $banDate";
            }
            return ['success' => false, 'error' => $message];
        }

        // Verify password
        $storedHash = $account['password'] ?? '';
        if (!$this->verifyPassword($password, $storedHash)) {
            return ['success' => false, 'error' => 'Geçersiz kullanıcı adı veya şifre'];
        }

        // Generate session token
        $token = $this->generateToken($account);

        return [
            'success' => true,
            'token' => $token,
            'account_id' => $account['id'],
            'login' => $account['login']
        ];
    }

    /**
     * Verify password using multiple hash methods
     * Supports: MySQL PASSWORD(), SHA1, MD5, plain (not recommended)
     */
    private function verifyPassword(string $password, string $storedHash): bool
    {
        // MySQL PASSWORD() function: *SHA1(SHA1(binary))
        // 41 characters starting with *
        if (strlen($storedHash) === 41 && $storedHash[0] === '*') {
            $expected = '*' . strtoupper(sha1(sha1($password, true)));
            return hash_equals($expected, strtoupper($storedHash));
        }

        // Plain SHA1: 40 hex characters
        if (strlen($storedHash) === 40 && ctype_xdigit($storedHash)) {
            return hash_equals(strtolower(sha1($password)), strtolower($storedHash));
        }

        // MD5: 32 hex characters
        if (strlen($storedHash) === 32 && ctype_xdigit($storedHash)) {
            return hash_equals(strtolower(md5($password)), strtolower($storedHash));
        }

        // Plain text (not secure, but some servers use it)
        return $password === $storedHash;
    }

    /**
     * Generate JWT-like token
     */
    private function generateToken(array $account): string
    {
        $payload = [
            'uid' => $account['id'],
            'login' => $account['login'],
            'iat' => time(),
            'exp' => time() + 86400 // 24 hours
        ];
        return base64_encode(json_encode($payload));
    }

    /**
     * Validate token and return account ID
     */
    public function validateToken(string $token): ?int
    {
        $payload = json_decode(base64_decode($token), true);

        if (!$payload || !isset($payload['uid'], $payload['exp'])) {
            return null;
        }

        if ($payload['exp'] < time()) {
            return null; // Token expired
        }

        // Re-check ban status
        $account = $this->db->selectOne(
            'account',
            "SELECT status FROM account WHERE id = ?",
            [$payload['uid']]
        );

        if (!$account || ($account['status'] ?? 'OK') !== 'OK') {
            return null;
        }

        return (int) $payload['uid'];
    }

    /**
     * Extract and validate token from Authorization header
     */
    public function getAuthenticatedUserId(): ?int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return null;
        }

        return $this->validateToken($matches[1]);
    }

    /**
     * Require authentication or throw error
     */
    public function requireAuth(): int
    {
        $userId = $this->getAuthenticatedUserId();

        if (!$userId) {
            require_once __DIR__ . '/../core/Response.php';
            Response::error('Oturum gerekli', 401);
        }

        return $userId;
    }
}
