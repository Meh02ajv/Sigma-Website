<?php
require 'config.php';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$lastId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;
$bac_year = isset($_GET['bac_year']) ? sanitize($_GET['bac_year']) : '';
$studies = isset($_GET['studies']) ? sanitize($_GET['studies']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize($_GET['sort_by']) : 'full_name';
$sort_order = isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), ['ASC', 'DESC']) ? strtoupper($_GET['sort_order']) : 'ASC';
$limit = 12;
$offset = $page * $limit;

$allowed_sort_columns = ['full_name', 'bac_year'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'full_name';
}

$current_date = date('m-d');
$query = "SELECT id, full_name, email, birth_date, studies, bac_year, profile_picture, 
          CASE WHEN DATE_FORMAT(birth_date, '%m-%d') = ? THEN 1 ELSE 0 END AS is_birthday 
          FROM users WHERE id > ?";
$params = [$current_date, $lastId];
$types = 'si';
if ($bac_year) {
    $query .= " AND bac_year = ?";
    $params[] = $bac_year;
    $types .= 'i';
}
if ($studies) {
    $query .= " AND studies LIKE ?";
    $params[] = "%$studies%";
    $types .= 's';
}
if ($search) {
    $query .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
$query .= " ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$profiles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['profiles' => $profiles]);
?>