<?php

define('JWT_SECRET', 'your_very_secret_key_here');
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getAuthorizationHeader() {
    $headers = null;

    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { // для Apache
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(
            array_map('ucwords', array_keys($requestHeaders)),
            array_values($requestHeaders)
        );
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    return $headers;
}

function validateJwtToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        throw new Exception('Неверный формат токена');
    }

    list($headerB64, $payloadB64, $signatureB64) = $parts;

    $payload = json_decode(base64_decode($payloadB64), true);
    if (!$payload) {
        throw new Exception('Невозможно декодировать payload');
    }

    if (!isset($payload['exp']) || time() > $payload['exp']) {
        throw new Exception('Срок действия токена истёк');
    }

    $expectedSignature = base64url_encode(
        hash_hmac('sha256', "$headerB64.$payloadB64", JWT_SECRET, true)
    );

    if ($signatureB64 !== $expectedSignature) {
        throw new Exception('Неверная подпись токена');
    }

    return $payload;
}

function authenticate() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        http_response_code(204);
        exit();
    }

    header("Access-Control-Allow-Origin: http://localhost:5173");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json");

    try {
        $authHeader = getAuthorizationHeader();
        $token = null;

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }
        elseif (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        }

        if (!$token) {
            throw new Exception('Токен не предоставлен', 401);
        }

        $payload = validateJwtToken($token);
        return (array)$payload;

    } catch (Exception $e) {
        http_response_code($e->getCode() ?: 401);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit();
    }
}
