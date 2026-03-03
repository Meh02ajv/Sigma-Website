<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envoie un email en utilisant PHPMailer avec optimisations anti-spam
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
        
        // Configuration anti-spam avancée
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            )
        );
        
        // Timeout pour éviter les blocages
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        // Headers anti-spam
        $mail->XMailer = ' '; // Masquer la version de PHPMailer
        $mail->Priority = 3; // Priorité normale (1=High, 3=Normal, 5=Low)
        
        // Destinataires
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $recipientName);
        $mail->addReplyTo(SMTP_REPLY_TO_EMAIL, SMTP_REPLY_TO_NAME);
        
        // Headers personnalisés pour améliorer la délivrabilité
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('X-Entity-ID', SMTP_FROM_EMAIL);
        $mail->addCustomHeader('Return-Path', SMTP_FROM_EMAIL);
        
        // List-Unsubscribe header (recommandé pour éviter le spam)
        $unsubscribe_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/Sigma-Website/settings.php';
        $mail->addCustomHeader('List-Unsubscribe', '<' . $unsubscribe_url . '>');
        $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        
        // Message-ID personnalisé pour une meilleure réputation
        $domain = 'sigma-alumni.local';
        $messageId = sprintf('<%s.%s@%s>', time(), bin2hex(random_bytes(8)), $domain);
        $mail->MessageID = $messageId;
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?? strip_tags($body);
        
        // DKIM - Si configuré dans config.php
        if (defined('DKIM_DOMAIN') && defined('DKIM_PRIVATE_KEY') && defined('DKIM_SELECTOR')) {
            $mail->DKIM_domain = DKIM_DOMAIN;
            $mail->DKIM_private = DKIM_PRIVATE_KEY;
            $mail->DKIM_selector = DKIM_SELECTOR;
            $mail->DKIM_passphrase = '';
            $mail->DKIM_identity = SMTP_FROM_EMAIL;
        }
        
        // Rate limiting simple pour éviter d'être blacklisté
        static $email_count = 0;
        static $last_batch_time = null;
        
        if ($last_batch_time === null) {
            $last_batch_time = time();
        }
        
        $email_count++;
        
        // Pause de 100ms tous les 10 emails
        if ($email_count % 10 === 0) {
            usleep(100000); // 100ms
        }
        
        // Pause de 2 secondes toutes les 50 emails
        if ($email_count % 50 === 0) {
            sleep(2);
        }
        
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
    
    $subject = "Confirmation de votre vote - " . $election_title;
    
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Sigma-Website";
    
    $body = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Confirmation de vote</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 40px 30px; text-align: center; }
            .header h1 { margin: 10px 0 0 0; font-size: 24px; font-weight: 600; }
            .icon { font-size: 48px; margin-bottom: 10px; }
            .content { padding: 40px 30px; background-color: #ffffff; }
            .content p { margin: 15px 0; color: #374151; }
            .info-box { background: #f9fafb; padding: 20px; border-left: 4px solid #2563eb; margin: 25px 0; border-radius: 4px; }
            .info-box h3 { margin: 0 0 15px 0; color: #1f2937; font-size: 16px; }
            .info-box p { margin: 8px 0; color: #4b5563; }
            .button { display: inline-block; padding: 14px 32px; background: #2563eb; color: white !important; text-decoration: none; border-radius: 6px; margin: 25px 0; font-weight: 500; }
            .button:hover { background: #1d4ed8; }
            .footer { background: #f9fafb; padding: 30px; text-align: center; color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; }
            .footer p { margin: 8px 0; }
            .footer a { color: #2563eb; text-decoration: none; }
            .alert-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .alert-box strong { color: #92400e; }
            ul { margin: 10px 0; padding-left: 25px; }
            ul li { margin: 8px 0; color: #4b5563; }
            @media only screen and (max-width: 600px) {
                .content, .header, .footer { padding: 20px !important; }
            }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>✅</div>
                    <h1>Vote Enregistré avec Succès</h1>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>" . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                    
                    <p>Nous vous confirmons que votre vote pour l'élection <strong>" . htmlspecialchars($election_title, ENT_QUOTES, 'UTF-8') . "</strong> a été enregistré avec succès.</p>
                    
                    <div class='info-box'>
                        <h3>📊 Détails de votre participation</h3>
                        <p><strong>Positions votées :</strong><br>" . htmlspecialchars($positions_list, ENT_QUOTES, 'UTF-8') . "</p>
                        <p><strong>Date et heure :</strong> " . date('d/m/Y à H:i') . "</p>
                    </div>
                    
                    <div class='alert-box'>
                        <p><strong>⚠️ Important</strong></p>
                        <ul>
                            <li>Votre vote est <strong>définitif et ne peut pas être modifié</strong></li>
                            <li>Les résultats seront publiés après la clôture du scrutin</li>
                            <li>Vous recevrez une notification lorsque les résultats seront disponibles</li>
                        </ul>
                    </div>
                    
                    <p>Merci de votre participation à la vie démocratique de SIGMA Alumni.</p>
                    
                    <center>
                        <a href='{$base_url}/elections.php' class='button'>Voir les détails de l'élection</a>
                    </center>
                </div>
                <div class='footer'>
                    <p><strong>SIGMA Alumni</strong> - Communauté des anciens élèves</p>
                    <p>Cet email a été envoyé automatiquement suite à votre vote.</p>
                    <p>Si vous n'avez pas voté, veuillez contacter immédiatement l'administration.</p>
                    <p style='margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;'>
                        <a href='{$base_url}/settings.php'>Gérer vos préférences de notification</a>
                    </p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Version texte améliorée pour éviter les filtres anti-spam
    $altBody = "Bonjour {$user['full_name']},\n\n" .
               "Nous vous confirmons que votre vote pour l'élection \"{$election_title}\" a été enregistré avec succès.\n\n" .
               "DÉTAILS DE VOTRE PARTICIPATION\n" .
               "Positions votées : {$positions_list}\n" .
               "Date et heure : " . date('d/m/Y à H:i') . "\n\n" .
               "IMPORTANT\n" .
               "- Votre vote est définitif et ne peut pas être modifié\n" .
               "- Les résultats seront publiés après la clôture du scrutin\n" .
               "- Vous recevrez une notification lorsque les résultats seront disponibles\n\n" .
               "Merci de votre participation à la vie démocratique de SIGMA Alumni !\n\n" .
               "---\n" .
               "SIGMA Alumni - Communauté des anciens élèves\n" .
               "Si vous n'avez pas voté, veuillez contacter immédiatement l'administration.\n" .
               "Pour gérer vos préférences de notification : " . 
               (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . 
               ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Sigma-Website/settings.php";
    
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
    
    $subject = "Résultats disponibles - " . $election['title'];
    
    $sent_count = 0;
    $failed_count = 0;
    
    while ($user = $result->fetch_assoc()) {
        // Valider l'email avant d'envoyer
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Email invalide ignoré pour l'utilisateur ID {$user['id']}: {$user['email']}");
            $failed_count++;
            continue;
        }
        
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Sigma-Website";
        
        $body = "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Résultats d'élection</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 40px 30px; text-align: center; }
                .header h1 { margin: 10px 0 0 0; font-size: 24px; font-weight: 600; }
                .icon { font-size: 48px; margin-bottom: 10px; }
                .content { padding: 40px 30px; background-color: #ffffff; }
                .content p { margin: 15px 0; color: #374151; }
                .button { display: inline-block; padding: 14px 32px; background: #10b981; color: white !important; text-decoration: none; border-radius: 6px; margin: 25px 0; font-weight: 500; }
                .button:hover { background: #059669; }
                .footer { background: #f9fafb; padding: 30px; text-align: center; color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; }
                .footer p { margin: 8px 0; }
                .footer a { color: #10b981; text-decoration: none; }
                .highlight-box { background: #ecfdf5; border: 1px solid #10b981; padding: 20px; margin: 25px 0; border-radius: 6px; text-align: center; }
                ul { margin: 10px 0; padding-left: 25px; }
                ul li { margin: 8px 0; color: #4b5563; }
                @media only screen and (max-width: 600px) {
                    .content, .header, .footer { padding: 20px !important; }
                }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='container'>
                    <div class='header'>
                        <div class='icon'>📊</div>
                        <h1>Résultats de l'Élection Disponibles</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour <strong>" . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                        
                        <p>Les résultats de l'élection <strong>" . htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') . "</strong> sont maintenant disponibles.</p>
                        
                        <div class='highlight-box'>
                            <p style='margin: 0; color: #065f46; font-size: 16px;'>
                                <strong>🎉 Le dépouillement est terminé</strong>
                            </p>
                            <p style='margin: 10px 0 0 0; color: #047857;'>
                                Découvrez les résultats complets et les candidats élus
                            </p>
                        </div>
                        
                        <p>Vous pouvez consulter :</p>
                        <ul>
                            <li>La répartition complète des votes par position</li>
                            <li>Les candidats élus pour chaque poste</li>
                            <li>Les statistiques de participation</li>
                        </ul>
                        
                        <center>
                            <a href='{$base_url}/elections.php#results' class='button'>Voir les Résultats Complets</a>
                        </center>
                        
                        <p>Merci d'avoir participé à cette élection et contribué à la vie démocratique de SIGMA Alumni.</p>
                    </div>
                    <div class='footer'>
                        <p><strong>SIGMA Alumni</strong> - Communauté des anciens élèves</p>
                        <p>Cet email a été envoyé automatiquement suite à la publication des résultats.</p>
                        <p>Pour toute question, contactez l'administration.</p>
                        <p style='margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;'>
                            <a href='{$base_url}/settings.php'>Gérer vos préférences de notification</a>
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Version texte améliorée pour éviter les filtres anti-spam
        $altBody = "Bonjour {$user['full_name']},\n\n" .
                   "Les résultats de l'élection \"{$election['title']}\" sont maintenant disponibles !\n\n" .
                   "Vous pouvez consulter les résultats complets, incluant la répartition des votes " .
                   "par position et les candidats élus.\n\n" .
                   "Lien vers les résultats : " . 
                   (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . 
                   ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Sigma-Website/elections.php#results\n\n" .
                   "Merci d'avoir participé à cette élection et contribué à la vie démocratique " .
                   "de SIGMA Alumni.\n\n" .
                   "---\n" .
                   "SIGMA Alumni - Communauté des anciens élèves\n" .
                   "Pour toute question, contactez l'administration\n" .
                   "Pour gérer vos préférences : " . 
                   (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . 
                   ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Sigma-Website/settings.php";
        
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
