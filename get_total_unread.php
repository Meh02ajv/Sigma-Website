<?php
// Récupérer le nombre total de messages non lus pour l'utilisateur connecté
// Ce fichier peut être appelé via AJAX ou inclus directement

require_once 'config.php';

if (!isset($_SESSION['user_email'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['count' => 0]);
    }
    exit;
}

$email = $_SESSION['user_email'];

// Get current user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if ($stmt) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user = $result->fetch_assoc();
    $stmt->close();
    
    if ($current_user) {
        // Get total unread messages count
        $stmt = $conn->prepare("SELECT COUNT(*) as total_unread FROM discussion WHERE recipient_id = ? AND is_read = 0");
        $stmt->bind_param("i", $current_user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_unread = $row['total_unread'];
        $stmt->close();
        
        // If AJAX request
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['count' => (int)$total_unread]);
            exit;
        }
        
        // If included in PHP
        $unread_message_count = (int)$total_unread;
    } else {
        $unread_message_count = 0;
    }
} else {
    $unread_message_count = 0;
}
?>
