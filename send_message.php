<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Méthode non autorisée');
}

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$message = $input['message'] ?? '';

if (empty($username)) {
    jsonResponse(false, 'Destinataire non spécifié');
}

if (empty($message)) {
    jsonResponse(false, 'Le message ne peut pas être vide');
}

if (strlen($message) > 5000) {
    jsonResponse(false, 'Le message est trop long (maximum 5000 caractères)');
}

$recipient = getUserByUsername($pdo, $username);

if (!$recipient) {
    jsonResponse(false, 'Utilisateur non trouvé');
}

$message = sanitize($message);

$encryptedMessage = encryptMessage($message);

$senderIP = getClientIP();
$userAgent = getUserAgent();
$parsedUA = parseUserAgent($userAgent);

try {
    $stmt = $pdo->prepare("
        INSERT INTO messages (
            recipient_id, 
            content, 
            sender_ip, 
            sender_user_agent, 
            sender_device, 
            sender_browser, 
            sender_os
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $recipient['id'],
        $encryptedMessage,
        $senderIP,
        $userAgent,
        $parsedUA['device'],
        $parsedUA['browser'],
        $parsedUA['os']
    ]);
    
    if (EMAIL_ENABLED) {
        $emailSubject = "Quelqu'un vous a envoyé un message anonyme sur sayIt !";
        
        $emailBody = "
            <p>Vous avez reçu un nouveau message anonyme sur " . SITE_NAME . ".</p>
            <div class='message-box' style='text-align: center; color: #666; font-style: italic;'>
                « Ce message est secret. Connectez-vous pour le révéler. »
            </div>
            <p>Cliquez sur le bouton ci-dessous pour découvrir ce qu'on vous a écrit !</p>
        ";
        sendEmailNotification($recipient['email'], $recipient['username'], $emailSubject, $emailBody);
    }
    
    jsonResponse(true, 'Message envoyé avec succès!');
} catch (PDOException $e) {
    jsonResponse(false, 'Erreur lors de l\'envoi du message. Veuillez réessayer.');
}
?>
