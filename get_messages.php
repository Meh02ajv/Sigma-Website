<?php
require 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_email']) || !isset($_GET['recipient_id'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized or missing parameters']));
}

$recipient_id = (int)$_GET['recipient_id'];
$email = $_SESSION['user_email'];

// Get current user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$stmt->close();

if (!$current_user) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'User not found']));
}

// Validate recipient_id
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid recipient ID']));
}
$stmt->close();

// Fetch messages between current user and recipient
$stmt = $conn->prepare("
    SELECT id as message_id, sender_id, recipient_id, content, sent_at, is_read 
    FROM discussion 
    WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
    ORDER BY sent_at DESC
    LIMIT 100
");

if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$stmt->bind_param("iiii", $current_user['id'], $recipient_id, $recipient_id, $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message_id' => $row['message_id'],
        'sender_id' => (int)$row['sender_id'],
        'recipient_id' => (int)$row['recipient_id'],
        'content' => htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'),
        'sent_at' => $row['sent_at'],
        'is_read' => (int)$row['is_read']
    ];
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($messages);
?>
