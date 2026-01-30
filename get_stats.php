<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    jsonResponse(false, 'Non autorisé. Veuillez vous connecter.');
}

$userId = getCurrentUserId();
$filter = $_GET['filter'] ?? 'all';

$now = new DateTime();
$today = (new DateTime())->setTime(0, 0, 0);
$yesterday = (clone $today)->modify('-1 day');
$thisWeekStart = (clone $today)->modify('monday this week');
$lastWeekStart = (clone $thisWeekStart)->modify('-1 week');
$lastWeekEnd = (clone $thisWeekStart)->modify('-1 second');
$thisMonthStart = (new DateTime())->modify('first day of this month')->setTime(0, 0, 0);
$lastMonthStart = (new DateTime())->modify('first day of last month')->setTime(0, 0, 0);
$lastMonthEnd = (new DateTime())->modify('last day of last month')->setTime(23, 59, 59);
$twoMonthsAgoStart = (new DateTime())->modify('first day of -2 months')->setTime(0, 0, 0);
$twoMonthsAgoEnd = (new DateTime())->modify('last day of -2 months')->setTime(23, 59, 59);

try {
    $query = "SELECT COUNT(*) as count FROM messages WHERE recipient_id = ?";
    $params = [$userId];
    
    switch ($filter) {
        case 'today':
            $query .= " AND received_at >= ?";
            $params[] = $today->format('Y-m-d H:i:s');
            break;
        case 'yesterday':
            $query .= " AND received_at >= ? AND received_at < ?";
            $params[] = $yesterday->format('Y-m-d H:i:s');
            $params[] = $today->format('Y-m-d H:i:s');
            break;
        case 'this_week':
            $query .= " AND received_at >= ?";
            $params[] = $thisWeekStart->format('Y-m-d H:i:s');
            break;
        case 'last_week':
            $query .= " AND received_at >= ? AND received_at <= ?";
            $params[] = $lastWeekStart->format('Y-m-d H:i:s');
            $params[] = $lastWeekEnd->format('Y-m-d H:i:s');
            break;
        case 'this_month':
            $query .= " AND received_at >= ?";
            $params[] = $thisMonthStart->format('Y-m-d H:i:s');
            break;
        case 'last_month':
            $query .= " AND received_at >= ? AND received_at <= ?";
            $params[] = $lastMonthStart->format('Y-m-d H:i:s');
            $params[] = $lastMonthEnd->format('Y-m-d H:i:s');
            break;
        case 'two_months_ago':
            $query .= " AND received_at >= ? AND received_at <= ?";
            $params[] = $twoMonthsAgoStart->format('Y-m-d H:i:s');
            $params[] = $twoMonthsAgoEnd->format('Y-m-d H:i:s');
            break;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ?");
    $stmt->execute([$userId]);
    $stats['total'] = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND received_at >= ?");
    $stmt->execute([$userId, $today->format('Y-m-d H:i:s')]);
    $stats['today'] = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND received_at >= ? AND received_at < ?");
    $stmt->execute([$userId, $yesterday->format('Y-m-d H:i:s'), $today->format('Y-m-d H:i:s')]);
    $stats['yesterday'] = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND received_at >= ?");
    $stmt->execute([$userId, $thisWeekStart->format('Y-m-d H:i:s')]);
    $stats['this_week'] = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND received_at >= ? AND received_at <= ?");
    $stmt->execute([$userId, $lastWeekStart->format('Y-m-d H:i:s'), $lastWeekEnd->format('Y-m-d H:i:s')]);
    $stats['last_week'] = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND received_at >= ?");
    $stmt->execute([$userId, $thisMonthStart->format('Y-m-d H:i:s')]);
    $stats['this_month'] = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND received_at >= ? AND received_at <= ?");
    $stmt->execute([$userId, $lastMonthStart->format('Y-m-d H:i:s'), $lastMonthEnd->format('Y-m-d H:i:s')]);
    $stats['last_month'] = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND received_at >= ? AND received_at <= ?");
    $stmt->execute([$userId, $twoMonthsAgoStart->format('Y-m-d H:i:s'), $twoMonthsAgoEnd->format('Y-m-d H:i:s')]);
    $stats['two_months_ago'] = (int)$stmt->fetch()['count'];
    
    jsonResponse(true, 'Statistiques récupérées', [
        'filter' => $filter,
        'count' => (int)$result['count'],
        'stats' => $stats
    ]);
} catch (PDOException $e) {
    jsonResponse(false, 'Erreur lors de la récupération des statistiques');
}
?>
