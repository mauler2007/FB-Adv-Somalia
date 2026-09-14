<?php

require_once __DIR__ . '/config.php';

function telegram_checker_check(string $canonicalUsername): array
{
    if (!defined('TELEGRAM_CHECKER_ENABLED') || !TELEGRAM_CHECKER_ENABLED) {
        return [
            'result' => 'unknown',
            'fragment_status' => null,
            'http_code' => 0,
            'error' => 'checker_disabled',
        ];
    }

    $url = defined('TELEGRAM_CHECKER_URL') ? trim((string) TELEGRAM_CHECKER_URL) : '';
    $token = defined('TELEGRAM_CHECKER_TOKEN') ? trim((string) TELEGRAM_CHECKER_TOKEN) : '';

    if ($url === '' || $token === '') {
        return [
            'result' => 'unknown',
            'fragment_status' => null,
            'http_code' => 0,
            'error' => 'checker_not_configured',
        ];
    }

    $username = ltrim(trim($canonicalUsername), '@');

    if ($username === '') {
        return [
            'result' => 'unknown',
            'fragment_status' => null,
            'http_code' => 0,
            'error' => 'empty_username',
        ];
    }

    $payload = json_encode(
        ['username' => $username],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($payload === false) {
        return [
            'result' => 'unknown',
            'fragment_status' => null,
            'http_code' => 0,
            'error' => 'json_encode_failed',
        ];
    }

    $connectTimeout = defined('TELEGRAM_CHECKER_CONNECT_TIMEOUT')
        ? max(1, (int) TELEGRAM_CHECKER_CONNECT_TIMEOUT)
        : 2;

    $timeout = defined('TELEGRAM_CHECKER_TIMEOUT')
        ? max($connectTimeout, (int) TELEGRAM_CHECKER_TIMEOUT)
        : 5;

    $ch = curl_init($url);

    if ($ch === false) {
        return [
            'result' => 'unknown',
            'fragment_status' => null,
            'http_code' => 0,
            'error' => 'curl_init_failed',
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-TOKEN: ' . $token,
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);

    $body = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($body === false || $curlErrno !== 0) {
        return [
            'result' => 'unknown',
            'fragment_status' => null,
            'http_code' => $httpCode,
            'error' => $curlError !== '' ? $curlError : 'curl_error_' . $curlErrno,
        ];
    }

    $data = json_decode($body, true);

    if (!is_array($data)) {
        return [
            'result' => 'unknown',
            'fragment_status' => null,
            'http_code' => $httpCode,
            'error' => 'invalid_json_response',
        ];
    }

    $detail = strtoupper(trim((string) ($data['detail'] ?? '')));
    $usernameStatus = strtoupper(trim((string) ($data['username_status'] ?? '')));

    $fragmentStatus = null;

    foreach (['TAKEN', 'AVAILABLE', 'AUCTION', 'SOLD', 'UNKNOWN', 'ERROR'] as $candidate) {
        if (str_contains($detail, $candidate) || str_contains($usernameStatus, $candidate)) {
            $fragmentStatus = $candidate;
            break;
        }
    }

    if ($fragmentStatus === 'TAKEN') {
        return [
            'result' => 'taken',
            'fragment_status' => 'TAKEN',
            'http_code' => $httpCode,
            'error' => null,
        ];
    }

    if (in_array($fragmentStatus, ['AVAILABLE', 'AUCTION', 'SOLD'], true)) {
        return [
            'result' => 'not_taken',
            'fragment_status' => $fragmentStatus,
            'http_code' => $httpCode,
            'error' => null,
        ];
    }

    return [
        'result' => 'unknown',
        'fragment_status' => $fragmentStatus,
        'http_code' => $httpCode,
        'error' => 'unrecognized_or_technical_response',
    ];
}
