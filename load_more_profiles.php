<?php
require 'config.php';

// Mode public (sans filtres)
$is_public = isset($_GET['public']) && $_GET['public'] == '1';

if ($is_public) {
    // Version simplifiée pour les utilisateurs non connectés
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 12) : 6;
    $search_name = isset($_GET['search_name']) ? sanitize($_GET['search_name']) : '';
    $bac_year = isset($_GET['bac_year']) ? sanitize($_GET['bac_year']) : '';
    
    $current_date = date('m-d');
    $query = "SELECT id, full_name, bac_year, profile_picture,
              CASE WHEN DATE_FORMAT(birth_date, '%m-%d') = ? THEN 1 ELSE 0 END AS is_birthday 
              FROM users WHERE 1=1";
    $params = [$current_date];
    $types = 's';
    
    if ($search_name) {
        $query .= " AND full_name LIKE ?";
        $params[] = "%$search_name%";
        $types .= 's';
    }
    
    if ($bac_year) {
        $query .= " AND bac_year = ?";
        $params[] = $bac_year;
        $types .= 'i';
    }
    
    $query .= " ORDER BY full_name ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

// Version normale pour utilisateurs connectés
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$lastId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;
$bac_year = isset($_GET['bac_year']) ? sanitize($_GET['bac_year']) : '';
$studies = isset($_GET['studies']) ? sanitize($_GET['studies']) : '';
$search_name = isset($_GET['search_name']) ? sanitize($_GET['search_name']) : '';
$profession = isset($_GET['profession']) ? sanitize($_GET['profession']) : '';
$company = isset($_GET['company']) ? sanitize($_GET['company']) : '';
$city = isset($_GET['city']) ? sanitize($_GET['city']) : '';
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
          profession, company, city, country,
          CASE WHEN DATE_FORMAT(birth_date, '%m-%d') = ? THEN 1 ELSE 0 END AS is_birthday 
          FROM users WHERE id > ?";
$params = [$current_date, $lastId];
$types = 'si';

if ($search_name) {
    $query .= " AND (full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search_name%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

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

if ($profession) {
    $query .= " AND profession LIKE ?";
    $params[] = "%$profession%";
    $types .= 's';
}

if ($company) {
    $query .= " AND company LIKE ?";
    $params[] = "%$company%";
    $types .= 's';
}

if ($city) {
    $query .= " AND city LIKE ?";
    $params[] = "%$city%";
    $types .= 's';
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