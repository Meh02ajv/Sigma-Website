# Système de Notifications par Email - Élections SIGMA Alumni

## 📧 Fonctionnalités

Le système envoie automatiquement des emails dans deux cas :

### 1. Confirmation de Vote
**Quand ?** Immédiatement après qu'un utilisateur soumet son vote

**Contenu :**
- Confirmation que le vote a été enregistré
- Liste des positions pour lesquelles l'utilisateur a voté
- Date et heure du vote
- Rappel que le vote est définitif
- Lien vers la page des élections

### 2. Publication des Résultats
**Quand ?** Lorsque l'administrateur publie les résultats via l'interface admin

**Destinataires :** Tous les utilisateurs qui ont voté pour cette élection

**Contenu :**
- Notification que les résultats sont disponibles
- Lien direct vers la section des résultats
- Remerciement pour la participation

## 🔧 Configuration

### Prérequis
- PHPMailer installé (déjà fait via Composer)
- Configuration SMTP dans `config.php`

### Paramètres SMTP (dans config.php)
```php
SMTP_HOST = 'smtp.gmail.com'
SMTP_USERNAME = 'gojomeh137@gmail.com'
SMTP_PASSWORD = 'vvvc qbzg sfey jkvi'
SMTP_PORT = 587
SMTP_FROM_EMAIL = 'gojomeh137@gmail.com'
SMTP_FROM_NAME = 'Communauté Sigma'
```

## 📝 Fichiers Créés

1. **send_email.php**
   - Fonctions réutilisables pour l'envoi d'emails
   - `sendEmail()` : Fonction générique
   - `sendVoteConfirmationEmail()` : Email de confirmation de vote
   - `sendResultsNotificationEmails()` : Emails pour tous les votants

2. **publish_results.php**
   - Script pour publier les résultats
   - Envoie automatiquement les notifications

3. **test_emails.php**
   - Page de test pour vérifier le fonctionnement
   - Accessible uniquement pour les tests

## 🚀 Utilisation

### Pour les utilisateurs (automatique)
1. L'utilisateur vote sur elections.php
2. ✅ Email de confirmation envoyé automatiquement
3. L'utilisateur reçoit l'email dans sa boîte

### Pour l'administrateur
1. Se connecter à admin.php
2. Aller dans l'onglet "Élections"
3. Cliquer sur "Publier les résultats" pour une élection terminée
4. ✅ Tous les votants reçoivent un email automatiquement

## 🧪 Tests

### Tester le système
1. Accéder à : `http://localhost/Sigma-Website/test_emails.php`
2. Vérifier que les 2 emails de test sont reçus
3. Vérifier le dossier spam si nécessaire

### Test réel
1. Créer une élection test
2. Voter avec un compte utilisateur
3. Vérifier la réception de l'email de confirmation
4. Publier les résultats depuis admin.php
5. Vérifier la réception de l'email de résultats

## 📊 Logs

Tous les envois d'emails sont enregistrés dans les logs PHP :
- `c:\xampp\php\logs\php_error_log` (Windows)
- Format : `Email envoyé avec succès à: [email] - Sujet: [sujet]`

## ⚠️ Troubleshooting

### L'email n'est pas reçu
1. Vérifier le dossier spam
2. Vérifier les logs PHP pour les erreurs
3. Vérifier la configuration SMTP dans config.php
4. Tester avec test_emails.php

### Gmail bloque les emails
- Vérifier que le mot de passe d'application est correct
- Activer "Autoriser les applications moins sécurisées" si nécessaire
- Utiliser un mot de passe d'application Gmail

### Erreur SMTP
- Vérifier le port (587 pour TLS, 465 pour SSL)
- Vérifier les identifiants SMTP
- Vérifier la connexion Internet

## 🔐 Sécurité

- Les emails utilisent STARTTLS (port 587)
- Les mots de passe SMTP sont dans config.php (à ne pas versionner)
- Les emails HTML sont sécurisés avec htmlspecialchars()
- Validation CSRF pour la publication des résultats

## 📈 Statistiques

Le système affiche le nombre d'emails envoyés :
- Message de succès après publication des résultats
- Logs détaillés pour le suivi

## 🎨 Personnalisation

Pour modifier les templates d'emails, éditer les fonctions dans `send_email.php` :
- HTML et styles CSS inline
- Version texte alternatif automatique
- Design responsive pour mobile
