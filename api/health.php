<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_method('GET');
json_response(true, 'Fayez OS API is available', [
    'version' => $config['version'],
    'phase' => 7,
    'capabilities' => ['os_shell', 'local_preferences', 'offline_shell', 'local_calculator', 'local_notes', 'local_tasks', 'local_calendar', 'local_expenses', 'local_currency', 'local_contacts', 'weather_forecasts', 'publisher_news', 'local_qr', 'local_files','accounts','manual_workspace_sync','private_files','local_search','encrypted_local_backup','privacy_screen','notification_controls','local_media','dialer_handoff'],
    'currency_provider_configured' => ($config['currency_provider'] === 'erapi'),
    'weather_provider_configured' => $config['weather_provider'] === 'openmeteo',
    'news_provider_configured' => $config['news_provider'] === 'bbc',
    'file_storage' => 'local_and_authenticated_server',
    'database_required' => false, // guest tools remain independent
    'database_required_for' => ['accounts','workspace_sync','private_files'],
    'database_status' => 'not_probed',
    'authentication_available' => $config['database']['user'] !== '',
    'cloud_sync_available' => $config['database']['user'] !== '',
]);
