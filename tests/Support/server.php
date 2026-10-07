<?php

declare(strict_types=1);

$stateDir = (string) ($argv[1] ?? '');
$certFile = (string) ($argv[2] ?? '');
$keyFile = (string) ($argv[3] ?? '');

$context = $certFile === ''
    ? stream_context_create()
    : stream_context_create(['ssl' => ['local_cert' => $certFile, 'local_pk' => $keyFile]]);
$flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr, $flags, $context);
if ($server === false) {
    fwrite(STDERR, "$errstr\n");
    exit(1);
}
$address = (string) stream_socket_get_name($server, false);
file_put_contents("$stateDir/port.tmp", substr($address, (int) strrpos($address, ':') + 1));
rename("$stateDir/port.tmp", "$stateDir/port");

for (;;) {
    $conn = @stream_socket_accept($server, -1);
    if ($conn === false) {
        continue;
    }
    if ($certFile !== '' && @stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_SERVER) !== true) {
        fclose($conn);
        continue;
    }
    handle($conn, $stateDir);
    fclose($conn);
}

/** @param resource $conn */
function handle($conn, string $stateDir): void
{
    $requestLine = fgets($conn);
    if ($requestLine === false) {
        return;
    }
    [$method, $path] = explode(' ', trim($requestLine)) + ['', ''];
    $headers = [];
    while (($line = fgets($conn)) !== false && rtrim($line, "\r\n") !== '') {
        [$name, $value] = explode(':', $line, 2) + ['', ''];
        $headers[strtolower(trim($name))] = trim($value);
    }
    if (strtolower($headers['expect'] ?? '') === '100-continue') {
        fwrite($conn, "HTTP/1.1 100 Continue\r\n\r\n");
    }
    $length = (int) ($headers['content-length'] ?? 0);
    $body = '';
    while (strlen($body) < $length && !feof($conn)) {
        $chunk = fread($conn, max(1, $length - strlen($body)));
        if ($chunk === false) {
            break;
        }
        $body .= $chunk;
    }

    $record = ['method' => $method, 'path' => $path, 'headers' => $headers, 'body' => base64_encode($body)];
    file_put_contents("$stateDir/requests", json_encode($record) . "\n", FILE_APPEND | LOCK_EX);

    $response = json_decode((string) file_get_contents("$stateDir/response"), true);
    $status = is_array($response) && is_int($response['status'] ?? null) ? $response['status'] : 500;
    $payload = is_array($response) && is_string($response['body'] ?? null) ? $response['body'] : '';
    $delay = is_array($response) && is_numeric($response['delay'] ?? null) ? (float) $response['delay'] : 0.0;

    usleep((int) ($delay * 1000000));
    @fwrite($conn, sprintf(
        "HTTP/1.1 %d Status\r\nContent-Type: application/json\r\nContent-Length: %d\r\nConnection: close\r\n\r\n%s",
        $status,
        strlen($payload),
        $payload
    ));
}
