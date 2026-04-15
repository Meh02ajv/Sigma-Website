<?php
/**
 * Script CRON pour l'envoi automatique des rappels d'evenements
 *
 * Frequence recommandee: toutes les 15 minutes
 * Exemple Windows Task Scheduler:
 * Program: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\Sigma-Website\cron_event_reminders.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/send_email.php';

date_default_timezone_set('Africa/Abidjan');

$log_dir = __DIR__ . '/logs';
$log_file = $log_dir . '/event_reminders_cron.log';

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
    logMessage('=== Debut CRON rappels evenements ===');

    // Ensure tracking columns exist to prevent duplicate sends for each reminder level.
    $reminder1hColumn = $conn->query("SHOW COLUMNS FROM event_reminders LIKE 'reminder_1h_sent_at'");
    if ($reminder1hColumn && $reminder1hColumn->num_rows === 0) {
        $conn->query("ALTER TABLE event_reminders ADD COLUMN reminder_1h_sent_at DATETIME NULL AFTER reminder_date");
        logMessage('Colonne reminder_1h_sent_at ajoutee.');
    }

    $reminder15mColumn = $conn->query("SHOW COLUMNS FROM event_reminders LIKE 'reminder_15m_sent_at'");
    if ($reminder15mColumn && $reminder15mColumn->num_rows === 0) {
        $conn->query("ALTER TABLE event_reminders ADD COLUMN reminder_15m_sent_at DATETIME NULL AFTER reminder_1h_sent_at");
        logMessage('Colonne reminder_15m_sent_at ajoutee.');
    }

    $statusColumn = $conn->query("SHOW COLUMNS FROM event_reminders LIKE 'reminder_email_status'");
    if ($statusColumn && $statusColumn->num_rows === 0) {
        $conn->query("ALTER TABLE event_reminders ADD COLUMN reminder_email_status VARCHAR(20) NULL AFTER reminder_15m_sent_at");
        logMessage('Colonne reminder_email_status ajoutee.');
    }

    $errorColumn = $conn->query("SHOW COLUMNS FROM event_reminders LIKE 'reminder_email_error'");
    if ($errorColumn && $errorColumn->num_rows === 0) {
        $conn->query("ALTER TABLE event_reminders ADD COLUMN reminder_email_error TEXT NULL AFTER reminder_email_status");
        logMessage('Colonne reminder_email_error ajoutee.');
    }

    // Select reminders eligible for H-1 or H-15 notifications and not yet sent for that level.
    $sql = "
        SELECT
            er.id AS reminder_id,
            er.user_id,
            er.event_id,
            er.reminder_1h_sent_at,
            er.reminder_15m_sent_at,
            e.title AS event_title,
            e.description AS event_description,
            e.location AS event_location,
            e.event_date,
            u.email,
            u.full_name
        FROM event_reminders er
        INNER JOIN events e ON e.id = er.event_id
        INNER JOIN users u ON u.id = er.user_id
        WHERE e.event_date > NOW()
          AND (
                (er.reminder_1h_sent_at IS NULL AND NOW() >= DATE_SUB(e.event_date, INTERVAL 1 HOUR))
                OR
                (er.reminder_15m_sent_at IS NULL AND NOW() >= DATE_SUB(e.event_date, INTERVAL 15 MINUTE))
              )
        ORDER BY e.event_date ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        logMessage('Erreur SQL: ' . $conn->error);
        exit(1);
    }

    if ($result->num_rows === 0) {
        logMessage('Aucun rappel (H-1 ou H-15) a envoyer pour le moment.');
        logMessage('=== Fin CRON rappels evenements ===');
        exit(0);
    }

    $sent = 0;
    $failed = 0;

    while ($row = $result->fetch_assoc()) {
        $eventTimestamp = strtotime($row['event_date']);
        $currentTimestamp = time();
        $secondsBeforeEvent = $eventTimestamp - $currentTimestamp;

        if ($secondsBeforeEvent <= 0) {
            continue;
        }

        $eventDate = date('d/m/Y', strtotime($row['event_date']));
        $eventTime = date('H:i', strtotime($row['event_date']));

        $due1h = ($secondsBeforeEvent <= 3600) && ($row['reminder_1h_sent_at'] === null);
        $due15m = ($secondsBeforeEvent <= 900) && ($row['reminder_15m_sent_at'] === null);

        if (!$due1h && !$due15m) {
            continue;
        }

        $remindersToSend = [];
        if ($due1h) {
            $remindersToSend[] = ['label' => '1 heure', 'column' => 'reminder_1h_sent_at', 'log' => 'H-1'];
        }
        if ($due15m) {
            $remindersToSend[] = ['label' => '15 minutes', 'column' => 'reminder_15m_sent_at', 'log' => 'H-15min'];
        }

        $safeName = htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($row['event_title'], ENT_QUOTES, 'UTF-8');
        $safeDescription = nl2br(htmlspecialchars($row['event_description'] ?? '', ENT_QUOTES, 'UTF-8'));
        $safeLocation = htmlspecialchars($row['event_location'] ?? 'Lieu non specifie', ENT_QUOTES, 'UTF-8');

        foreach ($remindersToSend as $reminderMeta) {
            $reminderLabel = $reminderMeta['label'];
            $subject = "Rappel evenement ({$reminderLabel}): " . $row['event_title'];

            $body = "
            <!DOCTYPE html>
            <html lang='fr'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Rappel evenement</title>
            </head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #fff; padding: 24px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>Rappel evenement - Dans {$reminderLabel}</h1>
                </div>
                <div style='background: #ffffff; padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;'>
                    <p>Bonjour <strong>{$safeName}</strong>,</p>
                    <p>Petit rappel: l'evenement <strong>{$safeTitle}</strong> commence dans <strong>{$reminderLabel}</strong>.</p>

                    <div style='background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px 16px; border-radius: 4px; margin: 16px 0;'>
                        <p style='margin: 0 0 8px 0;'><strong>Date:</strong> {$eventDate}</p>
                        <p style='margin: 0 0 8px 0;'><strong>Heure:</strong> {$eventTime}</p>
                        <p style='margin: 0;'><strong>Lieu:</strong> {$safeLocation}</p>
                    </div>

                    <p style='margin: 16px 0 8px 0;'><strong>Description:</strong></p>
                    <p style='margin-top: 0;'>{$safeDescription}</p>

                    <p style='margin-top: 24px;'>A tres bientot,</p>
                    <p><strong>SIGMA Alumni</strong></p>
                </div>
            </body>
            </html>
            ";

            $altBody = "RAPPEL EVENEMENT - DANS {$reminderLabel}\n\n" .
                "Bonjour {$row['full_name']},\n\n" .
                "L'evenement '{$row['event_title']}' commence dans {$reminderLabel}.\n" .
                "Date: {$eventDate}\n" .
                "Heure: {$eventTime}\n" .
                "Lieu: " . ($row['event_location'] ?? 'Lieu non specifie') . "\n\n" .
                "Description:\n" . ($row['event_description'] ?? '') . "\n\n" .
                "SIGMA Alumni";

            $ok = sendEmail($row['email'], $row['full_name'], $subject, $body, $altBody);

            if ($ok) {
                $sent++;
                $column = $reminderMeta['column'];
                $update = $conn->prepare("UPDATE event_reminders SET {$column} = NOW(), reminder_email_status = 'sent', reminder_email_error = NULL WHERE id = ?");
                $update->bind_param('i', $row['reminder_id']);
                $update->execute();
                $update->close();
                logMessage("Email rappel {$reminderMeta['log']} envoye a {$row['email']} pour event_id={$row['event_id']}");
            } else {
                $failed++;
                $error = 'Echec sendEmail()';
                $update = $conn->prepare("UPDATE event_reminders SET reminder_email_status = 'failed', reminder_email_error = ? WHERE id = ?");
                $update->bind_param('si', $error, $row['reminder_id']);
                $update->execute();
                $update->close();
                logMessage("Echec envoi rappel ({$reminderLabel}) a {$row['email']} pour event_id={$row['event_id']}");
            }
        }
    }

    logMessage("Termine. Emails envoyes: {$sent}, echecs: {$failed}");
    logMessage('=== Fin CRON rappels evenements ===');
} catch (Throwable $e) {
    logMessage('Erreur fatale: ' . $e->getMessage());
    exit(1);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
