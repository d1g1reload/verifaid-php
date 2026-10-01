<?php

/*
 * Router untuk server bawaan PHP (php -S) yang dipakai integration test.
 * Mengembalikan detail request yang diterima agar bisa diperiksa oleh test.
 */

declare(strict_types=1);

header('Content-Type: application/json');

$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/api/v1/rate-limited') {
    http_response_code(429);
    header('Retry-After: 42');
    echo json_encode(['status' => 'error', 'code' => 429, 'message' => 'Terlalu banyak request.']);

    return true;
}

if ($path === '/api/v1/bad-gateway') {
    http_response_code(502);
    header('Content-Type: text/html');
    echo '<html><body>502 Bad Gateway</body></html>';

    return true;
}

$headers = array_change_key_case(getallheaders(), CASE_LOWER);

$files = [];
foreach ($_FILES as $field => $file) {
    $files[$field] = [
        'name'  => $file['name'],
        'type'  => $file['type'],
        'size'  => $file['size'],
        'error' => $file['error'],
        'md5'   => $file['error'] === UPLOAD_ERR_OK ? md5_file($file['tmp_name']) : null,
    ];
}

echo json_encode([
    'status'  => 'success',
    'code'    => 200,
    'message' => 'Echo',
    'data'    => [
        'method'         => $_SERVER['REQUEST_METHOD'],
        'path'           => $path,
        'authorization'  => $headers['authorization'] ?? null,
        'accept'         => $headers['accept'] ?? null,
        'user_agent'     => $headers['user-agent'] ?? null,
        'content_type'   => $headers['content-type'] ?? null,
        'content_length' => $headers['content-length'] ?? null,
        'expect'         => $headers['expect'] ?? null,
        'body'           => $files === [] ? file_get_contents('php://input') : null,
        'files'          => $files,
    ],
]);

return true;
