# 🔔 SYSTÈME DE NOTIFICATIONS EN TEMPS RÉEL

Documentation complète du système de notifications implémenté sur SIGMA Alumni.

---

## 📋 STRUCTURE DU SYSTÈME

### 1. Base de données

Deux nouvelles tables ont été créées :

#### Table `notifications`
Stocke toutes les notifications des utilisateurs.

```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    icon VARCHAR(50) DEFAULT 'bell',
    is_read BOOLEAN DEFAULT FALSE,
    related_type VARCHAR(50),
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Table `notification_preferences`
Permet aux utilisateurs de gérer leurs préférences de notifications.

```sql
CREATE TABLE notification_preferences (
    user_id INT PRIMARY KEY,
    email_events BOOLEAN DEFAULT TRUE,
    email_elections BOOLEAN DEFAULT TRUE,
    email_messages BOOLEAN DEFAULT TRUE,
    email_suggestions BOOLEAN DEFAULT TRUE,
    email_mentions BOOLEAN DEFAULT TRUE,
    push_events BOOLEAN DEFAULT TRUE,
    push_elections BOOLEAN DEFAULT TRUE,
    push_messages BOOLEAN DEFAULT TRUE,
    push_suggestions BOOLEAN DEFAULT TRUE,
    push_mentions BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 📁 FICHIERS CRÉÉS

### 1. `sql/create_notifications_system.sql`
Script SQL pour créer les tables du système de notifications.

### 2. `includes/notification_manager.php`
Classe PHP pour gérer les notifications :
- Créer une notification
- Créer des notifications en masse
- Marquer comme lue
- Marquer toutes comme lues
- Récupérer les notifications
- Compter les non lues
- Supprimer une notification
- Nettoyer les anciennes notifications

### 3. `get_notifications.php`
API AJAX pour récupérer et gérer les notifications :
- `?action=list` - Liste des notifications
- `?action=count` - Nombre de non lues
- `?action=mark_read` (POST) - Marquer comme lue
- `?action=mark_all_read` (POST) - Marquer toutes comme lues
- `?action=delete` (POST) - Supprimer une notification

### 4. `notifications.php`
Page dédiée à l'affichage de toutes les notifications avec :
- Statistiques (total, non lues, lues)
- Filtres (toutes, non lues, par type)
- Actions (marquer comme lue, supprimer, tout marquer)

---

## 🎯 TYPES DE NOTIFICATIONS

Le système supporte 6 types de notifications :

### 1. **Événements** (`event`)
- **Icône** : `calendar-alt`
- **Couleur** : Bleu (#2196f3)
- **Déclencheur** : Création d'un nouvel événement
- **Fonction** : `notifyNewEvent($event_id, $event_title)`

### 2. **Élections** (`election`)
- **Icône** : `vote-yea`
- **Couleur** : Violet (#9c27b0)
- **Déclencheur** : Changement dans les élections
- **Fonction** : `notifyElectionUpdate($election_id, $message)`

### 3. **Messages** (`message`)
- **Icône** : `envelope`
- **Couleur** : Vert (#4caf50)
- **Déclencheur** : Réception d'un nouveau message
- **Fonction** : `notifyNewMessage($user_id, $sender_name)`

### 4. **Suggestions** (`suggestion`)
- **Icône** : `lightbulb`
- **Couleur** : Orange (#ff9800)
- **Déclencheur** : Admin traite une suggestion
- **Fonction** : `notifySuggestionProcessed($user_id, $suggestion_id, $status)`

### 5. **Signalements** (`report`)
- **Icône** : `flag`
- **Couleur** : Rouge (#f44336)
- **Déclencheur** : Admin traite un signalement
- **Fonction** : `notifyReportProcessed($user_id, $report_id, $action_taken)`

### 6. **Mentions** (`mention`)
- **Icône** : `at`
- **Couleur** : Rose (#e91e63)
- **Déclencheur** : Mention dans une discussion
- **Fonction** : `notifyMention($user_id, $mentioner_name, $context, $link)`

---

## 🔧 INSTALLATION

### Étape 1 : Créer les tables

Exécutez le script SQL dans phpMyAdmin :

```bash
1. Ouvrez http://localhost/phpmyadmin
2. Sélectionnez la base de données "laho"
3. Cliquez sur l'onglet "SQL"
4. Copiez le contenu de sql/create_notifications_system.sql
5. Cliquez sur "Exécuter"
```

### Étape 2 : Ajouter le badge de notifications

Le badge a déjà été ajouté dans `yearbook.php`. Pour l'ajouter dans d'autres pages :

```html
<a href="notifications.php" aria-label="Notifications" class="notification-icon">
    <i class="fas fa-bell"></i>
    <span class="unread-count" id="notifications-count"></span>
</a>
```

```javascript
// JavaScript pour mettre à jour le compteur
async function updateNotificationsCount() {
    try {
        const response = await fetch('get_notifications.php?action=count');
        const data = await response.json();
        if (data.success) {
            const notifBadge = document.getElementById('notifications-count');
            notifBadge.textContent = data.count;
            notifBadge.classList.toggle('show', data.count > 0);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Appeler au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    updateNotificationsCount();
    setInterval(updateNotificationsCount, 30000); // Rafraîchir toutes les 30s
});
```

---

## 💻 UTILISATION

### Créer une notification simple

```php
require 'includes/notification_manager.php';

$notif = new NotificationManager($conn);

$notif->create(
    $user_id,        // ID de l'utilisateur
    'event',         // Type
    'Nouveau événement',  // Titre
    'Conférence sur l\'IA le 15 janvier',  // Message
    'evenements.php?id=123',  // Lien optionnel
    'calendar-alt',  // Icône Font Awesome
    'event',         // Type de ressource liée
    123              // ID de la ressource liée
);
```

### Créer des notifications en masse

```php
// Notifier tous les utilisateurs
$stmt = $conn->query("SELECT id FROM users");
$user_ids = array_column($stmt->fetch_all(MYSQLI_ASSOC), 'id');

$notif->createBulk(
    $user_ids,
    'election',
    'Résultats des élections',
    'Les résultats sont disponibles !',
    'elections.php'
);
```

### Utiliser les fonctions helper

```php
// Pour un nouvel événement
notifyNewEvent($event_id, "Soirée d'intégration 2025");

// Pour une mise à jour des élections
notifyElectionUpdate($election_id, "Les résultats sont publiés !");

// Pour une suggestion traitée
notifySuggestionProcessed($user_id, $suggestion_id, 'approved');

// Pour un signalement traité
notifyReportProcessed($user_id, $report_id, "Compte suspendu");

// Pour une mention
notifyMention($user_id, "Marie Dupont", "un commentaire", "post.php?id=456");

// Pour un nouveau message
notifyNewMessage($user_id, "Jean Martin");
```

---

## 🎨 INTÉGRATION DANS VOS PAGES

### Exemple : Notifier lors de la création d'un événement

Dans votre fichier `evenements.php` (ou équivalent) :

```php
// Après avoir créé l'événement dans la base de données
require 'includes/notification_manager.php';

// Récupérer l'ID du nouvel événement
$event_id = $conn->insert_id;

// Créer les notifications
notifyNewEvent($event_id, $event_title);
```

### Exemple : Notifier lors d'une mention

```php
// Détection des mentions dans un commentaire (ex: @JeanMartin)
preg_match_all('/@(\w+)/', $comment_text, $mentions);

foreach ($mentions[1] as $username) {
    // Trouver l'utilisateur
    $stmt = $conn->prepare("SELECT id FROM users WHERE full_name LIKE ?");
    $search = "%$username%";
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        notifyMention(
            $user['id'],
            $_SESSION['full_name'],
            "un commentaire",
            "post.php?id=$post_id#comment-$comment_id"
        );
    }
}
```

---

## 📊 STATISTIQUES ET RAPPORTS

### Nettoyer les anciennes notifications

Exécutez régulièrement (via cron ou manuellement) :

```php
$notif = new NotificationManager($conn);
$deleted = $notif->cleanOldNotifications(30); // Supprimer les +30 jours lues
echo "Supprimées : $deleted notifications";
```

### Statistiques par utilisateur

```sql
SELECT 
    user_id,
    COUNT(*) as total,
    SUM(is_read = FALSE) as unread,
    SUM(is_read = TRUE) as read
FROM notifications
WHERE user_id = ?
GROUP BY user_id;
```

### Notifications les plus fréquentes

```sql
SELECT 
    type,
    COUNT(*) as count,
    AVG(is_read) * 100 as read_percentage
FROM notifications
GROUP BY type
ORDER BY count DESC;
```

---

## 🔔 NOTIFICATIONS EN TEMPS RÉEL (WebSocket)

Le système est prêt pour l'intégration WebSocket. Lorsqu'une notification est créée, elle est automatiquement envoyée au serveur WebSocket (si actif).

### Côté serveur (websocket_server.php)

Ajoutez la gestion des notifications :

```php
$socket->on('message', function($data) use ($socket) {
    $message = json_decode($data);
    
    if ($message->type === 'notification') {
        // Envoyer la notification au bon utilisateur
        $this->sendToUser($message->user_id, $data);
    }
});
```

### Côté client (JavaScript)

```javascript
socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    
    if (data.type === 'notification') {
        // Mettre à jour le badge
        updateNotificationsCount();
        
        // Afficher une notification desktop (optionnel)
        if (Notification.permission === 'granted') {
            new Notification(data.data.title, {
                body: data.data.message,
                icon: 'img/logo.png'
            });
        }
    }
};
```

---

## 🎯 PROCHAINES ÉTAPES

### Fonctionnalités à ajouter

1. **Notifications desktop**
   ```javascript
   // Demander la permission
   Notification.requestPermission();
   ```

2. **Préférences utilisateur**
   - Page dans `settings.php` pour gérer les préférences
   - Choix email vs push pour chaque type

3. **Digest par email**
   - Envoyer un résumé quotidien/hebdomadaire
   - Script cron pour regrouper les notifications

4. **Notifications groupées**
   - "5 personnes ont commenté votre post"
   - Au lieu de 5 notifications séparées

5. **Marquage automatique comme lue**
   - Quand l'utilisateur visite la page liée

---

## 🐛 DÉPANNAGE

### Les notifications n'apparaissent pas

1. Vérifiez que les tables sont créées :
   ```sql
   SHOW TABLES LIKE 'notifications';
   ```

2. Vérifiez les erreurs PHP :
   ```php
   error_log() dans includes/notification_manager.php
   ```

3. Console du navigateur (F12) pour les erreurs JavaScript

### Le compteur ne se met pas à jour

1. Vérifiez que `get_notifications.php` est accessible
2. Vérifiez la console pour les erreurs AJAX
3. Testez manuellement : `http://localhost/Sigma-Website/get_notifications.php?action=count`

### Les notifications en temps réel ne fonctionnent pas

1. Vérifiez que le serveur WebSocket est lancé
2. Vérifiez les logs du serveur WebSocket
3. Testez la connexion WebSocket dans la console

---

## ✅ CHECKLIST D'IMPLÉMENTATION

- ✅ Tables créées dans la base de données
- ✅ Classe NotificationManager implémentée
- ✅ API AJAX fonctionnelle
- ✅ Page notifications.php créée
- ✅ Badge ajouté dans yearbook.php
- ⏳ Badge à ajouter dans les autres pages
- ⏳ Intégrer dans les événements
- ⏳ Intégrer dans les élections
- ⏳ Intégrer dans la modération (suggestions/rapports)
- ⏳ Système de mentions
- ⏳ Page de préférences
- ⏳ Notifications desktop
- ⏳ WebSocket en temps réel

---

## 📞 SUPPORT

Pour toute question ou problème, consultez la documentation complète dans `AMELIORATIONS_SUGGEREES.md`.

**Version** : 1.0  
**Date** : 26 Décembre 2025  
**Auteur** : GitHub Copilot
