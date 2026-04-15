<?php
// Mode strict - aucune sortie avant le JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Utiliser config.php pour la connexion DB et la session
require_once 'config.php';

// Nettoyer toute sortie accidentelle
ob_clean();

// Header JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Fonction pour envoyer JSON et terminer
function sendJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Vérifier connexion DB
if ($conn->connect_error) {
    sendJSON(['success' => false, 'message' => 'Erreur DB: ' . $conn->connect_error]);
}

// Vérifier admin - utilise admin_logged_in comme dans admin_login.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    sendJSON(['success' => false, 'message' => 'Accès non autorisé. Veuillez vous reconnecter.']);
}

// Récupérer action
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Action: set_theme
if ($action === 'set_theme') {
    $theme = isset($_POST['theme']) ? $_POST['theme'] : 'none';
    
    // Valider
    $valid_themes = ['none', 'christmas', 'independence'];
    if (!in_array($theme, $valid_themes)) {
        sendJSON(['success' => false, 'message' => 'Thème invalide: ' . $theme]);
    }
    
    // Créer table si nécessaire
    $check = @$conn->query("SHOW TABLES LIKE 'site_themes'");
    if (!$check || $check->num_rows === 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS site_themes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            theme_name VARCHAR(50) NOT NULL DEFAULT 'none',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        @$conn->query("INSERT INTO site_themes (id, theme_name) VALUES (1, 'none')");
    }
    
    // Update ou insert
    $stmt = $conn->prepare("INSERT INTO site_themes (id, theme_name) VALUES (1, ?) ON DUPLICATE KEY UPDATE theme_name = ?");
    if (!$stmt) {
        sendJSON(['success' => false, 'message' => 'Erreur préparation: ' . $conn->error]);
    }
    
    $stmt->bind_param("ss", $theme, $theme);
    
    if ($stmt->execute()) {
        $theme_names = [
            'none' => 'Aucun thème',
            'christmas' => 'Thème de Noël',
            'independence' => 'Thème Indépendance du Togo'
        ];
        
        $stmt->close();
        $conn->close();
        sendJSON([
            'success' => true, 
            'message' => $theme_names[$theme] . ' activé avec succès',
            'theme' => $theme
        ]);
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        sendJSON(['success' => false, 'message' => 'Erreur update: ' . $error]);
    }
}

// Action: get_theme
elseif ($action === 'get_theme') {
    // Créer table si nécessaire
    $check = @$conn->query("SHOW TABLES LIKE 'site_themes'");
    if (!$check || $check->num_rows === 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS site_themes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            theme_name VARCHAR(50) NOT NULL DEFAULT 'none',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        @$conn->query("INSERT INTO site_themes (id, theme_name) VALUES (1, 'none')");
    }
    
    $result = $conn->query("SELECT theme_name FROM site_themes WHERE id = 1");
    
    if ($result && $row = $result->fetch_assoc()) {
        $conn->close();
        sendJSON(['success' => true, 'theme' => $row['theme_name']]);
    } else {
        @$conn->query("INSERT INTO site_themes (id, theme_name) VALUES (1, 'none')");
        $conn->close();
        sendJSON(['success' => true, 'theme' => 'none']);
    }
}

// Action invalide
else {
    $conn->close();
    sendJSON(['success' => false, 'message' => 'Action invalide']);
}
?>
