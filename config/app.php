<?php
declare(strict_types=1);
// Server environment variables, not frontend configuration, own secrets.
return [
    'name' => 'Fayez OS',
    'version' => '0.7.0',
    'environment' => getenv('APP_ENV') ?: 'production',
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: '')))),
    'currency_provider' => getenv('CURRENCY_PROVIDER') ?: 'disabled', // optional: erapi
    'weather_provider' => getenv('WEATHER_PROVIDER') ?: 'openmeteo', // set disabled to opt out
    'news_provider' => getenv('NEWS_PROVIDER') ?: 'bbc', // explicit refresh only; review publishing terms
    'session_name' => 'fayez_os_session',
    'https' => getenv('APP_HTTPS') === '1', // Set explicitly behind a trusted TLS proxy.
    'private_files_dir' => getenv('PRIVATE_FILES_DIR') ?: __DIR__ . '/../storage/private/files',
    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'fayez_os',
        'user' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];
