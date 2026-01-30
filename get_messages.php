<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    jsonResponse(false, 'Non autorisé. Veuillez vous connecter.');
}

$userId = getCurrentUserId();

try {
    $stmt = $pdo->prepare("
        SELECT id, content, received_at, is_read 
        FROM messages 
        WHERE recipient_id = ? 
        ORDER BY received_at DESC
    ");
    $stmt->execute([$userId]);
    $messages = $stmt->fetchAll();
    
    $formattedMessages = array_map(function($msg) {
        $decryptedContent = decryptMessage($msg['content']);
        
        if ($decryptedContent === false) {
            $decryptedContent = $msg['content'];
        }
        
        return [
            'id' => $msg['id'],
            'content' => $decryptedContent,
            'receivedAt' => $msg['received_at'],
            'formattedDate' => formatDate($msg['received_at']),
            'relativeTime' => getRelativeTime($msg['received_at']),
            'isRead' => (bool)$msg['is_read']
        ];
    }, $messages);
    
    jsonResponse(true, 'Messages récupérés', ['messages' => $formattedMessages]);
} catch (PDOException $e) {
    jsonResponse(false, 'Erreur lors de la récupération des messages');
}
?>
