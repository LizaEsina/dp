<?php
function checkAdmin($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user['role'] !== 'admin') {
        throw new Exception("Администраторские права требуются", 403);
    }
}