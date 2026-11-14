<?php

/**
 * A tiny local HTTP server for tests/CurlTransportTest.php, run with PHP's built-in server:
 * `php -S 127.0.0.1:<port> tests/fixtures/server.php`. Never reachable from outside.
 */

declare(strict_types=1);

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = (string) parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);

$json = static function (mixed $body, int $status = 200, ?string $retryAfter = null): void {
    http_response_code($status);
    header('Content-Type: application/json');
    if ($retryAfter !== null) {
        header('Retry-After: ' . $retryAfter);
    }
    $text = (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $accept = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    if (is_string($accept) && str_contains($accept, 'gzip')) {
        header('Content-Encoding: gzip');
        $text = (string) gzencode($text);
    }
    echo $text;
};

if ($path === '/v1/echo') {
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (is_string($key) && str_starts_with($key, 'HTTP_')) {
            $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
        }
    }
    $json([
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'headers' => $headers,
        'text' => 'સપ્તમી',
    ]);
    return true;
}

if (preg_match('#^/v1/status/(\d{3})$#', $path, $m) === 1) {
    $json(
        ['error' => ['code' => 'test_' . $m[1], 'message' => 'Status ' . $m[1], 'request_id' => 'req_local']],
        (int) $m[1],
        '0',
    );
    return true;
}

if ($path === '/v1/slow') {
    usleep(1_500_000);
    $json(['slow' => true]);
    return true;
}

if ($path === '/v1/redirect') {
    http_response_code(302);
    header('Location: /v1/echo?from=redirect');
    return true;
}

if ($path === '/v1/text') {
    header('Content-Type: text/plain');
    echo 'plain text';
    return true;
}

$json(['error' => ['code' => 'not_found', 'message' => 'Unknown path']], 404);
return true;
