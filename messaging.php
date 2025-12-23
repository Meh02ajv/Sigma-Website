<?php
require 'config.php';
if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}

$user_email = $_SESSION['user_email'];

// Get current user ID
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

// Initialize users array to avoid undefined variable error
$users = [];

// Fetch all users except the current user
$stmt = $conn->prepare("SELECT id, full_name, email, profile_picture FROM users WHERE email != ? ORDER BY full_name");
if ($stmt) {
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Handle query error
    error_log("Database query error: " . $conn->error);
}

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Define default profile picture path
$default_profile_picture = 'img/profile_pic.jpeg';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }
        :root {
            --primary-color: #1e3a8a;
            --primary-dark: #1e40af;
            --secondary-color: #d4af37;
            --accent-gold: #FFD700;
            --success-color: #16a34a;
            --error-color: #dc2626;
            --highlight-color: #dbeafe;
            --unread-color: #3b82f6;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        body {
            font-family: 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 50%, #f0f9ff 100%);
            background-attachment: fixed;
            color: var(--text-dark);
            overflow-x: hidden;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }
        header .left-icons {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        header .logo {
            width: 42px;
            height: 42px;
            object-fit: contain;
            filter: brightness(1.1);
        }
        header .center-title {
            flex-grow: 1;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        header .center-title h1 {
            font-size: 22px;
            margin: 0;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        header a, header button {
            color: white;
            text-decoration: none;
            font-size: 22px;
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
        }
        header a:hover, header button:hover {
            color: var(--accent-gold);
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.1);
        }
        header a:active, header button:active {
            transform: scale(0.95);
        }
        .messaging-container {
            display: flex;
            height: calc(100vh - 74px);
            max-width: 1600px;
            margin: 0 auto;
            box-shadow: var(--shadow-lg);
        }
        .user-list {
            width: 360px;
            background: white;
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) var(--bg-light);
        }
        .user-list::-webkit-scrollbar {
            width: 6px;
        }
        .user-list::-webkit-scrollbar-track {
            background: var(--bg-light);
        }
        .user-list::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }
        .user-list-header {
            padding: 16px 20px;
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .user-list-header h2 {
            font-size: 18px;
            color: var(--text-dark);
            margin: 0;
            font-weight: 500;
        }
        .user-card {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            border-bottom: 1px solid var(--border-color);
        }
        .user-card:hover {
            background-color: var(--highlight-color);
        }
        .user-card.active {
            background: linear-gradient(to right, var(--highlight-color), white);
            border-left: 4px solid var(--primary-color);
        }
        .user-card.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-color);
        }
        .user-card.unread {
            background: rgba(59, 130, 246, 0.05);
        }
        .user-card.unread::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background-color: var(--unread-color);
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }
            50% {
                box-shadow: 0 0 0 6px rgba(59, 130, 246, 0);
            }
        }
        .user-card img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 14px;
            border: 2px solid var(--border-color);
            transition: border-color 0.3s;
        }
        .user-card:hover img,
        .user-card.active img {
            border-color: var(--primary-color);
        }
        .user-card .info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        .user-card .info h3 {
            font-size: 15px;
            margin: 0 0 4px 0;
            color: var(--text-dark);
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-card .info p {
            font-size: 13px;
            color: var(--text-light);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-card.unread .info h3 {
            font-weight: 600;
            color: var(--primary-color);
        }
        .chat-window {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            position: relative;
        }
        .chat-header {
            padding: 18px 24px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            font-size: 18px;
            font-weight: 500;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .back-button {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 22px;
            margin-right: 14px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
        .back-button:active {
            transform: scale(0.95);
        }
        .chat-messages {
            flex: 1;
            padding: 20px 24px;
            overflow-y: auto;
            overflow-x: hidden;
            background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) var(--bg-light);
        }
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        .chat-messages::-webkit-scrollbar-track {
            background: var(--bg-light);
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }
        .message {
            margin-bottom: 14px;
            padding: 12px 16px;
            border-radius: 16px;
            max-width: 70%;
            word-wrap: break-word;
            word-break: break-word;
            position: relative;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            animation: messageSlideIn 0.3s ease;
        }
        .message.sent {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        .message.received {
            background: white;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            border-bottom-left-radius: 4px;
        }
        .message p {
            margin: 0;
            line-height: 1.5;
            font-size: 15px;
        }
        .message .timestamp {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 6px;
            display: block;
            text-align: right;
            font-weight: 400;
        }
        .message.received .timestamp {
            color: var(--text-light);
        }
        .chat-input {
            display: flex;
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            background: white;
            align-items: flex-end;
            gap: 12px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        .chat-input textarea {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid var(--border-color);
            border-radius: 24px;
            font-size: 15px;
            font-family: inherit;
            resize: none;
            height: 50px;
            min-height: 50px;
            max-height: 120px;
            transition: all 0.3s ease;
            line-height: 1.5;
        }
        .chat-input textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
            outline: none;
        }
        .chat-input textarea::placeholder {
            color: var(--text-light);
        }
        .chat-input button {
            margin: 0;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            min-width: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }
        .chat-input button:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c19e2e 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(212, 175, 55, 0.4);
        }
        .chat-input button:active {
            transform: translateY(0);
        }
        .chat-input button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .no-selection, .error-message, .info-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--text-light);
            text-align: center;
            padding: 40px 20px;
            gap: 12px;
        }
        .no-selection i, .error-message i, .info-message i {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 8px;
        }
        .error-message {
            color: var(--error-color);
        }
        .error-message i {
            color: var(--error-color);
        }
        .info-message {
            color: var(--primary-color);
        }
        .info-message i {
            color: var(--primary-color);
        }
        .hidden {
            display: none !important;
        }

        /* Tablette */
        @media (max-width: 1024px) {
            .messaging-container {
                height: calc(100vh - 74px);
            }
            
            .user-list {
                width: 320px;
            }
            
            .message {
                max-width: 75%;
            }
        }
        
        /* Mode mobile - Liste des utilisateurs en plein écran */
        @media (max-width: 768px) {
            header {
                padding: 14px 18px;
            }
            
            header .logo {
                width: 38px;
                height: 38px;
            }
            
            header .center-title h1 {
                font-size: 20px;
            }
            
            header a, header button {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            
            .messaging-container {
                flex-direction: column;
                height: calc(100vh - 66px);
            }
            
            .user-list {
                width: 100%;
                max-height: none;
                height: 100%;
                position: absolute;
                z-index: 100;
                transform: translateX(0);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .user-list.hidden-mobile {
                transform: translateX(-100%);
            }
            
            .chat-window {
                width: 100%;
                height: 100%;
            }
            
            .back-button {
                display: flex !important;
            }
            
            .chat-header {
                padding: 16px 18px;
                font-size: 17px;
            }
            
            .chat-messages {
                padding: 16px 18px;
            }
            
            .message {
                max-width: 80%;
                padding: 10px 14px;
            }
            
            .message p {
                font-size: 14px;
            }
            
            .chat-input {
                padding: 12px 18px;
                gap: 10px;
            }
            
            .chat-input textarea {
                height: 46px;
                min-height: 46px;
                padding: 12px 16px;
                font-size: 14px;
            }
            
            .chat-input button {
                width: 46px;
                height: 46px;
                min-width: 46px;
                font-size: 16px;
            }
            
            .user-card {
                padding: 12px 18px;
            }
            
            .user-card img {
                width: 46px;
                height: 46px;
            }
        }

        /* Mode mobile très petit */
        @media (max-width: 480px) {
            header {
                padding: 12px 15px;
            }
            
            header a, header button {
                width: 38px;
                height: 38px;
                font-size: 19px;
            }
            
            header .logo {
                width: 34px;
                height: 34px;
            }
            
            header .center-title h1 {
                font-size: 18px;
            }
            
            .user-list-header {
                padding: 14px 16px;
            }
            
            .user-list-header h2 {
                font-size: 16px;
            }
            
            .user-card {
                padding: 10px 16px;
            }
            
            .user-card img {
                width: 42px;
                height: 42px;
                margin-right: 12px;
            }
            
            .user-card .info h3 {
                font-size: 14px;
            }
            
            .user-card .info p {
                font-size: 12px;
            }
            
            .back-button {
                width: 36px;
                height: 36px;
                font-size: 20px;
                margin-right: 10px;
            }
            
            .chat-header {
                padding: 14px 16px;
                font-size: 16px;
            }
            
            .chat-messages {
                padding: 14px 16px;
            }
            
            .message {
                padding: 9px 13px;
                max-width: 85%;
                border-radius: 14px;
            }
            
            .message p {
                font-size: 13px;
            }
            
            .message .timestamp {
                font-size: 10px;
            }
            
            .chat-input {
                padding: 10px 16px;
                gap: 8px;
            }
            
            .chat-input textarea {
                font-size: 14px;
                height: 42px;
                min-height: 42px;
                padding: 10px 14px;
                border-radius: 20px;
            }
            
            .chat-input button {
                width: 42px;
                height: 42px;
                min-width: 42px;
                font-size: 15px;
            }
            
            .no-selection, .error-message, .info-message {
                font-size: 14px;
                padding: 30px 16px;
            }
            
            .no-selection i, .error-message i, .info-message i {
                font-size: 52px;
            }
        }
        
        /* Mode très petit écran */
        @media (max-width: 360px) {
            header .center-title h1 {
                font-size: 16px;
            }
            
            .message {
                max-width: 90%;
            }
        }

        /* Améliorations tactiles */
        @media (hover: none) {
            .user-card:hover {
                background-color: #fafafa;
                transform: none;
            }
            
            .user-card:active {
                background-color: var(--highlight-color);
            }
            
            header a:hover, header button:hover,
            .chat-input button:hover {
                color: white;
                transform: none;
            }
            
            header a:active, header button:active {
                color: var(--secondary-color);
            }
            
            .chat-input button:active {
                background-color: var(--secondary-color);
            }
        }

        /* Animation pour les nouveaux messages */
        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Indicateur de connexion */
        .connection-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 500;
            z-index: 1001;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            display: none;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .connection-status.connected {
            background: linear-gradient(135deg, var(--success-color), #15803d);
        }
        
        .connection-status.disconnected {
            background: linear-gradient(135deg, var(--error-color), #b91c1c);
        }
        
        .connection-status i {
            margin-right: 6px;
        }
        
        .no-users-message {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-light);
            font-style: italic;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .no-users-message i {
            font-size: 48px;
            opacity: 0.3;
        }
        
        /* Indicateur de saisie */
        .typing-indicator {
            display: none;
            padding: 8px 12px;
            margin-bottom: 10px;
            max-width: 70px;
        }
        
        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: var(--text-light);
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
            animation: typing 1.4s infinite;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
            margin-right: 0;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.7;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }
        
        /* Amélioration de l'accessibilité */
        *:focus-visible {
            outline: 3px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* Amélioration tactile */
        @media (hover: none) {
            .user-card:hover {
                background: white;
            }
            
            .user-card:active {
                background-color: var(--highlight-color);
            }
        }
        
        /* Mode sombre (bonus) */
        @media (prefers-color-scheme: dark) {
            :root {
                --text-dark: #f3f4f6;
                --text-light: #9ca3af;
                --bg-light: #1f2937;
                --border-color: #374151;
            }
            
            body {
                background: linear-gradient(135deg, #1f2937 0%, #111827 50%, #0f172a 100%);
            }
            
            .user-list, .chat-window {
                background: #1f2937;
            }
            
            .user-card {
                background: #111827;
                border-bottom-color: #374151;
            }
            
            .message.received {
                background: #374151;
                border-color: #4b5563;
            }
            
            .chat-messages {
                background: linear-gradient(to bottom, #1f2937, #111827);
            }
            
            .chat-input {
                background: #1f2937;
                border-top-color: #374151;
            }
        }
    </style>
</head>
<body data-user-id="<?php echo $current_user['id']; ?>" data-csrf-token="<?php echo $csrf_token; ?>">
    <header>
        <div class="left-icons">
            <a href="yearbook.php" aria-label="Aller au Yearbook"><i class="fas fa-users"></i></a>
        </div>
        <div class="center-title">
            <img src="img/image.png" alt="Logo" class="logo">
            <h1>Messagerie</h1>
        </div>
        <div></div>
    </header>
    <div class="messaging-container">
        <div class="user-list" id="user-list">
            <div class="user-list-header">
                <h2><i class="fas fa-users"></i> Contacts</h2>
            </div>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <?php
                    // Validate profile picture path
                    $profile_picture = $default_profile_picture;
                    if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/' . $user['profile_picture'])) {
                        $profile_picture = htmlspecialchars($user['profile_picture']);
                    }
                    ?>
                    <div class="user-card" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name']); ?>" tabindex="0" role="button" aria-label="Ouvrir la conversation avec <?php echo htmlspecialchars($user['full_name']); ?>">
                        <img src="<?php echo $profile_picture; ?>" alt="Photo de profil de <?php echo htmlspecialchars($user['full_name']); ?>" loading="lazy">
                        <div class="info">
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-users-message">
                    <i class="fas fa-user-friends"></i>
                    <p>Aucun autre utilisateur trouvé.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="chat-window">
            <div class="chat-header" id="chat-header">
                <button class="back-button" id="back-button" aria-label="Retour à la liste des contacts" style="display: none;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <span id="chat-title">Sélectionnez un utilisateur pour commencer à discuter</span>
            </div>
            <div class="chat-messages" id="chat-messages">
                <div class="no-selection">
                    <i class="fas fa-comments"></i>
                    <p>Sélectionnez un contact pour commencer la conversation</p>
                </div>
            </div>
            <div class="chat-input hidden" id="chat-input">
                <textarea id="message-input" placeholder="Écrivez votre message..." aria-label="Écrire un message" maxlength="1000"></textarea>
                <button id="send-message" aria-label="Envoyer le message" type="button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="connection-status" id="connection-status">
        <i class="fas fa-circle-notch fa-spin"></i> Connexion...
    </div>

    <script src="js/messaging.js"></script>
    <script>
        // Script inline temporaire supprimé - voir js/messaging.js
    </script>
    
    <!-- Script ancien WebSocket à supprimer -->
    <!--<script>
        const currentUserId = <?php echo $current_user['id']; ?>;
        let selectedUserId = null;
        let socket = null;
        let isSending = false;
        const displayedMessages = new Set();
        let isMobile = window.innerWidth <= 768;
        let reconnectAttempts = 0;
        const MAX_RECONNECT_ATTEMPTS = 5;
        let reconnectTimeout = null;

        // Initialiser l'application
        function initializeWebSocket() {
            // Afficher le statut de connexion
            const statusElement = document.getElementById('connection-status');
            statusElement.style.display = 'block';
            statusElement.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Connexion...';
            statusElement.className = 'connection-status';

            try {
                socket = new WebSocket('ws://localhost:8080');
            
            
                socket.onopen = () => {
                    console.log('Connected to WebSocket server');
                    reconnectAttempts = 0;
                    statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Connecté';
                    statusElement.className = 'connection-status connected';
                    setTimeout(() => {
                        statusElement.style.display = 'none';
                    }, 2000);
                    
                    socket.send(JSON.stringify({ type: 'set_user_id', user_id: currentUserId }));
                    loadUnreadIndicators();
                };
            } catch (error) {
                console.error('Error creating WebSocket:', error);
                statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erreur de connexion';
                statusElement.className = 'connection-status disconnected';
            }
            socket.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data);
                    if (message.type === 'set_user_id') {
                        console.log('User ID set:', message.user_id);
                        return;
                    }
                    if (message.type === 'error') {
                        console.error('Server error:', message.error);
                        displayError('Erreur serveur: ' + message.error);
                        if (message.message_id && displayedMessages.has(message.message_id)) {
                            const messageDiv = document.querySelector(`.message[data-messageId="${message.message_id}"]`);
                            if (messageDiv) {
                                messageDiv.remove();
                                displayedMessages.delete(message.message_id);
                            }
                            loadMessages(selectedUserId);
                        }
                        return;
                    }
                    if (!selectedUserId) {
                        console.log('Message ignored: no user selected');
                        loadUnreadIndicators();
                        return;
                    }
                    if (message.sender_id && message.recipient_id && message.content && message.sent_at && message.message_id) {
                        const isCurrentConversation = 
                            (message.sender_id === currentUserId && message.recipient_id === parseInt(selectedUserId)) ||
                            (message.sender_id === parseInt(selectedUserId) && message.recipient_id === currentUserId);
                        if (isCurrentConversation && !displayedMessages.has(message.message_id)) {
                            displayMessage(message);
                            displayedMessages.add(message.message_id);
                            if (message.sender_id !== currentUserId) {
                                markAsRead(selectedUserId);
                            }
                        } else {
                            console.log('Message ignored: not for current conversation or already displayed', message);
                            loadUnreadIndicators();
                        }
                    } else {
                        console.log('Invalid message format:', message);
                    }
                } catch (e) {
                    console.error('Error parsing WebSocket message:', e);
                }
            };

            socket.onclose = () => {
                console.log('Disconnected from WebSocket server.');
                const statusElement = document.getElementById('connection-status');
                
                if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                    reconnectAttempts++;
                    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
                    statusElement.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Déconnecté - Reconnexion (${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS})...`;
                    statusElement.className = 'connection-status disconnected';
                    statusElement.style.display = 'block';
                    
                    reconnectTimeout = setTimeout(initializeWebSocket, delay);
                } else {
                    statusElement.innerHTML = '<i class="fas fa-times-circle"></i> Connexion perdue';
                    statusElement.className = 'connection-status disconnected';
                    statusElement.style.display = 'block';
                }
            };

            socket.onerror = (error) => {
                console.error('WebSocket error:', error);
                const statusElement = document.getElementById('connection-status');
                statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erreur de connexion';
                statusElement.className = 'connection-status disconnected';
                statusElement.style.display = 'block';
            };
        }

        function displayMessage(message) {
            const messagesDiv = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${message.sender_id === currentUserId ? 'sent' : 'received'}`;
            messageDiv.dataset.messageId = message.message_id || 'temp-' + Date.now();
            messageDiv.innerHTML = `
                <p>${message.content}</p>
                <span class="timestamp">${new Date(message.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
            `;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            hidePlaceholderMessages();
        }

        function displayError(errorMsg) {
            const messagesDiv = document.getElementById('chat-messages');
            messagesDiv.innerHTML = '';
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <p>${errorMsg}</p>
            `;
            messagesDiv.appendChild(errorDiv);
            hidePlaceholderMessages();
        }

        function displayInfo(infoMsg) {
            const messagesDiv = document.getElementById('chat-messages');
            messagesDiv.innerHTML = '';
            const infoDiv = document.createElement('div');
            infoDiv.className = 'info-message';
            infoDiv.innerHTML = `
                <i class="fas fa-info-circle"></i>
                <p>${infoMsg}</p>
            `;
            messagesDiv.appendChild(infoDiv);
            hidePlaceholderMessages();
        }

        function hidePlaceholderMessages() {
            const noSelection = document.querySelector('.no-selection');
            if (noSelection) {
                noSelection.classList.add('hidden');
            }
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.classList.add('hidden');
            }
            const infoMessage = document.querySelector('.info-message');
            if (infoMessage) {
                infoMessage.classList.add('hidden');
            }
        }

        function resetChat() {
            selectedUserId = null;
            document.getElementById('chat-title').textContent = 'Sélectionnez un utilisateur pour commencer à discuter';
            document.getElementById('chat-input').classList.add('hidden');
            const messagesDiv = document.getElementById('chat-messages');
            messagesDiv.innerHTML = '';
            const noSelection = document.createElement('div');
            noSelection.className = 'no-selection';
            noSelection.innerHTML = `
                <i class="fas fa-comments"></i>
                <p>Sélectionnez un contact pour commencer la conversation</p>
            `;
            messagesDiv.appendChild(noSelection);
            document.querySelectorAll('.user-card').forEach(c => c.classList.remove('active'));
            displayedMessages.clear();
            
            // Réinitialiser le textarea
            const messageInput = document.getElementById('message-input');
            messageInput.value = '';
            messageInput.style.height = '';
            
            // Sur mobile, afficher à nouveau la liste des utilisateurs
            if (isMobile) {
                document.getElementById('user-list').classList.remove('hidden-mobile');
            }
        }

        function showChat() {
            if (isMobile) {
                document.getElementById('user-list').classList.add('hidden-mobile');
            }
            document.getElementById('chat-input').classList.remove('hidden');
            
            // Focus sur le champ de saisie
            setTimeout(() => {
                if (window.innerWidth > 768) {
                    document.getElementById('message-input').focus();
                }
            }, 100);
        }

        async function loadMessages(recipientId) {
            try {
                const response = await fetch(`get_messages.php?recipient_id=${recipientId}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`HTTP error! status: ${response.status}, response: ${text}`);
                }
                const messages = await response.json();
                if (messages.error) {
                    throw new Error(messages.error);
                }
                const messagesDiv = document.getElementById('chat-messages');
                messagesDiv.innerHTML = '';
                displayedMessages.clear();
                
                if (messages.length === 0) {
                    displayInfo('Les messages seront supprimés après 15 jours.');
                } else {
                    // Reverse messages to display oldest first, newest last
                    messages.reverse().forEach(message => {
                        const isValidMessage = 
                            (message.sender_id === currentUserId && message.recipient_id === parseInt(recipientId)) ||
                            (message.sender_id === parseInt(recipientId) && message.recipient_id === currentUserId);
                        if (isValidMessage && !displayedMessages.has(message.message_id || 'temp-' + message.sent_at)) {
                            displayMessage(message);
                            displayedMessages.add(message.message_id || 'temp-' + message.sent_at);
                        }
                    });
                    markAsRead(recipientId);
                }
                loadUnreadIndicators();
            } catch (error) {
                console.error('Error loading messages:', error);
                displayError('Erreur lors du chargement des messages: ' + error.message);
            }
        }

        async function markAsRead(recipientId) {
            try {
                const response = await fetch('mark_messages_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `recipient_id=${recipientId}&csrf_token=<?php echo $csrf_token; ?>`
                });
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`HTTP error! status: ${response.status}, response: ${text}`);
                }
                const result = await response.json();
                if (result.success) {
                    console.log(`Messages marked as read for recipient ${recipientId}`);
                    loadUnreadIndicators();
                } else {
                    console.error('Error marking messages as read:', result.error);
                }
            } catch (error) {
                console.error('Error marking messages as read:', error);
            }
        }

        async function loadUnreadIndicators() {
            try {
                const response = await fetch('get_unread_counts.php');
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`HTTP error! status: ${response.status}, response: ${text}`);
                }
                const unreadCounts = await response.json();
                if (unreadCounts.error) {
                    throw new Error(unreadCounts.error);
                }
                document.querySelectorAll('.user-card').forEach(card => {
                    const userId = parseInt(card.dataset.id);
                    card.classList.remove('unread');
                    if (unreadCounts[userId] && unreadCounts[userId] > 0) {
                        card.classList.add('unread');
                    }
                });
            } catch (error) {
                console.error('Error loading unread indicators:', error);
            }
        }

        function setActiveUser(card) {
            document.querySelectorAll('.user-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        }

        function sendMessage() {
            if (isSending) return;
            isSending = true;
            const input = document.getElementById('message-input');
            const content = input.value.trim();
            if (!content) {
                displayError('Veuillez entrer un message.');
                isSending = false;
                return;
            }
            if (!selectedUserId) {
                displayError('Veuillez sélectionner un utilisateur.');
                isSending = false;
                return;
            }
            if (!socket || socket.readyState !== WebSocket.OPEN) {
                displayError('Connexion au serveur perdue. Veuillez réessayer.');
                isSending = false;
                return;
            }
            const message_id = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            const message = {
                sender_id: currentUserId,
                recipient_id: parseInt(selectedUserId),
                content: content,
                sent_at: new Date().toISOString(),
                message_id: message_id
            };
            try {
                socket.send(JSON.stringify(message));
                console.log('Message sent:', message);
                input.value = '';
                // Réinitialiser la hauteur du textarea
                input.style.height = '50px';
                if (isMobile) {
                    input.style.height = '45px';
                }
                
                if (!displayedMessages.has(message_id)) {
                    displayMessage(message);
                    displayedMessages.add(message_id);
                }
            } catch (e) {
                console.error('Error sending message:', e);
                displayError('Erreur lors de l\'envoi du message: ' + e.message);
            } finally {
                isSending = false;
            }
        }

        // Gestion des événements - Cartes utilisateur
        function setupUserCardListeners() {
            document.querySelectorAll('.user-card').forEach(card => {
                // Click event
                card.addEventListener('click', () => {
                    handleUserSelection(card);
                });
                
                // Keyboard navigation
                card.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        handleUserSelection(card);
                    }
                });
            });
        }
        
        function handleUserSelection(card) {
            const userId = parseInt(card.dataset.id);
            if (selectedUserId === userId) {
                // Fermer la conversation si on clique sur le même utilisateur
                if (!isMobile) {
                    resetChat();
                }
            } else {
                // Ouvrir une nouvelle conversation
                selectedUserId = userId;
                document.getElementById('chat-title').innerHTML = `<i class="fas fa-user"></i> ${card.dataset.name}`;
                setActiveUser(card);
                showChat();
                loadMessages(selectedUserId);
            }
        }

        // Bouton de retour sur mobile
        document.getElementById('back-button').addEventListener('click', resetChat);

        // Gestion de l'envoi de message
        const sendButton = document.getElementById('send-message');
        sendButton.addEventListener('click', sendMessage);

        // Gestion de la textarea
        const messageInput = document.getElementById('message-input');
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Ajustement automatique de la hauteur de la textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            if (this.scrollHeight > 120) {
                this.style.overflowY = 'auto';
            } else {
                this.style.overflowY = 'hidden';
            }
        });

        // Gestion du redimensionnement de la fenêtre
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const wasMobile = isMobile;
                isMobile = window.innerWidth <= 768;
                
                // Réinitialiser l'interface si on passe du mobile au desktop ou inversement
                if (wasMobile !== isMobile) {
                    if (!isMobile && selectedUserId) {
                        // Afficher à nouveau la liste sur desktop
                        document.getElementById('user-list').classList.remove('hidden-mobile');
                    }
                }
            }, 250);
        });
        
        // Empêcher le zoom sur double tap (iOS)
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            initializeWebSocket();
            setupUserCardListeners();
            
            // Cacher la liste des utilisateurs sur mobile si une conversation est ouverte
            if (isMobile && selectedUserId) {
                document.getElementById('user-list').classList.add('hidden-mobile');
            }
            
            // Charger les indicateurs non lus au chargement
            setTimeout(loadUnreadIndicators, 1000);
        });
        
        // Ancien code WebSocket supprimé - Voir js/messaging.js
    </script>-->
</body>
</html>