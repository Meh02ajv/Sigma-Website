<?php
/**
 * Script CRON pour l'envoi automatique des voeux du Nouvel An
 * À exécuter le 1er janvier à 00:01
 * 
 * Configuration Windows Task Scheduler:
 * Commande: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\Sigma-Website\cron_new_year.php
 * Date: 01/01 tous les ans à 00:01
 */

require_once 'config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log file pour tracer les exécutions
$log_file = __DIR__ . '/logs/new_year_cron.log';
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("=== Début du script de voeux du Nouvel An ===");

// Vérifier qu'on est bien le 1er janvier
$today = date('m-d');
if ($today !== '01-01') {
    logMessage("ATTENTION : Ce script doit être exécuté uniquement le 1er janvier. Date actuelle : " . date('Y-m-d'));
    logMessage("=== Script annulé ===\n");
    exit;
}

try {
    // Connexion à la base de données
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion : " . $conn->connect_error);
    }
    
    logMessage("Connexion à la base de données réussie");
    
    $current_year = date('Y');
    logMessage("Année en cours : $current_year");
    
    // Récupérer tous les utilisateurs actifs
    $query = "SELECT id, full_name, email FROM users WHERE email IS NOT NULL ORDER BY full_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    logMessage("Nombre d'utilisateurs à contacter : " . count($users));
    
    // Fonction pour créer un email avec PHPMailer
    function createMailer() {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM, 'SIGMA Alumni');
        return $mail;
    }
    
    // Fonction pour envoyer les voeux du Nouvel An
    function sendNewYearEmail($user, $year) {
        try {
            $mail = createMailer();
            $mail->addAddress($user['email'], $user['full_name']);
            $mail->Subject = "🎆 Bonne Année $year - SIGMA Alumni !";
            $mail->isHTML(true);
            
            $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            line-height: 1.6; 
            color: #333; 
            margin: 0;
            padding: 0;
        }
        .container { 
            max-width: 650px; 
            margin: 0 auto; 
            padding: 0; 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e8ba3 100%);
        }
        .card { 
            background: white; 
            margin: 20px; 
            border-radius: 20px; 
            padding: 50px 40px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .header h1 { 
            color: #1e3c72; 
            font-size: 42px; 
            margin: 0; 
            font-weight: bold;
        }
        .year { 
            font-size: 80px; 
            font-weight: bold; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .emoji { 
            font-size: 70px; 
            margin: 20px 0; 
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .content { 
            text-align: center; 
        }
        .content p { 
            font-size: 18px; 
            line-height: 2; 
            margin: 20px 0; 
            color: #555;
        }
        .highlight { 
            color: #667eea; 
            font-weight: bold; 
            font-size: 22px; 
        }
        .wishes { 
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%); 
            padding: 30px; 
            border-radius: 15px; 
            margin: 30px 0; 
            border-left: 5px solid #667eea;
        }
        .wishes p { 
            margin: 15px 0; 
            font-size: 16px; 
            color: #333;
            text-align: left;
        }
        .wishes strong {
            color: #667eea;
        }
        .footer { 
            text-align: center; 
            margin-top: 40px; 
            padding-top: 25px; 
            border-top: 3px solid #f0f0f0; 
            color: #777; 
            font-size: 14px; 
        }
        .button { 
            display: inline-block; 
            margin: 25px 0; 
            padding: 18px 40px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            text-decoration: none; 
            border-radius: 30px; 
            font-weight: bold; 
            font-size: 16px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            transition: transform 0.3s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .fireworks {
            text-align: center;
            font-size: 40px;
            margin: 20px 0;
            letter-spacing: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="emoji">🎊</div>
                <h1>Bonne Année !</h1>
                <div class="year">{$year}</div>
                <div class="fireworks">✨ 🎆 🎇 ✨</div>
            </div>
            <div class="content">
                <p>Cher(e) <strong>{$user['full_name']}</strong>,</p>
                
                <p class="highlight">🥂 Toute l'équipe SIGMA Alumni vous présente ses meilleurs vœux pour cette nouvelle année ! 🥂</p>
                
                <div class="wishes">
                    <p>🌟 <strong>Santé</strong> - Que cette année vous apporte une santé de fer et une énergie débordante</p>
                    <p>💼 <strong>Réussite</strong> - Que vos projets professionnels se concrétisent et dépassent vos attentes</p>
                    <p>❤️ <strong>Bonheur</strong> - Que chaque jour soit rempli de moments précieux avec vos proches</p>
                    <p>🎯 <strong>Accomplissement</strong> - Que tous vos rêves deviennent réalité</p>
                    <p>🤝 <strong>Solidarité</strong> - Que notre réseau SIGMA continue de grandir et de prospérer ensemble</p>
                </div>
                
                <p>Que cette année {$year} soit riche en belles rencontres, en opportunités exceptionnelles et en moments inoubliables !</p>
                
                <p style="font-size: 20px; margin-top: 30px;">🎉 Ensemble, faisons de {$year} une année extraordinaire ! 🎉</p>
                
                <a href="http://localhost/Sigma-Website/dashboard.php" class="button">Accéder à votre espace SIGMA</a>
            </div>
            <div class="footer">
                <p style="font-size: 16px; margin-bottom: 10px;">Avec toute notre amitié et nos meilleurs vœux,</p>
                <p style="font-size: 18px; font-weight: bold; color: #667eea;">L'équipe SIGMA Alumni 🎓</p>
                <p style="font-size: 12px; color: #999; margin-top: 20px;">Cet email a été envoyé automatiquement le 1er janvier {$year}.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
            
            $mail->AltBody = "Cher(e) {$user['full_name']},\n\nBonne Année {$year} !\n\nToute l'équipe SIGMA Alumni vous présente ses meilleurs vœux pour cette nouvelle année.\n\nQue {$year} soit une année remplie de santé, réussite, bonheur et accomplissement !\n\nEnsemble, faisons de {$year} une année extraordinaire !\n\nAvec toute notre amitié,\nL'équipe SIGMA Alumni";
            
            $mail->send();
            logMessage("✓ Voeux envoyés à {$user['full_name']} ({$user['email']})");
            return true;
        } catch (Exception $e) {
            logMessage("✗ Erreur envoi email à {$user['full_name']}: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    $total_sent = 0;
    $total_errors = 0;
    
    // Envoyer les voeux à tous les utilisateurs
    foreach ($users as $user) {
        if (sendNewYearEmail($user, $current_year)) {
            $total_sent++;
        } else {
            $total_errors++;
        }
        
        // Petit délai pour éviter de surcharger le serveur SMTP
        usleep(100000); // 0.1 seconde
    }
    
    logMessage("=== Résumé de l'envoi ===");
    logMessage("Total d'emails envoyés : $total_sent");
    logMessage("Total d'erreurs : $total_errors");
    logMessage("=== Script terminé avec succès ===\n");
    
    $conn->close();
    
} catch (Exception $e) {
    logMessage("ERREUR FATALE : " . $e->getMessage());
    logMessage("=== Script terminé avec erreur ===\n");
}
