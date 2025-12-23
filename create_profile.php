<?php
require 'config.php';
require 'vendor/autoload.php'; // Required for PHPMailer (remove if using manual installation)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Add sanitize function
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

if (!isset($_SESSION['user_email'])) {
    header("Location: dashboard.php");
    exit;
}

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: creation_profil.php");
        exit;
    }

    // Sanitize inputs
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $birth_date = sanitize($_POST['birth_date']);
    $bac_year = (int)sanitize($_POST['bac_year']);
    $studies = sanitize($_POST['studies']);

    // Validate inputs
    if (empty($full_name) || empty($birth_date) || empty($bac_year) || empty($studies)) {
        $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
        header("Location: creation_profil.php");
        exit;
    }

    // Validate email matches session
    if ($email !== $_SESSION['user_email']) {
        $_SESSION['error'] = "L'email ne peut pas être modifié.";
        header("Location: creation_profil.php");
        exit;
    }

    // Check if user already has a complete profile
    $stmt = $conn->prepare("SELECT full_name, birth_date, bac_year, studies FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && !empty($user['full_name']) && !empty($user['birth_date']) && !empty($user['bac_year']) && !empty($user['studies'])) {
        $_SESSION['error'] = "Votre profil est déjà complet.";
        header("Location: dashboard.php");
        exit;
    }

    // Handle file upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error'] = "Seuls les fichiers JPEG, PNG ou GIF sont autorisés.";
            header("Location: creation_profil.php");
            exit;
        }

        if ($file['size'] > $max_size) {
            $_SESSION['error'] = "L'image est trop grande (max 2MB).";
            header("Location: creation_profil.php");
            exit;
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $upload_path = 'Uploads/' . $filename;

        // Create Uploads directory if it doesn't exist
        if (!is_dir('Uploads')) {
            mkdir('Uploads', 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            $_SESSION['error'] = "Erreur lors du téléchargement de l'image.";
            header("Location: creation_profil.php");
            exit;
        }

        $profile_picture = $upload_path;
    }

    // Update user in database
    $query = "UPDATE users SET full_name = ?, birth_date = ?, bac_year = ?, studies = ?";
    $params = [$full_name, $birth_date, $bac_year, $studies];
    $types = 'ssis';

    if ($profile_picture) {
        $query .= ", profile_picture = ?";
        $params[] = $profile_picture;
        $types .= 's';
    }

    $query .= " WHERE email = ?";
    $params[] = $email;
    $types .= 's';

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // Profile saved successfully, send welcome email to user
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email, $full_name);
            $mail->addReplyTo(SMTP_REPLY_TO_EMAIL, SMTP_REPLY_TO_NAME);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Bienvenue sur la Communauté Sigma !';
            $mail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <img src='https://votre-domaine.com/img/image.png' alt='Sigma Logo' style='width: 100px;'>
                        <h2 style='color: #1e3a8a;'>Bonjour $full_name,</h2>
                        <p>Félicitations ! Votre profil a été créé avec succès.</p>
                        <p>Nous sommes ravis de vous accueillir dans la Communauté Sigma. Vous pouvez maintenant explorer notre <a href='https://votre-domaine.com/yearbook.php' style='color: #1e3a8a; text-decoration: underline;'>Yearbook</a>.</p>
                        <p style='color: #7f8c8d;'>Cordialement,<br>L'équipe de la Communauté Sigma</p>
                        <p style='font-size: 12px; color: #95a5a6;'>Cet e-mail est automatique, veuillez ne pas y répondre.</p>
                    </div>
                </body>
                </html>
            ";
            $mail->AltBody = "Bonjour $full_name,\n\nFélicitations ! Votre profil a été créé avec succès.\nNous sommes ravis de vous accueillir dans la Communauté Sigma. Explorez notre Yearbook : https://votre-domaine.com/yearbook.php\n\nCordialement,\nL'équipe de la Communauté Sigma";

            $mail->send();
            $_SESSION['success'] = "Profil créé avec succès ! Un e-mail de bienvenue a été envoyé.";
        } catch (Exception $e) {
            $_SESSION['success'] = "Profil créé avec succès, mais l'e-mail de bienvenue n'a pas pu être envoyé.";
            error_log("PHPMailer User Email Error: {$mail->ErrorInfo}", 3, "logs/email_errors.log");
        }

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
            $admin_mail->Subject = 'Nouveau profil créé sur la Communauté Sigma';
            $admin_mail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <img src='https://votre-domaine.com/img/image.png' alt='Sigma Logo' style='width: 100px;'>
                        <h2 style='color: #1e3a8a;'>Nouveau profil créé</h2>
                        <p>Un nouveau profil a été créé sur la Communauté Sigma.</p>
                        <p><strong>Nom complet :</strong> $full_name</p>
                        <p><strong>Email :</strong> $email</p>
                        <p>Vous pouvez consulter les détails dans l'interface d'administration.</p>
                        <p style='color: #7f8c8d;'>Cordialement,<br>L'équipe de la Communauté Sigma</p>
                        <p style='font-size: 12px; color: #95a5a6;'>Cet e-mail est automatique, veuillez ne pas y répondre.</p>
                    </div>
                </body>
                </html>
            ";
            $admin_mail->AltBody = "Nouveau profil créé sur la Communauté Sigma.\n\nNom complet : $full_name\nEmail : $email\n\nVous pouvez consulter les détails dans l'interface d'administration.\n\nCordialement,\nL'équipe de la Communauté Sigma";

            $admin_mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Admin Email Error: {$admin_mail->ErrorInfo}", 3, "logs/email_errors.log");
            // Don't interrupt user flow if admin email fails
        }

        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Erreur lors de la création du profil.";
        header("Location: creation_profil.php");
        exit;
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: creation_profil.php");
    exit;
}

$conn->close();
?>