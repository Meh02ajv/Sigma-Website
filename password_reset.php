<?php
require 'config.php';
require 'vendor/autoload.php'; // Replace with manual includes if not using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    // Send reset email using PHPMailer
    $reset_link = "https://votre-domaine.com/reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token);
    $full_name = $user['full_name'] ?: 'Utilisateur';

    $mail = new PHPMailer(true);
    try {
        // Server settings (Gmail)
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $full_name);
        $mail->addReplyTo(SMTP_REPLY_TO_EMAIL, SMTP_REPLY_TO_NAME);
        
        // Attacher le logo
        $logo_path = __DIR__ . '/img/image.png';
        if (file_exists($logo_path)) {
            $mail->addEmbeddedImage($logo_path, 'logo', 'logo.png');
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Réinitialisation de votre mot de passe';
        $mail->Body = "
            <html>
            <head><meta charset='UTF-8'></head>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <img src='cid:logo' alt='Sigma Logo' style='width: 100px;'>
                    <h2 style='color: #1e3a8a;'>Bonjour $full_name,</h2>
                    <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le lien ci-dessous pour procéder :</p>
                    <p><a href='$reset_link' style='color: #1e3a8a; text-decoration: underline;'>Réinitialiser mon mot de passe</a></p>
                    <p>Ce lien est valable pendant 1 heure. Si vous n'avez pas demandé cette réinitialisation, ignorez cet e-mail.</p>
                    <p style='color: #7f8c8d;'>Cordialement,<br>L'équipe de la plateforme</p>
                    <p style='font-size: 12px; color: #95a5a6;'>Cet e-mail est automatique, veuillez ne pas y répondre.</p>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Bonjour $full_name,\n\nVous avez demandé à réinitialiser votre mot de passe. Cliquez sur le lien suivant pour procéder :\n$reset_link\n\nCe lien est valable pendant 1 heure. Si vous n'avez pas demandé cette réinitialisation, ignorez cet e-mail.\n\nCordialement,\nL'équipe de la plateforme";

        $mail->send();
        $_SESSION['reset_email'] = "Un lien de réinitialisation a été envoyé à votre adresse e-mail.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de l'envoi de l'e-mail de réinitialisation.";
        error_log("PHPMailer Error: {$mail->ErrorInfo}", 3, "logs/email_errors.log");
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