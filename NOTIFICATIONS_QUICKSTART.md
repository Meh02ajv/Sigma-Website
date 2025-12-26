# 🚀 GUIDE DE DÉMARRAGE RAPIDE - SYSTÈME DE NOTIFICATIONS

## ⚡ Installation en 5 minutes

### Étape 1 : Exécuter le script SQL (OBLIGATOIRE)

1. Ouvrez **phpMyAdmin** : http://localhost/phpmyadmin
2. Sélectionnez la base de données **laho**
3. Cliquez sur l'onglet **SQL**
4. Copiez-collez le contenu de `sql/create_notifications_system.sql`
5. Cliquez sur **Exécuter**

✅ Vous devriez voir : "2 tables créées, X lignes affectées"

---

### Étape 2 : Tester le système

1. **Accédez à la page notifications** : http://localhost/Sigma-Website/notifications.php
2. Vous devriez voir "Aucune notification pour le moment"

---

### Étape 3 : Créer une notification de test

Dans phpMyAdmin, exécutez ce test (remplacez `1` par votre ID utilisateur) :

```sql
-- Insérer une notification de test
INSERT INTO notifications (user_id, type, title, message, link, icon)
VALUES (1, 'event', 'Notification de test', 'Le système fonctionne !', 'dashboard.php', 'check-circle');
```

Rafraîchissez `notifications.php` - vous devriez voir la notification !

---

### Étape 4 : Vérifier le badge

1. Allez sur **yearbook.php**
2. Le badge de notification devrait afficher **1** à côté de l'icône 🔔

---

## 🎯 CRÉER DES NOTIFICATIONS DANS VOS PAGES

### Exemple 1 : Notifier tous les utilisateurs d'un nouvel événement

```php
<?php
require 'includes/notification_manager.php';

// Après avoir créé votre événement
$event_id = 123; // ID de l'événement créé
$event_title = "Soirée d'intégration 2025";

notifyNewEvent($event_id, $event_title);
?>
```

### Exemple 2 : Notifier un utilisateur spécifique

```php
<?php
require 'includes/notification_manager.php';

$notif = new NotificationManager($conn);

$notif->create(
    $user_id,        // ID de l'utilisateur
    'message',       // Type
    'Nouveau message',
    'Vous avez reçu un message de Jean',
    'messaging.php',
    'envelope'
);
?>
```

### Exemple 3 : Notifier lors d'une suggestion traitée

```php
<?php
require 'includes/notification_manager.php';

// Après avoir traité la suggestion
notifySuggestionProcessed(
    $user_id,
    $suggestion_id,
    'approved' // ou 'rejected', 'pending'
);
?>
```

---

## ✨ AJOUTER LE BADGE DANS D'AUTRES PAGES

### Dans le HTML

```html
<a href="notifications.php" aria-label="Notifications" class="notification-icon">
    <i class="fas fa-bell"></i>
    <span class="unread-count" id="notifications-count"></span>
</a>
```

### Dans le JavaScript

```javascript
// Fonction pour mettre à jour le compteur
async function updateNotificationsCount() {
    try {
        const response = await fetch('get_notifications.php?action=count');
        const data = await response.json();
        if (data.success) {
            const badge = document.getElementById('notifications-count');
            badge.textContent = data.count;
            badge.classList.toggle('show', data.count > 0);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Appeler au chargement
document.addEventListener('DOMContentLoaded', () => {
    updateNotificationsCount();
    setInterval(updateNotificationsCount, 30000); // Rafraîchir toutes les 30s
});
```

---

## 📋 CHECKLIST POST-INSTALLATION

- [ ] Script SQL exécuté ✓
- [ ] Page notifications.php accessible ✓
- [ ] Badge visible sur yearbook.php ✓
- [ ] Notification de test créée et visible ✓
- [ ] Badge à ajouter sur dashboard.php
- [ ] Badge à ajouter sur evenements.php
- [ ] Badge à ajouter sur messaging.php
- [ ] Badge à ajouter sur album.php
- [ ] Intégrer dans evenements.php (création événement)
- [ ] Intégrer dans elections.php (publication résultats)
- [ ] Intégrer dans admin.php (traitement suggestions)
- [ ] Intégrer dans signalement.php (traitement rapports)

---

## 🎨 TYPES DE NOTIFICATIONS DISPONIBLES

| Type | Icône | Couleur | Utilisation |
|------|-------|---------|-------------|
| `event` | calendar-alt | Bleu | Nouveaux événements |
| `election` | vote-yea | Violet | Élections |
| `message` | envelope | Vert | Messages |
| `suggestion` | lightbulb | Orange | Suggestions |
| `report` | flag | Rouge | Signalements |
| `mention` | at | Rose | Mentions |

---

## 🐛 PROBLÈMES FRÉQUENTS

### Le badge ne s'affiche pas

**Solution** : Vérifiez dans la console du navigateur (F12) s'il y a des erreurs JavaScript.

### Les notifications ne se créent pas

**Solution** : Vérifiez que vous avez bien :
1. Exécuté le script SQL
2. Inclus `require 'includes/notification_manager.php';`
3. Passé le bon `$user_id`

### Erreur "Table doesn't exist"

**Solution** : Vous n'avez pas exécuté le script SQL. Retournez à l'Étape 1.

---

## 📖 DOCUMENTATION COMPLÈTE

Pour plus de détails, consultez **NOTIFICATIONS_README.md**

---

**Prêt à notifier vos utilisateurs ! 🚀**
