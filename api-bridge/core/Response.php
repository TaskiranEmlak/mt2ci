<?php
/**
 * Metin2 Web Panel - Response Helper
 * JSON response standardization
 */

class Response
{
    public static function success(array $data = [], int $code = 200): void
    {
        http_response_code($code);
        echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function error(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
