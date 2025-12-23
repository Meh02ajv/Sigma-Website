<?php
require 'config.php';
if (!isset($_SESSION['user_email'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

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

// Fetch unread message counts per sender
$stmt = $conn->prepare("SELECT sender_id, COUNT(*) as unread_count FROM discussion WHERE recipient_id = ? AND is_read = 0 GROUP BY sender_id");
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$unread_counts = [];
while ($row = $result->fetch_assoc()) {
    $unread_counts[$row['sender_id']] = $row['unread_count'];
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($unread_counts);
?>