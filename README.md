# 🎓 SIGMA Alumni - Plateforme de Réseau des Anciens Élèves

![Version](https://img.shields.io/badge/version-2.2.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)
![WebSocket](https://img.shields.io/badge/WebSocket-Enabled-green.svg)
![UTF-8](https://img.shields.io/badge/Encoding-UTF--8-brightgreen.svg)
![Automation](https://img.shields.io/badge/Automation-CRON-orange.svg)

**Plateforme web complète pour gérer et animer la communauté des anciens élèves de SIGMA.**  
Réseau professionnel, messagerie temps réel, événements, élections et souvenirs partagés.

---

## 📋 Table des Matières

- [🎯 Vue d'ensemble](#-vue-densemble)
- [✨ Fonctionnalités](#-fonctionnalités)
- [🚀 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [📁 Structure du Projet](#-structure-du-projet)
- [🛠️ Technologies](#️-technologies)
- [📚 Documentation](#-documentation)
- [🤝 Contribution](#-contribution)

---

## 🎯 Vue d'ensemble

SIGMA Alumni est une plateforme sociale dédiée aux anciens élèves permettant de :

- 🔍 **Retrouver** ses anciens camarades via l'annuaire interactif
- 💬 **Communiquer** en temps réel avec la messagerie WebSocket
- 📅 **Participer** aux événements et activités de l'association
- 🗳️ **Voter** lors des élections du bureau
- 📸 **Partager** souvenirs et photos par promotion
- 🔔 **Recevoir** des notifications en temps réel

### Chiffres clés
- **~50 pages PHP** fonctionnelles
- **25+ tables MySQL** pour les données
- **WebSocket temps réel** pour la messagerie
- **Responsive design** mobile/tablette/desktop
- **Système de notifications** multi-types

---

## ✨ Fonctionnalités (Récapitulatif Complet)

### 🔐 Sécurité & Authentification
- **Inscription & Connexion** : Inscription sécurisée, connexion avec régénération automatique d'ID de session, et protection contre les attaques par force brute.
- **Sécurité Avancée** : Protection CSRF globale, validation stricte des entrées via `sanitize()`, headers de sécurité (CSP, XSS Protection, No-Sniff), et support complet UTF-8.
- **Mot de Passe** : Toggle de visibilité du mot de passe et réinitialisation sécurisée par e-mail avec tokens temporaires.
- **Onboarding** : Tutoriel interactif (Driver.js) pour guider les nouveaux membres lors de leur première connexion.

### 👤 Profils & Annuaire Dynamique
- **Profils Enrichis** : Photo de profil, biographie, année de bac, études, profession et entreprise.
- **Filtres de Recherche** : Recherche avancée par nom, promotion ou domaine d'expertise.
- **Yearbook Interactif** : Accès complet pour les membres et version publique restreinte pour la visibilité web.
- **Vie Privée** : Option pour masquer ou afficher son e-mail sur son profil public.

### 💬 Messagerie & Notifications Temps Réel
- **WebSocket (Ratchet)** : Messagerie instantanée fluide permettant des discussions en temps réel.
- **Système de Notifications** : Alertes centralisées pour les messages, les nouveaux événements, les élections et les anniversaires.
- **Optimisation** : Suppression automatique des messages obsolètes et marquage de lecture groupé.

### 📅 Événements & Automatisations
- **Gestionnaire d'Événements** : Publication d'événements avec galeries photos, descriptions et gestion des rappels.
- **Rappels E-mails (CRON)** : Envoi automatique de notifications par e-mail **2 heures** et **1 heure** avant le début d'un événement pour les participants.
- **Anniversaires** : 
    - Notification communautaire à **J-2** envoyée à tous les membres.
    - E-mail spécial de félicitations envoyé à l'intéressé le jour J.
- **Vœux** : Envoi automatique d'e-mails de bonne année le 1er janvier à minuit.

### 🗳️ Élections & Administration
- **Élections du Bureau** : Candidatures avec photos/vidéos, vote à bulletin secret (un seul vote par membre), et support du vote blanc.
- **Calcul des Résultats** : Gestion automatisée des égalités et affichage stylisé des vainqueurs.
- **Dashboard Admin** : Panneau complet pour gérer les membres, actualités, thèmes festifs (Noël, Halloween), et paramètres généraux.
- **Alertes Admin** : Réception d'un e-mail à chaque vote enregistré ou chaque réinitialisation de mot de passe.

---

## 🚀 Procédure d'Hébergement & Déploiement

### 1. Pré-requis Logiciels
Pour faire fonctionner la plateforme, votre serveur doit disposer de :
- **Serveur Web** : Apache 2.4+ (avec `mod_rewrite` activé) ou Nginx.
- **PHP 7.4 ou 8.x** : Avec extensions `mysqli`, `openssl`, `mbstring`, `curl`, `fileinfo`.
- **MySQL 5.7+** ou **MariaDB**.
- **Composer** : Installé sur le serveur pour gérer les dépendances.

### 2. Documents & Paramètres Supplémentaires
Avant la mise en ligne, préparez les éléments suivants :
- **Certificat SSL (HTTPS)** : **Obligatoire** pour la sécurité des sessions et des cookies.
- **Configuration SMTP** : Un compte e-mail (Gmail, Outlook ou SMTP professionnel) pour l'envoi des notifications.
- **Accès SSH** : Recommandé pour lancer le serveur WebSocket en arrière-plan.
- **Permissions** : Dossiers `uploads/`, `sessions/`, `logs/` et `souvenirs_pic/` doivent être accessibles en écriture par le serveur web (ex: `755` ou `775`).

### 3. Étapes d'Hébergement
1. **Téléversement** : Copiez tous les fichiers sur votre espace d'hébergement.
2. **Installation SQL** : Importez tous les fichiers situés dans le dossier `/sql/` (commencez par le schéma de base, puis les additifs).
3. **Dépendances** : Exécutez `composer install` à la racine pour installer PHPMailer et les outils WebSocket.
4. **Configuration** : Initialisez le fichier `.env` ou `config.php` avec les identifiants de production.
5. **CRON Jobs** : Configurez les tâches programmées sur votre panel d'hébergement :
   - `0 8 * * * php /chemin/vers/cron_birthday.php` (Tous les jours à 8h)
   - `*/15 * * * * php /chemin/vers/cron_event_reminders.php` (Toutes les 15 min)
6. **Lancement WebSocket** : Exécutez `php websocket_server.php` (idéalement via un gestionnaire de processus comme `PM2` ou un `systemd service` pour qu'il redémarre automatiquement).

---

## 📁 Structure du Projet

```
Sigma-Website/
├── config.php                 # Configuration principale
├── header.php                 # En-tête commun
├── footer.php                 # Pied de page commun
│
├── Pages principales
│   ├── dashboard.php          # Tableau de bord
│   ├── yearbook.php           # Annuaire des membres (authentifié)
│   ├── yearbook_public.php    # Annuaire public (sans connexion)
│   ├── messaging.php          # Messagerie
│   ├── notifications.php      # Centre de notifications
│   ├── evenements.php         # Événements
│   ├── elections.php          # Système électoral
│   ├── souvenirs.php          # Galerie photos
│   └── settings.php           # Paramètres utilisateur
│
├── Authentification
│   ├── login.php              # Connexion
│   ├── signup.php             # Inscription
│   ├── verification.php       # Vérification code
│   └── password_reset.php     # Récupération mot de passe
│
├── Administration
│   ├── admin.php              # Panneau admin
│   ├── admin_login.php        # Connexion admin
│   └── manage_emails.php      # Gestion emails
│
├── API / AJAX
│   ├── get_messages.php       # Récupérer messages
│   ├── send_message.php       # Envoyer message
│   ├── get_notifications.php  # Récupérer notifications
│   ├── autocomplete_users.php # Autocomplétion
│   ├── update_profile.php     # Mise à jour profil
│   └── mark_tutorial_completed.php # Complétion tutoriel
│
├── Automatisation CRON
│   ├── messaging.js           # Client WebSocket
│   └── tutorial.js            # Tutoriel interactif (Driver.js)otidiens
│   └── cron_new_year.php      # Voeux du Nouvel An
│
├── css/                       # Feuilles de style
├── js/                        # Scripts JavaScript
│   └── messaging.js           # Client WebSocket
│
├── img/                       # Images du site
├── uploads/                   # Uploads utilisateurs
│   ├── candidates/            # Photos candidats
│   ├── events/                # Photos événements
│   └── 2023_pic/, 2024_pic/   # Photos profils
│
├── souvenirs_pic/             # Photos souvenirs
│   ├── 2023/
│   ├── 2024/
│   └── 2025/
│
│   ├── add_tutorial_field.sql # Migration tutoriel
│   └── ...
│
├── sessions/                  # Sessions PHP
├── logs/                      # Logs CRON automatiques
├── sessions/                  # Sessions PHP
├── vendor/                    # Dépendances Composer
│CONFIGURATION_CRON.md       # ⭐ Configuration CRON
    ├── EMAIL_SYSTEM_DOCS.md
    ├── FONCTIONNALITES.md
    ├── GUIDE_INSTALLATION_SQL.md
    ├── INSTALLATION_TUTORIEL.md    # ⭐ Installation tutoriel
    ├── MESSAGERIE_README.md
    ├── NOTIFICATIONS_README.md
    ├── RECHERCHE_AVANCEE_README.md
    ├── THEMES_FESTIFS.md
    └── TUTORIEL_README.md          # ⭐ Guide tutorielN_SQL.md
    ├── MESSAGERIE_README.md
    ├── NOTIFICATIONS_README.md
    ├── RECHERCHE_AVANCEE_README.md
    └── THEMES_FESTIFS.md
```

---

## 🛠️ Technologies

### Backend
- **PHP 7.4+** - Langage serveur
- **MySQL 5.7+** - Base de données
- **Composer** - Gestionnaire de dépendances
- **PHPMailer** - Envoi d'emai
- **Driver.js 1.3.1** - Tutoriels interactifsls
- **Ratchet** - Serveur WebSocket

### Frontend
- **HTML5 / CSS3** - Structure et style
- **JavaScript ES6** - Interactivité
- **WebSocket API** - Communication temps réel
- **FontAwesome 6.5** - Icônes

### Bibliothèques
```json
{
  "require": {
    "phpmailer/phpmailer": "^6.8",
    "cboden/ratchet": "^0.4",
    "ezyang/htmlpurifier": "^4.16"
  }
}
```

### Sécurité
- **Bcrypt** - Hashage des mots de passe
- **HTMLPurifier** - Protection XSS
- **Prepared Statements** - Protection SQL Injection
- **CSRF Tokens** - Protection CSRF
- **Sessions sécurisées** - HttpOnly, SameSite

---

## 📚 Documentation

📂 **Toute la documentation est disponible dans le dossier [docs/](docs/)** - [Voir l'index complet](docs/INDEX.md)

### 🚀 Déploiement & Installation
- [🌐 Guide de déploiement](docs/DEPLOYMENT.md) - 🆕 Comparatif XAMPP / InfinityFree / Hébergement payant
- [📦 Installation SQL](docs/GUIDE_INSTALLATION_SQL.md) - Installation complète de la base de données
- [📖 Installation tutoriel](docs/INSTALLATION_TUTORIEL.md) - Installation du système de tutoriel interactif

### 📧 Système d'Emails
- [📧 Configuration email](docs/EMAIL_SYSTEM_DOCS.md) - Architecture et configuration SMTP (PHPMailer)
- [🧪 Tests des emails](docs/TESTS_EMAILS.md) - Procédures de test et débogage
- [📨 Délivrabilité emails](docs/GUIDE_DELIVRABILITE_EMAILS.md) - Configuration SPF/DKIM/DMARC
- [🎂 Automatisation CRON](docs/CONFIGURATION_CRON.md) - Configuration des emails automatiques (anniversaires, nouvel an)

### 💬 Fonctionnalités
- [📋 Fonctionnalités complètes](docs/FONCTIONNALITES.md) - Liste détaillée de toutes les fonctionnalités
- [🎓 Tutoriel interactif](docs/TUTORIEL_README.md) - Documentation du système de tutoriel Driver.js
- [💬 Système de messagerie](docs/MESSAGERIE_README.md) - Guide du système de messagerie WebSocket
- [🔔 Système de notifications](docs/NOTIFICATIONS_README.md) - Documentation des notifications
- [🔍 Recherche avancée](docs/RECHERCHE_AVANCEE_README.md) - Guide de la recherche avancée
- [🎨 Thèmes festifs](docs/THEMES_FESTIFS.md) - Gestion des thèmes saisonniers

### 🔧 Configuration
- [🎥 Upload vidéo](docs/CONFIG_VIDEO_UPLOAD.md) - Configuration des uploads vidéo
- [🚀 Améliorations suggérées](docs/AMELIORATIONS_SUGGEREES.md) - Roadmap et fonctionnalités futures

---

## 🤝 Contribution

### Workflow Git
```bash
# Créer une branche
git checkout -b feature/ma-fonctionnalite

# Faire vos modifications
git add .
git commit -m "✨ Ajout de ma fonctionnalité"

# Pousser vers GitHub
git push origin feature/ma-fonctionnalite

# Créer une Pull Request sur GitHub
```

### Conventions de code
- **PHP** : PSR-12 Code Style
- **SQL** : Noms en snake_case, tables au pluriel
- **Commits** : Utiliser les emojis Git conventionnels
  - ✨ Nouvelle fonctionnalité
  - 🐛 Correction de bug
  - 📝 Documentation
  - 🎨 Style/formatage
  - ♻️ Refactoring
  - 🔥 Suppression de code

---

## 📊 Roadmap

### ✅ Phase 1 - Fondations (Complété)
- [x] Système d'authentificati
- [x] Tutoriel interactif pour nouveaux utilisateurs
- [x] Emails automatiques (anniversaires & Nouvel An)on
- [x] Profils utilisateurs enrichis
- [x] Recherche avancée
- [x] Messagerie temps réel
- [x] Système de notifications

### 🚧 Phase 2 - En cours
- [ ] Système de mentorat
- [ ] Gestion événements avancée (inscriptions, QR codes)
- [ ] Paramètres de confidentialité (RGPD)
- [ ] Authentification 2FA

### 📅 Phase 3 - Planifié
- [ ] Offres d'emploi et stages
- [ ] Blog et actualités
- [ ] Groupes et communautés
- [ ] Dashboard avec analytics

Voir [AMELIORATIONS_SUGGEREES.md](docs/AMELIORATIONS_SUGGEREES.md) pour la roadmap complète.

---

## 🐛 Dépannage

### Le WebSocket ne fonctionne pas
```bash
# Vérifier que le serveur tourne
netstat -ano | findstr :8080

# Redémarrer le serveur
php websocket_server.php
```

### Erreurs de permissions
```bash
# Windows (XAMPP)
icacls sessions /grant Everyone:F /T
icacls uploads /grant Everyone:F /T

# Linux
chmod -R 755 sessions uploads souvenirs_pic
```

### Problèmes d'email
Vérifiez :
1. Configuration SMTP dans `config.php`
2. Mot de passe d'application Gmail (pas le mot de passe normal)
3. Extensions PHP activées : `openssl`, `sockets`

### Base de données
```bash
# Vérifier la connexion
mysql -u root -p -e "SELECT 1;"

# Réimporter le schéma si nécessaire
mysql -u root -p laho < sql/schema.sql
```

---

## 📄 License

**Proprietary** - © 2024-2025 SIGMA Alumni. Tous droits réservés.

Ce projet est privé et destiné uniquement à l'usage interne de l'association SIGMA.

---

## 👥 Équipe

- **Développement** : GitHub Copilot AI Assistant
- **Maintenance** : Association SIGMA Alumni
- **Support** : contact@sigma-alumni.org

---

## 📞 Support

- **Email** : support@sigma-alumni.org
- **GitHub Issues** : [Signaler un problème](https://github.com/Meh02ajv/Sigma-Website/issues)
- **Documentation** : Voir les fichiers markdown à la racine du projet

---

## 🙏 Remerciements

Merci à tous les contributeurs et membres de l'association SIGMA Alumni qui font vivre cette plateforme !

**Technologies utilisées avec** ❤️ :
- [Driver.js](https://driverjs.com/)
- [PHP](https://www.php.net/)
- [MySQL](https://www.mysql.com/)
- [Ratchet WebSocket](htt2.0  
**Dernière mise à jour** : 4 Janvier 2026  
**Nouveautés v2.2.0** :
- 🎓 **Tutoriel interactif** avec Driver.js au premier login
- 🎂 **Emails d'anniversaire automatiques** avec design HTML professionnel
- 🎆 **Voeux du Nouvel An** envoyés automatiquement le 1er janvier
- 📅 **Système CRON** pour automatisation complète
- 🖼️ **Gestion des images de fond** dans le panneau admin
- 📖 **Guide "Aide"** accessible à tout moment depuis les paramè
**Dernière mise à jour** : 3 Janvier 2026  
**Nouveautés v2.1.0** :
- ✅ Yearbook public accessible sans connexion
- ✅ Support complet UTF-8 (accents, apostrophes, caractères spéciaux)
- ✅ Toggle de visibilité du mot de passe
- ✅ Amélioration de la navigation et des filtres

**Site web** : https://sigma-alumni.org
