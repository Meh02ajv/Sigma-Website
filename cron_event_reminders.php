<?php
/**
 * Script CRON pour l'envoi des rappels d'événements
 * À exécuter toutes les 15 minutes
 */

require_once 'config.php';
require_once 'send_email.php';

// Log file
$log_file = __DIR__ . '/logs/event_reminders_cron.log';
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

function logEventReminder($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

logEventReminder("--- Début du script de rappel d'événements ---");

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion : " . $conn->connect_error);
    }

    /**
     * Rechercher les rappels à envoyer :
     * 1. L'événement doit avoir lieu dans moins de 125 minutes (pour le rappel de 2h) 
     *    ou moins de 65 minutes (pour le rappel de 1h).
     * 2. On vérifie si le rappel spécifique n'a pas déjà été envoyé.
     */
    
    // RAPPEL 2 HEURES AVANT
    $sql_2h = "SELECT er.id as reminder_id, u.email, u.full_name, e.title, e.event_date, e.location 
               FROM event_reminders er
               JOIN users u ON er.user_id = u.id
               JOIN events e ON er.event_id = e.id
               WHERE er.sent_2h = 0 
               AND e.event_date <= DATE_ADD(NOW(), INTERVAL 2 HOUR)
               AND e.event_date > NOW()";
               
    $result_2h = $conn->query($sql_2h);
    $sent_2h_count = 0;

    while ($row = $result_2h->fetch_assoc()) {
        $subject = "🔔 Rappel : L'événement \"{$row['title']}\" commence dans 2 heures !";
        $body = "
            <h2>Bonjour {$row['full_name']},</h2>
            <p>Ceci est un rappel automatique pour l'événement auquel vous êtes inscrit :</p>
            <p><strong>Événement :</strong> {$row['title']}<br>
            <strong>Date :</strong> " . date('d/m/Y à H:i', strtotime($row['event_date'])) . "<br>
            <strong>Lieu :</strong> {$row['location']}</p>
            <p>On se voit dans 2 heures !</p>
            <p>Cordialement,<br>L'équipe SIGMA Alumni</p>
        ";
        
        if (sendEmail($row['email'], $row['full_name'], $subject, $body)) {
            $conn->query("UPDATE event_reminders SET sent_2h = 1 WHERE id = " . $row['reminder_id']);
            $sent_2h_count++;
        }
    }
    logEventReminder("Rappels 2h envoyés : $sent_2h_count");

    // RAPPEL 1 HEURE AVANT
    $sql_1h = "SELECT er.id as reminder_id, u.email, u.full_name, e.title, e.event_date, e.location 
               FROM event_reminders er
               JOIN users u ON er.user_id = u.id
               JOIN events e ON er.event_id = e.id
               WHERE er.sent_1h = 0 
               AND e.event_date <= DATE_ADD(NOW(), INTERVAL 1 HOUR)
               AND e.event_date > NOW()";
               
    $result_1h = $conn->query($sql_1h);
    $sent_1h_count = 0;

    while ($row = $result_1h->fetch_assoc()) {
        $subject = "⚡ Dernière heure : \"{$row['title']}\" commence bientôt !";
        $body = "
            <h2>Bonjour {$row['full_name']},</h2>
            <p>L'événement <strong>{$row['title']}</strong> commence dans seulement <strong>1 heure</strong> !</p>
            <p>Préparez-vous à nous rejoindre à {$row['location']}.</p>
            <p>À tout de suite,<br>L'équipe SIGMA Alumni</p>
        ";
        
        if (sendEmail($row['email'], $row['full_name'], $subject, $body)) {
            $conn->query("UPDATE event_reminders SET sent_1h = 1 WHERE id = " . $row['reminder_id']);
            $sent_1h_count++;
        }
    }
    logEventReminder("Rappels 1h envoyés : $sent_1h_count");

    $conn->close();

} catch (Exception $e) {
    logEventReminder("ERREUR : " . $e->getMessage());
}

logEventReminder("--- Script terminé ---\n");
?>