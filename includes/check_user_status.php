<?php
function checkUserStatus($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user['status'] === 'blocked') {
        return false;
    }
    return true;
} 