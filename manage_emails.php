<?php
/**
 * SCRIPT D'ADMINISTRATION DES EMAILS
 * 
 * Ce script permet de gérer les logs d'emails :
 * - Voir les statistiques d'envoi
 * - Réinitialiser les logs pour une élection (permet de renvoyer les emails)
 * - Supprimer les anciens logs
 */

require 'config.php';

function showStats($conn) {
    echo "\n=== STATISTIQUES D'ENVOI D'EMAILS ===\n\n";
    
    $result = $conn->query("SELECT 
                               e.title,
                               el.email_type,
                               COUNT(*) as count,
                               el.status
                           FROM email_logs el
                           JOIN elections e ON el.election_id = e.id
                           GROUP BY e.title, el.email_type, el.status
                           ORDER BY e.title, el.email_type");
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "📧 {$row['title']} - {$row['email_type']}: {$row['count']} ({$row['status']})\n";
        }
    } else {
        echo "Aucun email envoyé pour le moment.\n";
    }
}

function resetElectionEmails($conn, $election_id, $email_type = null) {
    if ($email_type) {
        $stmt = $conn->prepare("DELETE FROM email_logs WHERE election_id = ? AND email_type = ?");
        $stmt->bind_param("is", $election_id, $email_type);
    } else {
        $stmt = $conn->prepare("DELETE FROM email_logs WHERE election_id = ?");
        $stmt->bind_param("i", $election_id);
    }
    
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    echo "\n✅ $affected logs supprimés pour l'élection ID $election_id" . ($email_type ? " (type: $email_type)" : "") . "\n";
    echo "Les emails peuvent maintenant être renvoyés.\n";
}

function deleteOldLogs($conn, $days = 90) {
    $stmt = $conn->prepare("DELETE FROM email_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    echo "\n✅ $affected anciens logs supprimés (plus de $days jours)\n";
}

// Menu principal
echo "=== GESTION DES EMAILS ===\n";
echo "1. Voir les statistiques\n";
echo "2. Réinitialiser les emails d'une élection (permet de renvoyer)\n";
echo "3. Supprimer les anciens logs (> 90 jours)\n";
echo "4. Quitter\n\n";

// Pour utilisation en ligne de commande
if (php_sapi_name() === 'cli') {
    $choice = isset($argv[1]) ? $argv[1] : '1';
    
    switch ($choice) {
        case '1':
            showStats($conn);
            break;
        case '2':
            $election_id = isset($argv[2]) ? (int)$argv[2] : 0;
            $email_type = isset($argv[3]) ? $argv[3] : null;
            if ($election_id > 0) {
                resetElectionEmails($conn, $election_id, $email_type);
            } else {
                echo "Usage: php manage_emails.php 2 <election_id> [email_type]\n";
                echo "Exemple: php manage_emails.php 2 25 vote_confirmation\n";
            }
            break;
        case '3':
            deleteOldLogs($conn);
            break;
        default:
            showStats($conn);
    }
} else {
    // Si appelé depuis un navigateur, afficher seulement les stats
    showStats($conn);
}

$conn->close();
?>
