<?php
require 'config.php';

// Récupérer la configuration générale
$stmt_config = $conn->prepare("SELECT setting_key, setting_value FROM general_config");
$stmt_config->execute();
$result_config = $stmt_config->get_result();
$general_config = [];
while ($row = $result_config->fetch_assoc()) {
    $general_config[$row['setting_key']] = $row['setting_value'];
}
$stmt_config->close();

// Image de fond avec fallback
$bg_image = (!empty($general_config['bg_connexion']) && file_exists($general_config['bg_connexion'])) 
    ? $general_config['bg_connexion'] 
    : 'img/2024.jpg';

// Initialize error message
$error = '';

// Sanitization function
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de validation du formulaire. Veuillez réessayer.";
    } else {
        // Sanitize inputs
        $email = sanitize($_POST['email']);
        $password = $_POST['password']; // Don't sanitize password - it's hashed
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Veuillez entrer une adresse email valide.";
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id, email, password, full_name, tutorial_completed FROM users WHERE email = ?");
            if (!$stmt) {
                $error = "Erreur de connexion à la base de données.";
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user) {
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Set session
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['full_name'] = $user['full_name'];

                        // Increment login_count if column exists
                        $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'login_count'");
                        if ($check_column && $check_column->num_rows > 0) {
                            $stmt = $conn->prepare("UPDATE users SET login_count = login_count + 1 WHERE id = ?");
                            $stmt->bind_param("i", $user['id']);
                            $stmt->execute();
                            $stmt->close();
                        }

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Vérifier si c'est la première connexion (tutoriel non complété)
                        if (isset($user['tutorial_completed']) && $user['tutorial_completed'] == 0) {
                            header("Location: dashboard.php?tutorial=1");
                        } else {
                            header("Location: dashboard.php");
                        }
                        exit;
                    } else {
                        // Incorrect password
                        $error = "Email ou mot de passe incorrect.";
                    }
                } else {
                    // Email doesn't exist
                    $error = "Email inexistant.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connexion à SIGMA Alumni">
    <title>Se connecter - SIGMA Alumni</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('<?php echo htmlspecialchars($bg_image); ?>');
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            background-size: cover;
            padding: 1rem;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            width: 100%;
            max-width: 400px;
            position: relative;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .back-arrow {
            position: absolute;
            top: 1rem;
            left: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #1e3a8a;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: rgba(30, 58, 138, 0.1);
        }

        .back-arrow:hover {
            color: #d4af37;
            background: rgba(212, 175, 55, 0.1);
            transform: scale(1.1);
        }

        .logo {
            width: 80px;
            margin: 0 auto 1.5rem;
            height: auto;
        }

        h2 {
            color: #1e3a8a;
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 0.75rem;
            color: #1e3a8a;
            pointer-events: none;
        }
        
        .input-wrapper .toggle-password {
            position: absolute;
            right: 0.75rem;
            left: auto;
            color: #666;
            pointer-events: auto;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .input-wrapper .toggle-password:hover {
            color: #1e3a8a;
        }

        input {
            width: 100%;
            padding: 0.75rem 3rem 0.75rem 2.5rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        input::placeholder {
            color: #999;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        button:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .error {
            color: #dc2626;
            font-size: 0.9rem;
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: rgba(220, 38, 38, 0.1);
            border-left: 4px solid #dc2626;
            border-radius: 4px;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .forgot-password {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .forgot-password a {
            color: #1e3a8a;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 120px;
            text-align: center;
        }

        .forgot-password a:hover {
            color: #d4af37;
            text-decoration: underline;
        }
        
        .continue-without-account {
            flex-basis: 100% !important;
            margin-top: 1rem !important;
            padding: 0.8rem 1.5rem;
            background-color: #3498db;
            color: white !important;
            border-radius: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .continue-without-account:hover {
            background-color: #2980b9;
            color: white !important;
            text-decoration: none !important;
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            color: #999;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 0.5rem;
            }

            .container {
                padding: 1.5rem 1.25rem;
                max-width: 100%;
            }

            .back-arrow {
                top: 0.75rem;
                left: 0.75rem;
                width: 2rem;
                height: 2rem;
                font-size: 1.25rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            .subtitle {
                font-size: 0.85rem;
            }

            .logo {
                width: 70px;
                margin-bottom: 1.25rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            input {
                font-size: 16px; /* Prevents zoom on iOS */
                padding: 0.65rem 0.65rem 0.65rem 2.2rem;
            }

            .forgot-password {
                flex-direction: column;
                gap: 0.75rem;
            }

            .forgot-password a {
                min-width: 100%;
                padding: 0.5rem 0;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1.25rem 1rem;
                border-radius: 8px;
            }

            h2 {
                font-size: 1.25rem;
                margin-bottom: 0.25rem;
            }

            .subtitle {
                font-size: 0.8rem;
                margin-bottom: 1rem;
            }

            .logo {
                width: 60px;
                margin-bottom: 1rem;
            }

            .form-group {
                margin-bottom: 0.85rem;
            }

            .form-group label {
                font-size: 0.85rem;
                margin-bottom: 0.4rem;
            }

            input {
                font-size: 16px;
                padding: 0.6rem 0.6rem 0.6rem 2rem;
                border-radius: 5px;
            }

            .input-wrapper i {
                left: 0.6rem;
                font-size: 0.9rem;
            }

            button {
                padding: 0.65rem;
                font-size: 0.95rem;
                margin-top: 0.4rem;
            }

            .error {
                font-size: 0.8rem;
                padding: 0.6rem;
                margin-top: 0.75rem;
            }

            .forgot-password {
                margin-top: 1rem;
                gap: 0.5rem;
            }

            .forgot-password a {
                font-size: 0.8rem;
                padding: 0.4rem 0;
            }
        }

        /* Touch-friendly adjustments */
        @media (hover: none) and (pointer: coarse) {
            button {
                min-height: 44px;
            }

            input {
                min-height: 44px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="accueil.php" class="back-arrow" aria-label="Retour à l'accueil">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <img src="img/image.png" alt="Sigma Logo" class="logo">
        <h2>Se connecter</h2>
        <p class="subtitle">Accédez à votre compte SIGMA Alumni</p>
        
        <?php if (!empty($error)): ?>
            <p class="error" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>
        
        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label for="email">Adresse email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="votre.email@exemple.com" 
                        required 
                        autocomplete="email"
                        aria-label="Adresse email"
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="Votre mot de passe" 
                        required 
                        autocomplete="current-password"
                        aria-label="Mot de passe"
                    >
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                </div>
            </div>
            
            <button type="submit">Se connecter</button>
        </form>
        
        <div class="forgot-password">
            <a href="verification.php">
                <i class="fas fa-user-plus"></i> Créer un compte
            </a>
            <a href="password_reset.php">
                <i class="fas fa-key"></i> Mot de passe oublié ?
            </a>
            <a href="yearbook_public.php" style="color: #667eea; margin-top: 10px; display: block; text-align: center;">
                <i class="fas fa-arrow-right"></i> Continuer sans compte
            </a>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>