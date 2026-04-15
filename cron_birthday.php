<?php
/**
 * Script CRON pour l'envoi automatique des emails d'anniversaire
 * À exécuter quotidiennement (par exemple à 8h du matin)
 * 
 * Configuration Windows Task Scheduler:
 * Commande: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\Sigma-Website\cron_birthday.php
 * Heure: 08:00 tous les jours
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log file pour tracer les exécutions
$log_file = __DIR__ . '/logs/birthday_cron.log';
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("=== Début du script d'anniversaires ===");

try {
    // Utilisez la connexion existante de config.php
    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion : " . $conn->connect_error);
    }
    
    logMessage("Connexion à la base de données réussie");
    
    // Date actuelle et date dans 2 jours
    $now = new DateTime();
    $in_two_days = (new DateTime())->add(new DateInterval('P2D'));
    
    // Formater les dates pour la comparaison (MM-DD)
    $current_month_day = $now->format('m-d');
    $in_two_days_month_day = $in_two_days->format('m-d');
    
    logMessage("Date actuelle : " . $now->format('Y-m-d') . " (MM-DD: $current_month_day)");
    logMessage("Date dans 2 jours : " . $in_two_days->format('Y-m-d') . " (MM-DD: $in_two_days_month_day)");
    
    // 1. Récupérer les utilisateurs dont c'est l'anniversaire AUJOURD'HUI
    $query = "SELECT id, full_name, email, birth_date FROM users 
              WHERE DATE_FORMAT(birth_date, '%m-%d') = ? AND email IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $current_month_day);
    $stmt->execute();
    $result = $stmt->get_result();
    $birthday_today = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    logMessage("Anniversaires aujourd'hui : " . count($birthday_today));
    
    // 2. Récupérer les utilisateurs dont l'anniversaire est dans 2 JOURS
    $query = "SELECT id, full_name, email, birth_date FROM users 
              WHERE DATE_FORMAT(birth_date, '%m-%d') = ? AND email IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $in_two_days_month_day);
    $stmt->execute();
    $result = $stmt->get_result();
    $birthday_in_two_days = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    logMessage("Anniversaires dans 2 jours : " . count($birthday_in_two_days));
    
    // 3. Récupérer tous les autres utilisateurs pour les notifications
    $query = "SELECT id, full_name, email FROM users WHERE email IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Fonction pour créer un email avec PHPMailer
    function createMailer() {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, 'SIGMA Alumni');
        return $mail;
    }
    
    // Fonction pour envoyer un email d'anniversaire personnalisé à la personne
    function sendBirthdayEmailToPerson($user) {
        try {
            $subject = '🎉 Joyeux Anniversaire ' . $user['full_name'] . ' !';
            
            $age = (new DateTime())->diff(new DateTime($user['birth_date']))->y;
            
            $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #667eea; font-size: 32px; margin: 0; }
        .emoji { font-size: 60px; margin: 20px 0; }
        .content { text-align: center; }
        .content p { font-size: 18px; line-height: 1.8; margin: 15px 0; }
        .highlight { color: #667eea; font-weight: bold; font-size: 24px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0; color: #777; font-size: 14px; }
        .button { display: inline-block; margin: 20px 0; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white !important; text-decoration: none; border-radius: 25px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="emoji">🎂🎉🎈</div>
                <h1>Joyeux Anniversaire !</h1>
            </div>
            <div class="content">
                <p>Cher(e) <strong>{$user['full_name']}</strong>,</p>
                <p class="highlight">🎊 Bon anniversaire pour vos {$age} ans ! 🎊</p>
                <p>Toute l'équipe SIGMA Alumni vous souhaite une merveilleuse journée remplie de joie, de bonheur et de belles surprises !</p>
                <p>Que cette nouvelle année soit riche en réussites personnelles et professionnelles. 🌟</p>
                <p>Profitez bien de cette journée spéciale qui vous est dédiée !</p>
                <a href="https://sigmawebsite.rf.gd/dashboard.php" class="button">Accéder à votre compte</a>
            </div>
            <div class="footer">
                <p>Avec toute notre amitié,<br><strong>L'équipe SIGMA Alumni</strong></p>
                <p style="font-size: 12px; color: #999;">Cet email a été envoyé automatiquement. Veuillez ne pas y répondre.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
            $altBody = "Cher(e) {$user['full_name']},\n\nJoyeux anniversaire pour vos {$age} ans !\n\nToute l'équipe SIGMA Alumni vous souhaite une merveilleuse journée !\n\nL'équipe SIGMA Alumni";
            
            if (sendEmail($user['email'], $user['full_name'], $subject, $body, $altBody)) {
                logMessage("✓ Email d'anniversaire envoyé à {$user['full_name']} ({$user['email']})");
                return true;
            }
            return false;
        } catch (Exception $e) {
            logMessage("✗ Erreur envoi email à {$user['full_name']}: " . $e->getMessage());
            return false;
        }
    }
    
    // Fonction pour envoyer une notification aux autres membres
    function sendBirthdayNotificationToOthers($birthday_user, $recipient, $is_reminder) {
        try {
            if ($is_reminder) {
                $subject = "🔔 Rappel : Anniversaire de {$birthday_user['full_name']} dans 2 jours";
                $title = "Rappel d'anniversaire";
                $message = "Dans <strong>2 jours</strong>, ce sera l'anniversaire de <strong>{$birthday_user['full_name']}</strong> !";
                $emoji = "⏰";
            } else {
                $subject = "🎉 Aujourd'hui c'est l'anniversaire de {$birthday_user['full_name']} !";
                $title = "C'est l'anniversaire !";
                $message = "Aujourd'hui c'est l'anniversaire de <strong>{$birthday_user['full_name']}</strong> !";
                $emoji = "🎂";
            }
            
            $mail->isHTML(true);
            $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .card { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; }
        .emoji { font-size: 50px; margin: 10px 0; }
        .content p { font-size: 16px; line-height: 1.8; }
        .footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; color: #777; font-size: 13px; }
        .button { display: inline-block; margin: 15px 0; padding: 12px 25px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="emoji">{$emoji}</div>
                <h2 style="color: #667eea; margin: 0;">{$title}</h2>
            </div>
            <div class="content">
                <p>Bonjour <strong>{$recipient['full_name']}</strong>,</p>
                <p>{$message}</p>
                <p>Pensez à lui souhaiter un joyeux anniversaire sur la plateforme SIGMA Alumni ! 🎈</p>
                <a href="http://localhost/Sigma-Website/messaging.php?user_id={$birthday_user['id']}" class="button">Envoyer un message</a>
            </div>
            <div class="footer">
                <p>L'équipe SIGMA Alumni</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
            
            $mail->AltBody = "Bonjour {$recipient['full_name']},\n\n{$message}\nPensez à lui souhaiter un joyeux anniversaire !\n\nL'équipe SIGMA Alumni";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    $total_sent = 0;
    
    // JOUR J : SEULEMENT LA PERSONNE CONCERNÉE REÇOIT LE MESSAGE D'ANNIVERSAIRE
    foreach ($birthday_today as $user) {
        if (sendBirthdayEmailToPerson($user)) {
            $total_sent++;
        }
        logMessage("  → Jour J: email envoyé uniquement à {$user['full_name']}");
    }
    
    // J-2 : ENVOYER LES RAPPELS À TOUS LES UTILISATEURS SAUF L'ANNIVERSAIRÉ
    foreach ($birthday_in_two_days as $user) {
        $notified = 0;
        foreach ($all_users as $recipient) {
            if ($recipient['id'] != $user['id']) {
                if (sendBirthdayNotificationToOthers($user, $recipient, true)) {
                    $notified++;
                }
            }
        }
        logMessage("  → {$notified} rappels envoyés pour {$user['full_name']}");
    }
    
    logMessage("=== Total d'emails d'anniversaire envoyés : $total_sent ===");
    logMessage("=== Script terminé avec succès ===\n");
    
    // Connexion gérée par config.php, ne pas fermer
    
} catch (Exception $e) {
    logMessage("ERREUR FATALE : " . $e->getMessage());
    logMessage("=== Script terminé avec erreur ===\n");
}
