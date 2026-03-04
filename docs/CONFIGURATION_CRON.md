# 📅 Configuration des Tâches Automatiques (CRON)

## 🎂 Script d'Anniversaires Automatique

### Fonctionnalités
- ✅ Envoi d'un email personnalisé "Joyeux Anniversaire" à chaque personne le jour de son anniversaire
- ✅ Notification aux autres membres le jour de l'anniversaire
- ✅ Rappel envoyé 2 jours avant l'anniversaire aux autres membres
- ✅ Design HTML professionnel avec animations
- ✅ Logs détaillés dans `logs/birthday_cron.log`

### Configuration Windows Task Scheduler

#### Étape 1 : Ouvrir le Planificateur de tâches
1. Appuyez sur `Windows + R`
2. Tapez `taskschd.msc` et validez

#### Étape 2 : Créer une tâche pour les anniversaires
1. Cliquez sur **"Créer une tâche"** (pas "Créer une tâche de base")
2. **Onglet Général** :
   - Nom : `SIGMA - Anniversaires Quotidiens`
   - Description : `Envoi automatique des emails d'anniversaire`
   - Cochez **"Exécuter même si l'utilisateur n'est pas connecté"**
   - Cochez **"Exécuter avec les autorisations maximales"**

3. **Onglet Déclencheurs** :
   - Cliquez **"Nouveau"**
   - Commencer la tâche : **"Selon une planification"**
   - Paramètres : **"Tous les jours"**
   - Démarrer le : Choisir la date d'aujourd'hui
   - Heure : **08:00:00** (8h du matin)
   - Répéter la tâche toutes les : *Laisser vide*
   - Activé : **Coché**

4. **Onglet Actions** :
   - Cliquez **"Nouvelle"**
   - Action : **"Démarrer un programme"**
   - Programme/script : `C:\xampp\php\php.exe`
   - Ajouter des arguments : `C:\xampp\htdocs\Sigma-Website\cron_birthday.php`
   - Démarrer dans : `C:\xampp\htdocs\Sigma-Website`

5. **Onglet Conditions** :
   - Décochez **"Démarrer la tâche uniquement si l'ordinateur est relié au secteur"**
   - Cochez **"Réveiller l'ordinateur pour exécuter cette tâche"** (optionnel)

6. **Onglet Paramètres** :
   - Cochez **"Autoriser l'exécution de la tâche à la demande"**
   - Cochez **"Exécuter la tâche dès que possible après le démarrage manqué"**

7. Cliquez **OK** et entrez votre mot de passe Windows si demandé

---

## 🎆 Script de Voeux du Nouvel An

### Fonctionnalités
- ✅ Envoi automatique le 1er janvier à 00:01
- ✅ Email HTML magnifique avec animations et design festif
- ✅ Voeux personnalisés pour chaque membre
- ✅ Logs détaillés dans `logs/new_year_cron.log`

### Configuration Windows Task Scheduler

#### Créer une tâche pour le Nouvel An
1. **Onglet Général** :
   - Nom : `SIGMA - Voeux Nouvel An`
   - Description : `Envoi automatique des voeux le 1er janvier`
   - Cochez **"Exécuter même si l'utilisateur n'est pas connecté"**
   - Cochez **"Exécuter avec les autorisations maximales"**

2. **Onglet Déclencheurs** :
   - Cliquez **"Nouveau"**
   - Commencer la tâche : **"Selon une planification"**
   - Paramètres : **"Une seule fois"**
   - Démarrer le : **01/01/2027** (prochaine année)
   - Heure : **00:01:00** (minuit et une minute)
   - Cochez **"Répéter la tâche toutes les"** : **1 an**
   - Pendant : **Indéfiniment**
   - Activé : **Coché**

3. **Onglet Actions** :
   - Cliquez **"Nouvelle"**
   - Action : **"Démarrer un programme"**
   - Programme/script : `C:\xampp\php\php.exe`
   - Ajouter des arguments : `C:\xampp\htdocs\Sigma-Website\cron_new_year.php`
   - Démarrer dans : `C:\xampp\htdocs\Sigma-Website`

4. **Onglet Conditions** :
   - Décochez **"Démarrer la tâche uniquement si l'ordinateur est relié au secteur"**
   - Cochez **"Réveiller l'ordinateur pour exécuter cette tâche"**

5. **Onglet Paramètres** :
   - Cochez **"Autoriser l'exécution de la tâche à la demande"**
   - Cochez **"Exécuter la tâche dès que possible après le démarrage manqué"**

6. Cliquez **OK**

---

## 🧪 Tester les Scripts Manuellement

### Test du script d'anniversaires
```powershell
cd C:\xampp\htdocs\Sigma-Website
C:\xampp\php\php.exe cron_birthday.php
```

### Test du script Nouvel An
```powershell
cd C:\xampp\htdocs\Sigma-Website
C:\xampp\php\php.exe cron_new_year.php
```

### Consulter les logs
```powershell
# Logs des anniversaires
type logs\birthday_cron.log

# Logs du Nouvel An
type logs\new_year_cron.log
```

---

## 📧 Configuration Email Requise

Assurez-vous que votre fichier `config.php` contient :

```php
// Configuration SMTP pour PHPMailer
define('SMTP_HOST', 'smtp.gmail.com');          // Serveur SMTP
define('SMTP_PORT', 587);                        // Port SMTP
define('SMTP_USER', 'votre-email@gmail.com');   // Email d'envoi
define('SMTP_PASS', 'votre-mot-de-passe-app');  // Mot de passe d'application
define('SMTP_FROM', 'votre-email@gmail.com');   // Email expéditeur
```

### Pour Gmail :
1. Activez la validation en 2 étapes
2. Générez un "Mot de passe d'application" : https://myaccount.google.com/apppasswords
3. Utilisez ce mot de passe dans `SMTP_PASS`

---

## 🔍 Vérification et Monitoring

### Vérifier l'exécution des tâches
1. Ouvrir le Planificateur de tâches
2. Bibliothèque du Planificateur de tâches
3. Chercher vos tâches SIGMA
4. Onglet **"Historique"** pour voir les exécutions

### Activer l'historique (si désactivé)
1. Dans le Planificateur, menu **"Action"**
2. Cliquez **"Activer l'historique de toutes les tâches"**

### Structure des logs
```
[2026-01-04 08:00:01] === Début du script d'anniversaires ===
[2026-01-04 08:00:01] Connexion à la base de données réussie
[2026-01-04 08:00:01] Date actuelle : 2026-01-04 (MM-DD: 01-04)
[2026-01-04 08:00:02] Anniversaires aujourd'hui : 2
[2026-01-04 08:00:02] ✓ Email d'anniversaire envoyé à Jean Dupont
[2026-01-04 08:00:03] → 45 notifications envoyées aux autres membres
[2026-01-04 08:00:10] === Total d'emails d'anniversaire envoyés : 2 ===
```

---

## 🚨 Dépannage

### La tâche ne s'exécute pas
- Vérifiez que XAMPP est démarré (Apache + MySQL)
- Vérifiez le chemin vers `php.exe`
- Consultez l'historique de la tâche
- Vérifiez les logs d'erreurs

### Les emails ne partent pas
- Vérifiez la configuration SMTP dans `config.php`
- Testez manuellement le script en ligne de commande
- Vérifiez que PHPMailer est installé (`vendor/phpmailer`)
- Consultez les logs pour les erreurs SMTP

### Forcer l'exécution immédiate
1. Ouvrir le Planificateur de tâches
2. Clic droit sur la tâche
3. **"Exécuter"**
4. Consulter les logs

---

## 📊 Statistiques

Les scripts génèrent des logs détaillés incluant :
- Nombre d'anniversaires du jour
- Nombre d'emails envoyés
- Nombre de notifications envoyées
- Erreurs éventuelles
- Durée d'exécution

Ces informations sont archivées dans le dossier `logs/` pour audit et monitoring.
