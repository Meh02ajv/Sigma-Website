<?php
require 'config.php';
require 'send_email.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_results'])) {
    $election_id = (int)$_POST['election_id'];
    
    // Mettre à jour le statut de publication des résultats
    $stmt = $conn->prepare("UPDATE elections SET results_published = 1 WHERE id = ?");
    $stmt->bind_param("i", $election_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Envoyer les notifications par email à tous les votants
        $sent_count = sendResultsNotificationEmails($election_id);
        
        $_SESSION['success'] = "Les résultats ont été publiés avec succès ! $sent_count notification(s) envoyée(s) par email.";
        error_log("Résultats publiés pour élection ID $election_id - $sent_count emails envoyés");
    } else {
        $_SESSION['error'] = "Erreur lors de la publication des résultats.";
        $stmt->close();
    }
    
    header("Location: admin.php?tab=elections");
    exit;
}

// Si accès direct, rediriger
header("Location: admin.php");
exit;
?>
