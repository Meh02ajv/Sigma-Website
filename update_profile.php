<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode de requête non autorisée.";
    header("Location: mod_prof.php");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Erreur de validation du formulaire. Veuillez réessayer.";
    header("Location: mod_prof.php");
    exit;
}

unset($_SESSION['csrf_token']);

// Sanitize inputs
$user_email = filter_var($_SESSION['user_email'], FILTER_SANITIZE_EMAIL);
$delete_picture = isset($_POST['delete_picture']) && $_POST['delete_picture'] == '1';

// Connect to DB
$conn = new mysqli("localhost", "root", "", "laho");
if ($conn->connect_error) {
    $_SESSION['error'] = "Erreur de connexion à la base de données : " . $conn->connect_error;
    header("Location: mod_prof.php");
    exit;
}
$conn->set_charset("utf8mb4");

// Handle profile picture deletion
$upload_dir = 'img/';
$default_image = 'img/profile_pic.jpeg';
$profile_picture = null;

if ($delete_picture) {
    // Get current picture to delete
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_picture = $result->fetch_assoc()['profile_picture'];
    $stmt->close();

    if ($current_picture && file_exists($current_picture) && $current_picture !== $default_image) {
        unlink($current_picture);
    }
    $profile_picture = $default_image;

    // Update only the profile picture in the database
    $sql = "UPDATE users SET profile_picture = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $profile_picture, $user_email);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Photo de profil supprimée avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de la photo de profil.";
    }
    $stmt->close();
    $conn->close();
    header("Location: mod_prof.php");
    exit;
}

// Handle other updates (if not deleting picture)
$full_name = htmlspecialchars(trim($_POST['full_name']), ENT_QUOTES, 'UTF-8');
$birth_date = htmlspecialchars(trim($_POST['birth_date']), ENT_QUOTES, 'UTF-8');
$bac_year = filter_var($_POST['bac_year'], FILTER_SANITIZE_NUMBER_INT);
$studies = htmlspecialchars(trim($_POST['studies']), ENT_QUOTES, 'UTF-8');
$password = isset($_POST['password']) ? $_POST['password'] : null;

// Validate required fields for other updates
if (empty($full_name) || empty($birth_date) || empty($bac_year) || empty($studies)) {
    $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
    header("Location: mod_prof.php");
    exit;
}

// Validate bac year
$current_year = date('Y');
if ($bac_year < 1900 || $bac_year > $current_year) {
    $_SESSION['error'] = "L'année du bac doit être entre 1900 et $current_year.";
    header("Location: mod_prof.php");
    exit;
}

// Validate password
if ($password && strlen($password) < 8) {
    $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
    header("Location: mod_prof.php?reset=1");
    exit;
}

// Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Check for upload errors first
    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la taille maximale autorisée par le serveur.",
            UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la taille maximale autorisée.",
            UPLOAD_ERR_PARTIAL => "Le fichier n'a été que partiellement téléchargé.",
            UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant.",
            UPLOAD_ERR_CANT_WRITE => "Échec de l'écriture du fichier sur le disque.",
            UPLOAD_ERR_EXTENSION => "Une extension PHP a arrêté l'upload."
        ];
        $error_code = $_FILES['profile_picture']['error'];
        $_SESSION['error'] = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : "Erreur inconnue lors de l'upload (code: $error_code).";
        header("Location: mod_prof.php");
        exit;
    }
    
    $file = $_FILES['profile_picture'];
    
    // Validate file size
    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "L'image est trop volumineuse (max 5MB). Taille: " . round($file['size'] / 1024 / 1024, 2) . "MB";
        header("Location: mod_prof.php");
        exit;
    }
    
    // Double validation: check MIME type and file extension
    $valid_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($file['type'], $valid_types) || !in_array($file_extension, $valid_extensions)) {
        $_SESSION['error'] = "Format d'image invalide. Formats acceptés: JPG, PNG, GIF, WebP. (Type détecté: " . htmlspecialchars($file['type']) . ")";
        header("Location: mod_prof.php");
        exit;
    }

    // Verify it's actually an image using getimagesize
    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) {
        $_SESSION['error'] = "Le fichier n'est pas une image valide. Veuillez sélectionner une vraie image.";
        header("Location: mod_prof.php");
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = 'profile_' . md5($user_email . time()) . '.' . $ext;
    $upload_path = $upload_dir . $new_filename;

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $_SESSION['error'] = "Impossible de créer le répertoire de destination.";
            header("Location: mod_prof.php");
            exit;
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $_SESSION['error'] = "Erreur lors du téléchargement de l'image. Vérifiez les permissions du dossier img/.";
        header("Location: mod_prof.php");
        exit;
    }

    // Set proper permissions
    @chmod($upload_path, 0644);

    // Delete old profile picture
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $current_picture = $user_data ? $user_data['profile_picture'] : null;
    $stmt->close();

    if ($current_picture && file_exists($current_picture) && $current_picture !== $default_image) {
        @unlink($current_picture);
    }

    $profile_picture = $upload_path;
}

// Build SQL update
$update_fields = "full_name = ?, birth_date = ?, bac_year = ?, studies = ?";
$params = [$full_name, $birth_date, $bac_year, $studies];
$types = "ssis";

if ($profile_picture !== null) {
    $update_fields .= ", profile_picture = ?";
    $params[] = $profile_picture;
    $types .= "s";
}

if ($password) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $update_fields .= ", password = ?";
    $params[] = $hashed_password;
    $types .= "s";
}

$params[] = $user_email;
$types .= "s";

// Final SQL
$sql = "UPDATE users SET $update_fields WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $success_msg = "Profil mis à jour avec succès";
    if ($profile_picture !== null) {
        $success_msg .= " (photo de profil modifiée)";
    }
    if ($password) {
        $success_msg .= " (mot de passe modifié)";
    }
    $_SESSION['success'] = $success_msg . ".";
} else {
    $_SESSION['error'] = "Erreur lors de la mise à jour du profil : " . $stmt->error;
}
$stmt->close();
$conn->close();

header("Location: mod_prof.php");
exit;
?>