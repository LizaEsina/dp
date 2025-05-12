<?php
// api/auth/check.php

require_once __DIR__ . '/../jwt_middleware.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Authorization");

try {
    // Проверяем авторизацию
    $tokenData = authenticate();
    
    echo json_encode([
        'success' => true,
        'user_id' => $tokenData['user_id'],
        'message' => 'Token is valid'
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}