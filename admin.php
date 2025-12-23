<?php
// En-têtes anti-cache pour forcer le rafraîchissement des résultats
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require 'config.php';
require_once __DIR__ . '/includes/election_results_helper.php';

if (!function_exists('getProfilePicture')) {
    function getProfilePicture($path)
    {
        if (!empty($path) && file_exists($path)) {
            return htmlspecialchars($path);
        }
        return 'img/profile_pic.jpeg';
    }
}

// Définir le fuseau horaire de Lomé
date_default_timezone_set('Africa/Abidjan'); // Lomé utilise ce fuseau
$conn->query("SET time_zone = '+00:00'"); // UTC+0 pour l'Afrique de l'Ouest

// Détection automatique des fêtes de fin d'année (1er décembre - 5 janvier)
$current_month = (int)date('m');
$current_day = (int)date('d');
$is_holiday_season = ($current_month == 12 && $current_day >= 1) || ($current_month == 1 && $current_day <= 5);

// Handle general configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_general_config'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    // Traitement des URLs et textes
    $text_fields = ['instagram_url', 'tiktok_url', 'contact_email', 'contact_phone', 'contact_address'];
    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            $value = trim($_POST[$field]);
            
            // Pour contact_address, wrapper automatiquement en <p> si pas de balises HTML
            if ($field === 'contact_address' && !preg_match('/<[^>]+>/', $value)) {
                // Convertir les retours à la ligne en <br> puis wrapper en <p>
                $value = '<p>' . nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) . '</p>';
            } else {
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            
            $stmt = $conn->prepare("INSERT INTO general_config (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $field, $value, $value);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Upload vidéo hero - VERSION SIMPLIFIÉE ET ROBUSTE
    if (isset($_FILES['hero_video']) && $_FILES['hero_video']['error'] === UPLOAD_ERR_OK) {
        $video = $_FILES['hero_video'];
        $ext = strtolower(pathinfo($video['name'], PATHINFO_EXTENSION));
        $allowed = ['mp4', 'webm', 'mov'];
        
        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Format non autorisé. Utilisez MP4, WebM ou MOV.";
        } elseif ($video['size'] > 2147483648) { // 2GB en bytes
            $_SESSION['error'] = "Fichier trop volumineux (max 2GB).";
        } else {
            // Chemins directs et simples
            $filename = 'hero_background.' . $ext;
            $upload_folder = 'C:\\xampp\\htdocs\\Sigma-Website\\uploads\\videos\\';
            $destination = $upload_folder . $filename;
            $db_path = 'uploads/videos/' . $filename;
            
            // S'assurer que le dossier existe
            if (!file_exists($upload_folder)) {
                @mkdir($upload_folder, 0777, true);
            }
            
            // Méthode 1: Essayer move_uploaded_file
            if (@move_uploaded_file($video['tmp_name'], $destination)) {
                $success = true;
            } else {
                // Méthode 2: Copie par stream (pour gros fichiers)
                $success = false;
                if ($in = @fopen($video['tmp_name'], 'rb')) {
                    if ($out = @fopen($destination, 'wb')) {
                        while (!feof($in)) {
                            fwrite($out, fread($in, 4194304)); // 4MB chunks
                        }
                        fclose($out);
                        $success = true;
                    }
                    fclose($in);
                }
            }
            
            if ($success && file_exists($destination)) {
                // Supprimer anciennes vidéos avec autres extensions
                foreach ($allowed as $old_ext) {
                    if ($old_ext != $ext) {
                        @unlink($upload_folder . 'hero_background.' . $old_ext);
                    }
                }
                
                // Update database
                $stmt = $conn->prepare("INSERT INTO general_config (setting_key, setting_value, setting_type) VALUES ('hero_video', ?, 'video') ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("ss", $db_path, $db_path);
                $stmt->execute();
                $stmt->close();
                
                $_SESSION['success'] = "Vidéo uploadée avec succès! (" . round($video['size']/1048576, 2) . " MB)";
            } else {
                $_SESSION['error'] = "Échec de l'upload. Vérifiez que le dossier C:\\xampp\\htdocs\\Sigma-Website\\uploads\\videos\\ existe et a les bonnes permissions.";
            }
        }
    }
    
    // Traitement des autres fichiers (logos, favicon)
    $other_files = [
        'footer_logo' => ['path' => 'img/', 'ext' => ['jpg', 'jpeg', 'png', 'svg'], 'max' => 5],
        'favicon' => ['path' => 'img/', 'ext' => ['ico', 'png', 'jpg'], 'max' => 2],
        'admin_logo' => ['path' => 'img/', 'ext' => ['jpg', 'jpeg', 'png', 'svg'], 'max' => 5],
        'header_logo' => ['path' => 'img/', 'ext' => ['jpg', 'jpeg', 'png', 'svg'], 'max' => 5]
    ];
    
    foreach ($other_files as $field => $config) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $config['ext'])) {
                $_SESSION['error'] = "Type de fichier non autorisé pour $field.";
                continue;
            }
            
            if ($file['size'] > $config['max'] * 1024 * 1024) {
                $_SESSION['error'] = "Le fichier $field est trop volumineux (max {$config['max']}MB).";
                continue;
            }
            
            $filename = $field . '_' . time() . '.' . $ext;
            $relative_path = $config['path'] . $filename;
            $full_path = __DIR__ . '/' . $config['path'] . $filename;
            
            // Créer le dossier
            $dir = __DIR__ . '/' . $config['path'];
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            
            // Écrire le fichier
            $content = file_get_contents($file['tmp_name']);
            if ($content !== false && file_put_contents($full_path, $content)) {
                // Supprimer l'ancien fichier
                $stmt = $conn->prepare("SELECT setting_value FROM general_config WHERE setting_key = ?");
                $stmt->bind_param("s", $field);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($old = $result->fetch_assoc()) {
                    $old_path = __DIR__ . '/' . $old['setting_value'];
                    if (file_exists($old_path) && $old['setting_value'] !== 'img/image.png' && $old['setting_value'] !== 'img/favicon.ico') {
                        @unlink($old_path);
                    }
                }
                $stmt->close();
                
                // Mettre à jour la base
                $stmt = $conn->prepare("INSERT INTO general_config (setting_key, setting_value, setting_type) VALUES (?, ?, 'image') ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $field, $relative_path, $relative_path);
                $stmt->execute();
                $stmt->close();
                
                // Si c'est admin_logo, mettre aussi à jour header_logo (logo synchronisé)
                if ($field === 'admin_logo') {
                    $header_key = 'header_logo';
                    $stmt = $conn->prepare("INSERT INTO general_config (setting_key, setting_value, setting_type) VALUES (?, ?, 'image') ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->bind_param("sss", $header_key, $relative_path, $relative_path);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $_SESSION['success'] = "Fichier $field mis à jour avec succès!";
            } else {
                $_SESSION['error'] = "Impossible d'écrire le fichier $field.";
            }
        }
    }
    
    // Si aucune erreur n'a été définie, succès général
    if (!isset($_SESSION['error']) && !isset($_SESSION['success'])) {
        $_SESSION['success'] = "Configuration générale mise à jour avec succès.";
    }
    header("Location: admin.php");
    exit;
}

// Récupération des configurations existantes
$config_stmt = $conn->prepare("SELECT setting_key, setting_value, setting_type FROM general_config");
$config_stmt->execute();
$config_result = $config_stmt->get_result();
$general_config = [];
while ($row = $config_result->fetch_assoc()) {
    $general_config[$row['setting_key']] = $row['setting_value'];
}
$config_stmt->close();

// Valeurs par défaut si non définies
$default_config = [
    'instagram_url' => 'https://instagram.com/sigmaofficial',
    'tiktok_url' => 'https://tiktok.com/@sigmaofficial',
    'contact_email' => 'contact@sigma-alumni.org',
    'contact_phone' => '+33 1 23 45 67 89',
    'contact_address' => '123 Rue de l\'Éducation, 75001 Paris, France',
    'footer_logo' => 'img/image.png',
    'hero_video' => 'path/to/local/video.mp4',
    'favicon' => 'img/favicon.ico',
    'admin_logo' => 'img/image.png',
    'header_logo' => 'img/image.png'
];

foreach ($default_config as $key => $value) {
    if (!isset($general_config[$key])) {
        $general_config[$key] = $value;
    }
}

// Handle event addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $title = filter_var($_POST['event_title'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['event_description'], FILTER_SANITIZE_STRING);
    $event_date = filter_var($_POST['event_date'], FILTER_SANITIZE_STRING);
    $location = filter_var($_POST['event_location'], FILTER_SANITIZE_STRING);
    
    $image_path = NULL;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['event_image'];
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_extensions)) {
            $_SESSION['error'] = "Type de fichier non autorisé. Utilisez JPG, JPEG ou PNG.";
            header("Location: admin.php");
            exit;
        }
        
        if ($file['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "L'image est trop volumineuse. Taille maximale : 2 Mo.";
            header("Location: admin.php");
            exit;
        }
        
        $filename = 'event_' . uniqid() . '.' . $ext;
        $destination = "Uploads/events/$filename";
        
        if (!is_dir("Uploads/events")) {
            mkdir("Uploads/events", 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path = $destination;
        } else {
            $_SESSION['error'] = "Erreur lors du téléchargement de l'image.";
            header("Location: admin.php");
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $description, $event_date, $location, $image_path);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Événement ajouté avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'événement.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle event deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $event_id = (int)filter_var($_POST['event_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Get image path before deletion
    $stmt = $conn->prepare("SELECT image_path FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    
    // Delete image file if exists
    if ($event['image_path'] && file_exists($event['image_path'])) {
        unlink($event['image_path']);
    }
    
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Événement supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de l'événement.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle event editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_event'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $event_id = (int)filter_var($_POST['event_id'], FILTER_SANITIZE_NUMBER_INT);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $event_date = filter_var($_POST['event_date'], FILTER_SANITIZE_STRING);
    $location = filter_var($_POST['location'], FILTER_SANITIZE_STRING);
    
    // Handle image update
    $image_path = NULL;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_extensions)) {
            $_SESSION['error'] = "Type de fichier non autorisé. Utilisez JPG, JPEG ou PNG.";
            header("Location: admin.php");
            exit;
        }
        
        if ($file['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "L'image est trop volumineuse. Taille maximale : 2 Mo.";
            header("Location: admin.php");
            exit;
        }
        
        // Delete old image
        $stmt = $conn->prepare("SELECT image_path FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_event = $result->fetch_assoc();
        $stmt->close();
        
        if ($old_event['image_path'] && file_exists($old_event['image_path'])) {
            unlink($old_event['image_path']);
        }
        
        $filename = 'event_' . uniqid() . '.' . $ext;
        $destination = "Uploads/events/$filename";
        
        if (!is_dir("Uploads/events")) {
            mkdir("Uploads/events", 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path = $destination;
        }
    }
    
    if ($image_path) {
        $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, image_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $description, $event_date, $location, $image_path, $event_id);
    } else {
        $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $description, $event_date, $location, $event_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Événement mis à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour de l'événement.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Fetch all events
$stmt = $conn->prepare("SELECT * FROM events ORDER BY event_date DESC");
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Add this at the top of admin.php, after session_start() and database connection
require 'vendor/autoload.php';
$purifier = new HTMLPurifier();
// Add this at the top of admin.php, after session_start() and database connection
require 'vendor/autoload.php';
$purifier = new HTMLPurifier();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle contact info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact_info'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $address = $purifier->purify($_POST['address']);
    $hours = $purifier->purify($_POST['hours']);
    $instagram_url = filter_var($_POST['instagram_url'], FILTER_SANITIZE_URL) ?: null;
    $tiktok_url = filter_var($_POST['tiktok_url'], FILTER_SANITIZE_URL) ?: null;
    $linkedin_url = filter_var($_POST['linkedin_url'], FILTER_SANITIZE_URL) ?: null;
    $facebook_url = filter_var($_POST['facebook_url'], FILTER_SANITIZE_URL) ?: null;
    $map_iframe = $purifier->purify($_POST['map_iframe']);
    
    // Validate inputs
    if (empty($email) || empty($phone) || empty($address) || empty($hours) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires correctement.";
        header("Location: admin.php");
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO contact_info (
            id, email, phone, address, hours, instagram_url, tiktok_url, linkedin_url, facebook_url, map_iframe
        ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            email = ?, phone = ?, address = ?, hours = ?, instagram_url = ?, tiktok_url = ?, linkedin_url = ?, facebook_url = ?, map_iframe = ?
    ");
    $stmt->bind_param(
        "ssssssssssssssssss",  // 18 's' pour 18 paramètres (9 INSERT + 9 UPDATE)
        $email, $phone, $address, $hours, $instagram_url, $tiktok_url, $linkedin_url, $facebook_url, $map_iframe,
        $email, $phone, $address, $hours, $instagram_url, $tiktok_url, $linkedin_url, $facebook_url, $map_iframe
    );
    if ($stmt->execute()) {
        $_SESSION['success'] = "Informations de contact mises à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour des informations.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle deleting contact submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submission'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM contact_submissions WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Message supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du message.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Fetch contact information
$stmt = $conn->prepare("SELECT * FROM contact_info WHERE id = 1");
$stmt->execute();
$contact_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contact_info) {
    $contact_info = [
        'email' => 'contact@sigma-alumni.org',
        'phone' => '+233 257201525',
        'address' => '<p>123 Rue de la Science<br>75000 Paris, France</p>',
        'hours' => '<p>Lundi - Vendredi : 9h - 18h</p>',
        'instagram_url' => 'https://instagram.com/sigmaalumni',
        'tiktok_url' => 'https://tiktok.com/@sigmaalumni',
        'linkedin_url' => 'https://linkedin.com/company/sigmaalumni',
        'facebook_url' => 'https://facebook.com/sigmaalumni',
        'map_iframe' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.292292615509614!3d48.85837007928746!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2964e34e2d%3A0x8ddca9ee380ef7e0!2sTour%20Eiffel!5e0!3m2!1sfr!2sfr!4v1628683204470!5m2!1sfr!2sfr" allowfullscreen="" loading="lazy"></iframe>'
    ];
}

// Fetch contact submissions
$stmt = $conn->prepare("SELECT id, name, email, subject, message, created_at FROM contact_submissions ORDER BY created_at DESC");
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Handle mission update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mission'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $content = $purifier->purify($_POST['mission_content']);
    $stmt = $conn->prepare("INSERT INTO objectifs_mission (id, content) VALUES (1, ?) ON DUPLICATE KEY UPDATE content = ?");
    $stmt->bind_param("ss", $content, $content);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Mission mise à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour de la mission.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle adding strategic objective
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_objectif'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = $purifier->purify($_POST['description']);
    $icon = filter_var($_POST['icon'], FILTER_SANITIZE_STRING);
    $order_index = (int)filter_var($_POST['order_index'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("INSERT INTO objectifs_strategic (title, description, icon, order_index) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $description, $icon, $order_index);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Objectif stratégique ajouté avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'objectif.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle editing strategic objective
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_objectif'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $id = (int)$_POST['id'];
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = $purifier->purify($_POST['description']);
    $icon = filter_var($_POST['icon'], FILTER_SANITIZE_STRING);
    $order_index = (int)filter_var($_POST['order_index'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("UPDATE objectifs_strategic SET title = ?, description = ?, icon = ?, order_index = ? WHERE id = ?");
    $stmt->bind_param("sssii", $title, $description, $icon, $order_index, $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Objectif stratégique mis à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour de l'objectif.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle deleting strategic objective
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_objectif'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM objectifs_strategic WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Objectif stratégique supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de l'objectif.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle adding fundamental value
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_value'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = $purifier->purify($_POST['description']);
    $icon = filter_var($_POST['icon'], FILTER_SANITIZE_STRING);
    $order_index = (int)filter_var($_POST['order_index'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("INSERT INTO objectifs_values (title, description, icon, order_index) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $description, $icon, $order_index);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Valeur fondamentale ajoutée avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout de la valeur.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle editing fundamental value
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_value'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $id = (int)$_POST['id'];
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = $purifier->purify($_POST['description']);
    $icon = filter_var($_POST['icon'], FILTER_SANITIZE_STRING);
    $order_index = (int)filter_var($_POST['order_index'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("UPDATE objectifs_values SET title = ?, description = ?, icon = ?, order_index = ? WHERE id = ?");
    $stmt->bind_param("sssii", $title, $description, $icon, $order_index, $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Valeur fondamentale mise à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour de la valeur.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle deleting fundamental value
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_value'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM objectifs_values WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Valeur fondamentale supprimée avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de la valeur.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Fetch mission content
$stmt = $conn->prepare("SELECT content FROM objectifs_mission WHERE id = 1");
$stmt->execute();
$mission_content = $purifier->purify($stmt->get_result()->fetch_assoc()['content'] ?? '<p>SIGMA Alumni s\'engage à créer un réseau dynamique et solidaire entre les anciens élèves de l\'établissement SIGMA, tout en contribuant au rayonnement de notre alma mater.</p><p>Notre association agit comme un pont entre les générations d\'élèves, favorisant l\'entraide, le partage d\'expériences et le développement professionnel de ses membres.</p>');
$stmt->close();

// Fetch strategic objectives
$stmt = $conn->prepare("SELECT id, title, description, icon, order_index FROM objectifs_strategic ORDER BY order_index ASC");
$stmt->execute();
$objectifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch fundamental values
$stmt = $conn->prepare("SELECT id, title, description, icon, order_index FROM objectifs_values ORDER BY order_index ASC");
$stmt->execute();
$values = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();



// Handle current bureau member addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bureau_member'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $user_id = (int)filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $position = filter_var($_POST['position'], FILTER_SANITIZE_STRING);
    $promotion_year = (int)filter_var($_POST['promotion_year'], FILTER_SANITIZE_NUMBER_INT);
    
    $stmt = $conn->prepare("INSERT INTO bureau_members (user_id, position, promotion_year, start_year, is_current) VALUES (?, ?, ?, YEAR(CURDATE()), 1)");
    $stmt->bind_param("isi", $user_id, $position, $promotion_year);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Membre ajouté au bureau avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout du membre au bureau.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle bureau member removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_bureau_member'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $member_id = (int)filter_var($_POST['member_id'], FILTER_SANITIZE_NUMBER_INT);
    
    $stmt = $conn->prepare("UPDATE bureau_members SET is_current = 0, end_year = YEAR(CURDATE()) WHERE id = ?");
    $stmt->bind_param("i", $member_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Membre retiré du bureau avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors du retrait du membre du bureau.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle historical bureau addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_historical_bureau'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $year_range = filter_var($_POST['year_range'], FILTER_SANITIZE_STRING);
    $members = filter_var($_POST['members'], FILTER_SANITIZE_STRING);
    
    $stmt = $conn->prepare("INSERT INTO bureau_history (year_range, members) VALUES (?, ?)");
    $stmt->bind_param("ss", $year_range, $members);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Bureau historique ajouté avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout du bureau historique.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle historical bureau removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_historical_bureau'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $history_id = (int)filter_var($_POST['history_id'], FILTER_SANITIZE_NUMBER_INT);
    
    $stmt = $conn->prepare("DELETE FROM bureau_history WHERE id = ?");
    $stmt->bind_param("i", $history_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Bureau historique supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du bureau historique.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Fetch current bureau members
$stmt = $conn->prepare("SELECT bm.*, u.full_name, u.profile_picture 
                        FROM bureau_members bm 
                        JOIN users u ON bm.user_id = u.id 
                        WHERE bm.is_current = 1 
                        ORDER BY bm.position");
$stmt->execute();
$current_bureau = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch bureau history
$stmt = $conn->prepare("SELECT * FROM bureau_history ORDER BY year_range DESC");
$stmt->execute();
$bureau_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['error'] = "Veuillez vous connecter en tant qu'administrateur.";
    header("Location: admin_login.php");
    exit;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle verification code update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_verification_code'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $new_code = filter_var($_POST['verification_code'], FILTER_SANITIZE_STRING);
    if (empty($new_code)) {
        $_SESSION['error'] = "Le code de vérification ne peut pas être vide.";
    } else {
        $stmt = $conn->prepare("UPDATE verification_codes SET code = ? WHERE id = 1");
        $stmt->bind_param("s", $new_code);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Code de vérification mis à jour avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du code.";
        }
        $stmt->close();
    }
    header("Location: admin.php");
    exit;
}

// Remplacer cette partie dans la validation des dates d'élection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_election'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $title = filter_var($_POST['election_title'], FILTER_SANITIZE_STRING);
    $campaign_start = !empty($_POST['campaign_start']) ? $_POST['campaign_start'] : $_POST['vote_start'];
    $vote_start = $_POST['vote_start'];
    $end_date = $_POST['end_date'];
    $results_date = $_POST['results_date'];

    // DEBUG: Log des dates reçues
    error_log("Création élection - Titre: " . $title);
    error_log("Création élection - Campagne: " . $campaign_start);
    error_log("Création élection - Vote: " . $vote_start);
    error_log("Création élection - Fin: " . $end_date);
    error_log("Création élection - Résultats: " . $results_date);
    
    
    // NOUVELLE VALIDATION DES DATES
    $now = new DateTime('now', new DateTimeZone('Africa/Abidjan'));
    $min_start_time = (new DateTime('now', new DateTimeZone('Africa/Abidjan')))->modify('+10 minutes');

    // Convertir les dates d'entrée (format datetime-local)
    $campaign_start_dt = DateTime::createFromFormat('Y-m-d\TH:i', $campaign_start, new DateTimeZone('Africa/Abidjan'));
    $vote_start_dt = DateTime::createFromFormat('Y-m-d\TH:i', $vote_start, new DateTimeZone('Africa/Abidjan'));
    $end_date_dt = DateTime::createFromFormat('Y-m-d\TH:i', $end_date, new DateTimeZone('Africa/Abidjan'));
    $results_date_dt = DateTime::createFromFormat('Y-m-d\TH:i', $results_date, new DateTimeZone('Africa/Abidjan'));

    
    if (!$vote_start_dt || !$end_date_dt || !$results_date_dt) {
        $_SESSION['error'] = "Format de date invalide. Utilisez le format datetime-local.";
    } elseif ($vote_start_dt < $min_start_time) {
        $_SESSION['error'] = "La date de début du vote doit être au moins 10 minutes dans le futur.";
    } elseif ($end_date_dt <= $vote_start_dt) {
        $_SESSION['error'] = "La date de fin du vote doit être postérieure à la date de début.";
    } elseif ($results_date_dt <= $end_date_dt) {
        $_SESSION['error'] = "La date de publication des résultats doit être postérieure à la date de fin du vote.";
    } else {
        // Convertir en format MySQL avec fuseau horaire cohérent
        $campaign_start_mysql = $campaign_start_dt->format('Y-m-d H:i:s');
        $vote_start_mysql = $vote_start_dt->format('Y-m-d H:i:s');
        $end_date_mysql = $end_date_dt->format('Y-m-d H:i:s');
        $results_date_mysql = $results_date_dt->format('Y-m-d H:i:s');
        
        // DEBUG: Log des dates converties
        error_log("Dates converties - Campagne: " . $campaign_start_mysql);
        error_log("Dates converties - Vote: " . $vote_start_mysql);
        error_log("Dates converties - Fin: " . $end_date_mysql);
        error_log("Dates converties - Résultats: " . $results_date_mysql);
        
        $stmt = $conn->prepare("INSERT INTO elections (title, campaign_start, start_date, end_date, results_date, results_published) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssss", $title, $campaign_start_mysql, $vote_start_mysql, $end_date_mysql, $results_date_mysql);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Élection créée avec succès.";
            // DEBUG: Log de succès
            error_log("Élection créée avec succès: " . $title);
        } else {
            $_SESSION['error'] = "Erreur lors de la création de l'élection: " . $stmt->error;
            // DEBUG: Log d'erreur
            error_log("Erreur création élection: " . $stmt->error);
        }
        $stmt->close();
    }
    header("Location: admin.php");
    exit;
}
// Handle results publication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_results'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $election_id = (int)filter_var($_POST['election_id'], FILTER_SANITIZE_NUMBER_INT);
    
    $stmt = $conn->prepare("UPDATE elections SET results_published = 1 WHERE id = ?");
    $stmt->bind_param("i", $election_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Envoyer les notifications par email à tous les votants
        require_once 'send_email.php';
        $sent_count = sendResultsNotificationEmails($election_id);
        
        $_SESSION['success'] = "Résultats publiés avec succès ! $sent_count notification(s) envoyée(s) par email aux votants.";
        error_log("Résultats publiés pour élection ID $election_id - $sent_count emails envoyés");
    } else {
        $_SESSION['error'] = "Erreur lors de la publication des résultats.";
        $stmt->close();
    }
    header("Location: admin.php");
    exit;
}

// Handle results unpublishing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unpublish_results'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $election_id = (int)filter_var($_POST['election_id'], FILTER_SANITIZE_NUMBER_INT);
    
    $stmt = $conn->prepare("UPDATE elections SET results_published = 0 WHERE id = ?");
    $stmt->bind_param("i", $election_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Résultats masqués avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors du masquage des résultats.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}
// Handle election deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_election'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $election_id = (int)filter_var($_POST['election_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM elections WHERE id = ?");
    $stmt->bind_param("i", $election_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Élection supprimée avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de l'élection.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle candidate creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $election_id = (int)filter_var($_POST['election_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = (int)filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $position = filter_var($_POST['position'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $video_url = filter_var($_POST['video_url'], FILTER_SANITIZE_URL);

$video_url = NULL;
if (isset($_FILES['candidate_video'])) {
    $video = $_FILES['candidate_video'];
    if ($video['error'] === UPLOAD_ERR_OK) {
        $allowed_video_ext = ['mp4', 'webm', 'mov'];
        $video_ext = strtolower(pathinfo($video['name'], PATHINFO_EXTENSION));
        if (in_array($video_ext, $allowed_video_ext)) {
            if ($video['size'] <= 50 * 1024 * 1024) { // 50MB max
                $video_filename = uniqid() . '.' . $video_ext;
                $video_dest = "Uploads/candidate_videos/" . $video_filename;
                if (!is_dir("Uploads/candidate_videos")) {
                    mkdir("Uploads/candidate_videos", 0777, true);
                }
                if (move_uploaded_file($video['tmp_name'], $video_dest)) {
                    $video_url = $video_dest;
                }
            }
        }
    }
}

    $profile_picture = NULL;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            $_SESSION['error'] = "Type de fichier non autorisé pour la photo de profil.";
            header("Location: admin.php");
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = "La photo de profil est trop volumineuse. Taille maximale : 5 Mo.";
            header("Location: admin.php");
            exit;
        }
        $filename = uniqid() . '.' . $ext;
        $destination = "Uploads/candidates/$filename";
        if (!is_dir("Uploads/candidates")) {
            mkdir("Uploads/candidates", 0777, true);
        }
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $profile_picture = $destination;
        } else {
            $_SESSION['error'] = "Erreur lors du téléchargement de la photo de profil.";
            header("Location: admin.php");
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO candidates (election_id, user_id, position, description, profile_picture, video_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $election_id, $user_id, $position, $description, $profile_picture, $video_url);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Candidat ajouté avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout du candidat.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle candidate deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_candidate'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $candidate_id = (int)filter_var($_POST['candidate_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT profile_picture FROM candidates WHERE id = ?");
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $candidate = $result->fetch_assoc();
    $stmt->close();

    if ($candidate['profile_picture'] && file_exists($candidate['profile_picture'])) {
        unlink($candidate['profile_picture']);
    }

    $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
    $stmt->bind_param("i", $candidate_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Candidat supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du candidat.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Fetch current verification code
$stmt = $conn->prepare("SELECT code FROM verification_codes WHERE id = 1");
$stmt->execute();
$current_code = $stmt->get_result()->fetch_assoc()['code'] ?? 'ID_2023';
$stmt->close();

// Dashboard metrics
$total_users = $conn->query("SELECT COUNT(DISTINCT id) FROM users")->fetch_row()[0];
$total_media = 0;
$media_extensions = ['jpg', 'jpeg', 'png', 'mp4', 'webm'];
$year_folders = glob('Uploads/*_pic', GLOB_ONLYDIR);
foreach ($year_folders as $folder) {
    $total_media += count(glob($folder . '/*.{' . implode(',', $media_extensions) . '}', GLOB_BRACE));
}
$total_messages = $conn->query("SELECT COUNT(*) FROM discussion")->fetch_row()[0];
$total_reports = $conn->query("SELECT COUNT(*) FROM reports")->fetch_row()[0];
$total_suggestions = $conn->query("SELECT COUNT(*) FROM suggestions")->fetch_row()[0];
$total_elections = $conn->query("SELECT COUNT(*) FROM elections")->fetch_row()[0];
$pending_reports = $total_reports; // Tous les signalements sont considérés comme en attente
$pending_suggestions = $total_suggestions; // Toutes les suggestions sont considérées comme en attente
$total_contact_submissions = $conn->query("SELECT COUNT(*) FROM contact_submissions")->fetch_row()[0] ?? 0;

// Fetch all elections with publication status
$stmt = $conn->prepare("SELECT *, 
                       CASE 
                           WHEN NOW() < campaign_start THEN 'pending'
                           WHEN NOW() BETWEEN campaign_start AND start_date THEN 'campaign'
                           WHEN NOW() BETWEEN start_date AND end_date THEN 'voting'
                           WHEN NOW() > end_date AND NOW() < results_date THEN 'processing'
                           WHEN NOW() >= results_date THEN 'completed'
                       END as election_status
                       FROM elections ORDER BY start_date DESC");
$stmt->execute();
$elections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all candidates with user details
$stmt = $conn->prepare("SELECT c.*, e.title as election_title, u.full_name, u.profile_picture as user_profile_picture 
                        FROM candidates c 
                        JOIN elections e ON c.election_id = e.id 
                        JOIN users u ON c.user_id = u.id 
                        ORDER BY e.start_date DESC, c.position, u.full_name");
$stmt->execute();
$candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch election results using shared helper
$results = [];
foreach ($elections as $election) {
    if (date('Y-m-d H:i:s') > $election['end_date']) {
        $results[$election['id']] = getElectionResults($conn, (int)$election['id']);
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $user_id = (int)filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT profile_picture, email, full_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            throw new Exception("Utilisateur introuvable.");
        }

        $stmt = $conn->prepare("DELETE FROM discussion WHERE sender_id = ? OR recipient_id = ?");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM reports WHERE reporter_email = ? OR reported_user = ?");
        $stmt->bind_param("ss", $user['email'], $user['full_name']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM suggestions WHERE email = ?");
        $stmt->bind_param("s", $user['email']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM candidates WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM votes WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        if ($user['profile_picture'] && file_exists($user['profile_picture']) && $user['profile_picture'] !== 'img/profile_pic.jpeg') {
            unlink($user['profile_picture']);
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Utilisateur et ses données associées supprimés avec succès.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur : " . $e->getMessage();
    }
    header("Location: admin.php");
    exit;
}

// Handle user edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $user_id = (int)filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $birth_date = filter_var($_POST['birth_date'], FILTER_SANITIZE_STRING);
    $bac_year = (int)filter_var($_POST['bac_year'], FILTER_SANITIZE_NUMBER_INT);
    $studies = filter_var($_POST['studies'], FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, birth_date = ?, bac_year = ?, studies = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $full_name, $email, $birth_date, $bac_year, $studies, $user_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profil utilisateur mis à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du profil.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle media deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $media_path = filter_var($_POST['media_path'], FILTER_SANITIZE_STRING);
    if (file_exists($media_path)) {
        if (unlink($media_path)) {
            $_SESSION['success'] = "Média supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression du média.";
        }
    } else {
        $_SESSION['error'] = "Fichier média introuvable.";
    }
    header("Location: admin.php");
    exit;
}

// Handle year deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_year'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $year = (int)filter_var($_POST['year'], FILTER_SANITIZE_NUMBER_INT);
    $year_folder = "Uploads/{$year}_pic";
    if (is_dir($year_folder)) {
        $files = glob($year_folder . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (rmdir($year_folder)) {
            $_SESSION['success'] = "Année $year et ses médias supprimés avec succès.";
            $year_folders = glob('Uploads/*_pic', GLOB_ONLYDIR);
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression de l'année $year.";
        }
    } else {
        $_SESSION['error'] = "Année $year introuvable.";
    }
    header("Location: admin.php");
    exit;
}

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $message_id = (int)filter_var($_POST['message_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM discussion WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Message supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du message.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle report deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $report_id = (int)filter_var($_POST['report_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Signalement supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du signalement.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle suggestion deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_suggestion'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $suggestion_id = (int)filter_var($_POST['suggestion_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM suggestions WHERE id = ?");
    $stmt->bind_param("i", $suggestion_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Suggestion supprimée avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de la suggestion.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle new year creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_year'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $year = (int)filter_var($_POST['year'], FILTER_SANITIZE_NUMBER_INT);
    if ($year < 1900 || $year > (int)date('Y') + 10) {
        $_SESSION['error'] = "Année invalide. Veuillez entrer une année entre 1900 et " . ((int)date('Y') + 10) . ".";
    } else {
        $year_folder = "Uploads/{$year}_pic";
        if (is_dir($year_folder)) {
            $_SESSION['error'] = "L'année $year existe déjà.";
        } else {
            if (mkdir($year_folder, 0777, true)) {
                $_SESSION['success'] = "Année $year ajoutée avec succès.";
                $year_folders = glob('Uploads/*_pic', GLOB_ONLYDIR);
            } else {
                $_SESSION['error'] = "Erreur lors de la création du dossier pour l'année $year.";
            }
        }
    }
    header("Location: admin.php");
    exit;
}


// Handle regulation article addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_regulation'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $article_number = (int)filter_var($_POST['article_number'], FILTER_SANITIZE_NUMBER_INT);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $content = $_POST['content']; // Allow HTML content
    $order_index = (int)filter_var($_POST['order_index'], FILTER_SANITIZE_NUMBER_INT);

    $stmt = $conn->prepare("INSERT INTO regulations (article_number, title, content, order_index) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $article_number, $title, $content, $order_index);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Article de règlement ajouté avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'article.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle regulation article update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_regulation'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $regulation_id = (int)filter_var($_POST['regulation_id'], FILTER_SANITIZE_NUMBER_INT);
    $article_number = (int)filter_var($_POST['article_number'], FILTER_SANITIZE_NUMBER_INT);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $content = $_POST['content']; // Allow HTML content
    $order_index = (int)filter_var($_POST['order_index'], FILTER_SANITIZE_NUMBER_INT);

    $stmt = $conn->prepare("UPDATE regulations SET article_number = ?, title = ?, content = ?, order_index = ? WHERE id = ?");
    $stmt->bind_param("issii", $article_number, $title, $content, $order_index, $regulation_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Article de règlement mis à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour de l'article.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle regulation article deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_regulation'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $regulation_id = (int)filter_var($_POST['regulation_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM regulations WHERE id = ?");
    $stmt->bind_param("i", $regulation_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Article de règlement supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de l'article.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle regulation footer update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_regulation_footer'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $footer_content = $_POST['footer_content']; // Allow HTML content
    $stmt = $conn->prepare("UPDATE regulations_footer SET content = ? WHERE id = 1");
    $stmt->bind_param("s", $footer_content);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Pied de page du règlement mis à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du pied de page.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Fetch all regulation articles
$stmt = $conn->prepare("SELECT * FROM regulations ORDER BY order_index ASC");
$stmt->execute();
$regulations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch regulation footer
$stmt = $conn->prepare("SELECT content FROM regulations_footer WHERE id = 1");
$stmt->execute();
$regulation_footer = $stmt->get_result()->fetch_assoc()['content'] ?? '<p>Fait à Paris, le 15 juin 2025</p><p>Le Président de SIGMA Alumni</p><p>Jean Dupont</p>';
$stmt->close();

// Handle media upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    $year = (int)filter_var($_POST['media_year'], FILTER_SANITIZE_NUMBER_INT);
    $year_folder = "Uploads/{$year}_pic";
    if (!is_dir($year_folder)) {
        $_SESSION['error'] = "L'année $year n'existe pas. Veuillez d'abord créer l'année.";
        header("Location: admin.php");
        exit;
    }
    if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['error'] = "Aucun fichier sélectionné.";
        header("Location: admin.php");
        exit;
    }
    $file = $_FILES['media_file'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'mp4', 'webm'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_extensions)) {
        $_SESSION['error'] = "Type de fichier non autorisé. Utilisez jpg, jpeg, png, mp4 ou webm.";
        header("Location: admin.php");
        exit;
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        $_SESSION['error'] = "Le fichier est trop volumineux. Taille maximale : 10 Mo.";
        header("Location: admin.php");
        exit;
    }
    $filename = uniqid() . '.' . $ext;
    $destination = "$year_folder/$filename";
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $_SESSION['success'] = "Média ajouté avec succès à l'année $year.";
        $total_media++;
    } else {
        $_SESSION['error'] = "Erreur lors du téléchargement du média.";
    }
    header("Location: admin.php");
    exit;
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// Fetch all users
$stmt = $conn->prepare("SELECT DISTINCT id, full_name, email, birth_date, bac_year, studies, profile_picture FROM users ORDER BY full_name");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
error_log("Fetched " . count($users) . " users for admin panel at " . date('Y-m-d H:i:s'));

// Fetch all media, grouped by year
$media_by_year = [];
foreach ($year_folders as $folder) {
    $year = preg_replace('/.*\/(\d{4})_pic$/', '$1', $folder);
    $files = glob($folder . '/*.{' . implode(',', $media_extensions) . '}', GLOB_BRACE);
    $media_by_year[$year] = [];
    foreach ($files as $file) {
        $media_by_year[$year][] = [
            'media_path' => $file,
            'filename' => basename($file),
            'type' => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['mp4', 'webm']) ? 'video' : 'image'
        ];
    }
}

// Fetch all messages
$stmt = $conn->prepare("SELECT d.id, d.sender_id, d.recipient_id, d.content, d.sent_at, d.is_read, 
                        u1.full_name AS sender_name, u2.full_name AS recipient_name 
                        FROM discussion d 
                        LEFT JOIN users u1 ON d.sender_id = u1.id 
                        LEFT JOIN users u2 ON d.recipient_id = u2.id 
                        ORDER BY d.sent_at DESC");
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all reports
$stmt = $conn->prepare("SELECT id, reporter_email, reported_user, reason, created_at FROM reports ORDER BY created_at DESC");
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all suggestions
$stmt = $conn->prepare("SELECT id, email, suggestion, created_at FROM suggestions ORDER BY created_at DESC");
$stmt->execute();
$suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle news addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $title = filter_var($_POST['news_title'], FILTER_SANITIZE_STRING);
    $excerpt = filter_var($_POST['news_excerpt'], FILTER_SANITIZE_STRING);
    $content = $_POST['news_content']; // Allow HTML content
    $order_index = (int)filter_var($_POST['order_index'], FILTER_SANITIZE_NUMBER_INT);
    
    // Check if we already have 3 active news
    $stmt = $conn->prepare("SELECT COUNT(*) FROM news WHERE is_active = 1");
    $stmt->execute();
    $active_news_count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    
    if ($active_news_count >= 3) {
        $_SESSION['error'] = "Maximum 3 actualités actives autorisées. Désactivez d'abord une actualité existante.";
        header("Location: admin.php");
        exit;
    }
    
    $image_path = NULL;
    if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['news_image'];
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_extensions)) {
            $_SESSION['error'] = "Type de fichier non autorisé. Utilisez JPG, JPEG ou PNG.";
            header("Location: admin.php");
            exit;
        }
        
        if ($file['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "L'image est trop volumineuse. Taille maximale : 2 Mo.";
            header("Location: admin.php");
            exit;
        }
        
        $filename = 'news_' . uniqid() . '.' . $ext;
        $destination = "Uploads/news/$filename";
        
        if (!is_dir("Uploads/news")) {
            mkdir("Uploads/news", 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path = $destination;
        } else {
            $_SESSION['error'] = "Erreur lors du téléchargement de l'image.";
            header("Location: admin.php");
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO news (title, excerpt, content, image_path, order_index) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $title, $excerpt, $content, $image_path, $order_index);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Actualité ajoutée avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'actualité.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle news deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_news'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $news_id = (int)filter_var($_POST['news_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Get image path before deletion
    $stmt = $conn->prepare("SELECT image_path FROM news WHERE id = ?");
    $stmt->bind_param("i", $news_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $news_item = $result->fetch_assoc();
    $stmt->close();
    
    // Delete image file if exists
    if ($news_item['image_path'] && file_exists($news_item['image_path'])) {
        unlink($news_item['image_path']);
    }
    
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $news_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Actualité supprimée avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de l'actualité.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle news toggle (active/inactive)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_news'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $news_id = (int)filter_var($_POST['news_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Get current status
    $stmt = $conn->prepare("SELECT is_active FROM news WHERE id = ?");
    $stmt->bind_param("i", $news_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $news_item = $result->fetch_assoc();
    $stmt->close();
    
    $new_status = $news_item['is_active'] ? 0 : 1;
    
    // Check if we're trying to activate and already have 3 active news
    if ($new_status === 1) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM news WHERE is_active = 1 AND id != ?");
        $stmt->bind_param("i", $news_id);
        $stmt->execute();
        $active_news_count = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        
        if ($active_news_count >= 3) {
            $_SESSION['error'] = "Maximum 3 actualités actives autorisées. Désactivez d'abord une actualité existante.";
            header("Location: admin.php");
            exit;
        }
    }
    
    $stmt = $conn->prepare("UPDATE news SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $news_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Statut de l'actualité mis à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du statut.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle news editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: admin.php");
        exit;
    }
    
    $news_id = (int)filter_var($_POST['news_id'], FILTER_SANITIZE_NUMBER_INT);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $excerpt = filter_var($_POST['excerpt'], FILTER_SANITIZE_STRING);
    $content = $_POST['content']; // Allow HTML content
    $order_index = (int)filter_var($_POST['order_index'], FILTER_SANITIZE_NUMBER_INT);
    
    // Handle image update
    $image_path = NULL;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_extensions)) {
            $_SESSION['error'] = "Type de fichier non autorisé. Utilisez JPG, JPEG ou PNG.";
            header("Location: admin.php");
            exit;
        }
        
        if ($file['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "L'image est trop volumineuse. Taille maximale : 2 Mo.";
            header("Location: admin.php");
            exit;
        }
        
        // Delete old image
        $stmt = $conn->prepare("SELECT image_path FROM news WHERE id = ?");
        $stmt->bind_param("i", $news_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_news = $result->fetch_assoc();
        $stmt->close();
        
        if ($old_news['image_path'] && file_exists($old_news['image_path'])) {
            unlink($old_news['image_path']);
        }
        
        $filename = 'news_' . uniqid() . '.' . $ext;
        $destination = "Uploads/news/$filename";
        
        if (!is_dir("Uploads/news")) {
            mkdir("Uploads/news", 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path = $destination;
        }
    }
    
    if ($image_path) {
        $stmt = $conn->prepare("UPDATE news SET title = ?, excerpt = ?, content = ?, image_path = ?, order_index = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssssii", $title, $excerpt, $content, $image_path, $order_index, $news_id);
    } else {
        $stmt = $conn->prepare("UPDATE news SET title = ?, excerpt = ?, content = ?, order_index = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sssii", $title, $excerpt, $content, $order_index, $news_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Actualité mise à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour de l'actualité.";
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Récupérer les statistiques de vote pour chaque élection
$election_stats = [];
foreach ($elections as $election) {
    // Nombre total d'utilisateurs pouvant voter
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $total_users = $stmt->get_result()->fetch_assoc()['total_users'];
    $stmt->close();
    
    // Nombre d'utilisateurs ayant voté
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as voters FROM votes WHERE election_id = ?");
    $stmt->bind_param("i", $election['id']);
    $stmt->execute();
    $voters = $stmt->get_result()->fetch_assoc()['voters'];
    $stmt->close();
    
    // Pourcentage de participation
    $participation_rate = $total_users > 0 ? round(($voters / $total_users) * 100, 1) : 0;
    
    $election_stats[$election['id']] = [
        'total_users' => $total_users,
        'voters' => $voters,
        'participation_rate' => $participation_rate,
        'remaining_voters' => $total_users - $voters
    ];
}

// Fetch all news
$stmt = $conn->prepare("SELECT * FROM news ORDER BY order_index ASC, created_at DESC");
$stmt->execute();
$news = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Communauté Sigma</title>
    
    <!-- Favicon -->
    <?php 
    $favicon_path = $general_config['favicon'] ?? 'img/favicon.ico';
    if (file_exists($favicon_path)): 
    ?>
        <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon_path); ?>">
        <link rel="shortcut icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon_path); ?>">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- CKEditor 5 - Alternative gratuite et open source à TinyMCE -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
    <style>
        :root {
            --sigma-blue: #1a237e;
            --sigma-blue-light: #2d3a9b;
            --sigma-gold: #d4af37;
            --sigma-light-blue: #e8eaf6;
            --sigma-dark: #0d0d0d;
            --sigma-gray: #f5f7fa;
            --sigma-border: #e2e8f0;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--sigma-gray);
            color: var(--sigma-dark);
            line-height: 1.6;
        }
        
        /* Layout principal */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a237e 0%, #0d47a1 50%, #1565c0 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar.collapsed .sidebar-logo h1,
        .sidebar.collapsed .nav-item span,
        .sidebar.collapsed .sidebar-footer button span {
            opacity: 0;
            display: none;
        }
        
        .sidebar.collapsed .sidebar-logo img {
            margin: 0 auto;
        }
        
        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 0.875rem;
            position: relative;
        }
        
        .sidebar.collapsed .nav-item i {
            margin-right: 0;
            font-size: 1.25rem;
        }
        
        .sidebar.collapsed .nav-item:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 0.875rem;
            margin-left: 10px;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            animation: tooltipFadeIn 0.2s ease;
        }
        
        @keyframes tooltipFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50%) translateX(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(-50%) translateX(0);
            }
        }
        
        .sidebar.collapsed .nav-item:hover,
        .sidebar.collapsed .nav-item.active {
            border-left: 4px solid var(--sigma-gold);
        }
        
        .sidebar.collapsed .sidebar-toggle {
            right: -15px;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .sidebar-logo img {
            height: 45px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
            transition: all 0.3s ease;
        }
        
        .sidebar-logo:hover img {
            transform: scale(1.05);
        }
        
        .sidebar-logo h1 {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: opacity 0.3s ease;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-section-title {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem 0.5rem;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--sigma-gold);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            opacity: 0.9;
        }
        
        .nav-section-title:first-child {
            margin-top: 0;
        }
        
        .nav-section-title i {
            font-size: 0.875rem;
            margin-right: 0.625rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255, 255, 255, 0.85);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            margin: 0.25rem 0.5rem;
            border-radius: 8px;
            border-left: 3px solid transparent;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: rgba(255, 255, 255, 0.05);
            transition: width 0.3s ease;
            border-radius: 8px;
        }
        
        .nav-item:hover::before {
            width: 100%;
        }
        
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.12);
            color: white;
            border-left-color: var(--sigma-gold);
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .nav-item.active {
            background: linear-gradient(90deg, rgba(212, 175, 55, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
            color: white;
            border-left-color: var(--sigma-gold);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .nav-item.active i {
            color: var(--sigma-gold);
            transform: scale(1.1);
        }
        
        .nav-item i {
            width: 24px;
            margin-right: 0.875rem;
            font-size: 1.15rem;
            transition: all 0.3s ease;
            z-index: 1;
        }
        
        .nav-item span {
            font-weight: 500;
            z-index: 1;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .nav-item:hover i {
            transform: scale(1.1);
        }
        
        .nav-badge {
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 12px;
            margin-left: auto;
            min-width: 20px;
            text-align: center;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.9;
            }
        }
        
        .sidebar-footer {
            padding: 1.5rem;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-footer .btn-danger {
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
        }
        
        .sidebar-footer .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.5);
        }
        
        /* Contenu principal */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 80px;
        }
        
        /* Bouton de bascule sidebar */
        .sidebar-toggle {
            position: absolute;
            top: 1.5rem;
            right: -15px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--sigma-gold) 0%, #ffd700 100%);
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--sigma-blue);
            font-size: 0.9rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
        }
        
        .sidebar-toggle:hover {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            transform: scale(1.15) rotate(180deg);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.5);
        }
        
        .sidebar-toggle:active {
            transform: scale(1.05) rotate(180deg);
        }
        
        .sidebar-toggle i {
            transition: transform 0.3s ease;
        }
        
        /* Header du contenu */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--sigma-border);
        }
        
        .content-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--sigma-blue);
        }
        
        .content-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        /* Sections */
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        /* Cartes */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--sigma-border);
        }
        
        .card-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--sigma-border);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--sigma-blue);
        }
        
        /* Grille responsive */
        .grid-responsive {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        /* Statistiques */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--sigma-blue);
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            background-color: var(--sigma-light-blue);
            color: var(--sigma-blue);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--sigma-blue);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #64748b;
            font-weight: 500;
        }
        
        /* Formulaires */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--sigma-border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .form-input:focus {
            border-color: var(--sigma-blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        /* Boutons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--sigma-blue);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--sigma-blue-light);
        }
        
        .btn-secondary {
            background-color: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }
        
        .btn-danger {
            background-color: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #b91c1c;
        }
        
        .btn-success {
            background-color: #16a34a;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #15803d;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        /* Tableaux */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--sigma-border);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background-color: #f8fafc;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid var(--sigma-border);
        }
        
        .table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--sigma-border);
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: #f8fafc;
        }
        
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--sigma-border);
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--sigma-blue);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
        }
        
        .modal-close:hover {
            color: var(--sigma-dark);
        }
        
        /* Alertes */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        /* Barre de progression */
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e2e8f0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--sigma-blue), var(--sigma-gold));
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-error {
            background-color: #fecaca;
            color: #991b1b;
        }
        
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            
            .sidebar.active {
                transform: translateX(0);
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .sidebar-toggle {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 260px;
            }
            
            .nav-item {
                padding: 0.75rem 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .content-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .grid-responsive {
                grid-template-columns: 1fr;
            }
        }
        
        /* Bouton menu mobile */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--sigma-blue);
            cursor: pointer;
            padding: 0.5rem;
        }
        
        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: block;
            }
        }
        
        /* Onglets */
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--sigma-border);
            margin-bottom: 1.5rem;
            overflow-x: auto;
        }
        
        .tab {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }
        
        .tab.active {
            color: var(--sigma-blue);
            border-bottom-color: var(--sigma-blue);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Images et médias */
        .media-preview {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--sigma-border);
        }
        
        /* Icônes d'action */
        .action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .action-edit {
            color: var(--sigma-blue);
        }
        
        .action-edit:hover {
            background-color: var(--sigma-light-blue);
        }
        
        .action-delete {
            color: #dc2626;
        }
        
        .action-delete:hover {
            background-color: #fef2f2;
        }
        
        /* Animation de chargement */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-chevron-left"></i>
            </div>
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="<?php echo htmlspecialchars($general_config['admin_logo']); ?>" alt="Logo SIGMA">
                    <h1>SIGMA Admin</h1>
                </div>
            </div>
            <div class="sidebar-nav">
                <!-- Vue d'ensemble -->
                <div class="nav-section-title">
                    <i class="fas fa-chart-line"></i>
                    <span>Vue d'ensemble</span>
                </div>
                <div class="nav-item active" data-target="dashboard" data-tooltip="Tableau de bord">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </div>
                
                <!-- Gestion du contenu -->
                <div class="nav-section-title">
                    <i class="fas fa-file-alt"></i>
                    <span>Contenu du site</span>
                </div>
                <div class="nav-item" data-target="events-management" data-tooltip="Événements">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Événements</span>
                </div>
                <div class="nav-item" data-target="news" data-tooltip="Actualités">
                    <i class="fas fa-newspaper"></i>
                    <span>Actualités</span>
                </div>
                <div class="nav-item" data-target="media" data-tooltip="Médias">
                    <i class="fas fa-images"></i>
                    <span>Galerie Photos</span>
                </div>
                <div class="nav-item" data-target="regulations" data-tooltip="Règlement">
                    <i class="fas fa-book"></i>
                    <span>Règlement</span>
                </div>
                <div class="nav-item" data-target="objectifs" data-tooltip="Objectifs">
                    <i class="fas fa-bullseye"></i>
                    <span>Objectifs</span>
                </div>
                
                <!-- Gestion des membres -->
                <div class="nav-section-title">
                    <i class="fas fa-users"></i>
                    <span>Membres & Bureau</span>
                </div>
                <div class="nav-item" data-target="users" data-tooltip="Utilisateurs">
                    <i class="fas fa-user-friends"></i>
                    <span>Utilisateurs</span>
                </div>
                <div class="nav-item" data-target="bureau" data-tooltip="Bureau">
                    <i class="fas fa-users-cog"></i>
                    <span>Bureau Actuel</span>
                </div>
                
                <!-- Élections -->
                <div class="nav-section-title">
                    <i class="fas fa-vote-yea"></i>
                    <span>Élections</span>
                </div>
                <div class="nav-item" data-target="elections" data-tooltip="Élections">
                    <i class="fas fa-poll"></i>
                    <span>Gestion Élections</span>
                </div>
                <div class="nav-item" data-target="candidates" data-tooltip="Candidats">
                    <i class="fas fa-user-tie"></i>
                    <span>Candidats</span>
                </div>
                <div class="nav-item" data-target="results" data-tooltip="Résultats">
                    <i class="fas fa-chart-bar"></i>
                    <span>Résultats</span>
                </div>
                
                <!-- Communication -->
                <div class="nav-section-title">
                    <i class="fas fa-comments"></i>
                    <span>Communication</span>
                </div>
                <div class="nav-item" data-target="messages" data-tooltip="Messages">
                    <i class="fas fa-inbox"></i>
                    <span>Messages</span>
                </div>
                <div class="nav-item" data-target="contact" data-tooltip="Contact">
                    <i class="fas fa-envelope"></i>
                    <span>Contacts</span>
                    <?php if ($total_contact_submissions > 0): ?>
                        <span class="nav-badge"><?php echo $total_contact_submissions; ?></span>
                    <?php endif; ?>
                </div>
                <div class="nav-item" data-target="reports" data-tooltip="Signalements">
                    <i class="fas fa-flag"></i>
                    <span>Signalements</span>
                    <?php if ($pending_reports > 0): ?>
                        <span class="nav-badge"><?php echo $pending_reports; ?></span>
                    <?php endif; ?>
                </div>
                <div class="nav-item" data-target="suggestions" data-tooltip="Suggestions">
                    <i class="fas fa-lightbulb"></i>
                    <span>Suggestions</span>
                    <?php if ($pending_suggestions > 0): ?>
                        <span class="nav-badge"><?php echo $pending_suggestions; ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Paramètres -->
                <div class="nav-section-title">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </div>
                <div class="nav-item" data-target="general-config" data-tooltip="Configuration">
                    <i class="fas fa-sliders-h"></i>
                    <span>Configuration</span>
                </div>
                <div class="nav-item" data-target="festive-themes" data-tooltip="Thèmes Festifs">
                    <i class="fas fa-palette"></i>
                    <span>Thèmes Festifs</span>
                </div>
            </div>
            
            <div class="sidebar-footer">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <button type="submit" name="logout" class="btn btn-danger w-full">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="main-content">
            <!-- Header du contenu -->
            <div class="content-header">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="content-title" id="contentTitle">Tableau de bord</h1>
                <div class="content-actions">
                   <button type="button" class="btn btn-secondary" onclick="refreshPage()">
                        <i class="fas fa-sync-alt"></i>
                        <span>Actualiser</span>
                    </button>
                </div>
            </div>
            
            <!-- Alertes -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Sections de contenu -->
            <!-- Tableau de bord -->
            <section id="dashboard" class="section active">
                <div class="grid-responsive">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Utilisateurs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-images fa-lg"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_media; ?></div>
                        <div class="stat-label">Médias</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-comments fa-lg"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_messages; ?></div>
                        <div class="stat-label">Messages</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-flag fa-lg"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_reports; ?></div>
                        <div class="stat-label">Signalements</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-lightbulb fa-lg"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_suggestions; ?></div>
                        <div class="stat-label">Suggestions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-vote-yea fa-lg"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_elections; ?></div>
                        <div class="stat-label">Élections</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Code de vérification</h2>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="form-group">
                            <label class="form-label">Code actuel</label>
                            <input type="text" name="verification_code" value="<?php echo htmlspecialchars($current_code); ?>" class="form-input" required>
                        </div>
                        <button type="submit" name="update_verification_code" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span>Mettre à jour</span>
                        </button>
                    </form>
                </div>
            </section>
            
            <!-- Bureau -->
            <section id="bureau" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Gestion du Bureau</h2>
                    </div>
                    
                    <!-- Contenu de la section bureau -->
                    <div class="tabs">
                        <div class="tab active" data-tab="current">Bureau Actuel</div>
                        <div class="tab" data-tab="history">Historique</div>
                    </div>

                    <div class="tab-content active" id="current">
                        <form method="POST" class="mb-6">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <select name="user_id" class="form-input" required>
                                    <option value="">Sélectionner un utilisateur</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="position" placeholder="Poste (ex: Président)" class="form-input" required>
                                <input type="number" name="promotion_year" placeholder="Année de promotion" class="form-input" min="1900" max="<?php echo (int)date('Y'); ?>" required>
                            </div>
                            <button type="submit" name="add_bureau_member" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <span>Ajouter au bureau</span>
                            </button>
                        </form>

                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Poste</th>
                                        <th>Promotion</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_bureau as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['position']); ?></td>
                                            <td><?php echo htmlspecialchars($member['promotion_year']); ?></td>
                                            <td>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                    <button type="submit" name="remove_bureau_member" class="action-icon action-delete" onclick="return confirm('Voulez-vous vraiment retirer ce membre du bureau ?');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-content" id="history">
                        <h3 class="text-xl font-semibold mb-4">Historique des Bureaux</h3>
                        <form method="POST" class="mb-8">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <input type="text" name="year_range" placeholder="Période (ex: 2024-2025)" class="form-input" required>
                                <textarea name="members" placeholder="Membres (format: Nom - Poste, séparés par des virgules)" class="form-input" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="add_historical_bureau" class="btn-primary">Ajouter un bureau historique</button>
                        </form>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-3">Période</th>
                                        <th class="p-3">Membres</th>
                                        <th class="p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bureau_history as $history): ?>
                                        <tr class="border-b">
                                            <td class="p-3"><?php echo htmlspecialchars($history['year_range']); ?></td>
                                            <td class="p-3"><?php echo htmlspecialchars(substr($history['members'], 0, 100)) . (strlen($history['members']) > 100 ? '...' : ''); ?></td>
                                            <td class="p-3">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="history_id" value="<?php echo $history['id']; ?>">
                                                    <button type="submit" name="remove_historical_bureau" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer ce bureau historique ?');"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Utilisateurs -->
            <section id="users" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-users text-blue-600 mr-2"></i>Gestion des utilisateurs</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Nom</th>
                                    <th class="p-3">Email</th>
                                    <th class="p-3">Date de naissance</th>
                                    <th class="p-3">Année Bac</th>
                                    <th class="p-3">Études</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr class="border-b">
                                        <td class="p-3"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($user['birth_date'] ?? 'N/A'); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($user['bac_year'] ?? 'N/A'); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($user['studies'] ?? 'N/A'); ?></td>
                                        <td class="p-3">
                                            <button onclick="openEditUserModal(<?php echo $user['id']; ?>)" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Médias -->
            <section id="media" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-images text-blue-600 mr-2"></i>Gestion des médias</h2>
                    <form method="POST" enctype="multipart/form-data" class="mb-8">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="flex space-x-4 mb-4">
                            <input type="number" name="year" placeholder="Année (ex: 2023)" class="form-input" min="1900" max="<?php echo (int)date('Y') + 10; ?>" required>
                            <button type="submit" name="add_year" class="btn-primary">Ajouter une année</button>
                        </div>
                    </form>
                    <form method="POST" enctype="multipart/form-data" class="mb-8">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="flex space-x-4 mb-4">
                            <select name="media_year" class="form-input" required>
                                <option value="">Sélectionner une année</option>
                                <?php foreach ($year_folders as $folder): ?>
                                    <?php $year = preg_replace('/.*\/(\d{4})_pic$/', '$1', $folder); ?>
                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="file" name="media_file" class="form-input" accept=".jpg,.jpeg,.png,.mp4,.webm" required>
                            <button type="submit" name="upload_media" class="btn-primary">Télécharger</button>
                        </div>
                    </form>
                    <?php foreach ($media_by_year as $year => $media): ?>
                        <div class="mb-8">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-semibold">Année <?php echo $year; ?></h3>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                                    <button type="submit" name="delete_year" class="btn-danger" onclick="return confirm('Voulez-vous vraiment supprimer cette année et tous ses médias ?');">Supprimer l'année</button>
                                </form>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($media as $item): ?>
                                    <div class="relative">
                                        <?php if ($item['type'] === 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($item['media_path']); ?>" alt="Média" class="w-full h-40 object-cover rounded-lg">
                                        <?php else: ?>
                                            <video src="<?php echo htmlspecialchars($item['media_path']); ?>" class="w-full h-40 object-cover rounded-lg" controls></video>
                                        <?php endif; ?>
                                        <form method="POST" class="absolute top-2 right-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="media_path" value="<?php echo htmlspecialchars($item['media_path']); ?>">
                                            <button type="submit" name="delete_media" class="text-red-600 hover:text-red-800 bg-white rounded-full p-2" onclick="return confirm('Voulez-vous vraiment supprimer ce média ?');"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- Messages -->
            <section id="messages" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-comments text-blue-600 mr-2"></i>Gestion des messages</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Expéditeur</th>
                                    <th class="p-3">Destinataire</th>
                                    <th class="p-3">Contenu</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Lu</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                    <tr class="border-b">
                                        <td class="p-3"><?php echo htmlspecialchars($message['sender_name'] ?? 'Inconnu'); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($message['recipient_name'] ?? 'Inconnu'); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(substr($message['content'], 0, 50)) . (strlen($message['content']) > 50 ? '...' : ''); ?></td>
                                        <td class="p-3"><?php echo date('d/m/Y H:i', strtotime($message['sent_at'])); ?></td>
                                        <td class="p-3"><?php echo $message['is_read'] ? 'Oui' : 'Non'; ?></td>
                                        <td class="p-3">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <button type="submit" name="delete_message" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer ce message ?');"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Signalements -->
            <section id="reports" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-flag text-blue-600 mr-2"></i>Gestion des signalements</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Rapporteur</th>
                                    <th class="p-3">Utilisateur signalé</th>
                                    <th class="p-3">Raison</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr class="border-b">
                                        <td class="p-3"><?php echo htmlspecialchars($report['reporter_email']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($report['reported_user']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(substr($report['reason'], 0, 50)) . (strlen($report['reason']) > 50 ? '...' : ''); ?></td>
                                        <td class="p-3"><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></td>
                                        <td class="p-3">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                <button type="submit" name="delete_report" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer ce signalement ?');"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Suggestions -->
            <section id="suggestions" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-lightbulb text-blue-600 mr-2"></i>Gestion des suggestions</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Email</th>
                                    <th class="p-3">Suggestion</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suggestions as $suggestion): ?>
                                    <tr class="border-b">
                                        <td class="p-3"><?php echo htmlspecialchars($suggestion['email']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(substr($suggestion['suggestion'], 0, 50)) . (strlen($suggestion['suggestion']) > 50 ? '...' : ''); ?></td>
                                        <td class="p-3"><?php echo date('d/m/Y H:i', strtotime($suggestion['created_at'])); ?></td>
                                        <td class="p-3">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                                <button type="submit" name="delete_suggestion" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer cette suggestion ?');"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Événements -->
            <section id="events-management" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-calendar-alt text-blue-600 mr-2"></i>Gestion des Événements</h2>
                    
                    <!-- Add Event Form -->
                    <h3 class="text-xl font-semibold mb-4">Ajouter un événement</h3>
                    <form method="POST" enctype="multipart/form-data" class="mb-8">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="grid grid-cols-1 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Titre *</label>
                                <input type="text" name="event_title" class="form-input" required maxlength="255">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Description *</label>
                                <textarea name="event_description" class="form-input" rows="3" required></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Date et heure *</label>
                                    <input type="datetime-local" name="event_date" class="form-input" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Lieu *</label>
                                    <input type="text" name="event_location" class="form-input" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Image (recommandé: 800x400px)</label>
                                <input type="file" name="event_image" class="form-input" accept=".jpg,.jpeg,.png">
                                <p class="text-sm text-gray-500 mt-1">Formats acceptés: JPG, JPEG, PNG. Taille max: 2MB</p>
                            </div>
                        </div>
                        <button type="submit" name="add_event" class="btn-primary">Ajouter l'événement</button>
                    </form>

                    <!-- Existing Events -->
                    <h3 class="text-xl font-semibold mb-4">Événements existants</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Image</th>
                                    <th class="p-3">Titre</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Lieu</th>
                                    <th class="p-3">Statut</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr class="border-b">
                                        <td class="p-3">
                                            <?php if ($event['image_path']): ?>
                                                <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="Image événement" class="w-20 h-12 object-cover rounded">
                                            <?php else: ?>
                                                <span class="text-gray-400">Aucune image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3"><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td class="p-3"><?php echo date('d/m/Y H:i', strtotime($event['event_date'])); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td class="p-3">
                                            <?php 
                                            $now = new DateTime();
                                            $eventDate = new DateTime($event['event_date']);
                                            if ($eventDate > $now) {
                                                echo '<span class="text-green-600">À venir</span>';
                                            } else {
                                                echo '<span class="text-gray-500">Passé</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="p-3">
                                            <button onclick="openEditEventModal(<?php echo $event['id']; ?>)" class="text-blue-600 hover:text-blue-800 mr-2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" name="delete_event" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer cet événement ?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Actualités -->
            <section id="news" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-newspaper text-blue-600 mr-2"></i>Gestion des Actualités</h2>
                    
                    <!-- Add News Form -->
                    <h3 class="text-xl font-semibold mb-4">Ajouter une actualité</h3>
                    <form method="POST" enctype="multipart/form-data" class="mb-8">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="grid grid-cols-1 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Titre *</label>
                                <input type="text" name="news_title" class="form-input" required maxlength="255">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Extrait * (court résumé)</label>
                                <textarea name="news_excerpt" class="form-input" rows="3" required maxlength="500"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Contenu *</label>
                                <textarea name="news_content" class="form-input" rows="6" required></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Image (recommandé: 800x400px)</label>
                                <input type="file" name="news_image" class="form-input" accept=".jpg,.jpeg,.png">
                                <p class="text-sm text-gray-500 mt-1">Formats acceptés: JPG, JPEG, PNG. Taille max: 2MB</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Ordre d'affichage</label>
                                <input type="number" name="order_index" class="form-input" value="0" min="0">
                            </div>
                        </div>
                        <button type="submit" name="add_news" class="btn-primary">Ajouter l'actualité</button>
                    </form>

                    <!-- Existing News -->
                    <h3 class="text-xl font-semibold mb-4">Actualités existantes (max 3 actives)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Image</th>
                                    <th class="p-3">Titre</th>
                                    <th class="p-3">Extrait</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Statut</th>
                                    <th class="p-3">Ordre</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($news as $news_item): ?>
                                    <tr class="border-b">
                                        <td class="p-3">
                                            <?php if ($news_item['image_path']): ?>
                                                <img src="<?php echo htmlspecialchars($news_item['image_path']); ?>" alt="Image actualité" class="w-20 h-12 object-cover rounded">
                                            <?php else: ?>
                                                <span class="text-gray-400">Aucune image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3"><?php echo htmlspecialchars($news_item['title']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(substr($news_item['excerpt'], 0, 50)) . (strlen($news_item['excerpt']) > 50 ? '...' : ''); ?></td>
                                        <td class="p-3"><?php echo date('d/m/Y H:i', strtotime($news_item['created_at'])); ?></td>
                                        <td class="p-3">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="news_id" value="<?php echo $news_item['id']; ?>">
                                                <button type="submit" name="toggle_news" class="<?php echo $news_item['is_active'] ? 'text-green-600 hover:text-green-800' : 'text-gray-400 hover:text-gray-600'; ?>">
                                                    <i class="fas fa-<?php echo $news_item['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="p-3"><?php echo htmlspecialchars($news_item['order_index']); ?></td>
                                        <td class="p-3">
                                            <button onclick="openEditNewsModal(<?php echo $news_item['id']; ?>)" class="text-blue-600 hover:text-blue-800 mr-2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="news_id" value="<?php echo $news_item['id']; ?>">
                                                <button type="submit" name="delete_news" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer cette actualité ?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Contact -->
            <section id="contact" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-envelope text-blue-600 mr-2"></i>
                            Gestion des Contacts
                        </h2>
                        <p class="text-gray-600 mt-2">Configurez les informations de contact et gérez les messages reçus</p>
                    </div>

                    <!-- Informations de Contact -->
                    <div class="mb-8">
                        <h3 class="text-xl font-semibold mb-4 text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            Informations de Contact
                        </h3>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Coordonnées principales -->
                                <div class="space-y-4">
                                    <h4 class="font-semibold text-gray-700 border-b pb-2">Coordonnées principales</h4>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Email de contact *</label>
                                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($contact_info['email']); ?>" class="form-input" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Téléphone *</label>
                                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($contact_info['phone']); ?>" class="form-input" required>
                                    </div>
                                </div>

                                <!-- Horaires -->
                                <div class="space-y-4">
                                    <h4 class="font-semibold text-gray-700 border-b pb-2">Horaires d'ouverture</h4>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Horaires *</label>
                                        <textarea name="hours" id="hours" class="form-input form-textarea" rows="3" required><?php echo htmlspecialchars($contact_info['hours']); ?></textarea>
                                        <p class="text-sm text-gray-500 mt-1">Ex: Lundi - Vendredi : 9h - 18h</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Adresse -->
                            <div class="form-group mb-6">
                                <label class="form-label">Adresse complète *</label>
                                <textarea name="address" id="address" class="form-input form-textarea" rows="3" required><?php echo htmlspecialchars($contact_info['address']); ?></textarea>
                            </div>

                            <!-- Réseaux sociaux -->
                            <div class="mb-6">
                                <h4 class="font-semibold text-gray-700 mb-4">Réseaux Sociaux</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">URL Instagram</label>
                                        <input type="url" name="instagram_url" id="instagram_url" value="<?php echo htmlspecialchars($contact_info['instagram_url'] ?? ''); ?>" class="form-input" placeholder="https://instagram.com/votre-compte">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">URL TikTok</label>
                                        <input type="url" name="tiktok_url" id="tiktok_url" value="<?php echo htmlspecialchars($contact_info['tiktok_url'] ?? ''); ?>" class="form-input" placeholder="https://tiktok.com/@votre-compte">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">URL LinkedIn</label>
                                        <input type="url" name="linkedin_url" id="linkedin_url" value="<?php echo htmlspecialchars($contact_info['linkedin_url'] ?? ''); ?>" class="form-input" placeholder="https://linkedin.com/company/votre-entreprise">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">URL Facebook</label>
                                        <input type="url" name="facebook_url" id="facebook_url" value="<?php echo htmlspecialchars($contact_info['facebook_url'] ?? ''); ?>" class="form-input" placeholder="https://facebook.com/votre-page">
                                    </div>
                                </div>
                            </div>

                            <!-- Carte -->
                            <div class="form-group">
                                <label class="form-label">Code iframe Google Maps *</label>
                                <textarea name="map_iframe" id="map_iframe" class="form-input form-textarea" rows="4" required><?php echo htmlspecialchars($contact_info['map_iframe']); ?></textarea>
                                <p class="text-sm text-gray-500 mt-1">
                                    <a href="https://www.google.com/maps" target="_blank" class="text-blue-600 hover:underline">
                                        Obtenir le code iframe depuis Google Maps
                                    </a>
                                </p>
                            </div>

                            <button type="submit" name="update_contact_info" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                Mettre à jour les informations
                            </button>
                        </form>
                    </div>

                    <!-- Messages Reçus -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-blue-700">
                                <i class="fas fa-inbox mr-2"></i>
                                Messages Reçus
                            </h3>
                            <span class="badge badge-info"><?php echo count($submissions); ?> message(s)</span>
                        </div>

                        <?php if (!empty($submissions)): ?>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Expéditeur</th>
                                            <th>Email</th>
                                            <th>Sujet</th>
                                            <th>Message</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissions as $submission): ?>
                                            <tr>
                                                <td class="whitespace-nowrap">
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo date('d/m/Y', strtotime($submission['created_at'])); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        <?php echo date('H:i', strtotime($submission['created_at'])); ?>
                                                    </div>
                                                </td>
                                                <td class="font-medium"><?php echo htmlspecialchars($submission['name']); ?></td>
                                                <td>
                                                    <a href="mailto:<?php echo htmlspecialchars($submission['email']); ?>" class="text-blue-600 hover:underline text-sm">
                                                        <?php echo htmlspecialchars($submission['email']); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                                        <?php echo htmlspecialchars($submission['subject']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="max-w-xs truncate" title="<?php echo htmlspecialchars(strip_tags($submission['message'])); ?>">
                                                        <?php echo htmlspecialchars(substr(strip_tags($submission['message']), 0, 60)); ?>
                                                        <?php if (strlen(strip_tags($submission['message'])) > 60): ?>...<?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex space-x-2">
                                                        <button onclick="openMessageModal(
                                                            '<?php echo htmlspecialchars($submission['name'], ENT_QUOTES); ?>',
                                                            '<?php echo htmlspecialchars($submission['email'], ENT_QUOTES); ?>',
                                                            '<?php echo htmlspecialchars($submission['subject'], ENT_QUOTES); ?>',
                                                            `<?php echo htmlspecialchars(str_replace('`', '\`', $submission['message']), ENT_QUOTES); ?>`,
                                                            '<?php echo date('d/m/Y à H:i', strtotime($submission['created_at'])); ?>'
                                                        )" class="action-icon action-edit" title="Voir le message">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                            <input type="hidden" name="id" value="<?php echo $submission['id']; ?>">
                                                            <button type="submit" name="delete_submission" class="action-icon action-delete" title="Supprimer" onclick="return confirm('Voulez-vous vraiment supprimer ce message ?');">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                <p>Aucun message reçu pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
            <!-- Configuration Générale -->
            <section id="general-config" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-cog text-blue-600 mr-2"></i>Configuration Générale</h2>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <!-- Réseaux sociaux -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold mb-4">Réseaux Sociaux</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">URL Instagram</label>
                                    <input type="url" name="instagram_url" value="<?php echo htmlspecialchars($general_config['instagram_url']); ?>" class="form-input" placeholder="https://instagram.com/votre-compte">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">URL TikTok</label>
                                    <input type="url" name="tiktok_url" value="<?php echo htmlspecialchars($general_config['tiktok_url']); ?>" class="form-input" placeholder="https://tiktok.com/@votre-compte">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations de contact -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold mb-4">Informations de Contact</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Email de contact</label>
                                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($general_config['contact_email']); ?>" class="form-input" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Téléphone</label>
                                    <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($general_config['contact_phone']); ?>" class="form-input" required>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Adresse</label>
                                    <textarea name="contact_address" class="form-input" rows="3" placeholder="Entrez l'adresse (le texte sera automatiquement formaté)" required><?php 
                                        // Afficher le texte brut (enlever les balises HTML pour l'édition)
                                        $address = $general_config['contact_address'];
                                        // Convertir <br> en retours à la ligne
                                        $address = str_replace(['<br>', '<br/>', '<br />'], "\n", $address);
                                        // Enlever les balises <p> et </p>
                                        $address = strip_tags($address);
                                        echo htmlspecialchars($address);
                                    ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-info-circle"></i> Entrez simplement le texte, il sera automatiquement formaté en HTML
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Médias -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold mb-4">Médias et Logos</h3>
                            
                            <!-- Logo du footer -->
                            <div class="mb-4 p-4 border rounded-lg">
                                <label class="block text-sm font-medium mb-2">Logo du Footer</label>
                                <div class="flex items-center space-x-4 mb-2">
                                    <?php if ($general_config['footer_logo'] && file_exists($general_config['footer_logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($general_config['footer_logo']); ?>" alt="Logo actuel" class="w-20 h-20 object-contain border rounded">
                                    <?php endif; ?>
                                    <input type="file" name="footer_logo" class="form-input" accept=".jpg,.jpeg,.png,.svg">
                                </div>
                                <p class="text-sm text-gray-500">Formats acceptés: JPG, PNG, SVG. Taille max: 5MB</p>
                            </div>
                            
                            <!-- Vidéo de fond -->
                            <div class="mb-4 p-4 border rounded-lg">
                                <label class="block text-sm font-medium mb-2">Vidéo de fond (page d'accueil)</label>
                                <div class="mb-2">
                                    <?php if ($general_config['hero_video'] && file_exists($general_config['hero_video'])): ?>
                                        <video src="<?php echo htmlspecialchars($general_config['hero_video']); ?>" class="w-64 h-36 object-cover border rounded" controls></video>
                                        <p class="text-xs text-gray-600 mt-1">Taille actuelle: <?php echo round(filesize($general_config['hero_video']) / (1024*1024), 2); ?> MB</p>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="hero_video" class="form-input" accept=".mp4,.webm,.mov">
                                <p class="text-sm text-gray-500">Formats acceptés: MP4, WebM, MOV. Taille max: 2GB</p>
                                <p class="text-xs text-orange-600 mt-1"><i class="fas fa-info-circle"></i> Note: L'upload peut prendre plusieurs minutes pour les gros fichiers.</p>
                            </div>
                            
                            <!-- Favicon -->
                            <div class="mb-4 p-4 border rounded-lg">
                                <label class="block text-sm font-medium mb-2">Favicon</label>
                                <div class="flex items-center space-x-4 mb-2">
                                    <?php if ($general_config['favicon'] && file_exists($general_config['favicon'])): ?>
                                        <img src="<?php echo htmlspecialchars($general_config['favicon']); ?>" alt="Favicon actuel" class="w-16 h-16 object-contain border rounded">
                                    <?php endif; ?>
                                    <input type="file" name="favicon" class="form-input" accept=".ico,.png,.jpg">
                                </div>
                                <p class="text-sm text-gray-500">Formats acceptés: ICO, PNG, JPG. Taille max: 2MB</p>
                            </div>
                            
                            <!-- Logo admin et header -->
                            <div class="p-4 border rounded-lg">
                                <label class="block text-sm font-medium mb-2">Logo de l'administration et du header</label>
                                <div class="flex items-center space-x-4 mb-2">
                                    <?php if ($general_config['admin_logo'] && file_exists($general_config['admin_logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($general_config['admin_logo']); ?>" alt="Logo admin actuel" class="w-20 h-20 object-contain border rounded">
                                    <?php endif; ?>
                                    <input type="file" name="admin_logo" class="form-input" accept=".jpg,.jpeg,.png,.svg">
                                </div>
                                <p class="text-sm text-gray-500">Formats acceptés: JPG, PNG, SVG. Taille max: 5MB</p>
                                <p class="text-xs text-blue-600 mt-1"><i class="fas fa-info-circle"></i> Note: Ce logo sera aussi utilisé dans le header du site.</p>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_general_config" class="btn-primary">Mettre à jour la configuration</button>
                    </form>
                </div>
            </section>

            <!-- Thèmes Festifs -->
            <section id="festive-themes" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-palette text-purple-600 mr-2"></i>Thèmes Festifs</h2>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Important:</strong> Un seul thème peut être actif à la fois. L'activation d'un nouveau thème désactivera automatiquement le précédent.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <!-- Aucun thème -->
                        <div class="theme-card border-2 rounded-lg p-6 cursor-pointer hover:shadow-lg transition" data-theme="none">
                            <div class="text-center mb-4">
                                <i class="fas fa-times-circle text-gray-500 text-5xl mb-3"></i>
                                <h3 class="text-xl font-bold text-gray-700">Aucun thème</h3>
                                <p class="text-sm text-gray-500 mt-2">Design standard du site</p>
                            </div>
                            <button type="button" class="btn-theme w-full" data-theme="none">
                                <i class="fas fa-check-circle mr-2"></i>Désactiver les thèmes
                            </button>
                        </div>
                        
                        <!-- Thème Noël -->
                        <div class="theme-card border-2 rounded-lg p-6 cursor-pointer hover:shadow-lg transition" data-theme="christmas">
                            <div class="text-center mb-4">
                                <div class="text-5xl mb-3">🎄</div>
                                <h3 class="text-xl font-bold" style="color: #c41e3a;">Fêtes de fin d'année</h3>
                                <p class="text-sm text-gray-600 mt-2">Ambiance festive de Noël avec flocons de neige animés</p>
                                <div class="mt-3 flex justify-center gap-2">
                                    <span class="inline-block w-8 h-8 rounded-full" style="background: #c41e3a;"></span>
                                    <span class="inline-block w-8 h-8 rounded-full" style="background: #165b33;"></span>
                                    <span class="inline-block w-8 h-8 rounded-full" style="background: #d4af37;"></span>
                                </div>
                            </div>
                            <button type="button" class="btn-theme w-full bg-red-600 hover:bg-red-700 text-white" data-theme="christmas">
                                <i class="fas fa-snowflake mr-2"></i>Activer Noël
                            </button>
                        </div>
                        
                        <!-- Thème Indépendance -->
                        <div class="theme-card border-2 rounded-lg p-6 cursor-pointer hover:shadow-lg transition" data-theme="independence">
                            <div class="text-center mb-4">
                                <div class="text-5xl mb-3">🇹🇬</div>
                                <h3 class="text-xl font-bold" style="color: #006a4e;">Indépendance du Togo</h3>
                                <p class="text-sm text-gray-600 mt-2">Célébrons la fierté nationale togolaise (27 avril)</p>
                                <div class="mt-3 flex justify-center gap-2">
                                    <span class="inline-block w-8 h-8 rounded-full" style="background: #006a4e;"></span>
                                    <span class="inline-block w-8 h-8 rounded-full" style="background: #ffcc00;"></span>
                                    <span class="inline-block w-8 h-8 rounded-full" style="background: #d21034;"></span>
                                </div>
                            </div>
                            <button type="button" class="btn-theme w-full text-white" style="background: linear-gradient(135deg, #006a4e, #d21034);" data-theme="independence">
                                <i class="fas fa-flag mr-2"></i>Activer Indépendance
                            </button>
                        </div>
                    </div>
                    
                    <div id="theme-status" class="mt-6 p-4 rounded-lg hidden">
                        <p class="text-center font-semibold"></p>
                    </div>
                </div>
            </section>
            
            <!-- Règlement -->
            <section id="regulations" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-book text-blue-600 mr-2"></i>Gestion du règlement</h2>
            
                    <!-- Add Regulation Article -->
                    <h3 class="text-xl font-semibold mb-4">Ajouter un article</h3>
                    <form method="POST" class="mb-8">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <input type="number" name="article_number" placeholder="Numéro d'article" class="form-input" min="1" required>
                            <input type="text" name="title" placeholder="Titre de l'article" class="form-input" required>
                            <textarea name="content" placeholder="Contenu de l'article (HTML autorisé)" class="form-input" rows="6" required></textarea>
                            <input type="number" name="order_index" placeholder="Ordre d'affichage" class="form-input" min="0" required>
                        </div>
                        <button type="submit" name="add_regulation" class="btn-primary">Ajouter l'article</button>
                    </form>
                    
                    <!-- Update Regulation Footer -->
                    <h3 class="text-xl font-semibold mb-4">Mettre à jour le pied de page</h3>
                    <form method="POST" class="mb-8">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-4">
                            <textarea name="footer_content" class="form-input" rows="4" required><?php echo htmlspecialchars($regulation_footer); ?></textarea>
                        </div>
                        <button type="submit" name="update_regulation_footer" class="btn-primary">Mettre à jour le pied de page</button>
                    </form>
                    
                    <!-- List Regulation Articles -->
                    <h3 class="text-xl font-semibold mb-4">Articles existants</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Numéro</th>
                                    <th class="p-3">Titre</th>
                                    <th class="p-3">Contenu</th>
                                    <th class="p-3">Ordre</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($regulations as $regulation): ?>
                                    <tr class="border-b">
                                        <td class="p-3"><?php echo htmlspecialchars($regulation['article_number']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($regulation['title']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(substr(strip_tags($regulation['content']), 0, 50)) . (strlen($regulation['content']) > 50 ? '...' : ''); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($regulation['order_index']); ?></td>
                                        <td class="p-3">
                                            <button onclick="openEditRegulationModal(<?php echo $regulation['id']; ?>)" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="regulation_id" value="<?php echo $regulation['id']; ?>">
                                                <button type="submit" name="delete_regulation" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer cet article ?');"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Objectifs -->
            <section id="objectifs" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-bullseye text-blue-600 mr-2"></i>
                            Gestion des Objectifs et Valeurs
                        </h2>
                        <p class="text-gray-600 mt-2">Définissez la mission, les objectifs stratégiques et les valeurs fondamentales de votre association</p>
                    </div>

                    <!-- Mission -->
                    <div class="mb-8">
                        <h3 class="text-xl font-semibold mb-4 text-blue-700">
                            <i class="fas fa-flag mr-2"></i>
                            Mission de l'Association
                        </h3>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="form-group">
                                <label class="form-label">Description de la mission *</label>
                                <textarea name="mission_content" id="mission_content" class="form-input form-textarea" rows="6" required><?php echo htmlspecialchars($mission_content); ?></textarea>
                                <p class="text-sm text-gray-500 mt-1">Décrivez la mission principale de votre association</p>
                            </div>
                            <button type="submit" name="update_mission" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                Mettre à jour la mission
                            </button>
                        </form>
                    </div>

                    <!-- Objectifs Stratégiques -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-blue-700">
                                <i class="fas fa-network-wired mr-2"></i>
                                Objectifs Stratégiques
                            </h3>
                            <span class="badge badge-info"><?php echo count($objectifs); ?> objectif(s)</span>
                        </div>

                        <!-- Formulaire d'ajout -->
                        <div class="bg-blue-50 p-4 rounded-lg mb-6">
                            <h4 class="font-semibold text-blue-800 mb-3">
                                <i class="fas fa-plus-circle mr-2"></i>
                                Ajouter un objectif stratégique
                            </h4>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="form-label">Titre *</label>
                                        <input type="text" name="title" class="form-input" placeholder="Ex: Renforcer le réseau alumni" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Icône Font Awesome *</label>
                                        <input type="text" name="icon" class="form-input" placeholder="Ex: fas fa-network-wired" required>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <a href="https://fontawesome.com/icons" target="_blank" class="text-blue-600 hover:underline">
                                                Voir les icônes disponibles
                                            </a>
                                        </p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label">Description *</label>
                                        <textarea name="description" class="form-input form-textarea" rows="3" placeholder="Décrivez cet objectif en détail..." required></textarea>
                                    </div>
                                    <div>
                                        <label class="form-label">Ordre d'affichage</label>
                                        <input type="number" name="order_index" class="form-input" value="0" min="0" required>
                                        <p class="text-sm text-gray-500 mt-1">Détermine l'ordre d'apparition (plus petit = premier)</p>
                                    </div>
                                </div>
                                <button type="submit" name="add_objectif" class="btn btn-primary">
                                    <i class="fas fa-plus mr-2"></i>
                                    Ajouter l'objectif
                                </button>
                            </form>
                        </div>

                        <!-- Liste des objectifs -->
                        <?php if (!empty($objectifs)): ?>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ordre</th>
                                            <th>Icône</th>
                                            <th>Titre</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($objectifs as $objectif): ?>
                                            <tr>
                                                <td class="font-semibold text-center"><?php echo htmlspecialchars($objectif['order_index']); ?></td>
                                                <td class="text-center">
                                                    <i class="<?php echo htmlspecialchars($objectif['icon']); ?> text-blue-600 text-lg"></i>
                                                </td>
                                                <td class="font-medium"><?php echo htmlspecialchars($objectif['title']); ?></td>
                                                <td>
                                                    <div class="max-w-xs truncate" title="<?php echo htmlspecialchars(strip_tags($objectif['description'])); ?>">
                                                        <?php echo htmlspecialchars(substr(strip_tags($objectif['description']), 0, 80)); ?>
                                                        <?php if (strlen(strip_tags($objectif['description'])) > 80): ?>...<?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex space-x-2">
                                                        <button onclick="openEditObjectifModal(
                                                            <?php echo $objectif['id']; ?>,
                                                            '<?php echo htmlspecialchars($objectif['title'], ENT_QUOTES); ?>',
                                                            `<?php echo htmlspecialchars(str_replace('`', '\`', $objectif['description']), ENT_QUOTES); ?>`,
                                                            '<?php echo htmlspecialchars($objectif['icon'], ENT_QUOTES); ?>',
                                                            <?php echo $objectif['order_index']; ?>
                                                        )" class="action-icon action-edit" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                            <input type="hidden" name="id" value="<?php echo $objectif['id']; ?>">
                                                            <button type="submit" name="delete_objectif" class="action-icon action-delete" title="Supprimer" onclick="return confirm('Voulez-vous vraiment supprimer cet objectif ?');">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-network-wired text-4xl mb-4 text-gray-300"></i>
                                <p>Aucun objectif stratégique défini pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Valeurs Fondamentales -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-blue-700">
                                <i class="fas fa-heart mr-2"></i>
                                Valeurs Fondamentales
                            </h3>
                            <span class="badge badge-info"><?php echo count($values); ?> valeur(s)</span>
                        </div>

                        <!-- Formulaire d'ajout -->
                        <div class="bg-purple-50 p-4 rounded-lg mb-6">
                            <h4 class="font-semibold text-purple-800 mb-3">
                                <i class="fas fa-plus-circle mr-2"></i>
                                Ajouter une valeur fondamentale
                            </h4>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="form-label">Titre *</label>
                                        <input type="text" name="title" class="form-input" placeholder="Ex: Solidarité" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Icône Font Awesome *</label>
                                        <input type="text" name="icon" class="form-input" placeholder="Ex: fas fa-hands-helping" required>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <a href="https://fontawesome.com/icons" target="_blank" class="text-purple-600 hover:underline">
                                                Voir les icônes disponibles
                                            </a>
                                        </p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label">Description *</label>
                                        <textarea name="description" class="form-input form-textarea" rows="3" placeholder="Expliquez cette valeur..." required></textarea>
                                    </div>
                                    <div>
                                        <label class="form-label">Ordre d'affichage</label>
                                        <input type="number" name="order_index" class="form-input" value="0" min="0" required>
                                        <p class="text-sm text-gray-500 mt-1">Détermine l'ordre d'apparition (plus petit = premier)</p>
                                    </div>
                                </div>
                                <button type="submit" name="add_value" class="btn btn-primary bg-purple-600 hover:bg-purple-700">
                                    <i class="fas fa-plus mr-2"></i>
                                    Ajouter la valeur
                                </button>
                            </form>
                        </div>

                        <!-- Liste des valeurs -->
                        <?php if (!empty($values)): ?>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ordre</th>
                                            <th>Icône</th>
                                            <th>Titre</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($values as $value): ?>
                                            <tr>
                                                <td class="font-semibold text-center"><?php echo htmlspecialchars($value['order_index']); ?></td>
                                                <td class="text-center">
                                                    <i class="<?php echo htmlspecialchars($value['icon']); ?> text-purple-600 text-lg"></i>
                                                </td>
                                                <td class="font-medium"><?php echo htmlspecialchars($value['title']); ?></td>
                                                <td>
                                                    <div class="max-w-xs truncate" title="<?php echo htmlspecialchars(strip_tags($value['description'])); ?>">
                                                        <?php echo htmlspecialchars(substr(strip_tags($value['description']), 0, 80)); ?>
                                                        <?php if (strlen(strip_tags($value['description'])) > 80): ?>...<?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex space-x-2">
                                                        <button onclick="openEditValueModal(
                                                            <?php echo $value['id']; ?>,
                                                            '<?php echo htmlspecialchars($value['title'], ENT_QUOTES); ?>',
                                                            `<?php echo htmlspecialchars(str_replace('`', '\`', $value['description']), ENT_QUOTES); ?>`,
                                                            '<?php echo htmlspecialchars($value['icon'], ENT_QUOTES); ?>',
                                                            <?php echo $value['order_index']; ?>
                                                        )" class="action-icon action-edit" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                            <input type="hidden" name="id" value="<?php echo $value['id']; ?>">
                                                            <button type="submit" name="delete_value" class="action-icon action-delete" title="Supprimer" onclick="return confirm('Voulez-vous vraiment supprimer cette valeur ?');">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-heart text-4xl mb-4 text-gray-300"></i>
                                <p>Aucune valeur fondamentale définie pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Élections -->
            <section id="elections" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-vote-yea text-blue-600 mr-2"></i>Gestion des élections</h2>
                    <form method="POST" class="mb-8">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label for="election_title" class="block text-sm font-medium mb-1">Titre de l'élection</label>
                                <input type="text" id="election_title" name="election_title" placeholder="Ex: Élection du bureau 2025" class="form-input" required>
                            </div>
                            <div>
                                <label for="campaign_start" class="block text-sm font-medium mb-1">Début de la campagne (facultatif)</label>
                                <input type="datetime-local" id="campaign_start" name="campaign_start" placeholder="Date de début de la campagne" class="form-input">
                            </div>
                            <div>
                                <label for="vote_start" class="block text-sm font-medium mb-1">Début du vote</label>
                                <input type="datetime-local" id="vote_start" name="vote_start" placeholder="Date de début du vote" class="form-input" required>
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium mb-1">Fin du vote</label>
                                <input type="datetime-local" id="end_date" name="end_date" placeholder="Date de fin du vote" class="form-input" required>
                            </div>
                            <div>
                                <label for="results_date" class="block text-sm font-medium mb-1">Publication des résultats</label>
                                <input type="datetime-local" id="results_date" name="results_date" placeholder="Date de publication des résultats" class="form-input" required>
                            </div>
                        </div>
                        <button type="submit" name="create_election" class="btn-primary">Créer une élection</button>
                    </form>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Titre</th>
                                    <th class="p-3">Début</th>
                                    <th class="p-3">Fin</th>
                                    <th class="p-3">Statut</th>
                                    <th class="p-3">Résultats</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($elections as $election): ?>
                                    <tr class="border-b">
                                        <td class="p-3"><?php echo htmlspecialchars($election['title']); ?></td>
                                        <td class="p-3"><?php echo date('d/m/Y H:i', strtotime($election['start_date'])); ?></td>
                                        <td class="p-3"><?php echo date('d/m/Y H:i', strtotime($election['end_date'])); ?></td>
                                        <td class="p-3">
                                            <?php 
                                            $status_classes = [
                                                'pending' => 'bg-gray-500',
                                                'campaign' => 'bg-purple-600',
                                                'voting' => 'bg-green-600',
                                                'processing' => 'bg-yellow-600',
                                                'completed' => 'bg-blue-600'
                                            ];
                                            ?>
                                            <span class="<?php echo $status_classes[$election['election_status']]; ?> text-white px-2 py-1 rounded text-xs">
                                                <?php echo $election['election_status']; ?>
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($election['election_status'] === 'completed' || $election['election_status'] === 'processing'): ?>
                                                <?php if ($election['results_published']): ?>
                                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Publiés</span>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                                        <button type="submit" name="unpublish_results" class="text-red-600 hover:text-red-800 ml-2" 
                                                                onclick="return confirm('Masquer les résultats ?');">
                                                            <i class="fas fa-eye-slash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Masqués</span>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                                        <button type="submit" name="publish_results" class="text-green-600 hover:text-green-800 ml-2"
                                                                onclick="return confirm('Publier les résultats ?');">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-gray-500">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                                <button type="submit" name="delete_election" class="text-red-600 hover:text-red-800" 
                                                        onclick="return confirm('Supprimer cette élection ?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Candidats -->
            <section id="candidates" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-user-tie text-blue-600 mr-2"></i>Gestion des candidats</h2>
                    <form method="POST" enctype="multipart/form-data" class="mb-8">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <select name="election_id" class="form-input" required>
                                <option value="">Sélectionner une élection</option>
                                <?php foreach ($elections as $election): ?>
                                    <option value="<?php echo $election['id']; ?>"><?php echo htmlspecialchars($election['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="user_id" class="form-input" required>
                                <option value="">Sélectionner un utilisateur</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="position" placeholder="Poste (ex: Président)" class="form-input" required>
                            <textarea name="description" placeholder="Description du candidat" class="form-input" rows="4"></textarea>
                            <input type="file" name="candidate_video" placeholder="form-input" accept="video/*">
                            <input type="file" name="profile_picture" class="form-input" accept=".jpg,.jpeg,.png">
                        </div>
                        <button type="submit" name="add_candidate" class="btn-primary">Ajouter un candidat</button>
                    </form>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3">Élection</th>
                                    <th class="p-3">Nom</th>
                                    <th class="p-3">Poste</th>
                                    <th class="p-3">Description</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr class="border-b">
                                        <td class="p-3"><?php echo htmlspecialchars($candidate['election_title']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($candidate['full_name']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($candidate['position']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(substr($candidate['description'] ?? '', 0, 50)) . (strlen($candidate['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                        <td class="p-3">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                <button type="submit" name="delete_candidate" class="text-red-600 hover:text-red-800" onclick="return confirm('Voulez-vous vraiment supprimer ce candidat ?');"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Résultats -->
            <section id="results" class="section">
                <div class="card">
                    <h2 class="text-2xl font-bold mb-6"><i class="fas fa-chart-bar text-blue-600 mr-2"></i>Résultats et Suivi des Élections</h2>
                    
                    <?php foreach ($elections as $election): ?>
                        <div class="mb-8 border rounded-lg p-6 bg-white shadow-sm">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($election['title']); ?></h3>
                                <div class="text-right">
                                    <div class="text-sm text-gray-600">Statut: 
                                        <span class="font-medium <?php 
                                            echo $election['election_status'] === 'voting' ? 'text-green-600' : 
                                                ($election['election_status'] === 'completed' ? 'text-blue-600' : 'text-gray-600'); 
                                        ?>">
                                            <?php echo $election['election_status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Suivi en temps réel de la participation -->
                            <?php if (isset($election_stats[$election['id']])): 
                                $stats = $election_stats[$election['id']];
                            ?>
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg mb-4">
                                    <h4 class="font-semibold text-lg mb-4 text-gray-800"><i class="fas fa-users mr-2"></i>Progression des votes en temps réel</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center mb-4">
                                        <div class="bg-white p-4 rounded-lg shadow">
                                            <div class="text-3xl font-bold text-blue-600"><?php echo $stats['total_users']; ?></div>
                                            <div class="text-sm text-gray-600 mt-1">Électeurs inscrits</div>
                                        </div>
                                        <div class="bg-white p-4 rounded-lg shadow">
                                            <div class="text-3xl font-bold text-green-600"><?php echo $stats['voters']; ?></div>
                                            <div class="text-sm text-gray-600 mt-1">Ont voté</div>
                                        </div>
                                        <div class="bg-white p-4 rounded-lg shadow">
                                            <div class="text-3xl font-bold text-orange-600"><?php echo $stats['remaining_voters']; ?></div>
                                            <div class="text-sm text-gray-600 mt-1">Restants</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Taux de participation -->
                                    <div class="bg-white p-4 rounded-lg shadow">
                                        <div class="flex justify-between text-sm mb-2">
                                            <span class="font-medium text-gray-700">Taux de participation</span>
                                            <span class="font-bold text-blue-600"><?php echo $stats['participation_rate']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-4">
                                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-4 rounded-full transition-all duration-500" 
                                                 style="width: <?php echo $stats['participation_rate']; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Résultats finaux et bouton de publication -->
                            <?php if (date('Y-m-d H:i:s') > $election['end_date']): ?>
                                <?php 
                                    $electionResults = $results[$election['id']] ?? [];
                                    $hasResults = !empty($electionResults);
                                    $resultsPublished = (bool)$election['results_published'];
                                ?>
                                
                                <!-- Boutons de publication -->
                                <div class="flex space-x-3 mb-6">
                                    <?php if ($resultsPublished): ?>
                                        <form method="POST" class="inline" onsubmit="setTimeout(function(){ location.href='admin.php?v=<?php echo time(); ?>#results'; }, 500);">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                            <button type="submit" name="unpublish_results" class="btn btn-warning flex items-center">
                                                <i class="fas fa-eye-slash mr-2"></i>Masquer les résultats
                                            </button>
                                        </form>
                                        <span class="bg-green-100 text-green-800 px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                            <i class="fas fa-check-circle mr-2"></i>Résultats publiés côté électeurs
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" class="inline" onsubmit="setTimeout(function(){ location.href='admin.php?v=<?php echo time(); ?>#results'; }, 500);">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                            <button type="submit" name="publish_results" class="btn btn-success flex items-center">
                                                <i class="fas fa-eye mr-2"></i>Publier les résultats
                                            </button>
                                        </form>
                                        <span class="bg-red-100 text-red-800 px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                            <i class="fas fa-times-circle mr-2"></i>Résultats masqués côté électeurs
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Affichage des résultats finaux -->
                                <?php if ($hasResults): ?>
                                    <div class="bg-white rounded-lg border border-gray-200">
                                        <div class="p-4 bg-gray-50 border-b border-gray-200">
                                            <h4 class="text-lg font-semibold text-gray-800"><i class="fas fa-trophy mr-2 text-yellow-500"></i>Résultats finaux par poste</h4>
                                        </div>
                                        <div class="p-6">
                                            <?php foreach ($electionResults as $position => $position_results): ?>
                                                <div class="mb-6 last:mb-0">
                                                    <h5 class="text-md font-semibold text-gray-700 mb-3 pb-2 border-b"><?php echo htmlspecialchars($position); ?></h5>
                                                    <div class="space-y-2">
                                                        <?php foreach ($position_results as $index => $result): ?>
                                                            <div class="flex items-center justify-between p-3 rounded-lg <?php echo $index === 0 && !$result['is_blank'] ? 'bg-green-50 border-2 border-green-200' : 'bg-gray-50'; ?>">
                                                                <div class="flex items-center space-x-3">
                                                                    <?php if ($index === 0 && !$result['is_blank']): ?>
                                                                        <i class="fas fa-crown text-yellow-500 text-lg"></i>
                                                                    <?php endif; ?>
                                                                    <?php if (!$result['is_blank']): ?>
                                                                        <img src="<?php echo getProfilePicture($result['profile_picture'] ?? ''); ?>" 
                                                                             alt="Candidat" class="h-10 w-10 rounded-full object-cover border-2 <?php echo $index === 0 ? 'border-yellow-400' : 'border-gray-300'; ?>">
                                                                    <?php else: ?>
                                                                        <div class="rounded-full h-10 w-10 border-2 border-gray-300 flex items-center justify-center text-gray-400">
                                                                            <i class="fas fa-ban"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <span class="font-medium <?php echo $index === 0 && !$result['is_blank'] ? 'text-green-700' : 'text-gray-700'; ?>">
                                                                            <?php echo htmlspecialchars($result['name']); ?>
                                                                        </span>
                                                                        <?php if ($index === 0 && !$result['is_blank']): ?>
                                                                            <span class="ml-2 bg-green-600 text-white px-2 py-0.5 rounded text-xs font-medium">Élu(e)</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center space-x-4">
                                                                    <div class="text-right">
                                                                        <div class="text-sm font-bold text-blue-600"><?php echo $result['percentage']; ?>%</div>
                                                                        <div class="text-xs text-gray-500"><?php echo $result['votes']; ?> vote<?php echo $result['votes'] > 1 ? 's' : ''; ?></div>
                                                                    </div>
                                                                    <div class="w-20 bg-gray-200 rounded-full h-2">
                                                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $result['percentage']; ?>%"></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-8 text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                        <p>Aucun vote enregistré pour cette élection.</p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-yellow-500 mr-3 text-xl"></i>
                                        <div>
                                            <p class="font-medium text-yellow-800">Élection en cours</p>
                                            <p class="text-sm text-yellow-600">Les résultats finaux seront disponibles après la fin du vote le <?php echo date('d/m/Y à H:i', strtotime($election['end_date'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($elections)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-vote-yea text-5xl mb-4"></i>
                            <p class="text-lg">Aucune élection créée pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Modals pour l'édition -->
    <!-- Modal d'édition d'utilisateur -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Modifier l'utilisateur</h3>
                <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label class="form-label">Nom complet</label>
                    <input type="text" name="full_name" id="edit_full_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="edit_email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="birth_date" id="edit_birth_date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Année Bac</label>
                    <input type="number" name="bac_year" id="edit_bac_year" class="form-input" min="1900" max="<?php echo (int)date('Y'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Études</label>
                    <input type="text" name="studies" id="edit_studies" class="form-input">
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('editUserModal')" class="btn btn-secondary">Annuler</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div id="editEventModal" class="modal">
        <div class="modal-content">
            <h3 class="text-xl font-bold mb-4">Modifier l'événement</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="event_id" id="edit_event_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Titre *</label>
                    <input type="text" name="title" id="edit_event_title" class="form-input" required maxlength="255">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Description *</label>
                    <textarea name="description" id="edit_event_description" class="form-input" rows="3" required></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Date et heure *</label>
                        <input type="datetime-local" name="event_date" id="edit_event_date" class="form-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Lieu *</label>
                        <input type="text" name="location" id="edit_event_location" class="form-input" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Image actuelle</label>
                    <div id="current_event_image" class="mb-2"></div>
                    <input type="file" name="image" class="form-input" accept=".jpg,.jpeg,.png">
                    <p class="text-sm text-gray-500 mt-1">Laisser vide pour conserver l'image actuelle</p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditEventModal()" class="btn-primary bg-gray-300 hover:bg-gray-400 text-gray-800">Annuler</button>
                    <button type="submit" name="edit_event" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit News Modal -->
    <div id="editNewsModal" class="modal">
        <div class="modal-content">
            <h3 class="text-xl font-bold mb-4">Modifier l'actualité</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="news_id" id="edit_news_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Titre *</label>
                    <input type="text" name="title" id="edit_title" class="form-input" required maxlength="255">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Extrait *</label>
                    <textarea name="excerpt" id="edit_excerpt" class="form-input" rows="3" required maxlength="500"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Contenu *</label>
                    <textarea name="content" id="edit_content" class="form-input" rows="6" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Image actuelle</label>
                    <div id="current_image" class="mb-2"></div>
                    <input type="file" name="image" class="form-input" accept=".jpg,.jpeg,.png">
                    <p class="text-sm text-gray-500 mt-1">Laisser vide pour conserver l'image actuelle</p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Ordre d'affichage</label>
                    <input type="number" name="order_index" id="edit_order_index" class="form-input" min="0">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditNewsModal()" class="btn-primary bg-gray-300 hover:bg-gray-400 text-gray-800">Annuler</button>
                    <button type="submit" name="edit_news" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Regulation Modal -->
    <div id="editRegulationModal" class="modal">
        <div class="modal-content">
            <h3 class="text-xl font-bold mb-4">Modifier l'article</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="regulation_id" id="edit_regulation_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Numéro d'article</label>
                    <input type="number" name="article_number" id="edit_article_number" class="form-input" min="1" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Titre</label>
                    <input type="text" name="title" id="edit_title" class="form-input" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Contenu (HTML autorisé)</label>
                    <textarea name="content" id="edit_content" class="form-input" rows="6" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Ordre d'affichage</label>
                    <input type="number" name="order_index" id="edit_order_index" class="form-input" min="0" required>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditRegulationModal()" class="btn-primary bg-gray-300 hover:bg-gray-400 text-gray-800">Annuler</button>
                    <button type="submit" name="edit_regulation" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Objective Modal - Version améliorée -->
    <div id="editObjectifModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit text-blue-600 mr-2"></i>
                    Modifier l'Objectif Stratégique
                </h3>
                <button class="modal-close" onclick="closeEditObjectifModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="id" id="edit_objectif_id">
                
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" id="edit_objectif_title" class="form-input" placeholder="Ex: Renforcer le réseau alumni" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea name="description" id="edit_objectif_description" class="form-input form-textarea" rows="4" placeholder="Décrivez cet objectif en détail..." required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Icône Font Awesome *</label>
                            <input type="text" name="icon" id="edit_objectif_icon" class="form-input" placeholder="Ex: fas fa-network-wired" required>
                            <p class="text-sm text-gray-500 mt-1">
                                <a href="https://fontawesome.com/icons" target="_blank" class="text-blue-600 hover:underline">
                                    Voir les icônes disponibles
                                </a>
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ordre d'affichage *</label>
                            <input type="number" name="order_index" id="edit_objectif_order" class="form-input" min="0" required>
                            <p class="text-sm text-gray-500 mt-1">Plus petit = premier</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" onclick="closeEditObjectifModal()" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i>
                        Annuler
                    </button>
                    <button type="submit" name="edit_objectif" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Value Modal - Version améliorée -->
    <div id="editValueModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit text-purple-600 mr-2"></i>
                    Modifier la Valeur Fondamentale
                </h3>
                <button class="modal-close" onclick="closeEditValueModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="id" id="edit_value_id">
                
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" id="edit_value_title" class="form-input" placeholder="Ex: Solidarité" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea name="description" id="edit_value_description" class="form-input form-textarea" rows="4" placeholder="Expliquez cette valeur..." required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Icône Font Awesome *</label>
                            <input type="text" name="icon" id="edit_value_icon" class="form-input" placeholder="Ex: fas fa-hands-helping" required>
                            <p class="text-sm text-gray-500 mt-1">
                                <a href="https://fontawesome.com/icons" target="_blank" class="text-purple-600 hover:underline">
                                    Voir les icônes disponibles
                                </a>
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ordre d'affichage *</label>
                            <input type="number" name="order_index" id="edit_value_order" class="form-input" min="0" required>
                            <p class="text-sm text-gray-500 mt-1">Plus petit = premier</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" onclick="closeEditValueModal()" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i>
                        Annuler
                    </button>
                    <button type="submit" name="edit_value" class="btn btn-primary bg-purple-600 hover:bg-purple-700">
                        <i class="fas fa-save mr-2"></i>
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal pour afficher les messages complets -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Détail du message</h3>
                <button class="modal-close" onclick="closeModal('messageModal')">&times;</button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="form-label font-semibold">Expéditeur</label>
                    <p id="messageSender" class="form-input bg-gray-50"></p>
                </div>
                <div>
                    <label class="form-label font-semibold">Email</label>
                    <p id="messageEmail" class="form-input bg-gray-50"></p>
                </div>
                <div>
                    <label class="form-label font-semibold">Sujet</label>
                    <p id="messageSubject" class="form-input bg-gray-50"></p>
                </div>
                <div>
                    <label class="form-label font-semibold">Date d'envoi</label>
                    <p id="messageDate" class="form-input bg-gray-50"></p>
                </div>
                <div>
                    <label class="form-label font-semibold">Message</label>
                    <div id="messageContent" class="form-input bg-gray-50 min-h-32 whitespace-pre-wrap"></div>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" onclick="closeModal('messageModal')" class="btn btn-secondary">Fermer</button>
            </div>
        </div>
    </div>

    <script>
        // Initialisation de CKEditor 5 (éditeur WYSIWYG gratuit et open source)
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser CKEditor pour tous les éditeurs de contenu
            const editors = document.querySelectorAll('textarea[name="content"], textarea#edit_content');
            
            editors.forEach(textarea => {
                if (textarea) {
                    ClassicEditor
                        .create(textarea, {
                            toolbar: {
                                items: [
                                    'undo', 'redo',
                                    '|', 'heading',
                                    '|', 'bold', 'italic',
                                    '|', 'bulletedList', 'numberedList',
                                    '|', 'link', 'insertTable',
                                    '|', 'sourceEditing'
                                ]
                            },
                            language: 'fr',
                            // Configuration pour convertir automatiquement le texte en HTML
                            typing: {
                                transformations: {
                                    include: [
                                        'quotes',
                                        'typography'
                                    ]
                                }
                            },
                            // Simplifier l'interface
                            removePlugins: ['MediaEmbedToolbar'],
                            heading: {
                                options: [
                                    { model: 'paragraph', title: 'Paragraphe', class: 'ck-heading_paragraph' },
                                    { model: 'heading2', view: 'h2', title: 'Titre 2', class: 'ck-heading_heading2' },
                                    { model: 'heading3', view: 'h3', title: 'Titre 3', class: 'ck-heading_heading3' }
                                ]
                            }
                        })
                        .then(editor => {
                            // Stocker l'instance pour une utilisation ultérieure si nécessaire
                            textarea.ckeditorInstance = editor;
                            
                            // Synchroniser avec le textarea lors de la soumission du formulaire
                            const form = textarea.closest('form');
                            if (form) {
                                form.addEventListener('submit', function() {
                                    textarea.value = editor.getData();
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de l\'initialisation de CKEditor:', error);
                        });
                }
            });
        });

        // Navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation sidebar
            const navItems = document.querySelectorAll('.nav-item');
            const sections = document.querySelectorAll('.section');
            const contentTitle = document.getElementById('contentTitle');
            
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const target = this.getAttribute('data-target');
                    
                    // Mettre à jour la navigation
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Afficher la section correspondante
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === target) {
                            section.classList.add('active');
                        }
                    });
                    
                    // Mettre à jour le titre
                    contentTitle.textContent = this.querySelector('span').textContent;
                    
                    // Fermer le menu mobile si ouvert
                    if (window.innerWidth <= 1024) {
                        document.getElementById('sidebar').classList.remove('active');
                    }
                });
            });
            
            // Bouton menu mobile
            document.getElementById('mobileMenuBtn').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
            
            // Navigation par onglets
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    const parent = this.closest('.card');
                    
                    // Mettre à jour les onglets
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Mettre à jour le contenu
                    const tabContents = parent.querySelectorAll('.tab-content');
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                        if (content.id === tabId) {
                            content.classList.add('active');
                        }
                    });
                });
            });
            
            // Fermer les modals en cliquant à l'extérieur
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            });
        });
        
        // Fonctions pour les modals
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function openEditUserModal(userId) {
            const users = <?php echo json_encode($users); ?>;
            const user = users.find(u => u.id == userId);
            if (user) {
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_full_name').value = user.full_name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_birth_date').value = user.birth_date || '';
                document.getElementById('edit_bac_year').value = user.bac_year || '';
                document.getElementById('edit_studies').value = user.studies || '';
                openModal('editUserModal');
            }
        }
        
        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        // Edit news modal
        function openEditNewsModal(newsId) {
            const news = <?php echo json_encode($news); ?>;
            const newsItem = news.find(n => n.id == newsId);
            if (newsItem) {
                document.getElementById('edit_news_id').value = newsItem.id;
                document.getElementById('edit_title').value = newsItem.title;
                document.getElementById('edit_excerpt').value = newsItem.excerpt;
                document.getElementById('edit_content').value = newsItem.content;
                document.getElementById('edit_order_index').value = newsItem.order_index;
                
                // Display current image
                const currentImageDiv = document.getElementById('current_image');
                if (newsItem.image_path) {
                    currentImageDiv.innerHTML = `<img src="${newsItem.image_path}" alt="Image actuelle" class="w-32 h-20 object-cover rounded">`;
                } else {
                    currentImageDiv.innerHTML = '<span class="text-gray-400">Aucune image</span>';
                }
                
                document.getElementById('editNewsModal').style.display = 'flex';
            }
        }

        function closeEditNewsModal() {
            document.getElementById('editNewsModal').style.display = 'none';
        }
        
        // Edit event modal
        function openEditEventModal(eventId) {
            const events = <?php echo json_encode($events); ?>;
            const event = events.find(e => e.id == eventId);
            if (event) {
                document.getElementById('edit_event_id').value = event.id;
                document.getElementById('edit_event_title').value = event.title;
                document.getElementById('edit_event_description').value = event.description;
                document.getElementById('edit_event_date').value = event.event_date.replace(' ', 'T');
                document.getElementById('edit_event_location').value = event.location;
                
                // Display current image
                const currentImageDiv = document.getElementById('current_event_image');
                if (event.image_path) {
                    currentImageDiv.innerHTML = `<img src="${event.image_path}" alt="Image actuelle" class="w-32 h-20 object-cover rounded">`;
                } else {
                    currentImageDiv.innerHTML = '<span class="text-gray-400">Aucune image</span>';
                }
                
                document.getElementById('editEventModal').style.display = 'flex';
            }
        }

        function closeEditEventModal() {
            document.getElementById('editEventModal').style.display = 'none';
        }
        
        // Edit regulation modal
        function openEditRegulationModal(regulationId) {
            const regulations = <?php echo json_encode($regulations); ?>;
            const regulation = regulations.find(r => r.id == regulationId);
            if (regulation) {
                document.getElementById('edit_regulation_id').value = regulation.id;
                document.getElementById('edit_article_number').value = regulation.article_number;
                document.getElementById('edit_title').value = regulation.title;
                document.getElementById('edit_content').value = regulation.content;
                document.getElementById('edit_order_index').value = regulation.order_index;
                document.getElementById('editRegulationModal').style.display = 'flex';
            }
        }
        
        function closeEditRegulationModal() {
            document.getElementById('editRegulationModal').style.display = 'none';
        }
        
        // Fonctions pour les modals d'édition des objectifs et valeurs
        function openEditObjectifModal(id, title, description, icon, order) {
            document.getElementById('edit_objectif_id').value = id;
            document.getElementById('edit_objectif_title').value = title;
            document.getElementById('edit_objectif_description').value = description;
            document.getElementById('edit_objectif_icon').value = icon;
            document.getElementById('edit_objectif_order').value = order;
            openModal('editObjectifModal');
        }

        function closeEditObjectifModal() {
            closeModal('editObjectifModal');
        }

        function openEditValueModal(id, title, description, icon, order) {
            document.getElementById('edit_value_id').value = id;
            document.getElementById('edit_value_title').value = title;
            document.getElementById('edit_value_description').value = description;
            document.getElementById('edit_value_icon').value = icon;
            document.getElementById('edit_value_order').value = order;
            openModal('editValueModal');
        }

        function closeEditValueModal() {
            closeModal('editValueModal');
        }
        
        // Fonction pour actualiser la page
        function refreshPage() {
            location.reload(true); // true force le rechargement depuis le serveur
        }

        // Ajoutez aussi cette fonction pour gérer l'actualisation avec F5
        document.addEventListener('keydown', function(event) {
            if (event.key === 'F5') {
                event.preventDefault();
                refreshPage();
            }
        });

        // Add to window.onclick function
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeEditUserModal();
                closeEditNewsModal();
                closeEditRegulationModal();
                closeEditEventModal();
                closeEditObjectifModal();
                closeEditValueModal();
            }
        };
        // Sauvegarder la section active dans le localStorage
        function saveActiveSection(sectionId) {
            localStorage.setItem('activeAdminSection', sectionId);
        }

        // Restaurer la section active au chargement
        function restoreActiveSection() {
            const activeSection = localStorage.getItem('activeAdminSection') || 'dashboard';
            const navItem = document.querySelector(`[data-target="${activeSection}"]`);
            if (navItem) {
                navItem.click();
            }
        }

        // Modifiez la navigation pour sauvegarder la section active
        document.addEventListener('DOMContentLoaded', function() {
            // ... code existant ...
            
            // Navigation sidebar
            const navItems = document.querySelectorAll('.nav-item');
            const sections = document.querySelectorAll('.section');
            const contentTitle = document.getElementById('contentTitle');
            
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const target = this.getAttribute('data-target');
                    
                    // Mettre à jour la navigation
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Afficher la section correspondante
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === target) {
                            section.classList.add('active');
                        }
                    });
                    
                    // Mettre à jour le titre
                    contentTitle.textContent = this.querySelector('span').textContent;
                    
                    // Sauvegarder la section active
                    saveActiveSection(target);
                    
                    // Fermer le menu mobile si ouvert
                    if (window.innerWidth <= 1024) {
                        document.getElementById('sidebar').classList.remove('active');
                    }
                });
            });
            
            // Restaurer la section active au chargement
            restoreActiveSection();
            
            // Forcer le rechargement si paramètre v présent (évite le cache)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('v')) {
                // Nettoyer l'URL après rechargement
                const cleanUrl = window.location.pathname + window.location.hash;
                window.history.replaceState({}, document.title, cleanUrl);
            }
            
            // Gestion de la rétraction de la sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleIcon = sidebarToggle.querySelector('i');
            
            // Restaurer l'état de la sidebar depuis localStorage
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            }
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                if (sidebar.classList.contains('collapsed')) {
                    toggleIcon.classList.remove('fa-chevron-left');
                    toggleIcon.classList.add('fa-chevron-right');
                    localStorage.setItem('sidebarCollapsed', 'true');
                } else {
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-chevron-left');
                    localStorage.setItem('sidebarCollapsed', 'false');
                }
            });

        });
        // Fonction pour afficher les messages complets
        function openMessageModal(sender, email, subject, content, date) {
            document.getElementById('messageSender').textContent = sender;
            document.getElementById('messageEmail').textContent = email;
            document.getElementById('messageSubject').textContent = subject;
            document.getElementById('messageContent').textContent = content;
            document.getElementById('messageDate').textContent = date;
            openModal('messageModal');
        }

        // Gestion des thèmes festifs
        document.addEventListener('DOMContentLoaded', function() {
            const themeButtons = document.querySelectorAll('.btn-theme');
            const themeCards = document.querySelectorAll('.theme-card');
            const themeStatus = document.getElementById('theme-status');
            
            // Récupérer le thème actuel
            fetch('theme_manager.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_theme'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateThemeUI(data.theme);
                }
            });
            
            // Gérer les clics sur les boutons de thème
            themeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const theme = this.getAttribute('data-theme');
                    activateTheme(theme);
                });
            });
            
            // Gérer les clics sur les cards
            themeCards.forEach(card => {
                card.addEventListener('click', function() {
                    const theme = this.getAttribute('data-theme');
                    activateTheme(theme);
                });
            });
            
            function activateTheme(theme) {
                // Désactiver tous les boutons pendant le traitement
                themeButtons.forEach(btn => btn.disabled = true);
                
                fetch('theme_manager.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=set_theme&theme=${theme}`
                })
                .then(response => {
                    // Vérifier si la réponse est OK
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    // Vérifier si c'est du JSON valide
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            updateThemeUI(data.theme);
                            showThemeMessage(data.message, 'success');
                            
                            // Recharger la page après 2 secondes pour appliquer le thème
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showThemeMessage(data.message || 'Erreur inconnue', 'error');
                            themeButtons.forEach(btn => btn.disabled = false);
                        }
                    } catch (e) {
                        console.error('Réponse reçue:', text);
                        showThemeMessage('Erreur: Réponse invalide du serveur', 'error');
                        themeButtons.forEach(btn => btn.disabled = false);
                    }
                })
                .catch(error => {
                    console.error('Erreur complète:', error);
                    showThemeMessage(`Erreur de connexion: ${error.message}`, 'error');
                    themeButtons.forEach(btn => btn.disabled = false);
                });
            }
            
            function updateThemeUI(activeTheme) {
                themeCards.forEach(card => {
                    const cardTheme = card.getAttribute('data-theme');
                    if (cardTheme === activeTheme) {
                        card.classList.add('border-blue-500', 'bg-blue-50');
                        card.classList.remove('border-gray-200');
                    } else {
                        card.classList.remove('border-blue-500', 'bg-blue-50');
                        card.classList.add('border-gray-200');
                    }
                });
                
                themeButtons.forEach(btn => btn.disabled = false);
            }
            
            function showThemeMessage(message, type) {
                themeStatus.classList.remove('hidden', 'bg-green-100', 'bg-red-100', 'text-green-800', 'text-red-800');
                
                if (type === 'success') {
                    themeStatus.classList.add('bg-green-100', 'text-green-800');
                } else {
                    themeStatus.classList.add('bg-red-100', 'text-red-800');
                }
                
                themeStatus.querySelector('p').innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle mr-2"></i>${message}`;
                themeStatus.classList.remove('hidden');
                
                // Masquer après 5 secondes
                setTimeout(() => {
                    themeStatus.classList.add('hidden');
                }, 5000);
            }
        });
    </script>
</body>
</html>