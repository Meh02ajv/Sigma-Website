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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        :root {
            --sigma-blue: #1a237e;
            --sigma-gold: #d4af37;
            --sigma-light-blue: #e8eaf6;
            --sigma-dark: #0d0d0d;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, var(--sigma-light-blue), #f5f5f5);
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--sigma-dark);
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            margin: 1rem;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .logo-container {
            background-color: white;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 3px solid var(--sigma-gold);
        }

        .logo-container div {
            font-weight: 700;
            color: var(--sigma-blue);
            font-size: 1.5rem;
            text-transform: uppercase;
        }

        h1 {
            color: var(--sigma-blue);
            text-align: center;
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--sigma-dark);
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--sigma-blue);
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25);
            outline: none;
        }

        .form-group i {
            position: absolute;
            right: 1rem;
            top: 65%;
            transform: translateY(-50%);
            color: var(--sigma-blue);
            font-size: 1rem;
        }

        button {
            background-color: var(--sigma-blue);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(26, 35, 126, 0.2);
        }

        button:hover {
            background-color: var(--sigma-gold);
            color: var(--sigma-blue);
            transform: translateY(-2px);
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 0.5rem;
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <div>SIGMA</div>
        </div>
        <h1>Connexion Administrateur</h1>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <form method="POST" action="admin_login.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Entrez votre email" value="meh.ajavon@ashesi.edu.gh" required class="form-control">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" name="password" id="password" placeholder="Entrez votre mot de passe" required class="form-control">
                <i class="fas fa-lock"></i>
            </div>
            <button type="submit">
                <i class="fas fa-sign-in-alt mr-2"></i> Se connecter
            </button>
        </form>
    </div>
</body>
</html>