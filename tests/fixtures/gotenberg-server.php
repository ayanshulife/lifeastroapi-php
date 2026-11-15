<?php

/**
 * A stand-in for Gotenberg's /forms/chromium/convert/html route, for tests/Pdf/GotenbergEngineTest.php:
 * answers with "%PDF-test " and a JSON echo of the form it received.
 */

declare(strict_types=1);

$uri = $_SERVER['REQUEST_URI'] ?? '';
if (!is_string($uri) || $uri !== '/gotenberg/forms/chromium/convert/html') {
    http_response_code(404);
    echo 'not found';
    return true;
}
$files = [];
// The engine posts files[0], files[1], ...: PHP groups those under $_FILES['files'].
foreach ($_FILES as $file) {
    if (!is_array($file)) {
        continue;
    }
    $names = (array) ($file['name'] ?? []);
    $tmps = (array) ($file['tmp_name'] ?? []);
    foreach ($names as $i => $name) {
        $tmp = $tmps[$i] ?? null;
        if (is_string($name) && is_string($tmp)) {
            $files[$name] = (string) file_get_contents($tmp);
        }
    }
}
header('Content-Type: application/pdf');
echo '%PDF-test ' . json_encode([
    'fields' => $_POST,
    'files' => $files,
    'auth' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
]);
return true;
