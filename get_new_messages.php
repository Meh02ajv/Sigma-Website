<?php
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_email'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$email = $_SESSION['user_email'];
$last_message_id = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;
$recipient_id = isset($_GET['recipient_id']) ? (int)$_GET['recipient_id'] : null;

// Get current user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    exit(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$stmt->close();

if (!$current_user) {
    http_response_code(403);
    exit(json_encode(['error' => 'User not found']));
}

// Fetch new messages
if ($recipient_id) {
    // Get new messages for specific conversation
    $stmt = $conn->prepare("
        SELECT id as message_id, sender_id, recipient_id, content, sent_at, is_read 
        FROM discussion 
        WHERE id > ? 
        AND ((sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?))
        ORDER BY sent_at ASC
        LIMIT 50
    ");
    
    if (!$stmt) {
        http_response_code(500);
        exit(json_encode(['error' => 'Database error: ' . $conn->error]));
    }
    
    $stmt->bind_param("iiiii", $last_message_id, $current_user['id'], $recipient_id, $recipient_id, $current_user['id']);
} else {
    // Get all new messages for current user
    $stmt = $conn->prepare("
        SELECT id as message_id, sender_id, recipient_id, content, sent_at, is_read 
        FROM discussion 
        WHERE id > ? 
        AND (sender_id = ? OR recipient_id = ?)
        ORDER BY sent_at ASC
        LIMIT 50
    ");
    
    if (!$stmt) {
        http_response_code(500);
        exit(json_encode(['error' => 'Database error: ' . $conn->error]));
    }
    
    $stmt->bind_param("iii", $last_message_id, $current_user['id'], $current_user['id']);
}

$stmt->execute();
$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message_id' => (int)$row['message_id'],
        'sender_id' => (int)$row['sender_id'],
        'recipient_id' => (int)$row['recipient_id'],
        'content' => htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'),
        'sent_at' => $row['sent_at'],
        'is_read' => (int)$row['is_read']
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages)
]);
?>
