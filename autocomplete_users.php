<?php
/**
 * API d'autocomplétion pour la recherche d'utilisateurs
 * Retourne une liste de suggestions basée sur le nom ou l'email
 */

require 'config.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer le terme de recherche
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Valider la recherche
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Préparer la recherche sécurisée
$searchTerm = "%$query%";

try {
    // Rechercher dans full_name et email
    $stmt = $conn->prepare("
        SELECT id, full_name, email, bac_year, profile_picture
        FROM users 
        WHERE (full_name LIKE ? OR email LIKE ?)
        ORDER BY full_name ASC
        LIMIT 10
    ");
    
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'bac_year' => $row['bac_year'],
            'profile_picture' => $row['profile_picture']
        ];
    }
    
    $stmt->close();
    
    // Retourner les résultats en JSON
    header('Content-Type: application/json');
    echo json_encode($users);
    
} catch (Exception $e) {
    error_log('Erreur autocomplétion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>
