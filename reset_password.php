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

// Sanitization function
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags($conn->real_escape_string(trim($data))));
}

// Validate query parameters
$email = isset($_GET['email']) ? sanitize($_GET['email']) : '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($email) || empty($token)) {
    $_SESSION['error'] = "Lien de réinitialisation invalide.";
    header("Location: password_reset.php");
    exit;
}

// Verify token
$stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE email = ? AND token = ?");
$stmt->bind_param("ss", $email, $token);
$stmt->execute();
$result = $stmt->get_result();
$reset = $result->fetch_assoc();
$stmt->close();

if (!$reset || strtotime($reset['expires_at']) < time()) {
    $_SESSION['error'] = "Lien de réinitialisation invalide ou expiré.";
    header("Location: password_reset.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate new password
    $password = sanitize($_POST['password']);
    $confirm_password = sanitize($_POST['confirm_password']);

    if (empty($password) || strlen($password) < 8) {
        $_SESSION['error'] = "Le mot de passe doit comporter au moins 8 caractères.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update password in users table
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        if ($stmt->execute()) {
            // Delete the used token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();

            $_SESSION['success'] = "Votre mot de passe a été réinitialisé avec succès. Veuillez vous connecter à la Communauté Sigma.";
            header("Location: connexion.php");
            exit;
        } else {
            $_SESSION['error'] = "Erreur lors de la réinitialisation du mot de passe.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe</title>
    <?php include 'includes/favicon.php'; ?>
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
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .logo {
            width: 100px;
            margin-bottom: 20px;
        }
        h2 {
            color: #1e3a8a;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #1e3a8a;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #163172;
        }
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 10px;
        }
        .success {
            color: #2ecc71;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="img/image.png" alt="Sigma Logo" class="logo">
        <h2>Réinitialiser le mot de passe</h2>
        <?php if (isset($_SESSION['error'])) { ?>
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <input type="password" name="password" placeholder="Nouveau mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
            <button type="submit">Réinitialiser</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>