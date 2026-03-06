# 🧪 Guide de Test - Système d'Emails

## ✅ Vue d'Ensemble

Ce guide détaille comment tester le système d'emails SMTP sur différents environnements.

**Environnements:**
- ✅ **XAMPP (Local)** - Fonctionne parfaitement
- ❌ **InfinityFree** - Ne fonctionne pas (ports SMTP bloqués)
- ✅ **Hébergement Payant** - Fonctionne parfaitement

---

## 1️⃣ Test du Système Complet (XAMPP Local)

### Test A: Email de Bienvenue

**Étapes:**
```
1. Ouvrir: http://localhost/Sigma-Website/signup.php
2. Créer un nouveau compte test:
   - Email: votre_email_test@gmail.com
   - Mot de passe: Test123!@#
   - Nom: Test User
3. Vérifier le code de vérification dans l'email
4. Compléter le profil sur create_profile.php
5. Vérifier la réception de l'email de bienvenue
```

**Résultat Attendu:**
```
📧 Email "Bienvenue sur SIGMA Alumni ! 🎉"
   ✅ Design moderne avec header vert
   ✅ Icône 🎉
   ✅ Liste des fonctionnalités
   ✅ Bouton "Explorer le Yearbook" cliquable
   ✅ Footer avec liens utiles
   ✅ Reçu en boîte de réception (pas spam)
```

**Vérification Admin:**
```
📧 L'admin (gojomeh137@gmail.com) reçoit:
   ✅ "Nouveau membre: Test User"
   ✅ Informations du profil
   ✅ Lien vers admin.php
```

---

### Test B: Réinitialisation de Mot de Passe

**Étapes:**
```
1. Ouvrir: http://localhost/Sigma-Website/password_reset.php
2. Entrer votre email
3. Cliquer "Envoyer"
4. Vérifier votre boîte mail
```

**Résultat Attendu:**
```
📧 Email avec:
   ✅ Design professionnel (header rouge)
   ✅ Icône 🔐
   ✅ Gros bouton rouge "Réinitialiser mon Mot de Passe"
   ✅ Bouton CLIQUABLE
   ✅ Lien de secours en texte
   ✅ Warning: Expire dans 1h, usage unique
   ✅ Lien fonctionne et redirige vers reset_password.php
```

**Test du lien:**
```
5. Cliquer sur le bouton rouge
6. Vous devez arriver sur: reset_password.php?token=...
7. Changer le mot de passe
8. Vérifier la connexion avec nouveau mot de passe
```

---

### Test C: Système de Vote (Élections)

**Préparation:**
```
1. Se connecter à admin.php
2. Créer une nouvelle élection:
   - Titre: "Test Élection Bureau 2026"
   - Date fin: demain
   - Statut: "ongoing"
3. Créer des positions et candidats
```

**Test Email de Démarrage:**
```
1. Dans admin.php, cliquer "Démarrer élection"
2. Tous les utilisateurs reçoivent: "Une nouvelle élection est ouverte !"
3. Vérifier l'email reçu
```

**Test Email de Confirmation de Vote:**
```
1. Se déconnecter de admin
2. Se connecter avec compte utilisateur normal
3. Aller sur elections.php
4. Voter pour un candidat
5. Vérifier l'email de confirmation
```

**Résultat Attendu:**
```
📧 Email "Confirmation de Vote - SIGMA Alumni"
   ✅ Design bleu professionnel
   ✅ Icône ✅
   ✅ "Votre vote a bien été enregistré"
   ✅ Date et heure du vote
   ✅ Positions votées listées
   ✅ Bouton "Voir l'Élection"
```

**Test Email de Résultats:**
```
1. Retourner sur admin.php
2. Cliquer "Publier les résultats"
3. Tous les votants reçoivent un email
```

**Résultat Attendu:**
```
📧 Email "Résultats Élection - SIGMA Alumni"
   ✅ Design vert
   ✅ "Les résultats de l'élection sont disponibles"
   ✅ Bouton "Voir les Résultats"
   ✅ Remerciement pour participation
```

---

### Test D: Emails CRON (Anniversaires)

### Test D: Emails CRON (Anniversaires)

**Simulation d'anniversaire:**
```powershell
cd c:\xampp\htdocs\Sigma-Website

# Méthode 1: Via URL sécurisée
# Ouvrir: http://localhost/Sigma-Website/cron_birthday.php?token=VOTRE_TOKEN

# Méthode 2: Via ligne de commande
php cron_birthday.php

# Note: Modifier temporairement la date de naissance dans la BDD
# pour tester avec la date actuelle
```

**Résultat Attendu:**
```
📧 Email "Joyeux Anniversaire ! 🎂"
   ✅ Design festif
   ✅ Message personnalisé avec prénom
   ✅ Icône 🎂
   ✅ Lien vers la messagerie communautaire
```

---

## 2️⃣ Test de Délivrabilité (Anti-Spam)

### Méthode Manuelle avec mail-tester.com

**Étapes:**
```
1. Aller sur: https://www.mail-tester.com
2. Copier l'adresse email temporaire affichée
3. Envoyer un email test via create_profile.php vers cette adresse
4. Retourner sur mail-tester.com
5. Cliquer "Then check your score"
```

**Score Attendu:** 7/10 ou plus ✅

**Facteurs d'amélioration:**
- SPF record configuré: +1 point
- DKIM configuré: +1 point
- Domaine professionnel (pas @gmail.com): +1 point

---

## 3️⃣ Vérifications Techniques

### A. Vérifier que vendor/ existe

```powershell
cd c:\xampp\htdocs\Sigma-Website
Test-Path vendor/phpmailer/phpmailer/
# Doit retourner: True
```

**Si False:**
```powershell
composer install
# ou
composer require phpmailer/phpmailer
```

---

### B. Vérifier la Configuration SMTP

**Ouvrir config.php et vérifier:**
```php
// Lignes 135-165
SMTP_HOST = 'smtp.gmail.com'              ✅
SMTP_USERNAME = 'gojomeh137@gmail.com'    ✅
SMTP_PASSWORD = 'vvvc qbzg sfey jkvi'     ✅ (App Password)
SMTP_PORT = 587                           ✅
SMTP_FROM_EMAIL = 'gojomeh137@gmail.com'  ✅
```

---

### C. Test de send_email.php

**Vérifier syntaxe:**
```powershell
php -l send_email.php
# Doit retourner: No syntax errors detected
```

**Vérifier les fonctions:**
```powershell
Select-String -Path send_email.php -Pattern "^function "
# Doit montrer:
# function sendEmail(
# function sendVoteConfirmationEmail(
# function sendResultsNotificationEmails(
# function sendVotingStartNotificationEmails(
```

---

### D. Test des Logs

**Activer les logs détaillés:**
```php
// Dans send_email.php, ajouter temporairement:
$mail->SMTPDebug = 2; // Verbose debug
```

**Vérifier les logs PHP:**
```powershell
Get-Content c:\xampp\php\logs\php_error_log -Tail 20
```

---

## 4️⃣ Checklist Complète de Test

### Avant Déploiement
- [ ] vendor/phpmailer/ existe
- [ ] config.php SMTP credentials valides
- [ ] send_email.php sans erreur de syntaxe
- [ ] 4 fonctions email présentes

### Tests Fonctionnels
- [ ] Email de bienvenue (create_profile.php)
- [ ] Notification admin (nouveau profil)
- [ ] Réinitialisation mot de passe (password_reset.php)
- [ ] Confirmation de vote (elections.php)
- [ ] Résultats élection (publish_results.php)
- [ ] Email anniversaire (cron_birthday.php)
- [ ] Email nouvel an (cron_new_year.php)

### Tests de Qualité
- [ ] Tous les boutons sont cliquables
- [ ] Tous les liens fonctionnent
- [ ] Design professionnel (HTML)
- [ ] Version texte alternative (AltBody)
- [ ] Pas d'erreur dans les logs
- [ ] Score mail-tester ≥ 7/10

---

## 🐛 Dépannage

### ❌ Email non reçu

**Vérifications:**
1. Vérifier le dossier spam
2. Vérifier les logs: `c:\xampp\php\logs\php_error_log`
3. Tester avec un autre email
4. Vérifier connexion Internet

**Solutions:**
```powershell
# Test ping Gmail SMTP
Test-NetConnection smtp.gmail.com -Port 587
# Doit montrer: TcpTestSucceeded : True
```

---

### ❌ Erreur "SMTP connect() failed"

**Causes:**
- Credentials SMTP incorrects
- Port 587 bloqué par firewall
- Gmail bloque l'accès

**Solutions:**
1. Vérifier App Password Gmail (pas mot de passe normal)
2. Activer "Accès moins sécurisés" Gmail
3. Vérifier firewall Windows
4. Tester avec port 465 (SSL) au lieu de 587 (TLS)

---

### ❌ Erreur "vendor/autoload.php not found"

**Solution:**
```powershell
cd c:\xampp\htdocs\Sigma-Website
composer install
```

---

### ❌ Email va dans spam

**Solutions:**
1. Tester avec mail-tester.com et suivre recommandations
2. Configurer SPF: [GUIDE_DELIVRABILITE_EMAILS.md](GUIDE_DELIVRABILITE_EMAILS.md)
3. Utiliser domaine professionnel
4. Envisager SendGrid/Mailgun pour production

---

## 📊 Tests sur Différents Environnements

### XAMPP (Local) ✅
```
Emails: ✅ Fonctionnent
SMTP: ✅ Port 587 ouvert
Tests: ✅ Tous passent
```

### InfinityFree ❌
```
Emails: ❌ Ne fonctionnent pas
SMTP: ❌ Ports bloqués (25, 587, 465)
Alternative: SendGrid API, Mailgun API
```

### Hébergement Payant ✅
```
Emails: ✅ Fonctionnent
SMTP: ✅ Tous les ports ouverts
Tests: ✅ Tous passent
Migration: Aucune modification de code
```

**Voir:** [DEPLOYMENT.md](DEPLOYMENT.md) pour guide complet

---

## 📚 Documentation Associée

- **[EMAIL_SYSTEM_DOCS.md](EMAIL_SYSTEM_DOCS.md)** - Architecture du système d'emails
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Guide de déploiement
- **[GUIDE_DELIVRABILITE_EMAILS.md](GUIDE_DELIVRABILITE_EMAILS.md)** - Configuration SPF/DKIM
- **[CONFIGURATION_CRON.md](CONFIGURATION_CRON.md)** - Automatisation des emails

---

**Dernière mise à jour:** 6 mars 2026

| Type Email | Couleur Header | Emoji |
|------------|----------------|-------|
| Reset Password | Rouge #dc2626 | 🔐 |
| Bienvenue | Vert #10b981 | 🎉 |
| Contact | Violet #8b5cf6 | 📧 |
| Vote | Bleu #2563eb | ✅ |
| Résultats | Vert #10b981 | 📊 |
| Admin Notif | Bleu #3b82f6 | 👤 |

---

## 📖 Documentation Complète

- **Détails techniques :** `CORRECTIFS_EMAILS_APPLIQUES.md`
- **Guide délivrabilité :** `GUIDE_DELIVRABILITE_EMAILS.md`
- **Test spam :** `test_email_spam.php`

---

## ✨ Ce qui a été corrigé

1. ✅ **Lien "Réinitialiser" ne fonctionnait pas**
   → Maintenant : Gros bouton rouge cliquable

2. ✅ **Emails allaient dans spam**
   → Maintenant : Headers anti-spam + rate limiting

3. ✅ **Design basique**
   → Maintenant : Templates HTML5 professionnels

4. ✅ **Code dupliqué**
   → Maintenant : Fonction centralisée `sendEmail()`

5. ✅ **Pas de version texte**
   → Maintenant : AltBody détaillé pour chaque email

6. ✅ **URLs en dur**
   → Maintenant : URLs dynamiques basées sur le serveur

---

## 🚀 Prêt pour la Production ?

### Checklist Pré-Déploiement

- [ ] Testé tous les types d'emails
- [ ] Score mail-tester ≥ 8/10
- [ ] Liens tous cliquables
- [ ] Design professionnel vérifié
- [ ] Pas d'emails en spam
- [ ] SPF configuré (recommandé)
- [ ] DKIM configuré (optionnel mais recommandé)
- [ ] Service SMTP professionnel (SendGrid/Mailgun pour >500 emails/jour)

---

**Tout devrait parfaitement fonctionner maintenant ! 🎉**

Testez et profitez de vos beaux emails professionnels qui arrivent en boîte de réception !
