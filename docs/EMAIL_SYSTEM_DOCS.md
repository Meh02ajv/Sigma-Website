# 📧 Système d'Emails - SIGMA Alumni

## 🎯 Vue d'Ensemble

Le système d'emails utilise **PHPMailer** avec **Gmail SMTP** pour envoyer des notifications automatiques aux utilisateurs.

**Status actuel:**
- ✅ **XAMPP (Local):** Fonctionne parfaitement
- ❌ **InfinityFree:** Ne fonctionne pas (ports SMTP bloqués)
- ✅ **Hébergement Payant:** Fonctionne parfaitement

> 💡 **Note:** Pour InfinityFree, consultez [DEPLOYMENT.md](DEPLOYMENT.md) pour les alternatives (SendGrid API, Mailgun API)

---

## 📧 Types d'Emails Envoyés

### 1. Email de Bienvenue
**Déclencheur:** Création de compte et complétion du profil  
**Fichier:** [create_profile.php](../create_profile.php)  
**Fonction:** `sendEmail()`

**Contenu:**
- Message de bienvenue personnalisé
- Liste des fonctionnalités disponibles
- Lien vers le yearbook
- Guide de démarrage rapide

---

### 2. Notification Administrateur (Nouveau Profil)
**Déclencheur:** Création de nouveau profil utilisateur  
**Destinataire:** `gojomeh137@gmail.com`  
**Fichier:** [create_profile.php](../create_profile.php)

**Contenu:**
- Nom complet du nouvel utilisateur
- Email
- Promotion (année)
- Lien rapide vers le profil admin

---

### 3. Confirmation de Vote
**Déclencheur:** Soumission de vote dans une élection  
**Fichier:** [elections.php](../elections.php)  
**Fonction:** `sendVoteConfirmationEmail()`

**Contenu:**
- Confirmation que le vote a été enregistré
- Date et heure du vote
- Rappel que le vote est définitif
- Lien vers la page des élections

---

### 4. Publication des Résultats d'Élection
**Déclencheur:** Admin publie les résultats  
**Destinataires:** Tous les votants de cette élection  
**Fichier:** [publish_results.php](../publish_results.php)  
**Fonction:** `sendResultsNotificationEmails()`

**Contenu:**
- Notification que les résultats sont disponibles
- Lien direct vers les résultats
- Remerciement pour la participation

---

### 5. Démarrage des Votes
**Déclencheur:** Admin démarre une élection  
**Destinataires:** Tous les utilisateurs vérifiés  
**Fonction:** `sendVotingStartNotificationEmails()`

**Contenu:**
- Notification qu'une nouvelle élection est ouverte
- Positions disponibles
- Date limite de vote
- Lien direct pour voter

---

### 6. Réinitialisation de Mot de Passe
**Déclencheur:** Demande de réinitialisation  
**Fichier:** [password_reset.php](../password_reset.php)

**Contenu:**
- Lien sécurisé avec token unique
- Bouton cliquable rouge
- Expiration: 1 heure
- Usage unique

---

### 7. Notifications d'Anniversaire (CRON)
**Déclencheur:** Tâche automatique quotidienne  
**Fichier:** [cron_birthday.php](../cron_birthday.php)

**Contenu:**
- Message d'anniversaire personnalisé
- Lien vers la messagerie communautaire
- Design festif

---

### 8. Notification Nouvel An (CRON)
**Déclencheur:** 1er janvier automatique  
**Fichier:** [cron_new_year.php](../cron_new_year.php)

**Contenu:**
- Vœux de bonne année
- Récapitulatif de l'année
- Lien vers le yearbook

---

## 🔧 Architecture Technique

### Fichier Principal: send_email.php

**Structure:**
```php
<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 4 fonctions principales:
function sendEmail($to, $recipientName, $subject, $body, $altBody = null)
function sendVoteConfirmationEmail($userId, $electionId)
function sendResultsNotificationEmails($electionId)
function sendVotingStartNotificationEmails($electionId)
```

**Caractéristiques:**
- ✅ PHPMailer avec SMTP Gmail
- ✅ TLS encryption (port 587)
- ✅ Templates HTML professionnels
- ✅ Version texte alternative (AltBody)
- ✅ Gestion d'erreurs avec try-catch
- ✅ Logging détaillé

---

## ⚙️ Configuration SMTP

### Dans config.php (lignes 135-165)

```php
// Configuration Email SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'gojomeh137@gmail.com');
define('SMTP_PASSWORD', 'vvvc qbzg sfey jkvi');  // App Password Gmail
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', 'gojomeh137@gmail.com');
define('SMTP_FROM_NAME', 'Communauté Sigma');
define('SMTP_REPLY_TO_EMAIL', 'support@votre-domaine.com');
define('SMTP_REPLY_TO_NAME', 'Support SIGMA');
```

**⚠️ Important:**
- Utiliser un **mot de passe d'application Gmail** (pas le mot de passe principal)
- Ne JAMAIS versionner config.php (dans .gitignore)

---

## 🚀 Utilisation

### Envoyer un Email Simple

```php
require 'send_email.php';

$result = sendEmail(
    $to = 'destinataire@example.com',
    $recipientName = 'Jean Dupont',
    $subject = 'Test Email',
    $body = '<h1>Bonjour</h1><p>Ceci est un test</p>',
    $altBody = 'Bonjour, Ceci est un test'
);

if ($result) {
    echo "Email envoyé avec succès!";
} else {
    echo "Erreur lors de l'envoi";
}
```

### Envoyer une Confirmation de Vote

```php
require 'send_email.php';

sendVoteConfirmationEmail(
    $userId = 123,
    $electionId = 5
);
```

### Notifier les Résultats

```php
require 'send_email.php';

$emailsSent = sendResultsNotificationEmails($electionId = 5);
echo "$emailsSent emails envoyés";
```

---

## 🧪 Tests et Débogage

### Test Local (XAMPP)

1. **Créer un compte test:**
   ```
   http://localhost/Sigma-Website/signup.php
   ```

2. **Vérifier la réception:**
   - Email de bienvenue dans votre boîte
   - Notification admin à gojomeh137@gmail.com

3. **Tester le vote:**
   - Créer une élection dans admin.php
   - Voter
   - Vérifier l'email de confirmation

### Logs et Débogage

**Logs PHP (Windows XAMPP):**
```
c:\xampp\php\logs\php_error_log
```

**Logs personnalisés dans send_email.php:**
```php
error_log("Email envoyé avec succès à: $to - Sujet: $subject");
error_log("Erreur email: " . $mail->ErrorInfo);
```

**Vérifier l'envoi:**
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

---

## ⚠️ Résolution de Problèmes

### Emails ne sont pas envoyés

**Sur XAMPP (Local):**
1. Vérifier que vendor/phpmailer/ existe
2. Vérifier SMTP credentials dans config.php
3. Vérifier connexion Internet
4. Consulter les logs: `c:\xampp\php\logs\php_error_log`

**Sur InfinityFree:**
- ❌ **NORMAL** - Ports SMTP bloqués
- Solutions: [DEPLOYMENT.md](DEPLOYMENT.md) (SendGrid API, Mailgun API)

**Sur Hébergement Payant:**
1. Vérifier vendor/ uploadé
2. Vérifier config.php SMTP settings
3. Tester avec email simple

---

### Emails vont dans SPAM

**Solutions:**
1. Configurer SPF dans DNS: [GUIDE_DELIVRABILITE_EMAILS.md](GUIDE_DELIVRABILITE_EMAILS.md)
2. Configurer DKIM
3. Utiliser domaine professionnel (pas @gmail.com)
4. Utiliser SendGrid/Mailgun en production

---

### Erreur "SMTP connect() failed"

**Causes:**
- Credentials incorrects
- Port bloqué (InfinityFree)
- Firewall bloque la connexion
- Gmail bloque l'accès

**Solutions:**
1. Vérifier App Password Gmail (pas mot de passe normal)
2. Activer "Accès moins sécurisés" Gmail
3. Vérifier port (587 pour TLS, 465 pour SSL)

---

## 📊 Statistiques et Monitoring

### Tracking des Emails Envoyés

Le système log automatiquement:
- ✅ Email envoyé: destinataire, sujet, timestamp
- ❌ Erreur: détails, stack trace
- 📊 Nombre d'emails groupés (élections)

**Exemple de log:**
```
[06-Mar-2026 14:30:45] Email envoyé avec succès à: user@example.com - Sujet: Bienvenue sur SIGMA Alumni
[06-Mar-2026 14:35:12] Erreur email: SMTP connect() failed
```

---

## 🔐 Sécurité

### Protections Implémentées

- ✅ **TLS Encryption** (port 587)
- ✅ **SMTP Authentication** (username/password)
- ✅ **XSS Protection** (htmlspecialchars pour contenu dynamique)
- ✅ **Token sécurisé** (réinitialisation mot de passe, 1h expiration)
- ✅ **Rate limiting** (CRON jobs espacés)
- ✅ **Validation email** (filter_var FILTER_VALIDATE_EMAIL)

### Bonnes Pratiques

1. **Ne jamais exposer credentials SMTP**
2. **Utiliser App Password Gmail** (pas mot de passe principal)
3. **Valider tous les emails** avant envoi
4. **Limiter le nombre d'envois** (éviter blacklist)
5. **Logger les erreurs** (pas les succès en production)

6. **Utiliser templates HTML professionnels**
7. **Inclure version texte alternative**

---

## 📚 Documentation Associée

- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Guide de déploiement (InfinityFree vs hébergement payant)
- **[GUIDE_DELIVRABILITE_EMAILS.md](GUIDE_DELIVRABILITE_EMAILS.md)** - Améliorer la délivrabilité (SPF, DKIM, DMARC)
- **[TESTS_EMAILS.md](TESTS_EMAILS.md)** - Procédures de test des emails
- **[CONFIGURATION_CRON.md](CONFIGURATION_CRON.md)** - Configuration des emails automatiques (anniversaires, nouvel an)

---

## 🎯 Points Clés

### ✅ Ce qui fonctionne
- PHPMailer avec Gmail SMTP sur XAMPP
- 4 fonctions d'envoi d'emails
- Templates HTML professionnels
- Gestion d'erreurs robuste
- Logs détaillés

### ⚠️ Limitations Connues
- **InfinityFree:** SMTP bloqué (ports 25, 587, 465)
- **Gmail:** Limite de ~500 emails/jour
- **Délivrabilité:** Emails peuvent aller en spam sans SPF/DKIM

### 🚀 Migration Vers Production
Lors du passage à un hébergement payant:
1. ✅ Uploader vendor/phpmailer/
2. ✅ Mettre à jour config.php (DB credentials)
3. ✅ Les emails fonctionneront immédiatement
4. ✅ Pas de modification de code nécessaire

---

**Dernière mise à jour:** 6 mars 2026
