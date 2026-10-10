<?php
declare(strict_types=1);
/** Fixed host/base only. No client URL, currency, credentials or private amounts are forwarded. */
function normalize_currency_provider(array $payload): array
{
    if (($payload['result'] ?? '') !== 'success' || ($payload['base_code'] ?? '') !== 'USD' || !isset($payload['rates']) || !is_array($payload['rates'])) {
        throw new RuntimeException('Unexpected exchange provider response.');
    }
    $timestamp = $payload['time_last_update_unix'] ?? null;
    if (!is_int($timestamp) || $timestamp <= 0 || $timestamp > time() + 300) throw new RuntimeException('Invalid provider timestamp.');
    $rates = [];
    foreach (['AFN','USD','EUR','GBP','IRR','PKR','TRY','AED','SAR'] as $currency) {
        $value = $payload['rates'][$currency] ?? null;
        if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value <= 0 || $value >= 1e12) throw new RuntimeException('Missing or invalid supported exchange rate.');
        // Plain fixed-decimal strings, not exponent notation; UI retains source precision up to 12 decimals.
        $text = rtrim(rtrim(sprintf('%.12F', $value), '0'), '.');
        if ($text === '0' || $text === '') throw new RuntimeException('Exchange rate is too small for supported precision.');
        $rates[$currency] = $text;
    }
    if ($rates['USD'] !== '1') throw new RuntimeException('Provider base-rate mismatch.');
    return ['base'=>'USD','rates'=>$rates,'source'=>'ExchangeRate-API (open access)','asOf'=>gmdate('c', $timestamp),'fetchedAt'=>gmdate('c')];
}
function fetch_currency_provider(): array
{
    $handle = curl_init('https://open.er-api.com/v6/latest/USD');
    if ($handle === false) throw new RuntimeException('Unable to initialize provider request.');
    $body = '';
    curl_setopt_array($handle, [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'FayezOS/0.3.0 CurrencyAdapter',
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
            if (strlen($body) + strlen($chunk) > 262144) return 0;
            $body .= $chunk;
            return strlen($chunk);
        },
    ]);
    $success = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    if ($success === false || $status !== 200) throw new RuntimeException('Provider transport request failed.');
    $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) throw new RuntimeException('Provider response must be an object.');
    return normalize_currency_provider($decoded);
}
