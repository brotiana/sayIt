<?php

require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Méthode non autorisée');
}

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

if (empty($username)) {
    jsonResponse(false, 'Le nom d\'utilisateur est requis');
}

if (!isValidUsername($username)) {
    jsonResponse(false, 'Le nom d\'utilisateur doit contenir uniquement des lettres, chiffres et underscores (3-50 caractères)');
}

if (usernameExists($pdo, $username)) {
    jsonResponse(false, 'Ce nom d\'utilisateur est déjà pris');
}

if (empty($email)) {
    jsonResponse(false, 'L\'email est requis');
}

if (!isValidEmail($email)) {
    jsonResponse(false, 'Format d\'email invalide');
}

if (emailExists($pdo, $email)) {
    jsonResponse(false, 'Cet email est déjà utilisé');
}

if (empty($password)) {
    jsonResponse(false, 'Le mot de passe est requis');
}

if (!isValidPassword($password)) {
    jsonResponse(false, 'Le mot de passe doit contenir au moins 10 caractères');
}

if ($password !== $confirmPassword) {
    jsonResponse(false, 'Les mots de passe ne correspondent pas');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword]);
    
    $userId = $pdo->lastInsertId();
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    
    jsonResponse(true, 'Inscription réussie!', [
        'user' => [
            'id' => $userId,
            'username' => $username,
            'shareLink' => SITE_URL . '/send.html?u=' . $username
        ]
    ]);
} catch (PDOException $e) {
    jsonResponse(false, 'Erreur lors de l\'inscription. Veuillez réessayer.');
}
?>
