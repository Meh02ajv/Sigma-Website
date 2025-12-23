<?php
require 'config.php';

// fetch elections similar to admin
$stmt = $conn->prepare("SELECT * FROM elections ORDER BY start_date DESC");
$stmt->execute();
$elections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include 'admin_results_logic.php';
?>
