<?php
declare(strict_types=1);
$config = require __DIR__ . '/../config/app.php';
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');

function json_response(bool $success, string $message, mixed $data = null, int $status = 200, ?string $code = null): void
{
    http_response_code($status);
    $payload = ['success' => $success, 'message' => $message, 'data' => $data];
    if ($code !== null) $payload['code'] = $code;
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
set_exception_handler(static function (Throwable $exception): void {
    error_log('[Fayez OS] ' . $exception->getMessage());
    json_response(false, 'Unable to complete the request. Please try again.', null, 500, 'SERVER_ERROR');
});
// No wildcard CORS. Same-origin XAMPP needs no CORS headers.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $config['allowed_origins'], true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Fayez-Account');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        json_response(false, 'Method not allowed.', null, 405, 'METHOD_NOT_ALLOWED');
    }
}
