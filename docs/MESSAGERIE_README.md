# Messagerie - Système AJAX (Sans WebSocket)

## 📝 Description

La messagerie fonctionne maintenant automatiquement avec **AJAX Polling** au lieu de WebSocket. 
**Aucun serveur WebSocket n'est nécessaire !**

## ✅ Avantages

- ✅ **Pas de configuration requise** - Fonctionne directement avec PHP/MySQL
- ✅ **Pas de terminal** - Aucun serveur à lancer manuellement
- ✅ **Compatible partout** - Fonctionne sur tous les hébergements web
- ✅ **Simple à maintenir** - Pas de dépendances externes
- ✅ **Temps réel** - Mise à jour toutes les 2 secondes

## 🚀 Comment ça marche ?

1. **AJAX Polling** : Le navigateur vérifie automatiquement les nouveaux messages toutes les 2 secondes
2. **Indicateurs non lus** : Mise à jour toutes les 5 secondes
3. **Base de données MySQL** : Tous les messages sont stockés dans la table `discussion`

## 📁 Fichiers principaux

- `messaging.php` - Interface de messagerie
- `js/messaging.js` - Logique JavaScript avec AJAX
- `send_message.php` - Envoi de messages (API)
- `get_messages.php` - Récupération des messages (API)
- `get_new_messages.php` - Polling des nouveaux messages (API)
- `get_unread_counts.php` - Compteur de messages non lus (API)
- `mark_messages_read.php` - Marquer comme lu (API)

## 🔧 Configuration

Aucune configuration nécessaire ! Assurez-vous simplement que :
- ✅ PHP est installé et configuré
- ✅ MySQL est actif
- ✅ La table `discussion` existe dans votre base de données

## 🗄️ Structure de la table `discussion`

```sql
CREATE TABLE IF NOT EXISTS discussion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    content TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    INDEX idx_conversation (sender_id, recipient_id, sent_at),
    INDEX idx_unread (recipient_id, is_read)
);
```

## 🎯 Fonctionnalités

- ✅ Envoi et réception de messages en temps réel
- ✅ Indicateurs de messages non lus
- ✅ Marquage automatique comme lu
- ✅ Interface responsive (mobile, tablette, desktop)
- ✅ Gestion des erreurs
- ✅ Limite de 1000 caractères par message
- ✅ Nettoyage automatique des anciens messages (via delete_old_messages.php)

## 📱 Responsive Design

- **Desktop** : Liste de contacts + fenêtre de chat côte à côte
- **Mobile** : Navigation avec bouton retour, vue plein écran
- **Tablette** : Vue adaptée avec largeurs ajustées

## 🔒 Sécurité

- ✅ Protection CSRF avec tokens
- ✅ Validation des entrées utilisateur
- ✅ Échappement des sorties HTML (protection XSS)
- ✅ Vérification des sessions utilisateur
- ✅ Requêtes préparées (protection SQL injection)

## ⚡ Performance

- Polling intelligent (s'arrête quand pas de conversation active)
- Limite de 50 messages par requête
- Cache des messages affichés (pas de doublons)
- Debouncing sur le redimensionnement
- Lazy loading des images de profil

## 🆚 Comparaison WebSocket vs AJAX

| Aspect | WebSocket | AJAX Polling |
|--------|-----------|--------------|
| Configuration | Serveur externe requis | Aucune |
| Déploiement | Complexe | Simple |
| Compatibilité | Limitée | Universelle |
| Temps réel | Instantané | ~2 secondes |
| Maintenance | Difficile | Facile |

## 🔄 Migration depuis WebSocket

Si vous utilisiez l'ancienne version WebSocket :
1. ✅ Aucune modification de base de données requise
2. ✅ Les anciens messages restent accessibles
3. ✅ Supprimez `websocket_server.php` (optionnel)
4. ✅ Rafraîchissez simplement la page !

## 📞 Support

Pour toute question ou problème, vérifiez :
1. Les logs d'erreur PHP
2. La console du navigateur (F12)
3. La connexion à la base de données
4. Les permissions des fichiers

---

**Dernière mise à jour** : 30 novembre 2025
**Version** : 2.0 (AJAX Polling)
