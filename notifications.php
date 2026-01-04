<?php
require 'config.php';
require 'includes/notification_manager.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}

// Récupérer l'ID de l'utilisateur
$stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: connexion.php");
    exit;
}

$user_id = $user['id'];
$notif = new NotificationManager($conn);

// Récupérer les notifications
$notifications = $notif->getUserNotifications($user_id, 100);
$unread_count = $notif->getUnreadCount($user_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Sigma Yearbook</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -20px -20px 20px -20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            color: white;
            font-size: 1.5rem;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .back-btn:hover {
            transform: translateX(-5px);
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 1px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary-color);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .stats {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-color);
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .tab {
            padding: 0.6rem 1.2rem;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: var(--border-radius);
            font-family: inherit;
            transition: all 0.3s;
        }

        .tab.active {
            background: var(--secondary-color);
            color: white;
        }

        .notifications-list {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .notification-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.3s;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item.unread {
            background: #e3f2fd;
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .icon-event { background: #e3f2fd; color: #2196f3; }
        .icon-election { background: #f3e5f5; color: #9c27b0; }
        .icon-message { background: #e8f5e9; color: #4caf50; }
        .icon-suggestion { background: #fff3e0; color: #ff9800; }
        .icon-report { background: #ffebee; color: #f44336; }
        .icon-mention { background: #fce4ec; color: #e91e63; }
        .icon-default { background: #f5f5f5; color: #757575; }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.3rem;
        }

        .notification-message {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #999;
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .action-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            background: transparent;
            border: none;
            color: #999;
        }

        .action-icon:hover {
            background: #e0e0e0;
            color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            header {
                padding: 1rem;
                margin: -20px -20px 20px -20px;
            }

            h1 {
                font-size: 1.3rem;
            }

            .stats {
                flex-direction: column;
                gap: 1rem;
            }

            .filter-tabs {
                flex-wrap: wrap;
            }

            .notification-item {
                padding: 1rem;
            }

            .notification-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-left">
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1><i class="fas fa-bell"></i> Notifications</h1>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" id="markAllRead">
                <i class="fas fa-check-double"></i> Tout marquer comme lu
            </button>
        </div>
    </header>

    <div class="container">
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value" id="totalCount"><?php echo count($notifications); ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="unreadCount"><?php echo $unread_count; ?></div>
                <div class="stat-label">Non lues</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="readCount"><?php echo count($notifications) - $unread_count; ?></div>
                <div class="stat-label">Lues</div>
            </div>
        </div>

        <div class="filter-tabs">
            <button class="tab active" data-filter="all">Toutes</button>
            <button class="tab" data-filter="unread">Non lues</button>
            <button class="tab" data-filter="event">Événements</button>
            <button class="tab" data-filter="message">Messages</button>
            <button class="tab" data-filter="election">Élections</button>
        </div>

        <div class="notifications-list" id="notificationsList">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune notification pour le moment</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" 
                         data-id="<?php echo $notif['id']; ?>"
                         data-type="<?php echo $notif['type']; ?>"
                         data-link="<?php echo htmlspecialchars($notif['link'] ?? ''); ?>">
                        <div class="notification-icon icon-<?php echo $notif['type']; ?>">
                            <i class="fas fa-<?php echo $notif['icon']; ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php echo $notif['title']; ?></div>
                            <div class="notification-message"><?php echo $notif['message']; ?></div>
                            <div class="notification-time">
                                <?php 
                                $time = strtotime($notif['created_at']);
                                $diff = time() - $time;
                                if ($diff < 60) echo "À l'instant";
                                elseif ($diff < 3600) echo "Il y a " . floor($diff/60) . " min";
                                elseif ($diff < 86400) echo "Il y a " . floor($diff/3600) . " h";
                                else echo date('d/m/Y à H:i', $time);
                                ?>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <?php if (!$notif['is_read']): ?>
                                <button class="action-icon mark-read" title="Marquer comme lue">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                            <button class="action-icon delete-notif" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filtres
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const notifications = document.querySelectorAll('.notification-item');
                
                notifications.forEach(notif => {
                    if (filter === 'all') {
                        notif.style.display = 'flex';
                    } else if (filter === 'unread') {
                        notif.style.display = notif.classList.contains('unread') ? 'flex' : 'none';
                    } else {
                        notif.style.display = notif.dataset.type === filter ? 'flex' : 'none';
                    }
                });
            });
        });

        // Marquer comme lue
        document.addEventListener('click', async function(e) {
            if (e.target.closest('.mark-read')) {
                e.stopPropagation();
                const item = e.target.closest('.notification-item');
                const id = item.dataset.id;
                
                const formData = new FormData();
                formData.append('id', id);
                
                const response = await fetch('get_notifications.php?action=mark_read', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    item.classList.remove('unread');
                    e.target.closest('.action-icon').remove();
                    updateCounts();
                }
            }
        });

        // Supprimer
        document.addEventListener('click', async function(e) {
            if (e.target.closest('.delete-notif')) {
                e.stopPropagation();
                if (!confirm('Supprimer cette notification ?')) return;
                
                const item = e.target.closest('.notification-item');
                const id = item.dataset.id;
                
                const formData = new FormData();
                formData.append('id', id);
                
                const response = await fetch('get_notifications.php?action=delete', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    item.remove();
                    updateCounts();
                    
                    if (document.querySelectorAll('.notification-item').length === 0) {
                        document.getElementById('notificationsList').innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <p>Aucune notification pour le moment</p>
                            </div>
                        `;
                    }
                }
            }
        });

        // Marquer toutes comme lues
        document.getElementById('markAllRead').addEventListener('click', async function() {
            const response = await fetch('get_notifications.php?action=mark_all_read', {
                method: 'POST'
            });
            
            const data = await response.json();
            if (data.success) {
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                    const markReadBtn = item.querySelector('.mark-read');
                    if (markReadBtn) markReadBtn.remove();
                });
                updateCounts();
            }
        });

        // Cliquer sur une notification
        document.addEventListener('click', function(e) {
            const item = e.target.closest('.notification-item');
            if (item && !e.target.closest('.action-icon')) {
                const link = item.dataset.link;
                if (link) {
                    window.location.href = link;
                }
            }
        });

        // Mettre à jour les compteurs
        function updateCounts() {
            const allNotifs = document.querySelectorAll('.notification-item');
            const unreadNotifs = document.querySelectorAll('.notification-item.unread');
            
            document.getElementById('totalCount').textContent = allNotifs.length;
            document.getElementById('unreadCount').textContent = unreadNotifs.length;
            document.getElementById('readCount').textContent = allNotifs.length - unreadNotifs.length;
        }
    </script>
</body>
</html>
