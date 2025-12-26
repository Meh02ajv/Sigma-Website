<?php
/**
 * API de gestion des notifications
 * Fonctions pour créer, lire et gérer les notifications
 */

require_once 'config.php';

class NotificationManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Créer une nouvelle notification
     */
    public function create($user_id, $type, $title, $message, $link = null, $icon = 'bell', $related_type = null, $related_id = null) {
        $stmt = $this->conn->prepare(
            "INSERT INTO notifications (user_id, type, title, message, link, icon, related_type, related_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param("issssssi", $user_id, $type, $title, $message, $link, $icon, $related_type, $related_id);
        
        if ($stmt->execute()) {
            $notification_id = $stmt->insert_id;
            $stmt->close();
            
            // Envoyer via WebSocket si disponible
            $this->sendWebSocketNotification($user_id, $notification_id);
            
            return $notification_id;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Créer une notification pour plusieurs utilisateurs
     */
    public function createBulk($user_ids, $type, $title, $message, $link = null, $icon = 'bell', $related_type = null, $related_id = null) {
        $notification_ids = [];
        foreach ($user_ids as $user_id) {
            $id = $this->create($user_id, $type, $title, $message, $link, $icon, $related_type, $related_id);
            if ($id) {
                $notification_ids[] = $id;
            }
        }
        return $notification_ids;
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notification_id, $user_id) {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = TRUE, read_at = NOW() 
             WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $notification_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead($user_id) {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = TRUE, read_at = NOW() 
             WHERE user_id = ? AND is_read = FALSE"
        );
        $stmt->bind_param("i", $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getUserNotifications($user_id, $limit = 50, $offset = 0, $unread_only = false) {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        if ($unread_only) {
            $sql .= " AND is_read = FALSE";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $notifications;
    }
    
    /**
     * Compter les notifications non lues
     */
    public function getUnreadCount($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM notifications 
             WHERE user_id = ? AND is_read = FALSE"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] ?? 0;
    }
    
    /**
     * Supprimer une notification
     */
    public function delete($notification_id, $user_id) {
        $stmt = $this->conn->prepare(
            "DELETE FROM notifications WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $notification_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Supprimer les anciennes notifications (plus de 30 jours)
     */
    public function cleanOldNotifications($days = 30) {
        $stmt = $this->conn->prepare(
            "DELETE FROM notifications 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND is_read = TRUE"
        );
        $stmt->bind_param("i", $days);
        $result = $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $affected_rows;
    }
    
    /**
     * Envoyer une notification via WebSocket
     */
    private function sendWebSocketNotification($user_id, $notification_id) {
        try {
            // Récupérer les détails de la notification
            $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE id = ?");
            $stmt->bind_param("i", $notification_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $notification = $result->fetch_assoc();
            $stmt->close();
            
            if (!$notification) {
                return false;
            }
            
            // Préparer le message WebSocket
            $ws_message = json_encode([
                'type' => 'notification',
                'user_id' => $user_id,
                'data' => $notification
            ]);
            
            // Envoyer au serveur WebSocket via socket TCP
            $socket = @fsockopen('localhost', 8080, $errno, $errstr, 1);
            if ($socket) {
                fwrite($socket, $ws_message);
                fclose($socket);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("WebSocket notification error: " . $e->getMessage());
            return false;
        }
    }
}

// Fonctions helper pour créer des notifications spécifiques

/**
 * Notification pour un nouvel événement
 */
function notifyNewEvent($event_id, $event_title) {
    global $conn;
    $notif = new NotificationManager($conn);
    
    // Notifier tous les utilisateurs
    $stmt = $conn->query("SELECT id FROM users");
    $user_ids = array_column($stmt->fetch_all(MYSQLI_ASSOC), 'id');
    $stmt->close();
    
    return $notif->createBulk(
        $user_ids,
        'event',
        'Nouvel événement',
        "Nouveau : $event_title",
        "evenements.php?id=$event_id",
        'calendar-alt',
        'event',
        $event_id
    );
}

/**
 * Notification pour un changement dans les élections
 */
function notifyElectionUpdate($election_id, $message) {
    global $conn;
    $notif = new NotificationManager($conn);
    
    // Notifier tous les utilisateurs
    $stmt = $conn->query("SELECT id FROM users");
    $user_ids = array_column($stmt->fetch_all(MYSQLI_ASSOC), 'id');
    $stmt->close();
    
    return $notif->createBulk(
        $user_ids,
        'election',
        'Mise à jour des élections',
        $message,
        "elections.php",
        'vote-yea',
        'election',
        $election_id
    );
}

/**
 * Notification quand un admin traite une suggestion
 */
function notifySuggestionProcessed($user_id, $suggestion_id, $status) {
    global $conn;
    $notif = new NotificationManager($conn);
    
    $messages = [
        'approved' => 'Votre suggestion a été approuvée !',
        'rejected' => 'Votre suggestion a été examinée',
        'pending' => 'Votre suggestion est en cours d\'examen'
    ];
    
    return $notif->create(
        $user_id,
        'suggestion',
        'Suggestion mise à jour',
        $messages[$status] ?? 'Votre suggestion a été traitée',
        "suggestion.php",
        'lightbulb',
        'suggestion',
        $suggestion_id
    );
}

/**
 * Notification quand un admin traite un signalement
 */
function notifyReportProcessed($user_id, $report_id, $action_taken) {
    global $conn;
    $notif = new NotificationManager($conn);
    
    return $notif->create(
        $user_id,
        'report',
        'Signalement traité',
        "Votre signalement a été traité : $action_taken",
        "signalement.php",
        'flag',
        'report',
        $report_id
    );
}

/**
 * Notification pour une mention dans une discussion
 */
function notifyMention($user_id, $mentioner_name, $context, $link) {
    global $conn;
    $notif = new NotificationManager($conn);
    
    return $notif->create(
        $user_id,
        'mention',
        'Vous avez été mentionné',
        "$mentioner_name vous a mentionné dans $context",
        $link,
        'at',
        'mention',
        null
    );
}

/**
 * Notification pour un nouveau message
 */
function notifyNewMessage($user_id, $sender_name) {
    global $conn;
    $notif = new NotificationManager($conn);
    
    return $notif->create(
        $user_id,
        'message',
        'Nouveau message',
        "Vous avez reçu un message de $sender_name",
        "messaging.php",
        'envelope',
        'message',
        null
    );
}
