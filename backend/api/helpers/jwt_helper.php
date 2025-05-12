<?php
require_once __DIR__ . '/vendor/autoload.php'; // Путь к автозагрузчику Composer
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

// Секретный ключ (должен быть сложным и храниться в безопасности)
define('JWT_SECRET', 'ваш_супер_сложный_секретный_ключ');
define('JWT_ALGORITHM', 'HS256');

function getBearerToken() {
    $headers = apache_request_headers();
    
    if (!isset($headers['Authorization'])) {
        throw new Exception('Authorization header is missing', 401);
    }
    
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    
    throw new Exception('Invalid Authorization header format', 401);
}

function generateJWT($userId) {
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600; // Токен на 1 час

    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'sub' => $userId
    ];

    return JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
}

function verifyJWT($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
        return (object)[
            'user_id' => $decoded->sub,
            'exp' => $decoded->exp
        ];
    } catch (ExpiredException $e) {
        throw new Exception('Token expired', 401);
    } catch (SignatureInvalidException $e) {
        throw new Exception('Invalid token signature', 401);
    } catch (Exception $e) {
        throw new Exception('Invalid token', 401);
    }
}