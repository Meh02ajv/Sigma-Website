<?php
require 'config.php';
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}

$user_email = $_SESSION['user_email'];
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Get current user ID for WebSocket
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$stmt->close();

if (!$current_user) {
    header("Location: connexion.php");
    exit;
}

// Vérifier les anniversaires et envoyer des rappels
checkBirthdayReminders($conn);

// Initial filters and pagination
$bac_year = isset($_GET['bac_year']) ? sanitize($_GET['bac_year']) : '';
$studies = isset($_GET['studies']) ? sanitize($_GET['studies']) : '';
$search_name = isset($_GET['search_name']) ? sanitize($_GET['search_name']) : '';
$profession = isset($_GET['profession']) ? sanitize($_GET['profession']) : '';
$city = isset($_GET['city']) ? sanitize($_GET['city']) : '';
$company = isset($_GET['company']) ? sanitize($_GET['company']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize($_GET['sort_by']) : 'full_name';
$sort_order = isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), ['ASC', 'DESC']) ? strtoupper($_GET['sort_order']) : 'ASC';
$limit = 12;
$offset = 0;

// Validate sort_by to prevent SQL injection
$allowed_sort_columns = ['full_name', 'bac_year'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'full_name';
}

// Build SQL query with birthday check and advanced search
$current_date = date('m-d');
$query = "SELECT id, full_name, email, birth_date, studies, bac_year, profile_picture,
          profession, company, city, country,
          CASE WHEN DATE_FORMAT(birth_date, '%m-%d') = ? THEN 1 ELSE 0 END AS is_birthday 
          FROM users WHERE 1=1";
$params = [$current_date];
$types = 's';

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

if ($city) {
    $query .= " AND city LIKE ?";
    $params[] = "%$city%";
    $types .= 's';
}

if ($company) {
    $query .= " AND company LIKE ?";
    $params[] = "%$company%";
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
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Fonction pour vérifier et envoyer les rappels d'anniversaire
function checkBirthdayReminders($conn) {
    // Date actuelle et date dans 2 jours
    $now = new DateTime();
    $in_two_days = (new DateTime())->add(new DateInterval('P2D'));
    
    // Formater les dates pour la comparaison
    $current_month_day = $now->format('m-d');
    $in_two_days_month_day = $in_two_days->format('m-d');
    
    // Récupérer tous les utilisateurs dont c'est l'anniversaire aujourd'hui ou dans 2 jours
    $query = "SELECT id, full_name, email, birth_date FROM users WHERE 
              DATE_FORMAT(birth_date, '%m-%d') = ? OR DATE_FORMAT(birth_date, '%m-%d') = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $current_month_day, $in_two_days_month_day);
    $stmt->execute();
    $result = $stmt->get_result();
    $birthday_users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Récupérer tous les utilisateurs sauf ceux qui ont leur anniversaire
    $query = "SELECT email FROM users WHERE 
              DATE_FORMAT(birth_date, '%m-%d') NOT IN (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $current_month_day, $in_two_days_month_day);
    $stmt->execute();
    $result = $stmt->get_result();
    $other_users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Envoyer les notifications
    foreach ($birthday_users as $user) {
        $user_month_day = (new DateTime($user['birth_date']))->format('m-d');
        
        if ($user_month_day === $current_month_day) {
            // Anniversaire aujourd'hui
            sendBirthdayNotification($user, $other_users, false);
        } elseif ($user_month_day === $in_two_days_month_day) {
            // Anniversaire dans 2 jours
            sendBirthdayNotification($user, $other_users, true);
        }
    }
}

// Fonction pour envoyer les notifications d'anniversaire
function sendBirthdayNotification($birthday_user, $recipients, $is_reminder) {
    foreach ($recipients as $recipient) {
        $to = $recipient['email'];
        $subject = $is_reminder ? 
            "Rappel: Anniversaire de {$birthday_user['full_name']} dans 2 jours" : 
            "Aujourd'hui c'est l'anniversaire de {$birthday_user['full_name']} !";
        
        $message = $is_reminder ?
            "Bonjour,\n\nDans 2 jours, ce sera l'anniversaire de {$birthday_user['full_name']} !\n\n" .
            "Pensez à lui souhaiter un joyeux anniversaire .\n\n" .
            "L'équipe Yearbook Sigma" :
            "Bonjour,\n\nAujourd'hui c'est l'anniversaire de {$birthday_user['full_name']} !\n\n" .
            "Pensez à lui souhaiter un joyeux anniversaire.\n\n" .
            "L'équipe Yearbook Sigma";
        
        $headers = "From: yearbook@sigma.com\r\n";
        $headers .= "Reply-To: no-reply@sigma.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // TODO: Implémenter l'envoi d'emails via PHPMailer (utiliser SMTP_* défini dans config.php)
        // mail($to, $subject, $message, $headers);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yearbook Sigma</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --birthday-color: #f39c12;
            --unread-color: #3498db;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            height: 40px;
            width: auto;
        }
        
        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
            color: white;
        }
        
        .nav-icons {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .nav-icons a {
            color: white;
            font-size: 1.2rem;
            transition: color 0.3s;
            position: relative;
        }
        
        .nav-icons a:hover {
            color: var(--secondary-color);
        }
        
        .unread-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--unread-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            display: none;
        }
        
        .unread-count.show {
            display: block;
        }
        
        .filters-container {
            background-color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            position: sticky;
            top: 78px;
            z-index: 900;
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            background-color: white;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .clear-btn, .apply-filters-btn {
            background-color: var(--light-color);
            color: var(--dark-color);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            align-self: flex-end;
        }
        
        .apply-filters-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .clear-btn:hover {
            background-color: #e0e0e0;
        }
        
        .apply-filters-btn:hover {
            background-color: #2980b9;
        }
        
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .profile-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            cursor: pointer;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .profile-card.birthday {
            box-shadow: 0 0 0 3px var(--birthday-color);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.7); }
            50% { box-shadow: 0 0 0 8px rgba(243, 156, 18, 0.3); }
            100% { box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.7); }
        }
        
        .profile-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        
        .profile-info {
            padding: 1.5rem;
        }
        
        .profile-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0 0 0.5rem;
            font-size: 1.1rem;
        }
        
        .profile-email {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            word-break: break-all;
        }
        
        .profile-detail {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .profile-detail i {
            margin-right: 0.5rem;
            color: var(--secondary-color);
            width: 20px;
            text-align: center;
        }
        
        .birthday-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: var(--birthday-color);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .profile-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.3s;
            display: flex;
            max-height: 90vh;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-image-container {
            flex: 1;
            background-color: #f1f1f1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .modal-image {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .modal-details {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2rem;
            color: #7f8c8d;
            cursor: pointer;
            transition: color 0.3s;
            background: rgba(255, 255, 255, 0.8);
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
        }
        
        .modal-close:hover {
            color: var(--accent-color);
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
        }
        
        .modal-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            color: var(--dark-color);
            margin: 0 0 0.5rem;
        }
        
        .modal-email {
            color: var(--secondary-color);
            font-size: 1rem;
            margin-bottom: 1.5rem;
            display: block;
        }
        
        .info-section {
            margin-bottom: 2rem;
        }
        
        .info-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2rem;
            color: var(--dark-color);
            margin: 0 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.3rem;
            display: block;
        }
        
        .info-value {
            color: #555;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-family: inherit;
            font-size: 0.9rem;
        }
        
        .contact-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .contact-btn:hover {
            background-color: #2980b9;
        }
        
        .report-btn {
            background-color: var(--light-color);
            color: var(--accent-color);
        }
        
        .report-btn:hover {
            background-color: #e0e0e0;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            grid-column: 1 / -1;
            font-style: italic;
            color: #7f8c8d;
        }
        
        .not-found {
            text-align: center;
            padding: 2rem;
            grid-column: 1 / -1;
            color: #7f8c8d;
        }
        
        .message {
            padding: 1rem;
            margin: 1rem 2rem;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
        }
        
        .success {
            background-color: rgba(39, 174, 96, 0.2);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }
        
        .hidden {
            display: none !important;
        }
        
        /* NOUVEAU : Bouton de fermeture des filtres pour desktop */
        .close-filters-desktop {
            display: none;
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            background: none;
            border: none;
            color: var(--dark-color);
            cursor: pointer;
            z-index: 2001;
        }
        
        /* Mobile interface */
        .mobile-controls {
            display: none;
        }

        .filter-toggle {
            display: none;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem auto;
            width: 90%;
            font-weight: 500;
            cursor: pointer;
        }

        .mobile-search {
            display: none;
        }

        /* Améliorations pour mobile */
        @media (max-width: 768px) {
            .logo-text {
                font-size: 1.2rem;
            }
            
            .nav-icons {
                gap: 1rem;
            }
            
            .filters-container {
                position: fixed;
                top: 0;
                left: -100%;
                width: 85%;
                height: 100vh;
                overflow-y: auto;
                padding: 2rem 1.5rem;
                transition: left 0.3s ease;
                z-index: 2000;
                background-color: white;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .filters-container.active {
                left: 0;
            }
            
            .filters {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .filter-toggle {
                display: block;
            }
            
            .desktop-search {
                display: none;
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 0 1rem;
            }
            
            .profile-card {
                margin-bottom: 0.5rem;
            }
            
            .profile-image {
                height: 200px;
            }
            
            .modal-content {
                width: 95%;
                height: 95%;
                flex-direction: column;
            }
            
            .modal-image-container {
                flex: none;
                height: 40%;
                padding: 1rem;
            }
            
            .modal-image {
                max-height: 100%;
            }
            
            .modal-details {
                flex: none;
                height: 60%;
                overflow-y: auto;
                padding: 1.5rem;
            }
            
            .modal-close {
                top: 0.5rem;
                right: 0.5rem;
                background: rgba(255, 255, 255, 0.9);
            }
            
            .modal-name {
                font-size: 1.5rem;
            }
            
            .modal-actions {
                flex-direction: column;
                position: sticky;
                bottom: 0;
                background: white;
                padding: 1rem 0;
                margin-top: 1rem;
                border-top: 1px solid #eee;
            }
            
            .action-btn {
                width: 100%;
                text-align: center;
            }
            
            /* Overlay pour les filtres */
            .filter-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 1999;
            }
            
            .filter-overlay.active {
                display: block;
            }
            
            /* Bouton de fermeture pour les filtres mobile */
            .close-filters {
                position: absolute;
                top: 1rem;
                right: 1rem;
                font-size: 1.5rem;
                background: none;
                border: none;
                color: var(--dark-color);
                cursor: pointer;
                z-index: 2001;
            }
            
            /* Cacher le bouton de fermeture desktop sur mobile */
            .close-filters-desktop {
                display: none !important;
            }
            
            .birthday-badge {
                top: 0.5rem;
                right: 0.5rem;
                font-size: 0.7rem;
                padding: 0.2rem 0.6rem;
            }
            
            .profile-image {
                height: 160px;
            }
        }

        /* Desktop - Afficher le bouton de fermeture */
        @media (min-width: 769px) {
            .close-filters-desktop {
                display: block;
            }
            
            .close-filters {
                display: none;
            }
        }

        /* Améliorations pour très petits écrans */
        @media (max-width: 480px) {
            header {
                padding: 0.8rem 1rem;
            }
            
            .logo {
                height: 32px;
            }
            
            .nav-icons a {
                font-size: 1rem;
            }
            
            .profile-info {
                padding: 1rem;
            }
            
            .profile-name {
                font-size: 1rem;
            }
            
            .profile-email {
                font-size: 0.8rem;
            }
            
            .profile-detail {
                font-size: 0.85rem;
            }
            
            .modal-image-container {
                height: 35%;
            }
            
            .modal-details {
                height: 65%;
                padding: 1rem;
            }
            
            .modal-name {
                font-size: 1.3rem;
            }
            
            .info-title {
                font-size: 1.1rem;
            }
        }

        /* Animation pour le chargement */
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .profile-card {
            animation: slideIn 0.3s ease;
        }
        
        .quote-section {
            text-align: center;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 1rem 0;
            font-style: italic;
            color: #6c757d;
            border-left: 4px solid var(--secondary-color);
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="img/white_logo.png" alt="Logo Sigma" class="logo">
            <span class="logo-text">Yearbook Sigma</span>
        </div>
        <div class="nav-icons">
            <a href="album.php" aria-label="Album"><i class="fas fa-images"></i></a>
            <a href="messaging.php" aria-label="Messagerie" class="message-icon">
                <i class="fas fa-envelope"></i>
                <span class="unread-count" id="unread-count"></span>
            </a>
            <a href="settings.php" aria-label="Paramètres"><i class="fas fa-cog"></i></a>
            <a href="dashboard.php" aria-label="Déconnexion"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>
    
    <?php if ($success || $error): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($success ?: $error); ?>
        </div>
    <?php endif; ?>
    
    <!-- SUPPRIMÉ : Barre de recherche mobile -->
    
    <!-- Bouton pour ouvrir les filtres sur mobile -->
    <button class="filter-toggle" id="filterToggle">
        <i class="fas fa-filter"></i> Filtres et options de tri
    </button>
    
    <!-- Overlay pour les filtres -->
    <div class="filter-overlay" id="filterOverlay"></div>
    
    <div class="filters-container" id="filtersContainer">
        <!-- Bouton de fermeture pour desktop -->
        <button class="close-filters-desktop" id="closeFiltersDesktop">&times;</button>
        <!-- Bouton de fermeture pour mobile -->
        <button class="close-filters" id="closeFilters">&times;</button>
        
        <div class="filters">
            <!-- Recherche par nom/prénom avec autocomplétion -->
            <div class="filter-group">
                <label for="searchName"><i class="fas fa-search"></i> Rechercher par nom</label>
                <input type="text" id="searchName" placeholder="Nom ou prénom..." value="<?php echo htmlspecialchars($search_name); ?>" autocomplete="off">
                <div id="autocompleteResults" style="display:none; position:absolute; background:white; border:1px solid #ddd; max-height:200px; overflow-y:auto; width:calc(100% - 2rem); z-index:1000;"></div>
            </div>
            
            <div class="filter-group">
                <label for="yearFilter"><i class="fas fa-graduation-cap"></i> Année du BAC</label>
                <select id="yearFilter">
                    <option value="">Toutes les années</option>
                    <?php
                    $stmt = $conn->query("SELECT DISTINCT bac_year FROM users WHERE bac_year IS NOT NULL ORDER BY bac_year DESC");
                    while ($row = $stmt->fetch_assoc()) {
                        $selected = $bac_year == $row['bac_year'] ? 'selected' : '';
                        echo "<option value='{$row['bac_year']}' $selected>{$row['bac_year']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="studyFilter"><i class="fas fa-book"></i> Filière</label>
                <select id="studyFilter">
                    <option value="">Toutes les filières</option>
                    <?php
                    $stmt = $conn->query("SELECT DISTINCT studies FROM users WHERE studies IS NOT NULL ORDER BY studies");
                    while ($row = $stmt->fetch_assoc()) {
                        $selected = $studies == $row['studies'] ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row['studies']) . "' $selected>" . htmlspecialchars($row['studies']) . "</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
            </div>
            
            <!-- Nouveau: Recherche par profession -->
            <div class="filter-group">
                <label for="professionFilter"><i class="fas fa-briefcase"></i> Profession</label>
                <input type="text" id="professionFilter" placeholder="Ex: Ingénieur, Médecin..." value="<?php echo htmlspecialchars($profession); ?>">
            </div>
            
            <!-- Nouveau: Recherche par entreprise -->
            <div class="filter-group">
                <label for="companyFilter"><i class="fas fa-building"></i> Entreprise</label>
                <input type="text" id="companyFilter" placeholder="Ex: Google, Microsoft..." value="<?php echo htmlspecialchars($company); ?>">
            </div>
            
            <!-- Nouveau: Recherche par ville -->
            <div class="filter-group">
                <label for="cityFilter"><i class="fas fa-map-marker-alt"></i> Ville</label>
                <input type="text" id="cityFilter" placeholder="Ex: Paris, Lomé..." value="<?php echo htmlspecialchars($city); ?>">
            </div>
            
            <div class="filter-group">
                <label for="sortBy"><i class="fas fa-sort"></i> Trier par</label>
                <select id="sortBy">
                    <option value="full_name" <?php echo $sort_by === 'full_name' ? 'selected' : ''; ?>>Nom</option>
                    <option value="bac_year" <?php echo $sort_by === 'bac_year' ? 'selected' : ''; ?>>Année du BAC</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sortOrder"><i class="fas fa-arrow-down"></i> Ordre</label>
                <select id="sortOrder">
                    <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                    <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                </select>
            </div>
            
            <button class="clear-btn" id="clearFilters"><i class="fas fa-eraser"></i> Réinitialiser</button>
            <button class="apply-filters-btn" id="applyFilters"><i class="fas fa-check"></i> Appliquer</button>
        </div>
    </div>
    
    <div class="main-content">
        <div class="profile-grid" id="profileGrid">
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $user): ?>
                    <div class="profile-card <?php echo $user['is_birthday'] ? 'birthday' : ''; ?>" 
                         data-id="<?php echo $user['id']; ?>" 
                         data-name="<?php echo htmlspecialchars($user['full_name']); ?>" 
                         data-email="<?php echo htmlspecialchars($user['email']); ?>" 
                         data-birthdate="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>" 
                         data-studies="<?php echo htmlspecialchars($user['studies'] ?? ''); ?>" 
                         data-bacyear="<?php echo $user['bac_year'] ?? ''; ?>"
                         data-profession="<?php echo htmlspecialchars($user['profession'] ?? ''); ?>"
                         data-company="<?php echo htmlspecialchars($user['company'] ?? ''); ?>"
                         data-city="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                         data-country="<?php echo htmlspecialchars($user['country'] ?? ''); ?>"
                         data-interests="<?php echo htmlspecialchars($user['interests'] ?? ''); ?>"
                         data-birthday="<?php echo $user['is_birthday'] ? 'true' : 'false'; ?>"
                         data-image="<?php echo $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : 'img/profile_pic.jpeg'; ?>">
                        <img src="<?php echo $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : 'img/profile_pic.jpeg'; ?>" alt="Photo de profil" class="profile-image">
                        <div class="profile-info">
                            <h3 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                            <div class="profile-detail">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?php echo htmlspecialchars($user['studies'] ?? 'Non spécifié'); ?></span>
                            </div>
                            <div class="profile-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo $user['bac_year'] ?? 'Non spécifié'; ?></span>
                            </div>
                        </div>
                        <?php if ($user['is_birthday']): ?>
                            <span class="birthday-badge">
                                <i class="fas fa-birthday-cake"></i>
                                Anniversaire
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="not-found" id="notFound">
                    <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Aucun utilisateur trouvé avec ces critères</p>
                </div>
            <?php endif; ?>
            <div class="loading hidden" id="loading">
                <p>Intégration sans Dérivation n'est que ruine de l'âme</p>
            </div>
        </div>
    </div>
    
    <!-- Modal pour l'agrandissement du profil -->
    <div class="profile-modal" id="profileModal">
        <div class="modal-content">
            <span class="modal-close" id="modalClose">&times;</span>
            <div class="modal-image-container">
                <img id="modalProfileImage" src="" alt="Photo de profil agrandie" class="modal-image">
            </div>
            <div class="modal-details">
                <div class="modal-header">
                    <h2 class="modal-name" id="modalProfileName"></h2>
                    <span class="modal-email" id="modalProfileEmail"></span>
                </div>
                
                <div class="tab-content active" id="tab-info">
                    <div class="info-section">
                        <h3 class="info-title">Informations académiques</h3>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-graduation-cap"></i> Filière</span>
                            <span class="info-value" id="modalProfileStudies"></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-calendar-alt"></i> Année du BAC</span>
                            <span class="info-value" id="modalProfileBacYear"></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-birthday-cake"></i> Date de naissance</span>
                            <span class="info-value" id="modalProfileBirthdate"></span>
                        </div>
                    </div>
                    
                    <div class="info-section" id="professionalSection">
                        <h3 class="info-title">Informations professionnelles</h3>
                        <div class="info-item" id="professionItem">
                            <span class="info-label"><i class="fas fa-briefcase"></i> Profession</span>
                            <span class="info-value" id="modalProfileProfession"></span>
                        </div>
                        <div class="info-item" id="companyItem">
                            <span class="info-label"><i class="fas fa-building"></i> Entreprise</span>
                            <span class="info-value" id="modalProfileCompany"></span>
                        </div>
                    </div>
                    
                    <div class="info-section" id="locationSection">
                        <h3 class="info-title">Localisation</h3>
                        <div class="info-item" id="locationItem">
                            <span class="info-label"><i class="fas fa-map-marker-alt"></i> Ville</span>
                            <span class="info-value" id="modalProfileLocation"></span>
                        </div>
                    </div>
                    
                    <div class="info-section" id="interestsSection">
                        <h3 class="info-title">Centres d'intérêt</h3>
                        <div class="info-item">
                            <span class="info-value" id="modalProfileInterests" style="font-style: italic; color: #666;"></span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button class="action-btn contact-btn" id="modalProfileContact">Contacter</button>
                    <button class="action-btn report-btn" id="modalProfileReport">Signaler</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Code JavaScript corrigé
        let isLoading = false;
        let page = 1;
        let lastId = <?php echo count($users) > 0 ? max(array_column($users, 'id')) : 0; ?>;
        const currentUserEmail = '<?php echo htmlspecialchars($user_email); ?>';
        const currentUserId = <?php echo $current_user['id']; ?>;
        let socket = null;
        
        const debounce = (func, delay) => {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => func(...args), delay);
            };
        };

        // Fonction pour formater la date de naissance
        function formatBirthdate(dateString) {
            if (!dateString) return 'Non spécifié';
            
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'Non spécifié';
                
                return date.toLocaleDateString('fr-FR', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            } catch (e) {
                return 'Non spécifié';
            }
        }

        // Initialize WebSocket
        function initializeWebSocket() {
            try {
                socket = new WebSocket('ws://localhost:8080');
                
                socket.onopen = () => {
                    console.log('Connected to WebSocket server');
                    socket.send(JSON.stringify({ type: 'set_user_id', user_id: currentUserId }));
                    updateUnreadCount();
                };

                socket.onmessage = (event) => {
                    try {
                        const message = JSON.parse(event.data);
                        if (message.type === 'set_user_id') {
                            console.log('User ID set:', message.user_id);
                            return;
                        }
                        if (message.recipient_id === currentUserId && message.sender_id) {
                            console.log('New message received, updating unread count');
                            updateUnreadCount();
                        }
                    } catch (e) {
                        console.error('Error parsing WebSocket message:', e);
                    }
                };

                socket.onclose = () => {
                    console.log('Disconnected from WebSocket server');
                };

                socket.onerror = (error) => {
                    console.error('WebSocket error:', error);
                };
            } catch (error) {
                console.error('WebSocket initialization failed:', error);
            }
        }

        // Update unread message count
        async function updateUnreadCount() {
            try {
                const response = await fetch('get_unread_counts.php');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const unreadCounts = await response.json();
                if (unreadCounts.error) {
                    throw new Error(unreadCounts.error);
                }
                const totalUnread = Object.values(unreadCounts).reduce((sum, count) => sum + parseInt(count), 0);
                const unreadBadge = document.getElementById('unread-count');
                unreadBadge.textContent = totalUnread;
                unreadBadge.classList.toggle('show', totalUnread > 0);
            } catch (error) {
                console.error('Error updating unread count:', error);
            }
        }

        // Infinite scroll
        window.addEventListener('scroll', debounce(() => {
            const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
            if (scrollTop + clientHeight >= scrollHeight - 100 && !isLoading) {
                loadMoreProfiles();
            }
        }, 200));

        // Load more profiles - avec recherche avancée
        async function loadMoreProfiles() {
            if (isLoading) return;
            isLoading = true;
            const loading = document.getElementById('loading');
            loading.classList.remove('hidden');

            try {
                const searchName = encodeURIComponent(document.getElementById('searchName').value);
                const bacYear = encodeURIComponent(document.getElementById('yearFilter').value);
                const studies = encodeURIComponent(document.getElementById('studyFilter').value);
                const profession = encodeURIComponent(document.getElementById('professionFilter').value);
                const company = encodeURIComponent(document.getElementById('companyFilter').value);
                const city = encodeURIComponent(document.getElementById('cityFilter').value);
                const sortBy = encodeURIComponent(document.getElementById('sortBy').value);
                const sortOrder = encodeURIComponent(document.getElementById('sortOrder').value);
                
                const response = await fetch(`load_more_profiles.php?page=${page}&lastId=${lastId}&search_name=${searchName}&bac_year=${bacYear}&studies=${studies}&profession=${profession}&company=${company}&city=${city}&sort_by=${sortBy}&sort_order=${sortOrder}`);
                const data = await response.json();
                const profileGrid = document.getElementById('profileGrid');
                
                if (data.profiles && data.profiles.length === 0) {
                    loading.innerHTML = '<p>Intégration sans Dérivation n\'est que ruine de l\'âme</p>';
                } else if (data.profiles) {
                    data.profiles.forEach(user => {
                        const card = document.createElement('div');
                        card.className = `profile-card ${user.is_birthday ? 'birthday' : ''}`;
                        card.dataset.id = user.id;
                        card.dataset.name = user.full_name;
                        card.dataset.email = user.email;
                        card.dataset.birthdate = user.birth_date || '';
                        card.dataset.studies = user.studies || 'Non spécifié';
                        card.dataset.bacyear = user.bac_year || 'Non spécifié';
                        card.dataset.profession = user.profession || '';
                        card.dataset.company = user.company || '';
                        card.dataset.city = user.city || '';
                        card.dataset.country = user.country || '';
                        card.dataset.interests = user.interests || '';
                        card.dataset.birthday = user.is_birthday ? 'true' : 'false';
                        card.dataset.image = user.profile_picture || 'img/profile_pic.jpeg';
                        
                        // Construction des détails avec les nouveaux champs
                        let detailsHTML = `
                            <div class="profile-detail">
                                <i class="fas fa-graduation-cap"></i>
                                <span>${user.studies || 'Non spécifié'}</span>
                            </div>
                            <div class="profile-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <span>${user.bac_year || 'Non spécifié'}</span>
                            </div>
                        `;
                        
                        if (user.profession) {
                            detailsHTML += `
                                <div class="profile-detail">
                                    <i class="fas fa-briefcase"></i>
                                    <span>${user.profession}</span>
                                </div>
                            `;
                        }
                        
                        if (user.city) {
                            detailsHTML += `
                                <div class="profile-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${user.city}${user.country ? ', ' + user.country : ''}</span>
                                </div>
                            `;
                        }
                        
                        card.innerHTML = `
                            <img src="${user.profile_picture || 'img/profile_pic.jpeg'}" alt="Photo de profil" class="profile-image">
                            <div class="profile-info">
                                <h3 class="profile-name">${user.full_name}</h3>
                                <p class="profile-email">${user.email}</p>
                                ${detailsHTML}
                            </div>
                            ${user.is_birthday ? '<span class="birthday-badge"><i class="fas fa-birthday-cake"></i> Anniversaire</span>' : ''}
                        `;
                        card.addEventListener('click', () => openProfileModal(card));
                        profileGrid.insertBefore(card, loading);
                        lastId = Math.max(lastId, user.id);
                    });
                    page++;
                }
            } catch (error) {
                console.error('Erreur:', error);
                loading.innerHTML = '<p>Intégration sans Dérivation n\'est que ruine de l\'âme</p>';
            } finally {
                isLoading = false;
            }
        }

        // Fonction openProfileModal
        function openProfileModal(element) {
            console.log('Opening modal for:', element.dataset.name);
            
            const id = element.dataset.id;
            const name = element.dataset.name;
            const email = element.dataset.email;
            const birthdate = element.dataset.birthdate;
            const studies = element.dataset.studies;
            const bacyear = element.dataset.bacyear;
            const profession = element.dataset.profession;
            const company = element.dataset.company;
            const city = element.dataset.city;
            const country = element.dataset.country;
            const interests = element.dataset.interests;
            const image = element.dataset.image;

            document.getElementById('modalProfileName').textContent = name;
            document.getElementById('modalProfileEmail').textContent = email;
            document.getElementById('modalProfileBirthdate').textContent = formatBirthdate(birthdate);
            document.getElementById('modalProfileStudies').textContent = studies || 'Non spécifié';
            document.getElementById('modalProfileBacYear').textContent = bacyear || 'Non spécifié';
            document.getElementById('modalProfileImage').src = image || 'img/profile_pic.jpeg';
            
            // Afficher les informations professionnelles (toujours visibles)
            document.getElementById('modalProfileProfession').textContent = profession || 'Non spécifié';
            document.getElementById('modalProfileCompany').textContent = company || 'Non spécifié';
            
            // Afficher la localisation (toujours visible)
            if (city || country) {
                let location = [];
                if (city) location.push(city);
                if (country) location.push(country);
                document.getElementById('modalProfileLocation').textContent = location.join(', ');
            } else {
                document.getElementById('modalProfileLocation').textContent = 'Non spécifié';
            }
            
            // Afficher les centres d'intérêt (toujours visible)
            document.getElementById('modalProfileInterests').textContent = interests || 'Non spécifié';
            
            document.getElementById('modalProfileContact').onclick = () => {
                window.location.href = `mailto:${email}`;
            };
            
            document.getElementById('modalProfileReport').onclick = () => {
                if (confirm('Voulez-vous vraiment signaler cet utilisateur ?')) {
                    window.location.href = `signalement.php?user_id=${id}`;
                }
            };

            if (email === currentUserEmail) {
                document.getElementById('modalProfileContact').style.display = 'none';
                document.getElementById('modalProfileReport').style.display = 'none';
            } else {
                document.getElementById('modalProfileContact').style.display = 'inline-block';
                document.getElementById('modalProfileReport').style.display = 'inline-block';
            }

            document.getElementById('profileModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Reload profiles with new filters - avec recherche avancée
        function reloadProfiles() {
            const searchName = encodeURIComponent(document.getElementById('searchName').value);
            const bacYear = encodeURIComponent(document.getElementById('yearFilter').value);
            const studies = encodeURIComponent(document.getElementById('studyFilter').value);
            const profession = encodeURIComponent(document.getElementById('professionFilter').value);
            const company = encodeURIComponent(document.getElementById('companyFilter').value);
            const city = encodeURIComponent(document.getElementById('cityFilter').value);
            const sortBy = encodeURIComponent(document.getElementById('sortBy').value);
            const sortOrder = encodeURIComponent(document.getElementById('sortOrder').value);
            
            window.location.href = `yearbook.php?search_name=${searchName}&bac_year=${bacYear}&studies=${studies}&profession=${profession}&company=${company}&city=${city}&sort_by=${sortBy}&sort_order=${sortOrder}`;
        }

        // Event listeners for filters
        function setupFilterListeners() {
            // Autocomplétion pour la recherche par nom
            const searchInput = document.getElementById('searchName');
            const autocompleteResults = document.getElementById('autocompleteResults');
            
            if (searchInput) {
                searchInput.addEventListener('input', debounce(async function() {
                    const query = this.value.trim();
                    
                    if (query.length < 2) {
                        autocompleteResults.style.display = 'none';
                        return;
                    }
                    
                    try {
                        const response = await fetch(`autocomplete_users.php?q=${encodeURIComponent(query)}`);
                        const data = await response.json();
                        
                        if (data.length > 0) {
                            autocompleteResults.innerHTML = data.map(user => 
                                `<div class="autocomplete-item" style="padding:0.8rem; cursor:pointer; border-bottom:1px solid #eee;" data-name="${user.full_name}">
                                    <strong>${user.full_name}</strong><br>
                                    <small style="color:#666;">${user.email} • ${user.bac_year || ''}</small>
                                </div>`
                            ).join('');
                            autocompleteResults.style.display = 'block';
                            
                            // Ajout des événements click sur les résultats
                            autocompleteResults.querySelectorAll('.autocomplete-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    searchInput.value = this.dataset.name;
                                    autocompleteResults.style.display = 'none';
                                });
                            });
                        } else {
                            autocompleteResults.style.display = 'none';
                        }
                    } catch (error) {
                        console.error('Erreur autocomplétion:', error);
                    }
                }, 300));
                
                // Fermer l'autocomplétion si on clique ailleurs
                document.addEventListener('click', function(e) {
                    if (e.target !== searchInput && e.target.parentElement !== autocompleteResults) {
                        autocompleteResults.style.display = 'none';
                    }
                });
            }
            
            // Événements sur les sélecteurs
            document.getElementById('yearFilter').addEventListener('change', () => reloadProfiles());
            document.getElementById('studyFilter').addEventListener('change', () => reloadProfiles());
            document.getElementById('sortBy').addEventListener('change', () => reloadProfiles());
            document.getElementById('sortOrder').addEventListener('change', () => reloadProfiles());
            
            // Bouton clear
            document.getElementById('clearFilters').addEventListener('click', () => {
                document.getElementById('searchName').value = '';
                document.getElementById('yearFilter').value = '';
                document.getElementById('studyFilter').value = '';
                document.getElementById('professionFilter').value = '';
                document.getElementById('companyFilter').value = '';
                document.getElementById('cityFilter').value = '';
                document.getElementById('sortBy').value = 'full_name';
                document.getElementById('sortOrder').value = 'ASC';
                reloadProfiles();
            });
            
            // Recherche en temps réel avec debounce pour les champs texte
            ['professionFilter', 'companyFilter', 'cityFilter'].forEach(filterId => {
                document.getElementById(filterId).addEventListener('input', debounce(() => {
                    // Optionnel: recherche automatique après 1 seconde
                    // reloadProfiles();
                }, 1000));
            });
        }

        // Gestion améliorée de la fermeture de la modal
        function setupModalListeners() {
            document.getElementById('modalClose').addEventListener('click', closeProfileModal);
            
            document.getElementById('profileModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('profileModal')) {
                    closeProfileModal();
                }
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && document.getElementById('profileModal').style.display === 'flex') {
                    closeProfileModal();
                }
            });
        }

        // Mobile interface handlers avec gestion améliorée du bouton de fermeture
        function setupMobileInterface() {
            const filterToggle = document.getElementById('filterToggle');
            const filtersContainer = document.getElementById('filtersContainer');
            const filterOverlay = document.getElementById('filterOverlay');
            const closeFilters = document.getElementById('closeFilters');
            const closeFiltersDesktop = document.getElementById('closeFiltersDesktop');
            const applyFiltersBtn = document.getElementById('applyFilters');
            
            if (filterToggle && filtersContainer) {
                filterToggle.addEventListener('click', () => {
                    filtersContainer.classList.add('active');
                    filterOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }
            
            // Gestion unique de la fermeture des filtres
            function closeFilterMenu() {
                filtersContainer.classList.remove('active');
                filterOverlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
            
            // Écouteurs pour tous les boutons de fermeture
            if (closeFilters) closeFilters.addEventListener('click', closeFilterMenu);
            if (closeFiltersDesktop) closeFiltersDesktop.addEventListener('click', closeFilterMenu);
            if (filterOverlay) filterOverlay.addEventListener('click', closeFilterMenu);
            
            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', () => {
                    reloadProfiles();
                    closeFilterMenu();
                });
            }
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            initializeWebSocket();
            setupFilterListeners();
            setupModalListeners();
            setupMobileInterface();
            
            document.querySelectorAll('.profile-card').forEach(card => {
                card.addEventListener('click', () => openProfileModal(card));
            });
            
            const loading = document.getElementById('loading');
            if (loading) {
                loading.innerHTML = '<p>Intégration sans Dérivation n\'est que ruine de l\'âme</p>';
            }
        });
    </script>
</body>
</html>