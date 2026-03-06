<?php
require 'config.php';
require 'send_email.php';

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
    
    // New optional fields
    $profession = isset($_POST['profession']) ? sanitize($_POST['profession']) : null;
    $company = isset($_POST['company']) ? sanitize($_POST['company']) : null;
    $city = isset($_POST['city']) ? sanitize($_POST['city']) : null;
    $country = isset($_POST['country']) ? sanitize($_POST['country']) : null;
    $interests = isset($_POST['interests']) ? sanitize($_POST['interests']) : null;
    $linkedin_url = isset($_POST['linkedin_url']) ? sanitize($_POST['linkedin_url']) : null;

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
    $stmt = $conn->prepare("SELECT full_name, birth_date, bac_year, studies, tutorial_completed FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && !empty($user['full_name']) && !empty($user['birth_date']) && !empty($user['bac_year']) && !empty($user['studies'])) {
        $_SESSION['error'] = "Votre profil est déjà complet.";
        // Check if tutorial should be shown
        if (isset($user['tutorial_completed']) && $user['tutorial_completed'] == 0) {
            header("Location: dashboard.php?tutorial=1");
        } else {
            header("Location: dashboard.php");
        }
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
    $query = "UPDATE users SET full_name = ?, birth_date = ?, bac_year = ?, studies = ?, profession = ?, company = ?, city = ?, country = ?, interests = ?, linkedin_url = ?";
    $params = [$full_name, $birth_date, $bac_year, $studies, $profession, $company, $city, $country, $interests, $linkedin_url];
    $types = 'ssisssssss';

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
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'];
        
        // Email de bienvenue pour l'utilisateur
        $subject = "Bienvenue dans la Communauté SIGMA Alumni ! 🎉";
        
        $body = "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bienvenue</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 40px 30px; text-align: center; }
                .header h1 { margin: 10px 0 0 0; font-size: 24px; font-weight: 600; }
                .icon { font-size: 48px; margin-bottom: 10px; }
                .content { padding: 40px 30px; background-color: #ffffff; }
                .content p { margin: 15px 0; color: #374151; line-height: 1.8; }
                .button { display: inline-block; padding: 14px 32px; background: #10b981; color: white !important; text-decoration: none; border-radius: 6px; margin: 25px 0; font-weight: 500; }
                .features { background: #f0fdf4; border: 1px solid #10b981; padding: 25px; margin: 25px 0; border-radius: 6px; }
                .features ul { margin: 10px 0; padding-left: 25px; }
                .features li { margin: 10px 0; color: #065f46; }
                .footer { background: #f9fafb; padding: 30px; text-align: center; color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; }
                .footer p { margin: 8px 0; }
                .footer a { color: #10b981; text-decoration: none; }
                @media only screen and (max-width: 600px) {
                    .content, .header, .footer { padding: 20px !important; }
                }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='container'>
                    <div class='header'>
                        <div class='icon'>🎉</div>
                        <h1>Bienvenue dans SIGMA Alumni !</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour <strong>" . htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                        
                        <p>Félicitations ! Votre profil a été créé avec succès. Nous sommes ravis de vous accueillir dans la grande famille SIGMA Alumni.</p>
                        
                        <div class='features'>
                            <p style='margin: 0 0 15px 0; color: #065f46; font-weight: 600;'>🚀 Découvrez ce que vous pouvez faire :</p>
                            <ul>
                                <li>Consulter le Yearbook et retrouver vos anciens camarades</li>
                                <li>Participer aux élections et événements</li>
                                <li>Partager des souvenirs et des photos</li>
                                <li>Utiliser la messagerie pour rester connecté</li>
                                <li>Découvrir les opportunités professionnelles</li>
                            </ul>
                        </div>
                        
                        <p>Commençez votre expérience dès maintenant en explorant la plateforme :</p>
                        
                        <center>
                            <a href='$base_url/Sigma-Website/yearbook.php' class='button'>Explorer le Yearbook</a>
                        </center>
                        
                        <p style='color: #6b7280; font-size: 14px; margin-top: 30px;'>
                            💡 <strong>Astuce :</strong> Complétez votre profil avec une photo et vos informations professionnelles pour maximiser vos connexions.
                        </p>
                        
                        <p style='margin-top: 30px;'>
                            Cordialement,<br>
                            <strong>L'équipe SIGMA Alumni</strong>
                        </p>
                    </div>
                    <div class='footer'>
                        <p><strong>SIGMA Alumni</strong> - Communauté des anciens élèves</p>
                        <p>Restons connectés et construisons ensemble l'avenir.</p>
                        <p style='margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;'>
                            <a href='$base_url/Sigma-Website/dashboard.php'>Tableau de bord</a> | 
                            <a href='$base_url/Sigma-Website/settings.php'>Paramètres</a> | 
                            <a href='$base_url/Sigma-Website/contact.php'>Contact</a>
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $altBody = "BIENVENUE DANS SIGMA ALUMNI !\n\n" .
                   "Bonjour $full_name,\n\n" .
                   "Félicitations ! Votre profil a été créé avec succès. Nous sommes ravis de vous accueillir dans la grande famille SIGMA Alumni.\n\n" .
                   "DÉCOUVREZ CE QUE VOUS POUVEZ FAIRE\n" .
                   "• Consulter le Yearbook et retrouver vos anciens camarades\n" .
                   "• Participer aux élections et événements\n" .
                   "• Partager des souvenirs et des photos\n" .
                   "• Utiliser la messagerie pour rester connecté\n" .
                   "• Découvrir les opportunités professionnelles\n\n" .
                   "Explorez le Yearbook : $base_url/Sigma-Website/yearbook.php\n\n" .
                   "Astuce : Complétez votre profil avec une photo et vos informations professionnelles pour maximiser vos connexions.\n\n" .
                   "Cordialement,\n" .
                   "L'équipe SIGMA Alumni\n\n" .
                   "---\n" .
                   "SIGMA Alumni - Communauté des anciens élèves\n" .
                   "Restons connectés et construisons ensemble l'avenir.";
        
        // Send welcome email
        if (sendEmail($email, $full_name, $subject, $body, $altBody)) {
            $_SESSION['success'] = "Profil créé avec succès ! Un e-mail de bienvenue a été envoyé.";
        } else {
            $_SESSION['success'] = "Profil créé avec succès, mais l'e-mail de bienvenue n'a pas pu être envoyé.";
            error_log("Welcome email failed for: $email");
        }
        
        // Email de notification pour l'admin
        $admin_subject = "👤 Nouveau profil créé - SIGMA Alumni";
        
        $admin_body = "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Nouveau profil</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 40px 30px; text-align: center; }
                .header h1 { margin: 10px 0 0 0; font-size: 24px; font-weight: 600; }
                .icon { font-size: 48px; margin-bottom: 10px; }
                .content { padding: 40px 30px; background-color: #ffffff; }
                .info-box { background: #f9fafb; padding: 20px; border-left: 4px solid #3b82f6; margin: 25px 0; border-radius: 4px; }
                .info-box p { margin: 8px 0; color: #4b5563; }
                .footer { background: #f9fafb; padding: 30px; text-align: center; color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; }
                .label { font-weight: 600; color: #1f2937; }
                @media only screen and (max-width: 600px) {
                    .content, .header, .footer { padding: 20px !important; }
                }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='container'>
                    <div class='header'>
                        <div class='icon'>👤</div>
                        <h1>Nouveau Profil Créé</h1>
                    </div>
                    <div class='content'>
                        <p>Un nouveau membre vient de rejoindre SIGMA Alumni !</p>
                        
                        <div class='info-box'>
                            <p><span class='label'>👤 Nom complet :</span> " . htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') . "</p>
                            <p><span class='label'>📨 Email :</span> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>
                            <p><span class='label'>📅 Date d'inscription :</span> " . date('d/m/Y à H:i') . "</p>
                        </div>
                        
                        <p>Vous pouvez consulter et gérer ce profil depuis l'interface d'administration.</p>
                        
                        <p style='color: #6b7280; font-size: 14px; margin-top: 30px;'>
                            Notification automatique du système SIGMA Alumni
                        </p>
                    </div>
                    <div class='footer'>
                        <p><strong>SIGMA Alumni</strong> - Administration</p>
                        <p>Cet email a été généré automatiquement.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $admin_altBody = "NOUVEAU PROFIL CRÉÉ - SIGMA ALUMNI\n\n" .
                        "Un nouveau membre vient de rejoindre SIGMA Alumni !\n\n" .
                        "Nom complet : $full_name\n" .
                        "Email : $email\n" .
                        "Date d'inscription : " . date('d/m/Y à H:i') . "\n\n" .
                        "Vous pouvez consulter et gérer ce profil depuis l'interface d'administration.\n\n" .
                        "Notification automatique du système SIGMA Alumni";
        
        // Send admin notification
        sendEmail('gojomeh137@gmail.com', 'Administrateur', $admin_subject, $admin_body, $admin_altBody);

        // Check if this is the first connection (tutorial not completed)
        $stmt = $conn->prepare("SELECT tutorial_completed FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_tutorial = $result->fetch_assoc();
        $stmt->close();

        // Redirect with tutorial parameter if first time
        if ($user_tutorial && isset($user_tutorial['tutorial_completed']) && $user_tutorial['tutorial_completed'] == 0) {
            header("Location: dashboard.php?tutorial=1");
        } else {
            header("Location: dashboard.php");
        }
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