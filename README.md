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

## ✨ Fonctionnalités

### 🔐 Authentification & Sécurité
- Inscription avec code de vérification
- Connexion sécurisée (bcrypt + sessions PHP)
- Toggle de visibilité du mot de passe (création compte & connexion)
- Récupération de mot de passe par email
- Protection CSRF et validation des entrées
- Support complet UTF-8 pour tous les caractères (français, accents, apostrophes)
- **Tutoriel interactif** au premier login (Driver.js)
- Guide contextuel disponible dans les paramètres

### 👤 Profils Enrichis
- Profil personnalisable (photo, bio, études, promotion)
- Recherche avancée avec filtres multiples
- Annuaire dynamique (yearbook)
- **Yearbook public** accessible sans connexion
- Autocomplétion des utilisateurs

### 💬 Messagerie Temps Réel
- WebSocket pour communication instantanée
- Conversations privées 1-to-1
- Tri automatique par conversation la plus récente
- Indicateurs de messages non lus
- Suppression automatique des anciens messages
- Navigation contextuelle (retour intelligent)
- Système de notifications intégré

### 🔔 Notifications
- **Types de notifications** :
  - Messages privés
  - Nouveaux événements
  - Élections en cours
  - Activités de l'association
- Badge de compteur global
- Centre de notifications dédié
- Marquage lu/non-lu
- Auto-archivage après 30 jours

### 📅 Gestion d'Événements
- Création et publication d'événements
- Affichage chronologique (événements à venir et passés)
- Upload d'images pour chaque événement
- Système de rappels personnalisés
- Interface d'administration complète

### 🗳️ Système Électoral
- Création de campagnes électorales
- Upload de vidéos et photos de candidats
- Vote sécurisé avec une voix par membre
- Publication des résultats
- Statistiques détaillées

### 📸 Galerie de Souvenirs
- Organisation par année (2023, 2024, 2025...)
- Upload multiple de photos
- Pagination dynamique (load more)
- Albums souvenirs par promotion

### 🎨 Personnalisation
- **Thèmes festifs** : Noël, Halloween, Saint-Valentin, etc.
- Gestion des thèmes par admin
- CSS dynamique selon le thème actif
- Activation/désactivation en un clic

### 👨‍💼 Administration
- Panneau d'administration sécurisé
- Gestion des utilisateurs
- Modération des contenus (actualités, événements, élections)
- Gestion complète des règlements, objectifs et valeurs
- Gestion des emails de masse avec logo intégré
- Système de signalement et suggestions
- Affichage correct des caractères spéciaux dans toutes les interfaces
- **Gestion des images de fond** dynamiques

### 🎂 Automatisation & Emails
- **Anniversaires automatiques** : Email personnalisé le jour J
- **Rappels d'anniversaire** : Notification aux membres 2 jours avant
- **Voeux du Nouvel An** : Email festif automatique le 1er janvier
- Emails HTML professionnels avec animations
- Scripts CRON configurables (Windows Task Scheduler)
- Logs détaillés de toutes les exécutions

---

## 🚀 Installation

### Prérequis
- **PHP** 7.4 ou supérieur
- **MySQL** 5.7+ ou MariaDB 10.3+
- **Composer** (pour les dépendances)
- **Apache** ou **Nginx** avec mod_rewrite
- **Extension PHP** : mysqli, session, mbstring, fileinfo

### Installation rapide

1. **Cloner le dépôt**
```bash
git clone https://github.com/Meh02ajv/Sigma-Website.git
cd Sigma-Website
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configurer la base de données**
```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE laho CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importer le schéma (voir GUIDE_INSTALLATION_SQL.md pour détails)
mysql -u root -p laho < sql/schema.sql
```

4. **Configurer l'application**
```bash
# Copier le fichier de configuration
cp config.example.php config.php

# Éditer config.php avec vos paramètres
nano config.php
```

5. **Configurer Apache**
```apache
<VirtualHost *:80>
    ServerName sigma-alumni.local
    DocumentRoot "C:/xampp/htdocs/Sigma-Website"
    
    <Directory "C:/xampp/htdocs/Sigma-Website">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

6. **Démarrer le serveur WebSocket**
```bash
php websocket_server.php
```

7. **Accéder à l'application**
```
http://localhost/Sigma-Website
ou
http://sigma-alumni.local
```

---

## ⚙️ Configuration

### config.php
Fichier principal de configuration :

```php
<?php
// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'laho');
define('DB_USER', 'root');
define('DB_PASS', '');

// Email (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'votre-email@gmail.com');
define('SMTP_PASS', 'votre-mot-de-passe-app');
define('SMTP_PORT', 587);

// WebSocket
define('WEBSOCKET_HOST', 'localhost');
define('WEBSOCKET_PORT', 8080);

// Chemins
define('BASE_URL', 'http://localhost/Sigma-Website');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Sécurité
define('SESSION_LIFETIME', 7200); // 2 heures
define('ENABLE_CSRF', true);
```

### Configuration des uploads
Voir [CONFIG_VIDEO_UPLOAD.md](docs/CONFIG_VIDEO_UPLOAD.md) pour :
- Limites de taille des fichiers
- Types MIME autorisés
- Permissions des dossiers

### Configuration email
Voir [EMAIL_SYSTEM_DOCS.md](docs/EMAIL_SYSTEM_DOCS.md) pour :
- Configuration SMTP
- Templates d'emails
- Système de files d'attente

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
