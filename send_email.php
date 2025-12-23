<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envoie un email en utilisant PHPMailer
 * 
 * @param string $to Email du destinataire
 * @param string $recipientName Nom du destinataire
 * @param string $subject Sujet de l'email
 * @param string $body Contenu HTML de l'email
 * @param string|null $altBody Contenu texte alternatif
 * @return bool True si envoyé avec succès, false sinon
 */
function sendEmail($to, $recipientName, $subject, $body, $altBody = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Destinataires
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $recipientName);
        $mail->addReplyTo(SMTP_REPLY_TO_EMAIL, SMTP_REPLY_TO_NAME);
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?? strip_tags($body);
        
        $mail->send();
        error_log("Email envoyé avec succès à: $to - Sujet: $subject");
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email à $to: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Envoie une notification de confirmation de vote
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $election_title Titre de l'élection
 * @param array $positions Positions pour lesquelles l'utilisateur a voté
 * @return bool
 */
function sendVoteConfirmationEmail($user_id, $election_title, $positions) {
    global $conn;
    
    // Récupérer les informations de l'utilisateur et l'ID de l'élection
    $stmt = $conn->prepare("SELECT u.email, u.full_name, e.id as election_id 
                           FROM users u 
                           CROSS JOIN elections e 
                           WHERE u.id = ? AND e.title = ?");
    $stmt->bind_param("is", $user_id, $election_title);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        error_log("Utilisateur ID $user_id ou élection '$election_title' non trouvé");
        return false;
    }
    
    // Vérifier que l'email existe et est valide
    if (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        error_log("Email invalide ou manquant pour l'utilisateur ID $user_id: " . ($user['email'] ?? 'null'));
        return false;
    }
    
    $election_id = $user['election_id'];
    
    // Vérifier si l'email a déjà été envoyé
    $stmt = $conn->prepare("SELECT id FROM email_logs 
                           WHERE user_id = ? AND election_id = ? AND email_type = 'vote_confirmation'");
    $stmt->bind_param("ii", $user_id, $election_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        error_log("Email de confirmation déjà envoyé à l'utilisateur ID $user_id pour l'élection ID $election_id");
        return true; // Retourner true car l'email a déjà été envoyé
    }
    
    $positions_list = implode(', ', $positions);
    
    $subject = "Confirmation de votre vote - " . htmlspecialchars($election_title);
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
            .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
            .button { display: inline-block; padding: 12px 30px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .info-box { background: white; padding: 20px; border-left: 4px solid #2563eb; margin: 20px 0; border-radius: 5px; }
            .icon { font-size: 48px; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='icon'>✅</div>
                <h1>Vote Enregistré avec Succès</h1>
            </div>
            <div class='content'>
                <p>Bonjour <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
                
                <p>Nous vous confirmons que votre vote pour l'élection <strong>" . htmlspecialchars($election_title) . "</strong> a été enregistré avec succès.</p>
                
                <div class='info-box'>
                    <h3>📊 Détails de votre participation</h3>
                    <p><strong>Positions votées :</strong><br>" . htmlspecialchars($positions_list) . "</p>
                    <p><strong>Date et heure :</strong> " . date('d/m/Y à H:i') . "</p>
                </div>
                
                <p><strong>⚠️ Important :</strong></p>
                <ul>
                    <li>Votre vote est <strong>définitif et ne peut pas être modifié</strong></li>
                    <li>Les résultats seront publiés après la clôture du scrutin</li>
                    <li>Vous recevrez une notification lorsque les résultats seront disponibles</li>
                </ul>
                
                <p>Merci de votre participation à la vie démocratique de SIGMA Alumni !</p>
                
                <center>
                    <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Sigma-Website/elections.php' class='button'>Voir les détails de l'élection</a>
                </center>
            </div>
            <div class='footer'>
                <p>Cet email a été envoyé automatiquement par SIGMA Alumni</p>
                <p>Si vous n'avez pas voté, veuillez contacter immédiatement l'administration</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $altBody = "Bonjour {$user['full_name']},\n\nVotre vote pour l'élection \"{$election_title}\" a été enregistré avec succès.\n\nPositions votées : {$positions_list}\nDate : " . date('d/m/Y à H:i') . "\n\nVotre vote est définitif et ne peut pas être modifié.\n\nMerci de votre participation !\n\nSIGMA Alumni";
    
    $email_sent = sendEmail($user['email'], $user['full_name'], $subject, $body, $altBody);
    
    // Logger l'envoi dans la base de données
    if ($email_sent) {
        $stmt = $conn->prepare("INSERT INTO email_logs (user_id, election_id, email_type, email_address, status) 
                               VALUES (?, ?, 'vote_confirmation', ?, 'sent')");
        $stmt->bind_param("iis", $user_id, $election_id, $user['email']);
        $stmt->execute();
        $stmt->close();
    }
    
    return $email_sent;
}

/**
 * Envoie une notification de publication des résultats à tous les votants
 * 
 * @param int $election_id ID de l'élection
 * @return int Nombre d'emails envoyés
 */
function sendResultsNotificationEmails($election_id) {
    global $conn;
    
    // Récupérer les informations de l'élection
    $stmt = $conn->prepare("SELECT title FROM elections WHERE id = ?");
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $election = $result->fetch_assoc();
    $stmt->close();
    
    if (!$election) {
        return 0;
    }
    
    // Récupérer tous les utilisateurs qui ont voté ET qui ont un email valide ET qui n'ont pas encore reçu l'email
    $stmt = $conn->prepare("SELECT DISTINCT u.id, u.email, u.full_name 
                           FROM votes v 
                           JOIN users u ON v.user_id = u.id 
                           LEFT JOIN email_logs el ON el.user_id = u.id 
                               AND el.election_id = v.election_id 
                               AND el.email_type = 'results_notification'
                           WHERE v.election_id = ? 
                           AND u.email IS NOT NULL 
                           AND u.email != ''
                           AND el.id IS NULL");
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subject = "Résultats disponibles - " . htmlspecialchars($election['title']);
    
    $sent_count = 0;
    $failed_count = 0;
    
    while ($user = $result->fetch_assoc()) {
        // Valider l'email avant d'envoyer
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Email invalide ignoré pour l'utilisateur ID {$user['id']}: {$user['email']}");
            $failed_count++;
            continue;
        }
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
                .button { display: inline-block; padding: 12px 30px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .icon { font-size: 48px; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>📊</div>
                    <h1>Résultats de l'Élection Disponibles</h1>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
                    
                    <p>Les résultats de l'élection <strong>" . htmlspecialchars($election['title']) . "</strong> sont maintenant disponibles !</p>
                    
                    <p>Vous pouvez consulter les résultats complets, incluant la répartition des votes par position et les candidats élus.</p>
                    
                    <center>
                        <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Sigma-Website/elections.php#results' class='button'>Voir les Résultats</a>
                    </center>
                    
                    <p>Merci d'avoir participé à cette élection et contribué à la vie démocratique de SIGMA Alumni.</p>
                </div>
                <div class='footer'>
                    <p>Cet email a été envoyé automatiquement par SIGMA Alumni</p>
                    <p>Pour toute question, contactez l'administration</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $altBody = "Bonjour {$user['full_name']},\n\nLes résultats de l'élection \"{$election['title']}\" sont maintenant disponibles.\n\nConsultez-les sur : " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Sigma-Website/elections.php#results\n\nMerci de votre participation !\n\nSIGMA Alumni";
        
        if (sendEmail($user['email'], $user['full_name'], $subject, $body, $altBody)) {
            // Logger l'envoi dans la base de données
            $log_stmt = $conn->prepare("INSERT INTO email_logs (user_id, election_id, email_type, email_address, status) 
                                       VALUES (?, ?, 'results_notification', ?, 'sent')");
            $log_stmt->bind_param("iis", $user['id'], $election_id, $user['email']);
            $log_stmt->execute();
            $log_stmt->close();
            $sent_count++;
        } else {
            error_log("Échec d'envoi pour l'utilisateur ID {$user['id']} ({$user['email']})");
            $failed_count++;
        }
    }
    
    $stmt->close();
    
    error_log("Notifications de résultats - Envoyés: $sent_count, Échecs: $failed_count pour l'élection ID $election_id");
    
    return $sent_count;
}
?>
