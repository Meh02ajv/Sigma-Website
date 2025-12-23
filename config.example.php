<?php
/**
 * =====================================================
 * CONFIGURATION EXEMPLE - SIGMA ALUMNI
 * =====================================================
 * 
 * INSTRUCTIONS :
 * 1. Copiez ce fichier en config.php
 * 2. Remplacez les valeurs d'exemple par vos vraies valeurs
 * 3. Ne commitez JAMAIS config.php sur Git
 * 
 * @package SigmaAlumni
 */

// =====================================================
// 1. CONFIGURATION DU FUSEAU HORAIRE
// =====================================================
date_default_timezone_set('Africa/Abidjan');

// =====================================================
// 2. GESTION DES SESSIONS
// =====================================================
$session_path = __DIR__ . '/sessions';
if (!file_exists($session_path)) {
    mkdir($session_path, 0777, true);
}
ini_set('session.save_path', $session_path);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// 3. CONFIGURATION DE LA BASE DE DONNÉES
// =====================================================
$db_host = 'localhost';           // Votre serveur MySQL
$db_user = 'votre_utilisateur';   // Votre utilisateur MySQL
$db_pass = 'votre_mot_de_passe';  // Votre mot de passe MySQL
$db_name = 'nom_base_de_donnees'; // Nom de votre base de données

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Échec de la connexion : " . htmlspecialchars($conn->connect_error));
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+00:00'");

// =====================================================
// 4. FONCTIONS UTILITAIRES
// =====================================================
if (!function_exists('configSanitize')) {
    function configSanitize($data) {
        global $conn;
        return htmlspecialchars(strip_tags($conn->real_escape_string($data)));
    }
}

// =====================================================
// 5. CONFIGURATION SMTP (Pour l'envoi d'emails)
// =====================================================
define('SMTP_HOST', 'votre.serveur.smtp.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'votre_email@exemple.com');
define('SMTP_PASSWORD', 'votre_mot_de_passe_email');
define('SMTP_FROM_EMAIL', 'noreply@votresite.com');
define('SMTP_FROM_NAME', 'SIGMA Alumni');

// =====================================================
// 6. CONFIGURATION DE L'APPLICATION
// =====================================================
define('SITE_URL', 'http://localhost/Sigma-Website');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB en bytes
?>
