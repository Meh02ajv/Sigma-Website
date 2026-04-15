<?php
/**
 * API pour ajouter un rappel d'événement
 * Permet aux utilisateurs connectés de recevoir des notifications email à H-1 et H-15 minutes
 */
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter']);
    exit;
}

// Check if event_id is provided
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Événement non spécifié']);
    exit;
}

$event_id = intval($_POST['event_id']);
$user_id = (int)$_SESSION['user_id'];

// Verify event exists
$stmt = $conn->prepare("SELECT id, event_date FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
    exit;
}

$event = $result->fetch_assoc();
$stmt->close();

// reminder_date est conservé comme date de référence de l'événement
$event_day = date('Y-m-d', strtotime($event['event_date']));
$reminder_date = $event['event_date'];

// Check if reminder already exists
$stmt = $conn->prepare("SELECT id FROM event_reminders WHERE user_id = ? AND event_id = ?");
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Rappel déjà existant']);
    exit;
}
$stmt->close();

// Add reminder
$stmt = $conn->prepare("INSERT INTO event_reminders (user_id, event_id, reminder_date) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $event_id, $reminder_date);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Rappel ajouté avec succès']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du rappel']);
}

$stmt->close();
$conn->close();
?>