<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/currency-provider.php';
require_method('GET');
if ($_GET !== []) json_response(false, 'This endpoint does not accept query parameters.', null, 400, 'INVALID_PARAMETERS');
if (($config['currency_provider'] ?? 'disabled') !== 'erapi') {
    json_response(false, 'Provider not configured on the server. Use Manual rate, or ask the administrator to enable the currency provider.', null, 503, 'PROVIDER_NOT_CONFIGURED');
}
if (!extension_loaded('curl')) json_response(false, 'The currency provider requires the PHP cURL extension. Manual conversion is still available.', null, 503, 'PROVIDER_UNAVAILABLE');
$directory = __DIR__ . '/../../storage/cache';
if (!is_dir($directory) && !@mkdir($directory, 0700, true)) json_response(false, 'Provider cache is unavailable. Please try again later.', null, 503, 'CACHE_UNAVAILABLE');
$cacheFile = $directory . '/currency-usd.json';
$readCache = static function () use ($cacheFile): ?array {
    if (!is_file($cacheFile)) return null;
    $age = time() - (int) filemtime($cacheFile);
    if ($age < 0 || $age >= 3600) return null;
    $decoded = json_decode((string) @file_get_contents($cacheFile), true);
    if (!is_array($decoded) || !isset($decoded['rates'], $decoded['asOf'], $decoded['source']) || ($decoded['base'] ?? '') !== 'USD') return null;
    $decoded['cached'] = true;
    return $decoded;
};
if (($cached = $readCache()) !== null) json_response(true, 'Saved provider rates are available.', $cached);
$lock = @fopen($directory . '/currency.lock', 'c');
if ($lock === false) json_response(false, 'Provider cache is unavailable. Please try again later.', null, 503, 'CACHE_UNAVAILABLE');
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    header('Retry-After: 5');
    json_response(false, 'Rates are being refreshed. Please try again in a few seconds.', null, 503, 'PROVIDER_BUSY');
}
// Lock is released automatically even when the response exits this request.
if (($cached = $readCache()) !== null) json_response(true, 'Saved provider rates are available.', $cached);
$attemptFile = $directory . '/currency-attempt';
$lastAttempt = is_file($attemptFile) ? (int) @file_get_contents($attemptFile) : 0;
if (time() - $lastAttempt < 60) {
    header('Retry-After: ' . (60 - (time() - $lastAttempt)));
    json_response(false, 'Please wait a minute before requesting provider rates again.', null, 429, 'RATE_LIMITED');
}
if (@file_put_contents($attemptFile, (string) time(), LOCK_EX) === false) json_response(false, 'Provider cache is unavailable.', null, 503, 'CACHE_UNAVAILABLE');
try {
    $data = fetch_currency_provider();
    $data['cached'] = false;
    $temporary = tempnam($directory, 'rates-');
    if ($temporary === false) throw new RuntimeException('Cannot prepare provider cache.');
    $written = file_put_contents($temporary, json_encode($data, JSON_THROW_ON_ERROR), LOCK_EX);
    if ($written === false || !rename($temporary, $cacheFile)) { @unlink($temporary); throw new RuntimeException('Cannot persist provider cache.'); }
    @chmod($cacheFile, 0600);
    json_response(true, 'Provider rates received successfully.', $data);
} catch (Throwable $error) {
    error_log('[Fayez OS currency] ' . $error->getMessage());
    json_response(false, 'Unable to retrieve provider rates. Try again later, or use a clearly labeled manual or saved rate.', null, 502, 'PROVIDER_ERROR');
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
