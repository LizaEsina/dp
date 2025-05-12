<?php
function cors() {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            header("Access-Control-Allow-Methods: *, GET, POST, PUT, DELETE, OPTIONS");
        }

        // Указываем явное разрешение на использование заголовка Content-Type
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        exit(0); // Прервать на OPTIONS-запросе
    }
}
