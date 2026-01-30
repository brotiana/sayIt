<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Méthode non autorisée');
}

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username)) {
    jsonResponse(false, 'Le nom d\'utilisateur est requis');
}

if (empty($password)) {
    jsonResponse(false, 'Le mot de passe est requis');
}

$user = getUserByUsername($pdo, $username);

if (!$user) {
    jsonResponse(false, 'Nom d\'utilisateur ou mot de passe incorrect');
}

if (!password_verify($password, $user['password'])) {
    jsonResponse(false, 'Nom d\'utilisateur ou mot de passe incorrect');
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

jsonResponse(true, 'Connexion réussie!', [
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'shareLink' => SITE_URL . '/send.html?u=' . $user['username']
    ]
]);
?>
