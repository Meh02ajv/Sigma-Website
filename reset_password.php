<?php
require 'config.php';
require 'send_email.php';

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
        // Vérifier le nombre de changements ce mois (limite: 2 par mois)
        $current_month = date('Y-m');
        $stmt = $conn->prepare("SELECT COUNT(*) as change_count FROM password_change_history 
                               WHERE email = ? AND DATE_FORMAT(changed_at, '%Y-%m') = ?");
        $stmt->bind_param("ss", $email, $current_month);
        $stmt->execute();
        $result = $stmt->get_result();
        $count_row = $result->fetch_assoc();
        $stmt->close();

        if ($count_row['change_count'] >= 2) {
            $_SESSION['error'] = "Vous avez déjà changé votre mot de passe 2 fois ce mois. Limite atteinte. Réessayez le mois prochain.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update password in users table
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            if ($stmt->execute()) {
                // Enregistrer le changement dans l'historique
                $stmt = $conn->prepare("INSERT INTO password_change_history (email, changed_at) VALUES (?, NOW())");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();

                // Delete the used token
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();

                // Récupérer le nom de l'utilisateur
                $stmt = $conn->prepare("SELECT full_name FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $user_result = $stmt->get_result();
                $user = $user_result->fetch_assoc();
                $stmt->close();
                $full_name = $user['full_name'] ?? 'Utilisateur';

                // Envoyer un email de confirmation
                $subject = "✅ Confirmation - Votre mot de passe a été modifié";
                $body = "
                <!DOCTYPE html>
                <html lang='fr'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Confirmation de changement de mot de passe</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                        .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                        .header { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 40px 30px; text-align: center; }
                        .header h1 { margin: 10px 0 0 0; font-size: 24px; font-weight: 600; }
                        .icon { font-size: 48px; margin-bottom: 10px; }
                        .content { padding: 40px 30px; background-color: #ffffff; }
                        .content p { margin: 15px 0; color: #374151; line-height: 1.8; }
                        .info-box { background: #f0fdf4; border-left: 4px solid #059669; padding: 15px 20px; margin: 25px 0; border-radius: 4px; }
                        .info-box p { margin: 5px 0; color: #166534; }
                        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px 20px; margin: 25px 0; border-radius: 4px; }
                        .warning p { margin: 5px 0; color: #92400e; }
                        .footer { background: #f9fafb; padding: 30px; text-align: center; color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; }
                        .footer p { margin: 8px 0; }
                        .footer a { color: #059669; text-decoration: none; }
                        @media only screen and (max-width: 600px) {
                            .content, .header, .footer { padding: 20px !important; }
                            .header h1 { font-size: 20px !important; }
                        }
                    </style>
                </head>
                <body>
                    <div class='email-wrapper'>
                        <div class='container'>
                            <div class='header'>
                                <div class='icon'>✅</div>
                                <h1>Mot de passe modifié</h1>
                            </div>
                            <div class='content'>
                                <p>Bonjour <strong>$full_name</strong>,</p>
                                <p>Votre mot de passe pour la Communauté Sigma a été modifié avec succès.</p>
                                <div class='info-box'>
                                    <p><strong>Date et heure :</strong> " . date('d/m/Y à H:i') . "</p>
                                </div>
                                <div class='warning'>
                                    <p><strong>⚠️ N'oubliez pas :</strong></p>
                                    <p>Si vous n'avez pas effectué ce changement, contactez immédiatement le support ou changez votre mot de passe.</p>
                                </div>
                                <p>Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
                                <p>Cordialement,<br><strong>L'équipe SIGMA Alumni</strong></p>
                            </div>
                            <div class='footer'>
                                <p>© 2025 Communauté Sigma. Tous droits réservés.</p>
                                <p><a href='https://sigma-alumni.com'>Visiter notre site</a></p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ";
                $altBody = "Bonjour $full_name, votre mot de passe a été modifié avec succès le " . date('d/m/Y à H:i') . ".";

                // Envoyer l'email (ne pas bloquer si erreur)
                sendEmail($email, $full_name, $subject, $body, $altBody);

                $_SESSION['success'] = "Votre mot de passe a été réinitialisé avec succès. Un email de confirmation a été envoyé. Veuillez vous connecter à la Communauté Sigma.";
                header("Location: connexion.php");
                exit;
            } else {
                $_SESSION['error'] = "Erreur lors de la réinitialisation du mot de passe.";
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
            padding: 10px 10px 10px 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .password-input-group {
            position: relative;
            width: 100%;
            margin-bottom: 10px;
        }

        .password-input-group input {
            width: 100%;
            margin-bottom: 0;
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #666;
            padding: 5px;
        }

        .toggle-password:hover {
            color: #1e3a8a;
        }
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
            <div class="password-input-group">
                <input type="password" id="password" name="password" placeholder="Nouveau mot de passe" required>
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">👁️</button>
            </div>
            <div class="password-input-group">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">👁️</button>
            </div>
            <button type="submit">Réinitialiser</button>
        </form>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const button = event.target;
            
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = '🙈';
            } else {
                field.type = 'password';
                button.textContent = '👁️';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>