<?php
require 'config.php';
require 'send_email.php';

// Sanitization function
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate email
    $email = sanitize($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Adresse e-mail invalide.";
        header("Location: password_reset.php");
        exit;
    }

    // Check if email exists in users table
    $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $_SESSION['error'] = "Aucun compte associé à cet e-mail.";
        header("Location: password_reset.php");
        exit;
    }

    // Generate a secure reset token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

    // Store token in password_resets table
    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
    $stmt->bind_param("sssss", $email, $token, $expires_at, $token, $expires_at);
    if (!$stmt->execute()) {
        $_SESSION['error'] = "Erreur lors de la génération du lien de réinitialisation.";
        header("Location: password_reset.php");
        exit;
    }
    $stmt->close();

    // Générer le lien de réinitialisation
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'];
    $reset_link = $base_url . "/Sigma-Website/reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token);
    $full_name = $user['full_name'] ?: 'Utilisateur';

    // Template HTML professionnel avec bouton cliquable
    $subject = "Réinitialisation de votre mot de passe - SIGMA Alumni";
    
    $body = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Réinitialisation de mot de passe</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 40px 30px; text-align: center; }
            .header h1 { margin: 10px 0 0 0; font-size: 24px; font-weight: 600; }
            .icon { font-size: 48px; margin-bottom: 10px; }
            .content { padding: 40px 30px; background-color: #ffffff; }
            .content p { margin: 15px 0; color: #374151; line-height: 1.8; }
            .button-container { text-align: center; margin: 35px 0; }
            .button { display: inline-block; padding: 16px 40px; background: #dc2626; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; }
            .button:hover { background: #b91c1c; }
            .warning-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px 20px; margin: 25px 0; border-radius: 4px; }
            .warning-box p { margin: 5px 0; color: #92400e; }
            .footer { background: #f9fafb; padding: 30px; text-align: center; color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; }
            .footer p { margin: 8px 0; }
            .footer a { color: #dc2626; text-decoration: none; }
            .link-text { word-break: break-all; background: #f3f4f6; padding: 10px; border-radius: 4px; font-size: 12px; color: #6b7280; margin-top: 15px; }
            @media only screen and (max-width: 600px) {
                .content, .header, .footer { padding: 20px !important; }
                .button { padding: 14px 30px; font-size: 14px; }
            }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>🔐</div>
                    <h1>Réinitialisation de Mot de Passe</h1>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>" . htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                    
                    <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte SIGMA Alumni.</p>
                    
                    <p>Pour réinitialiser votre mot de passe, cliquez sur le bouton ci-dessous :</p>
                    
                    <div class='button-container'>
                        <a href='" . htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') . "' class='button'>Réinitialiser mon Mot de Passe</a>
                    </div>
                    
                    <p style='font-size: 13px; color: #6b7280;'>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                    <div class='link-text'>" . htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') . "</div>
                    
                    <div class='warning-box'>
                        <p><strong>⚠️ Important :</strong></p>
                        <p>• Ce lien est valable pendant <strong>1 heure</strong></p>
                        <p>• Pour des raisons de sécurité, le lien ne peut être utilisé qu'une seule fois</p>
                        <p>• Si vous n'avez pas demandé cette réinitialisation, ignorez cet email</p>
                    </div>
                    
                    <p>Si vous rencontrez des difficultés, n'hésitez pas à contacter notre équipe support.</p>
                    
                    <p style='color: #6b7280; font-size: 14px; margin-top: 30px;'>
                        Cordialement,<br>
                        <strong>L'équipe SIGMA Alumni</strong>
                    </p>
                </div>
                <div class='footer'>
                    <p><strong>SIGMA Alumni</strong> - Communauté des anciens élèves</p>
                    <p>Cet email a été envoyé automatiquement suite à votre demande de réinitialisation.</p>
                    <p style='margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;'>
                        <a href='$base_url/Sigma-Website/settings.php'>Gérer vos préférences</a> | 
                        <a href='$base_url/Sigma-Website/contact.php'>Nous contacter</a>
                    </p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Version texte alternative détaillée
    $altBody = "RÉINITIALISATION DE MOT DE PASSE - SIGMA ALUMNI\n\n" .
               "Bonjour $full_name,\n\n" .
               "Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte SIGMA Alumni.\n\n" .
               "Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :\n" .
               "$reset_link\n\n" .
               "INFORMATIONS IMPORTANTES\n" .
               "• Ce lien est valable pendant 1 heure\n" .
               "• Pour des raisons de sécurité, le lien ne peut être utilisé qu'une seule fois\n" .
               "• Si vous n'avez pas demandé cette réinitialisation, ignorez cet email\n\n" .
               "Si vous rencontrez des difficultés, contactez notre équipe support.\n\n" .
               "Cordialement,\n" .
               "L'équipe SIGMA Alumni\n\n" .
               "---\n" .
               "SIGMA Alumni - Communauté des anciens élèves\n" .
               "Cet email a été envoyé automatiquement suite à votre demande.\n" .
               "Gérer vos préférences : $base_url/Sigma-Website/settings.php";
    
    // Utiliser la fonction sendEmail optimisée
    if (sendEmail($email, $full_name, $subject, $body, $altBody)) {
        $_SESSION['reset_email'] = "Un lien de réinitialisation a été envoyé à votre adresse e-mail.";
    } else {
        $_SESSION['error'] = "Erreur lors de l'envoi de l'e-mail de réinitialisation.";
        error_log("Password reset email failed for: $email");
    }

    header("Location: password_reset.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Récupération du mot de passe</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('img/2024.jpg');
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            background-size: cover;
        }
        .container {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .back-arrow {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #1e3a8a;
            text-decoration: none;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .back-arrow:hover {
            color: #d4af37;
            transform: scale(1.1);
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
            border: Typographe1px solid #ccc;
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
        .success {
            color: #2ecc71;
            font-size: 14px;
            margin-top: 10px;
        }
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="connexion.php" class="back-arrow" aria-label="Retour"><i class="fas fa-arrow-left"></i></a>
        <img src="img/image.png" alt="Sigma Logo" class="logo">
        <h2>Récupération du mot de passe</h2>
        <?php if (isset($_SESSION['error'])) { ?>
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php } ?>
        <?php if (isset($_SESSION['reset_email'])) { ?>
            <p class="success"><?php echo $_SESSION['reset_email']; unset($_SESSION['reset_email']); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Votre adresse email" required>
            <button type="submit">Envoyer</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>