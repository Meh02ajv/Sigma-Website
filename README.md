# 🎓 SIGMA Alumni - Plateforme de Gestion des Anciens Élèves

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)

Plateforme web complète de gestion et d'animation de la communauté des anciens élèves de SIGMA. Cette application permet de maintenir le lien entre les alumni, faciliter le networking professionnel, organiser des événements et gérer les élections du bureau.

---

## 📑 Table des Matières

- [Vue d'ensemble](#vue-densemble)
- [Fonctionnalités](#fonctionnalités)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [Structure du Projet](#structure-du-projet)
- [Technologies Utilisées](#technologies-utilisées)
- [Guide de Contribution](#guide-de-contribution)
- [Maintenance](#maintenance)
- [Support](#support)

---

## 🎯 Vue d'ensemble

SIGMA Alumni est une plateforme web qui centralise toutes les interactions entre les anciens élèves de l'établissement SIGMA. Elle offre un espace sécurisé pour :

- **Réseautage** : Retrouver et contacter d'anciens camarades
- **Communication** : Système de messagerie en temps réel
- **Événements** : Organisation et participation aux événements alumni
- **Gouvernance** : Élections démocratiques du bureau
- **Mémoire** : Conservation des souvenirs et photos par promotion

### Statistiques du Projet
- **Fichiers PHP** : ~40 pages fonctionnelles
- **Base de données** : 20+ tables MySQL
- **WebSocket** : Messagerie temps réel
- **Responsive** : 7 breakpoints pour mobile/tablette/desktop

---

## ✨ Fonctionnalités

### 🔐 Authentification & Profils
- Inscription avec vérification par code
- Connexion sécurisée avec hashage de mot de passe (bcrypt)
- Profils personnalisables (photo, bio, études, promotion)
- Modification de profil et paramètres

### 📖 Yearbook (Trombinoscope)
- Consultation de tous les profils alumni
- Filtres par année de bac et domaine d'études
- Recherche en temps réel
- Notifications d'anniversaire automatiques
- Affichage modal avec informations détaillées

### 💬 Messagerie en Temps Réel
- Chat 1-to-1 entre membres
- WebSocket pour messages instantanés
- Notifications de messages non lus
- Interface responsive mobile/desktop
- Historique des conversations

### 🗳️ Système d'Élections
- Création d'élections par les admins
- Candidatures avec vidéos de présentation
- Vote sécurisé (un vote par utilisateur)
- Comptage automatique des résultats
- Publication des résultats après clôture

### 📸 Album & Souvenirs
- Galeries photos par année
- Upload d'images et vidéos
- Organisation par promotion
- Téléchargement des médias

### 🎉 Gestion d'Événements
- Création et publication d'événements
- Affichage calendrier
- Photos d'événements
- Gestion admin complète

### 📰 Actualités & Informations
- Page d'accueil avec hero vidéo
- Flux d'actualités
- Informations sur le bureau
- Page "À propos" (objectifs, règlement, mission)
- Page de contact avec formulaire

### 🎨 Thèmes Festifs
- Thème de Noël (fêtes de fin d'année)
- Thème Indépendance du Togo
- Activation/désactivation depuis l'admin
- Animations CSS personnalisées

### 👔 Espace Administration
- Tableau de bord avec statistiques
- Gestion des utilisateurs (CRUD)
- Gestion des élections complète
- Configuration générale du site
- Upload de médias (logos, vidéos, favicon)
- Gestion du contenu (règlement, objectifs, valeurs)
- Modération (signalements, suggestions)
- Envoi d'emails groupés

---

## 🏗️ Architecture

### Schéma de l'Application

```
┌─────────────────────────────────────────────────────────┐
│                    SIGMA ALUMNI                          │
│                  (Frontend Web App)                      │
└────────────┬────────────────────────────────────────────┘
             │
             │ HTTP/HTTPS
             │
┌────────────▼────────────────────────────────────────────┐
│               Apache/PHP Backend                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Pages PHP (MVC-like)                            │  │
│  │  - accueil.php, dashboard.php, yearbook.php      │  │
│  │  - messaging.php, elections.php, admin.php       │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Core Files                                       │  │
│  │  - config.php (DB connection)                    │  │
│  │  - header.php, footer.php (templates)            │  │
│  │  - includes/ (helpers, utilities)                │  │
│  └──────────────────────────────────────────────────┘  │
└────────────┬────────────────────────────────────────────┘
             │
             │ MySQL Protocol
             │
┌────────────▼────────────────────────────────────────────┐
│                  MySQL Database                          │
│  Tables: users, elections, candidates, votes,           │
│          discussion, events, media, etc.                │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│            WebSocket Server (Port 8080)                   │
│  - Real-time messaging                                   │
│  - Ratchet PHP library                                   │
└──────────────────────────────────────────────────────────┘
```

### Pattern de Développement

Le projet suit une approche **Procédurale PHP** avec séparation des concerns :

- **Pages** : Chaque page = 1 fichier PHP autonome
- **Includes** : Composants réutilisables (header, footer, helpers)
- **Config** : Configuration centralisée dans `config.php`
- **Assets** : CSS inline + fichiers externes pour thèmes

---

## 🚀 Installation

### Prérequis

- **PHP** : 7.4 ou supérieur
- **MySQL** : 5.7 ou supérieur
- **Apache** : Avec mod_rewrite activé
- **Composer** : Pour les dépendances PHP
- **Extensions PHP** :
  - `mysqli`
  - `session`
  - `json`
  - `fileinfo`
  - `gd` ou `imagick` (pour images)

### Étape 1 : Cloner le Projet

```bash
git clone https://github.com/Hariel16/Sigma-Website.git
cd Sigma-Website
```

### Étape 2 : Installer les Dépendances

```bash
composer install
```

Dépendances installées :
- `phpmailer/phpmailer` - Envoi d'emails
- `cboden/ratchet` - WebSocket server
- `ezyang/htmlpurifier` - Nettoyage HTML
- `mpdf/mpdf` - Génération de PDF

### Étape 3 : Configuration de la Base de Données

1. Créer une base de données MySQL :
```sql
CREATE DATABASE sigma_alumni CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importer le schéma (si fourni) ou laisser le script créer les tables automatiquement

3. Configurer `config.php` :
```php
<?php
$servername = "localhost";
$username = "votre_user";
$password = "votre_password";
$dbname = "sigma_alumni";
```

### Étape 4 : Configuration Apache

Créer un VirtualHost ou pointer `DocumentRoot` vers le dossier du projet :

```apache
<VirtualHost *:80>
    ServerName sigma-alumni.local
    DocumentRoot "C:/xampp/htdocs/Sigma-Website"
    
    <Directory "C:/xampp/htdocs/Sigma-Website">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Étape 5 : Permissions des Dossiers

Donner les droits d'écriture aux dossiers d'upload :

```bash
chmod 755 uploads/
chmod 755 uploads/videos/
chmod 755 img/
chmod 755 souvenirs_pic/
```

### Étape 6 : Démarrer le WebSocket Server

Pour la messagerie en temps réel :

```bash
php websocket_server.php
```

> **Note** : En production, utiliser un process manager comme `supervisor` pour maintenir le WebSocket actif.

### Étape 7 : Créer le Premier Admin

1. Accéder à `signup.php` et créer un compte
2. Manuellement dans la BDD, mettre `is_admin = 1` pour ce compte
3. Se connecter à `admin.php`

---

## ⚙️ Configuration

### Fichier `config.php`

```php
<?php
// Démarrage de session (si pas déjà démarrée)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sigma_alumni";

// Connexion MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Encodage UTF-8
$conn->set_charset("utf8mb4");
?>
```

### Configuration PHP (`php.ini`)

Pour les uploads de gros fichiers (vidéos) :

```ini
upload_max_filesize = 2048M
post_max_size = 2048M
max_execution_time = 600
memory_limit = 512M
```

### Variables d'Environnement (Optionnel)

Pour la production, utiliser des variables d'environnement :

```bash
export DB_HOST=localhost
export DB_USER=sigma_user
export DB_PASS=secure_password
export DB_NAME=sigma_alumni_prod
```

---

## 📁 Structure du Projet

```
Sigma-Website/
│
├── 📄 Pages Principales
│   ├── accueil.php              # Page d'accueil avec hero vidéo
│   ├── dashboard.php            # Tableau de bord membre
│   ├── yearbook.php             # Trombinoscope des alumni
│   ├── messaging.php            # Messagerie temps réel
│   ├── elections.php            # Système de vote
│   ├── album.php                # Albums photos
│   ├── souvenirs.php            # Galerie de souvenirs
│   ├── contact.php              # Formulaire de contact
│   └── admin.php                # Interface d'administration
│
├── 🔐 Authentification
│   ├── connexion.php            # Page de connexion
│   ├── signup.php               # Inscription
│   ├── verification.php         # Vérification du code
│   ├── login.php                # Traitement de connexion
│   ├── logout.php               # Déconnexion
│   ├── password_reset.php       # Demande de réinitialisation
│   └── reset_password.php       # Nouveau mot de passe
│
├── 👤 Gestion de Profil
│   ├── creation_profil.php      # Création du profil initial
│   ├── create_profile.php       # Traitement création profil
│   ├── mod_prof.php             # Modification de profil
│   ├── update_profile.php       # Traitement mise à jour profil
│   └── settings.php             # Paramètres utilisateur
│
├── 📰 Pages Informatives
│   ├── bureau.php               # Présentation du bureau
│   ├── objectifs.php            # Objectifs de l'association
│   ├── reglement.php            # Règlement intérieur
│   ├── info.php                 # Page À propos
│   └── evenements.php           # Liste des événements
│
├── 🔧 Core & Configuration
│   ├── config.php               # Configuration DB et session
│   ├── header.php               # En-tête HTML commun
│   ├── footer.php               # Pied de page commun
│   └── includes/
│       ├── favicon.php          # Snippet favicon dynamique
│       └── election_results_helper.php  # Helper résultats élections
│
├── 🌐 API & Services
│   ├── theme_manager.php        # API thèmes festifs (JSON)
│   ├── get_messages.php         # API récupération messages
│   ├── send_message.php         # API envoi message
│   ├── get_unread_counts.php    # API compteurs non lus
│   ├── mark_messages_read.php   # API marquer comme lu
│   ├── load_more_profiles.php   # API pagination profiles
│   ├── load_more_photos.php     # API pagination photos
│   ├── submit_report.php        # API signalement
│   ├── submit_suggestion.php    # API suggestion
│   └── send_email.php           # Service envoi email
│
├── 🎨 Assets
│   ├── festive_themes.css       # Thèmes de Noël et Indépendance
│   ├── img/                     # Images et logos
│   │   ├── image.png            # Logo principal
│   │   ├── white_logo.png       # Logo blanc (header)
│   │   ├── profile_pic.jpeg     # Avatar par défaut
│   │   └── *.jpg                # Backgrounds et médias
│   └── js/                      # Scripts JavaScript
│
├── 📤 Uploads
│   ├── uploads/
│   │   ├── videos/              # Vidéos hero background
│   │   ├── candidates/          # Photos candidats élections
│   │   ├── candidate_videos/    # Vidéos candidatures
│   │   ├── events/              # Photos d'événements
│   │   ├── news/                # Images d'actualités
│   │   └── 202X_pic/            # Photos par année
│   └── souvenirs_pic/
│       ├── 2023/, 2024/, 2025/  # Souvenirs par année
│
├── 🔌 WebSocket
│   ├── websocket_server.php     # Serveur WebSocket Ratchet
│   └── websocket_log.txt        # Logs du serveur
│
├── 📦 Dépendances
│   ├── composer.json            # Dépendances PHP
│   ├── composer.lock            # Versions verrouillées
│   └── vendor/                  # Packages Composer
│       ├── phpmailer/
│       ├── cboden/ratchet/
│       ├── ezyang/htmlpurifier/
│       └── mpdf/mpdf/
│
├── 📚 Documentation
│   ├── README.md                # Ce fichier
│   ├── FONCTIONNALITES.md       # Détail des fonctionnalités
│   ├── AMELIORATIONS_SUGGEREES.md  # Roadmap futures features
│   ├── THEMES_FESTIFS.md        # Guide thèmes festifs
│   ├── MESSAGERIE_README.md     # Doc système de messagerie
│   ├── EMAIL_SYSTEM_DOCS.md     # Doc système d'emails
│   └── CONFIG_VIDEO_UPLOAD.md   # Config upload vidéos
│
└── 🗑️ Fichiers de Développement (à supprimer en production)
    ├── test_*.php               # Fichiers de test
    ├── check_*.php              # Scripts de vérification
    ├── dump_*.php               # Scripts de debug
    └── *.backup                 # Sauvegardes anciennes
```

---

## 🛠️ Technologies Utilisées

### Backend
- **PHP 7.4+** : Langage serveur
- **MySQL 5.7+** : Base de données relationnelle
- **Apache 2.4** : Serveur web
- **Composer** : Gestionnaire de dépendances

### Frontend
- **HTML5** : Structure sémantique
- **CSS3** : Styles avec variables CSS
- **JavaScript (ES6+)** : Interactivité
- **Font Awesome 6** : Icônes
- **Google Fonts** : Typographie (Montserrat, Roboto)

### Bibliothèques PHP
- **PHPMailer** : Envoi d'emails SMTP
- **Ratchet** : Serveur WebSocket
- **HTMLPurifier** : Nettoyage et sécurisation HTML
- **mPDF** : Génération de PDF

### Sécurité
- **password_hash()** : Hashage bcrypt des mots de passe
- **CSRF Tokens** : Protection contre les attaques CSRF
- **Prepared Statements** : Protection contre SQL injection
- **htmlspecialchars()** : Protection XSS
- **Session sécurisées** : session_regenerate_id()

### Responsive Design
- **Media Queries** : 7 breakpoints
- **Flexbox & Grid** : Layouts modernes
- **Mobile-first** : Approche responsive

---

## 👨‍💻 Guide de Contribution

### Standards de Code

#### PHP
```php
<?php
/**
 * Description de la fonction
 * 
 * @param string $param Description du paramètre
 * @return bool Valeur de retour
 */
function nomFonction($param) {
    // Code ici
    return true;
}
?>
```

#### Conventions
- **Variables** : `$snake_case`
- **Fonctions** : `camelCase()`
- **Classes** : `PascalCase`
- **Constantes** : `UPPER_CASE`

#### Commentaires
- Commenter TOUTES les fonctions complexes
- Expliquer le "pourquoi", pas le "comment"
- Garder les commentaires à jour

### Git Workflow

1. **Créer une branche** pour chaque feature
```bash
git checkout -b feature/nom-feature
```

2. **Commits atomiques** avec messages clairs
```bash
git commit -m "feat: ajout système de notifications"
```

3. **Pull Request** avec description détaillée

4. **Code Review** avant merge

### Testing

Avant chaque commit :
- Tester sur navigateurs : Chrome, Firefox, Safari, Edge
- Vérifier responsive : Mobile, Tablet, Desktop
- Tester fonctionnalités modifiées
- Vérifier logs PHP (pas d'erreurs)

---

## 🔧 Maintenance

### Logs

Les logs sont dans plusieurs endroits :
- **Apache** : `C:/xampp/apache/logs/error.log`
- **PHP** : Voir `php.ini` pour `error_log`
- **WebSocket** : `websocket_log.txt`

### Backups

**Base de données** (quotidien recommandé) :
```bash
mysqldump -u root -p sigma_alumni > backup_$(date +%Y%m%d).sql
```

**Fichiers uploads** :
```bash
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

### Mises à jour

1. **PHP/MySQL** : Suivre les patchs de sécurité
2. **Dépendances Composer** :
```bash
composer update
```
3. **Frontend** : Mettre à jour CDN (Font Awesome, etc.)

### Monitoring

Points à surveiller :
- Espace disque (uploads de vidéos)
- Performance MySQL (requêtes lentes)
- WebSocket uptime
- Logs d'erreurs PHP

---

## 📞 Support

### Issues GitHub
Pour signaler un bug : [github.com/Hariel16/Sigma-Website/issues](https://github.com/Hariel16/Sigma-Website/issues)

### Contact Développeur
- **Email** : [Votre email]
- **GitHub** : [@Hariel16](https://github.com/Hariel16)

### Documentation Additionnelle
- [FONCTIONNALITES.md](FONCTIONNALITES.md) - Détail complet de chaque feature
- [AMELIORATIONS_SUGGEREES.md](AMELIORATIONS_SUGGEREES.md) - Roadmap et suggestions
- [THEMES_FESTIFS.md](THEMES_FESTIFS.md) - Guide des thèmes saisonniers

---

## 📜 Licence

© 2025 SIGMA Alumni. Tous droits réservés.

Ce projet est propriétaire et confidentiel. Toute reproduction, distribution ou utilisation sans autorisation écrite préalable est strictement interdite.

---

## 🙏 Remerciements

- **SIGMA** - L'établissement et sa communauté
- **Les Alumni** - Pour leurs retours et suggestions
- **Les Contributeurs** - Pour leur travail sur le projet

---

**Version** : 1.0.0  
**Date** : Décembre 2025  
**Auteur** : Équipe de développement SIGMA Alumni

*Fait avec ❤️ pour la communauté SIGMA*
