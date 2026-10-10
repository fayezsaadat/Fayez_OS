<?php
declare(strict_types=1);
/** Phase 5 connection factory. The health endpoint never queries MySQL. */
function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $config = require __DIR__ . '/app.php';
    $db = $config['database'];
    if ($db['user'] === '') throw new RuntimeException('Database credentials have not been configured.');
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}
