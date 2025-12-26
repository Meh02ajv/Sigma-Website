# 📋 PROCHAINES ÉTAPES - SYSTÈME DE NOTIFICATIONS

Le système de notifications est **100% implémenté** ! Voici comment le finaliser et l'utiliser.

---

## ✅ CE QUI EST DÉJÀ FAIT

- ✅ Base de données (tables + indexes)
- ✅ API REST complète (get_notifications.php)
- ✅ Classe NotificationManager avec toutes les fonctions
- ✅ Page centre de notifications (notifications.php)
- ✅ Badge de notifications dans yearbook.php
- ✅ 6 types de notifications prédéfinis
- ✅ Page de test (test_notifications.php)
- ✅ Documentation complète

---

## 🚀 ÉTAPES SUIVANTES (PAR PRIORITÉ)

### 1️⃣ ÉTAPE CRITIQUE : Exécuter le SQL (5 minutes)

**OBLIGATOIRE** - Sans cela, rien ne fonctionnera !

1. Ouvrir **phpMyAdmin** : http://localhost/phpmyadmin
2. Sélectionner la base de données **`laho`**
3. Onglet **SQL**
4. Copier-coller le contenu de `sql/create_notifications_system.sql`
5. Cliquer sur **Exécuter**
6. Vérifier : "2 tables créées" en vert

---

### 2️⃣ TESTER LE SYSTÈME (10 minutes)

**Vérifier que tout fonctionne correctement**

1. Aller sur : http://localhost/Sigma-Website/test_notifications.php
2. Cliquer sur **"Événement"** pour créer une notification de test
3. Vérifier :
   - ✅ Le badge dans yearbook.php affiche "1"
   - ✅ La notification apparaît dans notifications.php
   - ✅ Le clic sur la notification fonctionne
4. Tester les autres types de notifications
5. Tester "Marquer comme lue" et "Supprimer"

---

### 3️⃣ AJOUTER LE BADGE PARTOUT (15 minutes)

**Cohérence visuelle dans toute l'application**

Ajouter le badge de notifications dans les fichiers suivants :

#### Fichiers à modifier :
- `dashboard.php`
- `messaging.php`
- `evenements.php`
- `album.php`
- `elections.php`
- Tout autre header personnalisé

#### Code à ajouter dans chaque header :

```html
<!-- Badge de notifications (dans le menu de navigation) -->
<a href="notifications.php" class="notification-link">
    <i class="fas fa-bell"></i>
    <span id="notifications-count" class="notification-badge"></span>
</a>
```

#### CSS à ajouter :

```css
.notification-link {
    position: relative;
    color: white;
    margin: 0 15px;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    display: none;
}

.notification-badge.has-notifications {
    display: block;
}
```

#### JavaScript à ajouter avant `</body>` :

```javascript
// Mise à jour du badge de notifications
async function updateNotificationsCount() {
    try {
        const response = await fetch('get_notifications.php?action=count');
        const data = await response.json();
        
        const badge = document.getElementById('notifications-count');
        if (data.count > 0) {
            badge.textContent = data.count;
            badge.classList.add('has-notifications');
        } else {
            badge.classList.remove('has-notifications');
        }
    } catch (error) {
        console.error('Erreur récupération notifications:', error);
    }
}

// Rafraîchir toutes les 30 secondes
updateNotificationsCount();
setInterval(updateNotificationsCount, 30000);
```

---

### 4️⃣ INTÉGRER DANS LES ÉVÉNEMENTS (10 minutes)

**Créer des notifications quand un événement est ajouté**

Dans `evenements.php`, après la création d'un événement :

```php
// Ajouter en haut du fichier
require 'includes/notification_manager.php';

// Après l'insertion de l'événement dans la base
$event_id = $conn->insert_id;
$event_title = $_POST['title']; // Ou le nom de votre variable

// Notifier tous les utilisateurs
notifyNewEvent($event_id, $event_title);
```

---

### 5️⃣ INTÉGRER DANS LES ÉLECTIONS (10 minutes)

**Notifier quand les résultats sont publiés**

Dans `elections.php` ou `publish_results.php`, après publication :

```php
require 'includes/notification_manager.php';

// Après avoir publié les résultats
$election_id = $_POST['election_id'];
$message = "Les résultats des élections sont maintenant disponibles !";

notifyElectionUpdate($election_id, $message);
```

---

### 6️⃣ INTÉGRER DANS L'ADMIN (15 minutes)

**Notifier les utilisateurs des actions admin**

Dans `admin.php`, quand une suggestion/signalement est traité :

```php
require 'includes/notification_manager.php';

// Quand une suggestion est approuvée/rejetée
notifySuggestionProcessed($user_id, $suggestion_id, 'approved'); // ou 'rejected'

// Quand un signalement est traité
notifyReportProcessed($user_id, $report_id, "Utilisateur averti");
```

---

### 7️⃣ AJOUTER DÉTECTION DES MENTIONS (20 minutes)

**Notifier quand quelqu'un mentionne un utilisateur**

Dans `send_message.php` ou tout système de commentaires :

```php
require 'includes/notification_manager.php';

// Détecter les mentions @username dans le message
$message_content = $_POST['message'];
preg_match_all('/@(\w+)/', $message_content, $matches);

if (!empty($matches[1])) {
    foreach ($matches[1] as $username) {
        // Trouver l'utilisateur mentionné
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            notifyMention(
                $user['id'],
                $_SESSION['user_name'],
                "un message",
                "messaging.php?thread=" . $thread_id
            );
        }
        $stmt->close();
    }
}
```

---

### 8️⃣ CRÉER PAGE PRÉFÉRENCES (30 minutes)

**Permettre aux utilisateurs de gérer leurs préférences**

Dans `settings.php`, ajouter section :

```php
<?php
require 'includes/notification_manager.php';
$notif = new NotificationManager($conn);

// Récupérer les préférences actuelles
$stmt = $conn->prepare("
    SELECT * FROM notification_preferences 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$prefs = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si pas de préférences, utiliser valeurs par défaut
if (!$prefs) {
    $prefs = [
        'email_events' => 1,
        'email_elections' => 1,
        'email_messages' => 1,
        'email_suggestions' => 1,
        'email_reports' => 1,
        'email_mentions' => 1,
        'push_events' => 1,
        'push_elections' => 1,
        'push_messages' => 1,
        'push_suggestions' => 1,
        'push_reports' => 1,
        'push_mentions' => 1
    ];
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("
        INSERT INTO notification_preferences (
            user_id, email_events, email_elections, email_messages,
            email_suggestions, email_reports, email_mentions,
            push_events, push_elections, push_messages,
            push_suggestions, push_reports, push_mentions
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            email_events = VALUES(email_events),
            email_elections = VALUES(email_elections),
            email_messages = VALUES(email_messages),
            email_suggestions = VALUES(email_suggestions),
            email_reports = VALUES(email_reports),
            email_mentions = VALUES(email_mentions),
            push_events = VALUES(push_events),
            push_elections = VALUES(push_elections),
            push_messages = VALUES(push_messages),
            push_suggestions = VALUES(push_suggestions),
            push_reports = VALUES(push_reports),
            push_mentions = VALUES(push_mentions)
    ");
    
    $stmt->bind_param("iiiiiiiiiiiii",
        $user_id,
        $_POST['email_events'] ?? 0,
        $_POST['email_elections'] ?? 0,
        $_POST['email_messages'] ?? 0,
        $_POST['email_suggestions'] ?? 0,
        $_POST['email_reports'] ?? 0,
        $_POST['email_mentions'] ?? 0,
        $_POST['push_events'] ?? 0,
        $_POST['push_elections'] ?? 0,
        $_POST['push_messages'] ?? 0,
        $_POST['push_suggestions'] ?? 0,
        $_POST['push_reports'] ?? 0,
        $_POST['push_mentions'] ?? 0
    );
    
    $stmt->execute();
    $stmt->close();
}
?>

<h3>Préférences de notifications</h3>

<form method="POST">
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Email</th>
                <th>Push</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Événements</td>
                <td><input type="checkbox" name="email_events" <?php echo $prefs['email_events'] ? 'checked' : ''; ?>></td>
                <td><input type="checkbox" name="push_events" <?php echo $prefs['push_events'] ? 'checked' : ''; ?>></td>
            </tr>
            <tr>
                <td>Élections</td>
                <td><input type="checkbox" name="email_elections" <?php echo $prefs['email_elections'] ? 'checked' : ''; ?>></td>
                <td><input type="checkbox" name="push_elections" <?php echo $prefs['push_elections'] ? 'checked' : ''; ?>></td>
            </tr>
            <tr>
                <td>Messages</td>
                <td><input type="checkbox" name="email_messages" <?php echo $prefs['email_messages'] ? 'checked' : ''; ?>></td>
                <td><input type="checkbox" name="push_messages" <?php echo $prefs['push_messages'] ? 'checked' : ''; ?>></td>
            </tr>
            <tr>
                <td>Suggestions</td>
                <td><input type="checkbox" name="email_suggestions" <?php echo $prefs['email_suggestions'] ? 'checked' : ''; ?>></td>
                <td><input type="checkbox" name="push_suggestions" <?php echo $prefs['push_suggestions'] ? 'checked' : ''; ?>></td>
            </tr>
            <tr>
                <td>Signalements</td>
                <td><input type="checkbox" name="email_reports" <?php echo $prefs['email_reports'] ? 'checked' : ''; ?>></td>
                <td><input type="checkbox" name="push_reports" <?php echo $prefs['push_reports'] ? 'checked' : ''; ?>></td>
            </tr>
            <tr>
                <td>Mentions</td>
                <td><input type="checkbox" name="email_mentions" <?php echo $prefs['email_mentions'] ? 'checked' : ''; ?>></td>
                <td><input type="checkbox" name="push_mentions" <?php echo $prefs['push_mentions'] ? 'checked' : ''; ?>></td>
            </tr>
        </tbody>
    </table>
    <button type="submit">Enregistrer</button>
</form>
```

---

## 📊 CHECKLIST COMPLÈTE

### Installation de base
- [ ] SQL exécuté dans phpMyAdmin
- [ ] Test de création de notification
- [ ] Badge visible dans yearbook

### Intégration interface
- [ ] Badge ajouté dans dashboard.php
- [ ] Badge ajouté dans messaging.php
- [ ] Badge ajouté dans evenements.php
- [ ] Badge ajouté dans album.php
- [ ] Badge ajouté dans elections.php

### Intégration fonctionnelle
- [ ] Notifications lors de création d'événements
- [ ] Notifications lors de publication résultats élections
- [ ] Notifications lors du traitement suggestions
- [ ] Notifications lors du traitement signalements
- [ ] Détection des mentions @username
- [ ] Notifications pour nouveaux messages

### Fonctionnalités avancées
- [ ] Page préférences notifications
- [ ] Envoi d'emails pour notifications importantes
- [ ] Notifications desktop (browser)
- [ ] Nettoyage automatique anciennes notifications

---

## 🎯 ORDRE RECOMMANDÉ

1. **Jour 1** : Exécuter SQL + Tester (Étapes 1-2) - 15 min
2. **Jour 2** : Ajouter badges partout (Étape 3) - 15 min
3. **Jour 3** : Intégrer événements + élections (Étapes 4-5) - 20 min
4. **Jour 4** : Intégrer admin + mentions (Étapes 6-7) - 35 min
5. **Jour 5** : Page préférences (Étape 8) - 30 min

**Total estimé : 2 heures de travail réparties sur 5 jours**

---

## 🆘 BESOIN D'AIDE ?

Consultez :
- `NOTIFICATIONS_README.md` - Documentation complète
- `NOTIFICATIONS_QUICKSTART.md` - Guide démarrage rapide
- `test_notifications.php` - Exemples d'utilisation

Ou demandez de l'aide ! 💬

---

## 🎉 PRÊT À COMMENCER ?

Commencez par **Étape 1** (SQL) maintenant ! ⏱️
