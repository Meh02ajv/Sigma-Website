<?php
require 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Élection non trouvée']);
    exit;
}

echo json_encode($result->fetch_assoc());
?>