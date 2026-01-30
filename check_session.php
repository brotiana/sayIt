<?php

require_once 'functions.php';

if (isLoggedIn()) {
    $user = getUserById($pdo, getCurrentUserId());
    
    if ($user && isset($user['username']) && $user['username'] !== null) {
        jsonResponse(true, 'Session active', [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'shareLink' => SITE_URL . '/send.html?u=' . $user['username']
            ]
        ]);
    } else {
        session_destroy();
        jsonResponse(false, 'Session invalide');
    }
} else {
    jsonResponse(false, 'Non connecté');
}
?>
