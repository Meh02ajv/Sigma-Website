<?php
/**
 * ENDPOINT API - SAUVEGARDE DE LA PRÉFÉRENCE DE THÈME
 * 
 * Sauvegarde la préférence de mode sombre/clair de l'utilisateur
 * dans la base de données
 */

session_start();
require_once 'config.php';

// En-têtes JSON
header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté'
    ]);
    exit;
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Valider les données
if (!isset($data['theme']) || !in_array($data['theme'], ['light', 'dark'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thème invalide. Utilisez "light" ou "dark".'
    ]);
    exit;
}

$theme = $data['theme'];
$dark_mode = ($theme === 'dark') ? 1 : 0;

try {
    // Récupérer l'ID de l'utilisateur
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $_SESSION['user_email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non trouvé'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $stmt->close();
    
    // Mettre à jour la préférence de thème
    $stmt = $conn->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->bind_param("ii", $dark_mode, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Préférence de thème sauvegardée',
            'theme' => $theme,
            'dark_mode' => $dark_mode
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la sauvegarde: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
