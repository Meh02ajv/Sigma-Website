<?php
/**
 * API AJAX pour récupérer les notifications
 */
require 'config.php';
require 'includes/notification_manager.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer l'ID de l'utilisateur
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['error' => 'Utilisateur non trouvé']);
    exit;
}

$user_id = $user['id'];
$notif = new NotificationManager($conn);

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Récupérer les notifications
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        
        $notifications = $notif->getUserNotifications($user_id, $limit, $offset, $unread_only);
        
        // Formater les dates
        foreach ($notifications as &$n) {
            $n['created_at_formatted'] = timeAgo($n['created_at']);
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $notif->getUnreadCount($user_id)
        ]);
        break;
        
    case 'count':
        // Compter les non lues
        $count = $notif->getUnreadCount($user_id);
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        break;
        
    case 'mark_read':
        // Marquer comme lue
        if (!isset($_POST['id'])) {
            echo json_encode(['error' => 'ID manquant']);
            exit;
        }
        
        $notification_id = (int)$_POST['id'];
        $result = $notif->markAsRead($notification_id, $user_id);
        
        echo json_encode([
            'success' => $result,
            'unread_count' => $notif->getUnreadCount($user_id)
        ]);
        break;
        
    case 'mark_all_read':
        // Marquer toutes comme lues
        $result = $notif->markAllAsRead($user_id);
        
        echo json_encode([
            'success' => $result,
            'unread_count' => 0
        ]);
        break;
        
    case 'delete':
        // Supprimer une notification
        if (!isset($_POST['id'])) {
            echo json_encode(['error' => 'ID manquant']);
            exit;
        }
        
        $notification_id = (int)$_POST['id'];
        $result = $notif->delete($notification_id, $user_id);
        
        echo json_encode([
            'success' => $result,
            'unread_count' => $notif->getUnreadCount($user_id)
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Action non reconnue']);
}

$conn->close();

/**
 * Formater une date en "il y a X temps"
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return "À l'instant";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Il y a $minutes min";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Il y a $hours h";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Il y a $days jour" . ($days > 1 ? 's' : '');
    } else {
        return date('d/m/Y', $timestamp);
    }
}
