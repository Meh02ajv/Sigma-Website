# 🎓 SIGMA Alumni - Plateforme de Réseau des Anciens Élèves

![Version](https://img.shields.io/badge/version-2.5.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)
![PHPMailer](https://img.shields.io/badge/PHPMailer-6.9-red.svg)
![UTF-8](https://img.shields.io/badge/Encoding-UTF--8-brightgreen.svg)

**Plateforme web complète, sécurisée et optimisée pour la gestion de la communauté des anciens élèves de SIGMA.**  
Réseau professionnel, messagerie temps réel, gestion d'événements, élections et souvenirs partagés.

---

## 🎯 Vue d'ensemble

SIGMA Alumni est une solution "clé en main" pour animer une association d'alumni. Cette version 2.5 inclut des optimisations majeures pour le déploiement professionnel (SMTP sécurisé, nettoyage d'encodage, et automatisation CRON).

---

## ✨ Fonctionnalités Majeures

### 🔐 Sécurité & Authentification
- **Système d'inscription robuste** avec vérification par OTP (One-Time Password) envoyé par email.
- **Réinitialisation de mot de passe** sécurisée avec tokens temporaires.
- **Protection Anti-Injection SQL** via l'utilisation systématique de `mysqli` prepared statements.
- **Sessions Sécurisées** : Gestion centralisée dans le dossier `/sessions` avec protection contre le vol de session (HTTPOnly, SameSite).

### 📧 Système d'Emailing Professionnel
- **PHPMailer Intégré** : Utilisation du protocole SMTP (Gmail, SendGrid, etc.) pour une délivrabilité maximale (finit les spams !).
- **Routage Intelligent** : Distinction automatique entre les notifications Administrateur et les emails Utilisateurs.
- **Automatisations (CRON)** : 
  - `cron_birthday.php` : Envoi automatique de vœux d'anniversaire personnalisés.
  - `cron_new_year.php` : Envoi de vœux de bonne année à toute la base de données.

### 💬 Communication & Interaction
- **Messagerie Privée** : Système de chat fluide avec tri par pertinence.
- **Notifications Temps Réel** : Badge de compteur sur le header pour les messages non lus et les alertes système.
- **Élections & Sondages** : Module complet pour élire le bureau avec support multimédia (vidéos de campagne).

### 📸 Patrimoine de l'Association
- **Album Souvenirs** : Galerie photo organisée par année de promotion.
- **Yearbook Dynamique** : Annuaire complet avec recherche avancée et profils enrichis.

---

## 🚀 Installation Rapide (Local - XAMPP)

1. **Cloner le projet** dans votre dossier `htdocs`.
2. **Importer la base de données** : Utilisez le fichier `sql/sigma_db.sql` dans PHPMyAdmin.
3. **Configurer `config.php`** :
   - Le script détectera automatiquement `localhost` et chargera les paramètres par défaut de XAMPP.
4. **Lancer le serveur** : Accédez à `http://localhost/Sigma-Website`.

---

## ⚙️ Configuration Production (Déploiement)

Le projet est conçu pour être déployé sur n'importe quel hébergeur (InfinityFree, LWS, HostGator).

### Étapes de Déploiement :
1. **Éditer [config.php](config.php)** : Remplissez la section `CONFIGURATION PRODUCTION` avec vos accès SQL.
2. **Configurer le SMTP** : Entrez vos identifiants d'envoi d'emails (obligatoire pour les inscriptions et mots de passe oubliés).
3. **Permissions des dossiers** : Assurez-vous que `/uploads/`, `/sessions/` et `/souvenirs_pic/` sont en mode écriture (CHMOD 755).
4. **Optimisation Vidéo** : Compressez le fichier `assets/video/hero_background.mp4` avant l'upload (recommandé < 10Mo).

> 📘 Pour un guide détaillé pas-à-pas, consultez le fichier : **[DEPLOYMENT_README.md](DEPLOYMENT_README.md)**

---

## 🛠️ Stack Technique
- **Backend** : PHP 7.4+ (Architecture modulaire)
- **Frontend** : HTML5, CSS3, JavaScript (Vanilla + jQuery pour AJAX)
- **Base de données** : MySQL / MariaDB
- **Emails** : PHPMailer 6.9+
- **Assets** : FontAwesome 6, Google Fonts, Driver.js (Tutoriel)

---

## 📁 Structure du Projet
- `/includes/` : Coeur de l'application (config, managers, helpers).
- `/php/` : Fichiers de configuration environnementale.
- `/sql/` : Scripts de structure et données initiales.
- `/uploads/` : Stockage des médias utilisateurs (protégé).
- `/docs/` : Documentation technique détaillée par module.

---

## 📚 Documentation Supplémentaire
- [Guide des Emails](docs/EMAIL_SYSTEM_DOCS.md)
- [Configuration CRON](docs/CONFIGURATION_CRON.md)
- [Optimisation Vidéos](docs/CONFIG_VIDEO_UPLOAD.md)

---
*Développé avec ❤️ pour la communauté SIGMA.*
