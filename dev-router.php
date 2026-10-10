<?php
declare(strict_types=1);
// PHP CLI preview only. Apache uses .htaccess instead.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (str_contains($path, '..') || preg_match('~^/(config|includes|database|uploads|storage|docs|tests|native|scripts)(/|$)|/\.|\.(md|sql|log|ini|zip)$|^/capacitor\.config\.json$|^/dev-router\.php$~i', $path)) {
    http_response_code(403);
    echo 'Access denied';
    return true;
}
if (str_starts_with($path, '/app/')) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=(self)');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; media-src 'self' blob:; font-src 'self'; connect-src 'self'; worker-src 'self'; manifest-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
    // PHP's built-in static handler discards router headers when returning false.
    // Serve only the HTML entry ourselves so CSP/permission tests match Apache.
    if(in_array($path,['/app/','/app/index.html'],true)) {
        if(!in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true)){header('Allow: GET, HEAD');http_response_code(405);return true;}
        header('Content-Type: text/html; charset=UTF-8');
        if($_SERVER['REQUEST_METHOD']==='GET')readfile(__DIR__.'/app/index.html');
        return true;
    }
}
return false;
