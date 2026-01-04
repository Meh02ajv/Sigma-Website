<?php
require 'config.php';

// Hardcoded admin credentials
define('ADMIN_EMAIL', 'meh.ajavon@ashesi.edu.gh');
define('ADMIN_PASSWORD', 'KOBAhariel123');

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
    error_log("Session started in admin_login.php");
} else {
    error_log("Session already started in admin_login.php");
}

// Generate CSRF token for the login form
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        error_log("CSRF validation failed for login attempt");
        header("Location: admin_login.php");
        exit;
    }

    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Debug: Log submitted credentials
    error_log("Login attempt - Email: $email");

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Veuillez entrer un email et un mot de passe.";
        error_log("Empty email or password submitted");
    } elseif ($email !== ADMIN_EMAIL || $password !== ADMIN_PASSWORD) {
        $_SESSION['error'] = "Email ou mot de passe incorrect.";
        error_log("Credentials mismatch: Email=$email, Password=$password");
    } else {
        $_SESSION['admin_logged_in'] = true;
        error_log("Login successful for $email");
        // Regenerate CSRF token after successful login
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: admin.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - Communauté Sigma</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <?php require 'includes/favicon.php'; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #042632ff;
            --secondary-color: #3498db;
            --accent-color: #d4af37;
            --dark-color: #2c3e50;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --danger: #e74c3c;
            --success: #27ae60;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        .login-container {
            max-width: 480px;
            width: 100%;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3949ab 100%);
            padding: 3rem 2rem 2rem;
            text-align: center;
        }

        .logo-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
        }

        h1 {
            color: var(--white);
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            font-weight: 300;
        }

        .login-body {
            padding: 2.5rem 2rem;
        }

        .alert-danger {
            background: #fee;
            color: var(--danger);
            border-left: 4px solid var(--danger);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-danger i {
            font-size: 1.2rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-bg);
            font-family: 'Open Sans', sans-serif;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            outline: none;
            background: var(--white);
        }

        .form-control::placeholder {
            color: #999;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--secondary-color);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
            color: var(--white);
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.5);
        }

        @media (max-width: 576px) {
            .login-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .logo-container {
                width: 100px;
                height: 100px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .login-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <?php 
                // Récupérer le logo admin depuis la base de données
                $admin_logo = 'img/image.png'; // Valeur par défaut
                $stmt = $conn->prepare("SELECT setting_value FROM general_config WHERE setting_key = 'admin_logo'");
                if ($stmt) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $admin_logo = $row['setting_value'];
                    }
                    $stmt->close();
                }
                
                // Vérifier si le fichier existe
                if (!file_exists($admin_logo)) {
                    $admin_logo = 'img/image.png';
                }
                ?>
                <img src="<?php echo htmlspecialchars($admin_logo); ?>" alt="Logo SIGMA" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display: none; color: white; font-size: 2rem; font-weight: 700; text-align: center; line-height: 120px;">SIGMA</div>
            </div>
            <h1>Administration</h1>
            <p class="subtitle">Espace réservé aux administrateurs</p>
        </div>
        
        <div class="login-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form method="POST" action="admin_login.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" placeholder="admin@sigma.com" value="meh.ajavon@ashesi.edu.gh" required class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" placeholder="••••••••" required class="form-control">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Se connecter</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>