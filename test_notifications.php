<?php
/**
 * PAGE DE TEST DU SYSTÈME DE NOTIFICATIONS
 * Permet de créer des notifications de test pour chaque type
 */

require 'config.php';
require 'includes/notification_manager.php';

if (!isset($_SESSION['user_email'])) {
    die("Vous devez être connecté pour accéder à cette page.");
}

// Récupérer l'ID de l'utilisateur actuel
$stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$user_id = $user['id'];
$notif = new NotificationManager($conn);

$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'event':
            notifyNewEvent(999, "Soirée de Noël 2025");
            $message = "✅ Notification 'Événement' créée !";
            break;
            
        case 'election':
            notifyElectionUpdate(999, "Les résultats des élections sont disponibles !");
            $message = "✅ Notification 'Élection' créée !";
            break;
            
        case 'message':
            notifyNewMessage($user_id, "Test User");
            $message = "✅ Notification 'Message' créée !";
            break;
            
        case 'suggestion':
            notifySuggestionProcessed($user_id, 999, 'approved');
            $message = "✅ Notification 'Suggestion approuvée' créée !";
            break;
            
        case 'report':
            notifyReportProcessed($user_id, 999, "Utilisateur averti");
            $message = "✅ Notification 'Signalement traité' créée !";
            break;
            
        case 'mention':
            notifyMention($user_id, "Marie Dupont", "un commentaire", "dashboard.php");
            $message = "✅ Notification 'Mention' créée !";
            break;
            
        case 'bulk':
            // Créer pour tous les utilisateurs
            $stmt = $conn->query("SELECT id FROM users LIMIT 10");
            $user_ids = array_column($stmt->fetch_all(MYSQLI_ASSOC), 'id');
            $notif->createBulk(
                $user_ids,
                'event',
                'Test notification en masse',
                'Ceci est un test de notification envoyée à plusieurs utilisateurs',
                'dashboard.php',
                'bullhorn'
            );
            $message = "✅ Notifications en masse créées pour " . count($user_ids) . " utilisateurs !";
            break;
            
        case 'clean':
            $deleted = $notif->cleanOldNotifications(0); // Supprimer toutes les lues
            $message = "🧹 $deleted notifications supprimées !";
            break;
    }
}

// Statistiques
$total = $notif->getUserNotifications($user_id, 1000);
$unread = $notif->getUnreadCount($user_id);
$read = count($total) - $unread;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Système de Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .test-btn {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .test-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .test-btn i {
            font-size: 1.5rem;
        }

        .danger-btn {
            border-color: #e74c3c;
            color: #e74c3c;
        }

        .danger-btn:hover {
            background: #e74c3c;
            color: white;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .action-link {
            flex: 1;
            background: #667eea;
            color: white;
            text-decoration: none;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
        }

        .action-link:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-flask"></i> Test Système de Notifications</h1>
        <p class="subtitle">Créez des notifications de test pour vérifier le fonctionnement du système</p>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($total); ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $unread; ?></div>
                <div class="stat-label">Non lues</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $read; ?></div>
                <div class="stat-label">Lues</div>
            </div>
        </div>

        <h2 style="margin-bottom: 20px;">Créer des notifications de test</h2>

        <form method="POST">
            <div class="grid">
                <button type="submit" name="action" value="event" class="test-btn">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <strong>Événement</strong><br>
                        <small>Nouveau événement créé</small>
                    </div>
                </button>

                <button type="submit" name="action" value="election" class="test-btn">
                    <i class="fas fa-vote-yea"></i>
                    <div>
                        <strong>Élection</strong><br>
                        <small>Résultats publiés</small>
                    </div>
                </button>

                <button type="submit" name="action" value="message" class="test-btn">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <strong>Message</strong><br>
                        <small>Nouveau message reçu</small>
                    </div>
                </button>

                <button type="submit" name="action" value="suggestion" class="test-btn">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <strong>Suggestion</strong><br>
                        <small>Suggestion approuvée</small>
                    </div>
                </button>

                <button type="submit" name="action" value="report" class="test-btn">
                    <i class="fas fa-flag"></i>
                    <div>
                        <strong>Signalement</strong><br>
                        <small>Signalement traité</small>
                    </div>
                </button>

                <button type="submit" name="action" value="mention" class="test-btn">
                    <i class="fas fa-at"></i>
                    <div>
                        <strong>Mention</strong><br>
                        <small>Vous avez été mentionné</small>
                    </div>
                </button>

                <button type="submit" name="action" value="bulk" class="test-btn">
                    <i class="fas fa-bullhorn"></i>
                    <div>
                        <strong>En masse</strong><br>
                        <small>Notifier 10 utilisateurs</small>
                    </div>
                </button>

                <button type="submit" name="action" value="clean" class="test-btn danger-btn" onclick="return confirm('Supprimer toutes les notifications lues ?')">
                    <i class="fas fa-trash"></i>
                    <div>
                        <strong>Nettoyer</strong><br>
                        <small>Supprimer les lues</small>
                    </div>
                </button>
            </div>
        </form>

        <div class="actions">
            <a href="notifications.php" class="action-link">
                <i class="fas fa-bell"></i> Voir mes notifications
            </a>
            <a href="dashboard.php" class="action-link">
                <i class="fas fa-home"></i> Retour au dashboard
            </a>
        </div>
    </div>

    <script>
        // Rafraîchir les stats automatiquement après action
        <?php if ($message): ?>
        setTimeout(() => {
            window.location.reload();
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
