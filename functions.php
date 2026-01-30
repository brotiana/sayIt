<?php

require_once 'config.php';

function getEncryptionKey() {
    $key = getenv('ENCRYPTION_KEY');
    if (!$key) {
        $key = 'aM7x9Kp2sL4vN8qR1wE3tY6uI0oP5hJ';
    }
    return $key;
}

function encryptMessage($plainText) {
    $key = hash('sha256', getEncryptionKey(), true);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($plainText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptMessage($encryptedText) {
    $key = hash('sha256', getEncryptionKey(), true);
    $data = base64_decode($encryptedText);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    return filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : 'Unknown';
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

function parseUserAgent($userAgent) {
    $result = [
        'device' => 'Unknown',
        'browser' => 'Unknown',
        'os' => 'Unknown'
    ];
    
    if (preg_match('/Windows NT 10/i', $userAgent)) {
        $result['os'] = 'Windows 10/11';
    } elseif (preg_match('/Windows NT 6.3/i', $userAgent)) {
        $result['os'] = 'Windows 8.1';
    } elseif (preg_match('/Windows NT 6.2/i', $userAgent)) {
        $result['os'] = 'Windows 8';
    } elseif (preg_match('/Windows NT 6.1/i', $userAgent)) {
        $result['os'] = 'Windows 7';
    } elseif (preg_match('/Mac OS X/i', $userAgent)) {
        $result['os'] = 'macOS';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $result['os'] = 'Linux';
    } elseif (preg_match('/Android/i', $userAgent)) {
        $result['os'] = 'Android';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
        $result['os'] = 'iOS';
    }
    
    if (preg_match('/Firefox\/([0-9.]+)/i', $userAgent, $matches)) {
        $result['browser'] = 'Firefox ' . $matches[1];
    } elseif (preg_match('/Edg\/([0-9.]+)/i', $userAgent, $matches)) {
        $result['browser'] = 'Edge ' . $matches[1];
    } elseif (preg_match('/Chrome\/([0-9.]+)/i', $userAgent, $matches)) {
        $result['browser'] = 'Chrome ' . $matches[1];
    } elseif (preg_match('/Safari\/([0-9.]+)/i', $userAgent, $matches)) {
        if (preg_match('/Version\/([0-9.]+)/i', $userAgent, $vMatches)) {
            $result['browser'] = 'Safari ' . $vMatches[1];
        } else {
            $result['browser'] = 'Safari';
        }
    } elseif (preg_match('/Opera|OPR\/([0-9.]+)/i', $userAgent, $matches)) {
        $result['browser'] = 'Opera ' . ($matches[1] ?? '');
    }
    
    if (preg_match('/Mobile|Android|iPhone|iPod/i', $userAgent)) {
        $result['device'] = 'Mobile';
    } elseif (preg_match('/iPad|Tablet/i', $userAgent)) {
        $result['device'] = 'Tablet';
    } else {
        $result['device'] = 'Desktop';
    }
    
    return $result;
}

function sanitize($input) {
    return trim($input);
}

function sanitizeForDisplay($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPassword($password) {
    return strlen($password) >= 10;
}

function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

function usernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch() !== false;
}

function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

function getUserByUsername($pdo, $username) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function sendEmailNotification($toEmail, $toName, $subject, $message) {
    if (!EMAIL_ENABLED) {
        return false;
    }
    
    $htmlMessage = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #1a1a1a; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 30px 20px; background: #ffffff; border: 1px solid #e5e7eb; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; background: #f9fafb; border-radius: 0 0 8px 8px; border: 1px solid #e5e7eb; border-top: none; }
            .btn { display: inline-block; padding: 14px 28px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white !important; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px; }
            .message-box { background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2563eb; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . SITE_NAME . '</h1>
            </div>
            <div class="content">
                <p>Bonjour <strong>' . htmlspecialchars($toName) . '</strong>,</p>
                ' . $message . '
                <p style="text-align: center;">
                    <a href="' . SITE_URL . '/dashboard.html" class="btn">Voir mes messages</a>
                </p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. Tous droits réservés.</p>
                <p style="color: #999; font-size: 11px;">Vous recevez cet email car vous êtes inscrit sur ' . SITE_NAME . '.</p>
            </div>
        </div>
    </body>
    </html>';
    
    $data = [
        'from' => EMAIL_FROM,
        'to' => [$toEmail],
        'subject' => $subject,
        'html' => $htmlMessage
    ];
    
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log('Resend API Error: ' . $response);
        return false;
    }
    
    return true;
}

function formatDate($datetime) {
    $date = new DateTime($datetime);
    return $date->format('d/m/Y à H:i');
}

function getRelativeTime($datetime) {
    $now = new DateTime();
    $date = new DateTime($datetime);
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return "Il y a " . $diff->y . " an" . ($diff->y > 1 ? "s" : "");
    } elseif ($diff->m > 0) {
        return "Il y a " . $diff->m . " mois";
    } elseif ($diff->d > 0) {
        return "Il y a " . $diff->d . " jour" . ($diff->d > 1 ? "s" : "");
    } elseif ($diff->h > 0) {
        return "Il y a " . $diff->h . " heure" . ($diff->h > 1 ? "s" : "");
    } elseif ($diff->i > 0) {
        return "Il y a " . $diff->i . " minute" . ($diff->i > 1 ? "s" : "");
    } else {
        return "À l'instant";
    }
}
?>
