<?php
// Snippet pour ajouter le favicon sur toutes les pages
// À inclure dans le <head> de chaque page

// S'assurer que la connexion existe
if (!isset($conn)) {
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
    } elseif (file_exists('config.php')) {
        require_once 'config.php';
    }
}

// Récupérer le favicon depuis la base de données
$favicon_path = 'img/image.png'; // Fallback vers le logo par défaut

if (isset($conn) && $conn) {
    $favicon_result = @$conn->query("SELECT setting_value FROM general_config WHERE setting_key = 'favicon' LIMIT 1");
    if ($favicon_result && $row = $favicon_result->fetch_assoc()) {
        $favicon_path = $row['setting_value'];
    }
}

// Vérifier que le fichier existe, sinon chercher un favicon existant
if (!file_exists($favicon_path)) {
    // Chercher n'importe quel fichier favicon dans img/
    $possible_favicons = glob('img/favicon*.*');
    if (!empty($possible_favicons)) {
        $favicon_path = $possible_favicons[0];
    } else {
        $favicon_path = 'img/image.png'; // Logo par défaut
    }
}

// Afficher le favicon
if (file_exists($favicon_path)): 
    $ext = strtolower(pathinfo($favicon_path, PATHINFO_EXTENSION));
    $mime_type = ($ext === 'ico') ? 'image/x-icon' : 'image/' . $ext;
?>
    <link rel="icon" type="<?php echo $mime_type; ?>" href="<?php echo htmlspecialchars($favicon_path); ?>">
    <link rel="shortcut icon" type="<?php echo $mime_type; ?>" href="<?php echo htmlspecialchars($favicon_path); ?>">
<?php endif; ?>
