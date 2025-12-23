<?php
// Include config file
require 'config.php';
require 'vendor/autoload.php'; // Required for PHPMailer (remove if using manual installation)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode de requête non autorisée.";
    header("Location: suggestion.php");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Erreur de validation du formulaire. Veuillez réessayer.";
    header("Location: suggestion.php");
    exit;
}

// Unset CSRF token after validation
unset($_SESSION['csrf_token']);

// Sanitize and validate inputs
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$suggestion = filter_var($_POST['suggestion'], FILTER_SANITIZE_STRING);

// Validate required fields
if (empty($email) || empty($suggestion)) {
    $_SESSION['error'] = "Tous les champs sont obligatoires.";
    header("Location: suggestion.php");
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Adresse e-mail invalide.";
    header("Location: suggestion.php");
    exit;
}

// Connect to the database
try {
    $pdo = new PDO("mysql:host=localhost;dbname=laho", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de connexion à la base de données : " . $e->getMessage();
    header("Location: suggestion.php");
    exit;
}

// Insert suggestion into database
try {
    $stmt = $pdo->prepare("INSERT INTO suggestions (email, suggestion, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$email, $suggestion]);

    // Send notification email to admin
    $admin_mail = new PHPMailer(true);
    try {
        // Server settings
        $admin_mail->isSMTP();
        $admin_mail->Host = SMTP_HOST;
        $admin_mail->SMTPAuth = true;
        $admin_mail->Username = SMTP_USERNAME;
        $admin_mail->Password = SMTP_PASSWORD;
        $admin_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $admin_mail->Port = SMTP_PORT;

        // Recipients
        $admin_mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $admin_mail->addAddress('gojomeh137@gmail.com', 'Administrateur');
        $admin_mail->addReplyTo(SMTP_REPLY_TO_EMAIL, SMTP_REPLY_TO_NAME);

        // Content
        $admin_mail->isHTML(true);
        $admin_mail->Subject = 'Nouvelle suggestion sur la Communauté Sigma';
        $admin_mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <img src='https://votre-domaine.com/img/image.png' alt='Sigma Logo' style='width: 100px;'>
                    <h2 style='color: #1e3a8a;'>Nouvelle suggestion</h2>
                    <p>Une nouvelle suggestion a été soumise sur la Communauté Sigma.</p>
                    <p><strong>Soumis par :</strong> $email</p>
                    <p><strong>Suggestion :</strong> $suggestion</p>
                    <p>Vous pouvez consulter les détails dans l'interface d'administration.</p>
                    <p style='color: #7f8c8d;'>Cordialement,<br>L'équipe de la Communauté Sigma</p>
                    <p style='font-size: 12px; color: #95a5a6;'>Cet e-mail est automatique, veuillez ne pas y répondre.</p>
                </div>
            </body>
            </html>
        ";
        $admin_mail->AltBody = "Nouvelle suggestion sur la Communauté Sigma.\n\nSoumis par : $email\nSuggestion : $suggestion\n\nVous pouvez consulter les détails dans l'interface d'administration.\n\nCordialement,\nL'équipe de la Communauté Sigma";

        $admin_mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Admin Email Error: {$admin_mail->ErrorInfo}", 3, "logs/email_errors.log");
        // Don't interrupt user flow if admin email fails
    }

    $_SESSION['success'] = "Suggestion envoyée avec succès.";
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de l'enregistrement de la suggestion : " . $e->getMessage();
    header("Location: suggestion.php");
    exit;
}

// Redirect to settings page
header("Location: settings.php");
exit;
?>