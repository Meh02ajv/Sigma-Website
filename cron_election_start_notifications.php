<?php
/**
 * Script CRON pour l'envoi automatique des notifications de debut de vote
 * A executer frequemment (par exemple toutes les 5 minutes)
 *
 * Exemple Windows Task Scheduler:
 * Program: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\Sigma-Website\cron_election_start_notifications.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/send_email.php';

date_default_timezone_set('Africa/Abidjan');

$log_dir = __DIR__ . '/logs';
$log_file = $log_dir . '/election_start_notifications.log';

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function logMessage($message)
{
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    echo "[$timestamp] $message" . PHP_EOL;
}

try {
    logMessage('=== Debut CRON notifications debut vote ===');

    $sentAtColumn = $conn->query("SHOW COLUMNS FROM elections LIKE 'voting_notification_sent_at'");
    if ($sentAtColumn && $sentAtColumn->num_rows === 0) {
        $conn->query("ALTER TABLE elections ADD COLUMN voting_notification_sent_at DATETIME NULL AFTER results_date");
        logMessage('Colonne voting_notification_sent_at ajoutee.');
    }

    $statusColumn = $conn->query("SHOW COLUMNS FROM elections LIKE 'voting_notification_status'");
    if ($statusColumn && $statusColumn->num_rows === 0) {
        $conn->query("ALTER TABLE elections ADD COLUMN voting_notification_status VARCHAR(20) NULL AFTER voting_notification_sent_at");
        logMessage('Colonne voting_notification_status ajoutee.');
    }

    $errorColumn = $conn->query("SHOW COLUMNS FROM elections LIKE 'voting_notification_error'");
    if ($errorColumn && $errorColumn->num_rows === 0) {
        $conn->query("ALTER TABLE elections ADD COLUMN voting_notification_error TEXT NULL AFTER voting_notification_status");
        logMessage('Colonne voting_notification_error ajoutee.');
    }

    $sql = "
        SELECT id, title, start_date, end_date, voting_notification_sent_at
        FROM elections
        WHERE NOW() >= start_date
          AND NOW() <= end_date
          AND voting_notification_sent_at IS NULL
        ORDER BY start_date ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        logMessage('Erreur SQL: ' . $conn->error);
        exit(1);
    }

    if ($result->num_rows === 0) {
        logMessage('Aucune election a notifier pour le lancement du vote.');
        logMessage('=== Fin CRON notifications debut vote ===');
        exit(0);
    }

    $sent = 0;
    $failed = 0;

    while ($election = $result->fetch_assoc()) {
        logMessage('Traitement election ID ' . $election['id'] . ' - ' . $election['title']);

        $mailResult = sendVotingStartNotificationEmails((int)$election['id'], $conn);

        if (($mailResult['sent'] ?? 0) > 0) {
            $sent += (int)$mailResult['sent'];
            $update = $conn->prepare("UPDATE elections SET voting_notification_sent_at = NOW(), voting_notification_status = 'sent', voting_notification_error = NULL WHERE id = ?");
            $update->bind_param('i', $election['id']);
            $update->execute();
            $update->close();
            logMessage('Notifications de lancement envoyees pour election ID ' . $election['id']);
        } else {
            $failed++;
            $error = 'Aucun email envoye ou echec sendVotingStartNotificationEmails()';
            $update = $conn->prepare("UPDATE elections SET voting_notification_status = 'failed', voting_notification_error = ? WHERE id = ?");
            $update->bind_param('si', $error, $election['id']);
            $update->execute();
            $update->close();
            logMessage('Echec notification de lancement pour election ID ' . $election['id']);
        }
    }

    logMessage("Termine. Emails envoyes: {$sent}, echecs: {$failed}");
    logMessage('=== Fin CRON notifications debut vote ===');
} catch (Throwable $e) {
    logMessage('Erreur fatale: ' . $e->getMessage());
    exit(1);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
