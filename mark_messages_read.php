<?php
require 'config.php';
if (!isset($_SESSION['user_email']) || !isset($_POST['recipient_id']) || !isset($_POST['csrf_token'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized or missing parameters']));
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$recipient_id = (int)$_POST['recipient_id'];
$email = $_SESSION['user_email'];

// Get current user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$stmt->close();

if (!$current_user) {
    http_response_code(403);
    exit(json_encode(['error' => 'User not found']));
}

// Validate recipient_id
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid recipient ID']));
}
$stmt->close();

// Mark messages as read
$stmt = $conn->prepare("UPDATE discussion SET is_read = 1 WHERE sender_id = ? AND recipient_id = ? AND is_read = 0");
$stmt->bind_param("ii", $recipient_id, $current_user['id']);
$stmt->execute();
$affected_rows = $stmt->affected_rows;
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'affected_rows' => $affected_rows]);
?>