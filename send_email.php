<?php
/**
 * SYSTÈME D'ENVOI D'EMAILS - SMTP/PHPMailer
 * 
 * Utilise PHPMailer pour envoyer des emails via SMTP.
 * Compatible avec Gmail, Outlook, et autres serveurs SMTP.
 * 
 * @package SigmaAlumni
 * @version 1.0.0 - SMTP
 */

require_once 'config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envoie un email en utilisant PHPMailer avec SMTP
 * 
 * @param string $to Email du destinataire
 * @param string $recipientName Nom du destinataire
 * @param string $subject Sujet de l'email
 * @param string $body Contenu HTML de l'email
 * @param string|null $altBody Contenu texte alternatif (optionnel)
 * @return bool True si envoyé avec succès, false sinon
 */
function sendEmail($to, $recipientName, $subject, $body, $altBody = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Configuration anti-spam
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            )
        );
        
        // Timeout
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        // Headers
        $mail->XMailer = ' ';
        $mail->Priority = 3;
        
        // Destinataires
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $recipientName);
        $mail->addReplyTo(SMTP_REPLY_TO_EMAIL, SMTP_REPLY_TO_NAME);
        
        // Headers personnalisés
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('X-Entity-ID', SMTP_FROM_EMAIL);
        $mail->addCustomHeader('Return-Path', SMTP_FROM_EMAIL);
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?? strip_tags($body);
        
        $mail->send();
        error_log("Email SMTP envoyé avec succès à: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur sendEmail SMTP: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envoie un email de confirmation de vote
 * 
 * @param string $to Email du votant
 * @param string $recipientName Nom du votant
 * @param string $electionTitle Titre de l'élection
 * @return bool
 */
function sendVoteConfirmationEmail($to, $recipientName, $electionTitle) {
    $subject = "✅ Confirmation de vote - $electionTitle";
    
    $body = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Confirmation de vote</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='margin: 0; font-size: 24px;'>✅ Vote enregistré !</h1>
        </div>
        <div style='background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;'>
            <p>Bonjour <strong>" . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            
            <p>Nous confirmons que votre vote pour <strong>" . htmlspecialchars($electionTitle, ENT_QUOTES, 'UTF-8') . "</strong> a été enregistré avec succès.</p>
            
            <div style='background: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                <p style='margin: 0; color: #166534;'><strong>ℹ️ Important :</strong> Votre vote est anonyme et confidentiel.</p>
            </div>
            
            <p>Les résultats seront publiés une fois le scrutin clôturé.</p>
            
            <p style='margin-top: 30px;'>Merci de votre participation !</p>
            
            <p style='color: #6b7280; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                <strong>SIGMA Alumni</strong><br>
                Communauté des anciens élèves
            </p>
        </div>
    </body>
    </html>
    ";
    
    $altBody = "VOTE ENREGISTRÉ\n\n" .
               "Bonjour $recipientName,\n\n" .
               "Nous confirmons que votre vote pour \"$electionTitle\" a été enregistré avec succès.\n\n" .
               "Important : Votre vote est anonyme et confidentiel.\n\n" .
               "Les résultats seront publiés une fois le scrutin clôturé.\n\n" .
               "Merci de votre participation !\n\n" .
               "---\n" .
               "SIGMA Alumni - Communauté des anciens élèves";
    
    return sendEmail($to, $recipientName, $subject, $body, $altBody);
}

/**
 * Envoie des notifications de résultats aux votants
 * 
 * @param int $election_id ID de l'élection
 * @param mysqli $conn Connexion à la base de données
 * @return array Statistiques d'envoi ['sent' => int, 'failed' => int]
 */
function sendResultsNotificationEmails($election_id, $conn) {
    $stats = ['sent' => 0, 'failed' => 0];
    
    try {
        // Récupérer les infos de l'élection
        $stmt = $conn->prepare("SELECT title FROM elections WHERE id = ?");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $election = $result->fetch_assoc();
        $stmt->close();
        
        if (!$election) {
            error_log("Élection $election_id introuvable");
            return $stats;
        }
        
        // Récupérer les votants
        $stmt = $conn->prepare("
            SELECT DISTINCT u.email, u.full_name 
            FROM votes v
            JOIN users u ON v.user_id = u.id
            WHERE v.election_id = ?
        ");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                    "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $results_url = $base_url . "/Sigma-Website/elections.php?id=$election_id";
        
        while ($voter = $result->fetch_assoc()) {
            $subject = "📊 Résultats publiés - " . $election['title'];
            
            $body = "
            <!DOCTYPE html>
            <html lang='fr'>
            <head>
                <meta charset='UTF-8'>
                <title>Résultats publiés</title>
            </head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>📊 Résultats Publiés</h1>
                </div>
                <div style='background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;'>
                    <p>Bonjour <strong>" . htmlspecialchars($voter['full_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                    
                    <p>Les résultats de l'élection <strong>" . htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') . "</strong> sont maintenant disponibles !</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$results_url' style='display: inline-block; background: #8b5cf6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                            Voir les résultats
                        </a>
                    </div>
                    
                    <p style='color: #6b7280; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                        <strong>SIGMA Alumni</strong><br>
                        Merci de votre participation !
                    </p>
                </div>
            </body>
            </html>
            ";
            
            $altBody = "RÉSULTATS PUBLIÉS\n\n" .
                      "Bonjour {$voter['full_name']},\n\n" .
                      "Les résultats de l'élection \"{$election['title']}\" sont maintenant disponibles !\n\n" .
                      "Voir les résultats : $results_url\n\n" .
                      "---\n" .
                      "SIGMA Alumni - Merci de votre participation !";
            
            if (sendEmail($voter['email'], $voter['full_name'], $subject, $body, $altBody)) {
                $stats['sent']++;
            } else {
                $stats['failed']++;
            }
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Erreur sendResultsNotificationEmails: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Envoie des notifications de début de vote
 * 
 * @param int $election_id ID de l'élection
 * @param mysqli $conn Connexion à la base de données
 * @return array Statistiques d'envoi
 */
function sendVotingStartNotificationEmails($election_id, $conn) {
    $stats = ['sent' => 0, 'failed' => 0];
    
    try {
        // Récupérer l'élection
        $stmt = $conn->prepare("SELECT title, start_date, end_date FROM elections WHERE id = ?");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $election = $result->fetch_assoc();
        $stmt->close();
        
        if (!$election) {
            return $stats;
        }
        
        // Récupérer tous les utilisateurs actifs
        $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE full_name IS NOT NULL AND full_name != ''");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                    "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $vote_url = $base_url . "/Sigma-Website/elections.php?id=$election_id";
        
        while ($user = $result->fetch_assoc()) {
            $subject = "🗳️ Nouvelle élection - " . $election['title'];
            
            $body = "
            <!DOCTYPE html>
            <html lang='fr'>
            <head>
                <meta charset='UTF-8'>
                <title>Nouvelle élection</title>
            </head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>🗳️ Les votes sont ouverts !</h1>
                </div>
                <div style='background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;'>
                    <p>Bonjour <strong>" . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                    
                    <p>Une nouvelle élection vient de commencer : <strong>" . htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') . "</strong></p>
                    
                    <div style='background: #f0f9ff; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>📅 Début :</strong> " . date('d/m/Y à H:i', strtotime($election['start_date'])) . "</p>
                        <p style='margin: 5px 0;'><strong>⏰ Fin :</strong> " . date('d/m/Y à H:i', strtotime($election['end_date'])) . "</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$vote_url' style='display: inline-block; background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                            Voter maintenant
                        </a>
                    </div>
                    
                    <p style='color: #6b7280; font-size: 14px;'>Votre voix compte ! Participez dès maintenant.</p>
                    
                    <p style='color: #6b7280; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                        <strong>SIGMA Alumni</strong><br>
                        Communauté des anciens élèves
                    </p>
                </div>
            </body>
            </html>
            ";
            
            $altBody = "LES VOTES SONT OUVERTS !\n\n" .
                      "Bonjour {$user['full_name']},\n\n" .
                      "Une nouvelle élection vient de commencer : {$election['title']}\n\n" .
                      "Début : " . date('d/m/Y à H:i', strtotime($election['start_date'])) . "\n" .
                      "Fin : " . date('d/m/Y à H:i', strtotime($election['end_date'])) . "\n\n" .
                      "Voter maintenant : $vote_url\n\n" .
                      "Votre voix compte ! Participez dès maintenant.\n\n" .
                      "---\n" .
                      "SIGMA Alumni - Communauté des anciens élèves";
            
            if (sendEmail($user['email'], $user['full_name'], $subject, $body, $altBody)) {
                $stats['sent']++;
            } else {
                $stats['failed']++;
            }
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Erreur sendVotingStartNotificationEmails: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Envoie un email à l'admin pour notifier qu'un utilisateur a voté
 * @param string $userName Nom du votant
 * @param string $userEmail Email du votant
 * @param string $electionTitle Titre de l'élection
 * @return bool
 */
function notifyAdminUserVoted($userName, $userEmail, $electionTitle) {
    $subject = "🗳️ Un utilisateur a voté - $electionTitle";
    $body = "<p>L'utilisateur <strong>" . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . "</strong> (" . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . ") vient de voter pour l'élection <strong>" . htmlspecialchars($electionTitle, ENT_QUOTES, 'UTF-8') . "</strong>.</p>\n<p>Consultez le tableau de bord admin pour plus de détails.</p>";
    return sendEmail(ADMIN_NOTIFICATION_EMAIL, 'Admin SIGMA', $subject, $body);
}

/**
 * Notifie l'admin d'un changement de mot de passe utilisateur
 * 
 * @param string $userEmail Email de l'utilisateur
 * @return bool
 */
function notifyAdminPasswordChanged($userEmail) {
    $subject = "🔒 Changement de mot de passe - SIGMA Alumni";
    $body = "
        <h2>Alerte de sécurité</h2>
        <p>L'utilisateur avec l'adresse e-mail <strong>" . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . "</strong> a réinitialisé son mot de passe avec succès.</p>
        <p>Date de l'action : " . date('d/m/Y H:i:s') . "</p>
        <p>Si cet utilisateur n'est pas à l'origine de cette demande, une enquête de sécurité peut être nécessaire.</p>
    ";
    return sendEmail(ADMIN_NOTIFICATION_EMAIL, 'Admin SIGMA', $subject, $body);
}
