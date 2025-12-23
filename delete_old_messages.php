<?php
require 'config.php';

// Set retention period (15 days)
$days = 15;
$cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));

// Delete messages older than the cutoff date
$stmt = $conn->prepare("DELETE FROM discussion WHERE sent_at < ?");
$stmt->bind_param("s", $cutoff_date);
if ($stmt->execute()) {
    $deleted_rows = $stmt->affected_rows;
    echo "Successfully deleted $deleted_rows messages older than $days days.\n";
} else {
    echo "Error deleting messages: " . $stmt->error . "\n";
}
$stmt->close();

$conn->close();
?>