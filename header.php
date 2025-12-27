<?php
// filepath: c:\xampp\htdocs\Sigma-Website\header.php
// Use centralized database connection from config.php
// La session est déjà démarrée dans config.php, pas besoin de la redémarrer
require_once 'config.php';

// Fetch configurations with error handling
$configs = [];
$config_sql = "SELECT setting_key, setting_value FROM general_config LIMIT 50";
$config_result = $conn->query($config_sql);

if ($config_result) {
    while($row = $config_result->fetch_assoc()) {
        $configs[$row['setting_key']] = htmlspecialchars($row['setting_value']);
    }
} else {
    error_log("Config query failed: " . $conn->error);
}

// Récupérer le thème actif
$active_theme = 'none';
$theme_result = $conn->query("SELECT theme_name FROM site_themes WHERE id = 1 LIMIT 1");
if ($theme_result && $row = $theme_result->fetch_assoc()) {
    $active_theme = $row['theme_name'];
}

// Récupérer la préférence de mode sombre de l'utilisateur
$user_dark_mode = false;
if (isset($_SESSION['user_id'])) {
    // Vérifier si la colonne dark_mode existe avant de l'utiliser
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'dark_mode'");
    if ($check_column && $check_column->num_rows > 0) {
        $user_theme_result = $conn->query("SELECT dark_mode FROM users WHERE id = " . intval($_SESSION['user_id']) . " LIMIT 1");
        if ($user_theme_result && $theme_row = $user_theme_result->fetch_assoc()) {
            $user_dark_mode = (bool)$theme_row['dark_mode'];
        }
    }
}
$user_theme_preference = $user_dark_mode ? 'dark' : 'light';

$isLoggedIn = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);
$user_full_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : '';
?>
<!DOCTYPE html>
<html lang="fr" data-user-theme="<?php echo $user_theme_preference; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="SIGMA Alumni - Communauté d'anciens élèves">
    <title>SIGMA Alumni - <?php echo ucfirst(str_replace('.php', '', $current_page)); ?></title>
    
    <!-- Favicon -->
    <?php include 'includes/favicon.php'; ?>
    
    <!-- FontAwesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Dark Mode Support -->
    <link rel="stylesheet" href="css/dark-mode.css">
    
    <?php if ($active_theme !== 'none'): ?>
    <!-- Thème festif actif -->
    <link rel="stylesheet" href="festive_themes.css">
    <?php endif; ?>
    <style>
        /* Force FontAwesome to work properly */
        .fa, .fas, .far, .fal, .fad, .fab {
            font-family: 'Font Awesome 6 Free', 'Font Awesome 6 Brands' !important;
            font-weight: 900;
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .fab {
            font-family: 'Font Awesome 6 Brands' !important;
            font-weight: 400;
        }

        /* CSS Variables */
        :root {
            --primary-blue: #2563eb;
            --secondary-blue: #1e40af;
            --dark-blue: #1e3a8a;
            --light-blue: #eff6ff;
            --accent-blue: #3b82f6;
            --accent-gray: #64748b;
            --light-gray: #f8fafc;
            --white: #ffffff;
            --gold-accent: #0891b2;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            color: var(--accent-gray);
            line-height: 1.6;
            padding-top: 70px;
        }

        /* Header Styles */
        header {
            background: var(--white);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
            border-bottom: 1px solid #e2e8f0;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.875rem 5%;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .logo {
            display: flex;
            align-items: center;
            z-index: 1001;
            text-decoration: none;
            color: inherit;
            gap: 0.875rem;
            padding: 0.5rem;
            border-radius: 12px;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .logo:hover {
            background: rgba(30, 58, 138, 0.05);
            transform: translateY(-2px);
        }

        .logo img {
            height: 52px;
            width: auto;
            max-width: 52px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
            transition: var(--transition);
            display: block;
        }

        .logo:hover img {
            filter: drop-shadow(0 4px 8px rgba(30, 58, 138, 0.3));
        }

        .logo-text-wrapper {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2px;
            min-width: 0;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .logo-subtext {
            font-size: 0.625rem;
            color: var(--accent-gray);
            letter-spacing: 1px;
            font-weight: 600;
            text-transform: uppercase;
        }

        nav {
            display: flex;
            align-items: center;
            flex: 1;
            margin-left: 2rem;
        }

        nav ul {
            display: flex;
            list-style: none;
            align-items: center;
            gap: 0.5rem;
        }

        nav ul li {
            margin-left: 0;
            position: relative;
        }

        nav ul li a {
            text-decoration: none;
            color: var(--accent-gray);
            font-weight: 600;
            transition: var(--transition);
            padding: 0.75rem 1.125rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            font-size: 0.9375rem;
            border-radius: 10px;
            background: transparent;
        }

        nav ul li a i {
            font-size: 1.125rem;
            width: 1.25rem;
            text-align: center;
            transition: var(--transition);
        }

        nav ul li a:hover {
            color: var(--primary-blue);
            background: linear-gradient(135deg, var(--light-blue) 0%, rgba(59, 130, 246, 0.1) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
        }

        nav ul li a:hover i {
            transform: scale(1.15);
            color: var(--secondary-blue);
        }

        nav ul li a.active {
            color: var(--white);
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }

        nav ul li a.active i {
            color: var(--white);
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .nav-spacer {
            flex: 1;
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-left: 1.5rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-greeting {
            font-size: 0.9375rem;
            color: var(--accent-gray);
            font-weight: 600;
            padding: 0 0.5rem;
        }

        .btn {
            background: var(--primary-blue);
            color: var(--white);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
            font-size: 0.9375rem;
            text-align: center;
            font-family: inherit;
            box-shadow: var(--shadow);
        }

        .btn i {
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .btn:hover {
            background: var(--secondary-blue);
            box-shadow: var(--shadow-lg);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn:nth-child(1) {
            background: transparent;
            color: var(--primary-blue);
            border: 1.5px solid var(--primary-blue);
        }

        .btn:nth-child(1):hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        .logout-btn {
            background: transparent;
            border: none;
            color: var(--accent-gray);
            font-weight: 500;
            cursor: pointer;
            font-size: 0.9375rem;
            padding: 0.5rem 1rem;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            border-radius: 6px;
        }

        .logout-btn i {
            font-size: 1rem;
            transition: var(--transition);
        }

        .logout-btn:hover {
            color: #dc2626;
            background: #fee2e2;
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 2rem;
            height: 1.5rem;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
            margin-left: auto;
        }

        .menu-toggle span {
            width: 2rem;
            height: 0.25rem;
            background: var(--dark-blue);
            border-radius: 10px;
            transition: var(--transition);
            position: relative;
            transform-origin: center;
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(10px, 10px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* Mobile Menu Overlay */
        .menu-overlay {
            display: none;
            position: fixed;
            top: 60px;
            left: 0;
            width: 100%;
            height: calc(100vh - 60px);
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Masquer le logo quand le menu mobile est ouvert */
        body.menu-open .logo {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            nav ul {
                gap: 0.75rem;
            }

            nav ul li a {
                padding: 0.5rem 0.875rem;
                font-size: 0.9rem;
            }

            .auth-buttons {
                gap: 0.75rem;
            }
        }

        @media (max-width: 1024px) {
            .header-container {
                padding: 0.75rem 4%;
            }

            nav {
                margin-left: 1.5rem;
            }

            nav ul {
                gap: 0.5rem;
            }

            nav ul li a {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            nav ul li a::after {
                left: 0.75rem;
            }

            nav ul li a:hover::after,
            nav ul li a.active::after {
                width: calc(100% - 1.5rem);
            }

            .auth-buttons {
                gap: 0.5rem;
                margin-left: 1rem;
            }

            .btn {
                padding: 0.5rem 0.875rem;
                font-size: 0.85rem;
            }

            .logo img {
                height: 45px;
            }

            .logo-text {
                font-size: 1.35rem;
            }

            .logo-subtext {
                font-size: 0.65rem;
            }
        }

        @media (max-width: 900px) {
            nav ul li a {
                padding: 0.5rem 0.625rem;
                font-size: 0.825rem;
            }

            .btn {
                padding: 0.45rem 0.75rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }

            header {
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .header-container {
                padding: 0.625rem 4%;
                justify-content: space-between;
            }

            .menu-toggle {
                display: flex;
                order: 3;
            }

            .logo {
                order: 1;
                padding: 0.25rem;
                gap: 0.625rem;
            }

            .logo img {
                height: 42px;
            }

            .logo-text {
                font-size: 1.25rem;
            }

            .logo-subtext {
                font-size: 0.6rem;
            }

            nav {
                position: fixed;
                top: 60px;
                left: 0;
                height: calc(100vh - 60px);
                width: 100%;
                background: var(--white);
                flex-direction: column;
                justify-content: flex-start;
                align-items: stretch;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                margin: 0;
                z-index: 999;
                overflow-y: auto;
                padding: 1.5rem 0;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
                order: 4;
            }

            nav.active {
                transform: translateX(0);
            }

            nav ul {
                flex-direction: column;
                width: 100%;
                gap: 0;
                padding: 0;
            }

            nav ul li {
                width: 100%;
                margin: 0;
                border-bottom: 1px solid #f1f5f9;
            }

            nav ul li:first-child {
                border-top: 1px solid #f1f5f9;
            }

            nav ul li a {
                display: flex;
                align-items: center;
                padding: 1.125rem 1.5rem;
                font-size: 1rem;
                width: 100%;
                transition: all 0.2s ease;
            }

            nav ul li a::after {
                display: none;
            }

            nav ul li a:hover {
                background: #f8fafc;
                padding-left: 1.75rem;
            }

            nav ul li a.active {
                background: var(--light-blue);
                border-left: 4px solid var(--primary-blue);
                padding-left: calc(1.5rem - 4px);
                font-weight: 600;
            }

            .auth-buttons {
                flex-direction: column;
                width: 100%;
                padding: 1.5rem;
                gap: 0.875rem;
                border-top: 2px solid var(--light-gray);
                margin: 1rem 0 0 0;
            }

            .btn {
                width: 100%;
                padding: 0.875rem;
                font-size: 0.95rem;
                justify-content: center;
            }

            .user-greeting {
                display: none;
            }

            .user-menu {
                gap: 0;
                order: 2;
            }

            .user-menu .dropdown {
                position: static;
            }

            .user-menu .dropdown-menu {
                position: fixed;
                top: 60px;
                right: 0;
                left: auto;
                width: 280px;
                max-width: calc(100vw - 2rem);
                margin: 0;
            }

            .menu-overlay.active {
                display: block;
            }
        }

        @media (max-width: 600px) {
            nav ul li a {
                padding: 1rem 1.25rem;
                font-size: 0.95rem;
            }

            nav ul li a:hover {
                padding-left: 1.5rem;
            }

            .auth-buttons {
                padding: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding-top: 55px;
            }

            header {
                height: 55px;
            }

            .header-container {
                padding: 0.5rem 3%;
            }

            .logo {
                gap: 0.5rem;
                padding: 0.125rem;
            }

            .logo img {
                height: 36px;
            }

            .logo-text {
                font-size: 1.1rem;
            }

            .logo-subtext {
                font-size: 0.55rem;
                display: none;
            }

            nav {
                top: 55px;
                height: calc(100vh - 55px);
                padding: 1rem 0;
            }

            nav ul li a {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }

            nav ul li a:hover {
                padding-left: 1.25rem;
            }

            nav ul li a.active {
                padding-left: calc(1rem - 4px);
            }

            .auth-buttons {
                padding: 1rem;
                gap: 0.75rem;
            }

            .btn {
                padding: 0.75rem;
                font-size: 0.875rem;
            }

            .menu-toggle {
                width: 1.75rem;
                height: 1.375rem;
            }

            .menu-toggle span {
                width: 1.75rem;
                height: 0.2rem;
            }

            .menu-toggle.active span:nth-child(1) {
                transform: rotate(45deg) translate(8px, 8px);
            }

            .menu-toggle.active span:nth-child(3) {
                transform: rotate(-45deg) translate(6px, -6px);
            }

            .user-menu .dropdown-menu {
                width: 260px;
            }

            .menu-overlay {
                top: 55px;
                height: calc(100vh - 55px);
            }
        }

        @media (max-width: 380px) {
            .header-container {
                padding: 0.5rem 2%;
            }

            .logo img {
                height: 32px;
            }

            .logo-text {
                font-size: 1rem;
            }

            nav ul li a {
                padding: 0.75rem 0.875rem;
                font-size: 0.875rem;
            }

            .menu-toggle {
                width: 1.5rem;
                height: 1.25rem;
            }

            .menu-toggle span {
                width: 1.5rem;
                height: 0.175rem;
            }
        }

        /* Message Badge */
        .message-badge {
            position: absolute;
            top: -8px;
            right: 10px;
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        @media (max-width: 768px) {
            .message-badge {
                top: 50%;
                right: 15px;
                transform: translateY(-50%);
            }
        }

        /* Print styles */
        @media print {
            header {
                position: static;
            }

            body {
                padding-top: 0;
            }
        }
    </style>
</head>
<body class="<?php echo $active_theme !== 'none' ? 'theme-' . $active_theme : ''; ?>">
    <header>
        <div class="header-container">
            <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'accueil.php'; ?>" class="logo" aria-label="SIGMA Alumni - Accueil">
                <?php 
                $header_logo = $configs['admin_logo'] ?? 'img/image.png';
                if (!file_exists($header_logo)) {
                    $header_logo = 'img/image.png';
                }
                ?>
                <img src="<?php echo htmlspecialchars($header_logo); ?>" alt="SIGMA Alumni Logo" loading="lazy">
                <div class="logo-text-wrapper">
                    <div class="logo-text">SIGMA</div>
                    <div class="logo-subtext">SCIENCE-CONSCIENCE-METHODE</div>
                </div>
            </a>
            
            <button type="button" class="menu-toggle" aria-label="Activer le menu de navigation" aria-expanded="false" aria-controls="main-nav">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </button>
            
            <nav id="main-nav" role="navigation" aria-label="Navigation principale">
                <ul>
                    <li>
                        <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'accueil.php'; ?>" 
                           <?php echo ($current_page == 'accueil.php' || $current_page == 'dashboard.php') ? 'class="active"' : ''; ?>>
                           <i class="fas fa-home"></i> <span>Accueil</span>
                        </a>
                    </li>
                    <li>
                        <a href="evenements.php" <?php echo $current_page == 'evenements.php' ? 'class="active"' : ''; ?>>
                            <i class="fas fa-calendar-alt"></i> <span>Événements</span>
                        </a>
                    </li>
                    <li>
                        <a href="bureau.php" <?php echo $current_page == 'bureau.php' ? 'class="active"' : ''; ?>>
                            <i class="fas fa-users"></i> <span>Bureau</span>
                        </a>
                    </li>
                    <li>
                        <a href="contact.php" <?php echo $current_page == 'contact.php' ? 'class="active"' : ''; ?>>
                            <i class="fas fa-envelope"></i> <span>Contact</span>
                        </a>
                    </li>
                    
                    <?php if ($isLoggedIn): ?>
                        <li>
                            <a href="messaging.php" <?php echo $current_page == 'messaging.php' ? 'class="active"' : ''; ?> id="messaging-nav-link" style="position: relative;">
                                <i class="fas fa-comments"></i> <span>Messagerie</span>
                                <span class="message-badge" id="message-badge" style="display: none;">0</span>
                            </a>
                        </li>
                        <li>
                            <a href="mod_prof.php" <?php echo $current_page == 'profil.php' ? 'class="active"' : ''; ?>>
                                <i class="fas fa-user-circle"></i> <span>Profil</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="logout-btn" id="logoutLink" role="button" tabindex="0">
                                <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="auth-buttons">
                            <a href="verification.php" class="btn">
                                <i class="fas fa-user-plus"></i> S'inscrire
                            </a>
                            <a href="connexion.php" class="btn">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <div class="menu-overlay"></div>
    </header>

    <script>
        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const nav = document.querySelector('#main-nav');
        const navLinks = document.querySelectorAll('#main-nav a');
        const menuOverlay = document.querySelector('.menu-overlay');

        function closeMenu() {
            nav.classList.remove('active');
            menuToggle.classList.remove('active');
            menuOverlay.classList.remove('active');
            menuToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            document.body.classList.remove('menu-open');
        }

        function openMenu() {
            nav.classList.add('active');
            menuToggle.classList.add('active');
            menuOverlay.classList.add('active');
            menuToggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            document.body.classList.add('menu-open');
        }

        menuToggle.addEventListener('click', () => {
            if (nav.classList.contains('active')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // Close menu when clicking overlay
        menuOverlay.addEventListener('click', closeMenu);

        // Close menu when a link is clicked
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Don't close menu for logout or other special links
                if (!link.id.includes('logout')) {
                    closeMenu();
                }
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('header') && nav.classList.contains('active')) {
                closeMenu();
            }
        });

        // Close menu on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && nav.classList.contains('active')) {
                closeMenu();
            }
        });

        // Logout functionality
        const logoutLink = document.getElementById('logoutLink');
        if (logoutLink) {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                    window.location.href = 'logout.php';
                }
            });

            // Allow keyboard activation
            logoutLink.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        }

        // Header scroll effect (optional)
        let lastScrollTop = 0;
        const header = document.querySelector('header');

        window.addEventListener('scroll', () => {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down
                header.style.boxShadow = '0 -2px 6px rgba(0, 0, 0, 0.1)';
            } else {
                // Scrolling up
                header.style.boxShadow = 'var(--shadow)';
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });

        // Update unread message count badge
        <?php if ($isLoggedIn): ?>
        function updateMessageBadge() {
            fetch('get_total_unread.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('message-badge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching unread count:', error);
            });
        }

        // Update badge immediately on page load
        updateMessageBadge();

        // Poll for updates every 5 seconds
        setInterval(updateMessageBadge, 5000);
        <?php endif; ?>
    </script>
    
    <!-- Dark Mode Script -->
    <script src="js/theme-manager.js"></script>
