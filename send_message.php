<?php
require 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user_email'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['recipient_id']) || !isset($data['content'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing required parameters']));
}

$recipient_id = (int)$data['recipient_id'];
$content = trim($data['content']);
$email = $_SESSION['user_email'];

// Validate content
if (empty($content) || strlen($content) > 1000) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid message content']));
}

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

// Validate recipient exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    exit(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid recipient ID']));
}
$stmt->close();

// Insert message
$stmt = $conn->prepare("INSERT INTO discussion (sender_id, recipient_id, content, sent_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
if (!$stmt) {
    http_response_code(500);
    exit(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$stmt->bind_param("iis", $current_user['id'], $recipient_id, $content);

if ($stmt->execute()) {
    $message_id = $stmt->insert_id;
    $stmt->close();
    
    // Get the message with timestamp
    $stmt = $conn->prepare("SELECT id as message_id, sender_id, recipient_id, content, sent_at FROM discussion WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => [
            'message_id' => (int)$message['message_id'],
            'sender_id' => (int)$message['sender_id'],
            'recipient_id' => (int)$message['recipient_id'],
            'content' => htmlspecialchars($message['content'], ENT_QUOTES, 'UTF-8'),
            'sent_at' => $message['sent_at']
        ]
    ]);
} else {
    http_response_code(500);
    $stmt->close();
    exit(json_encode(['error' => 'Failed to send message']));
}
?>
