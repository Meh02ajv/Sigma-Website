# 🚀 Guide de Déploiement - SIGMA Alumni

## 📊 Compatibilité des Plateformes d'Hébergement

### ✅ XAMPP (Développement Local)
**Status:** Entièrement fonctionnel

**Ce qui fonctionne:**
- ✅ Système d'emails SMTP (PHPMailer + Gmail)
- ✅ Base de données MySQL/MariaDB
- ✅ Uploads de fichiers (photos, albums)
- ✅ Sessions PHP
- ✅ CRON jobs (via scripts manuels ou tâches planifiées Windows)
- ✅ WebSockets (messagerie en temps réel)
- ✅ Toutes les fonctionnalités

**Configuration requise:**
- PHP 7.4+
- MySQL 5.7+ ou MariaDB
- Extensions: mysqli, gd, mbstring, openssl
- vendor/phpmailer/ (via Composer)

---

### ⚠️ InfinityFree (Hébergement Gratuit)
**Status:** Fonctionnel avec limitations sur les emails

**Ce qui fonctionne:**
- ✅ Base de données MySQL
- ✅ Interface utilisateur (profils, yearbook, albums)
- ✅ Uploads de fichiers
- ✅ Sessions PHP
- ✅ Formulaires et interactions

**Ce qui NE fonctionne PAS:**
- ❌ Emails SMTP (ports 25, 587, 465 bloqués)
- ❌ PHPMailer avec Gmail SMTP
- ❌ Notifications par email
- ❌ Emails de bienvenue
- ❌ Réinitialisation de mot de passe par email
- ❌ CRON jobs automatiques

**Pourquoi les emails ne marchent pas:**
InfinityFree bloque tous les ports SMTP pour éviter le spam:
- Port 25 (SMTP standard): ❌ Bloqué
- Port 587 (SMTP TLS): ❌ Bloqué
- Port 465 (SMTP SSL): ❌ Bloqué

**Solutions alternatives pour InfinityFree:**
1. **SendGrid API** (100 emails/jour gratuit)
2. **Mailgun API** (5000 emails/mois gratuit)
3. **SMTP2GO** (1000 emails/mois, port 2525 non bloqué)
4. **Désactiver les emails** (le site fonctionne, juste sans notifications)

---

### ✅ Hébergement Payant de Qualité
**Recommandations:** HostGator, Bluehost, SiteGround, OVH, Hostinger

**Status:** Entièrement fonctionnel

**Système d'emails SMTP:**
- ✅ Ports SMTP ouverts (25, 587, 465)
- ✅ PHPMailer fonctionne immédiatement
- ✅ Gmail SMTP compatible
- ✅ Meilleure délivrabilité (pas de spam)
- ✅ Pas de modification de code nécessaire

**Avantages supplémentaires:**
- ✅ CRON jobs automatiques (anniversaires, élections)
- ✅ SSL/HTTPS inclus
- ✅ Plus d'espace disque
- ✅ Support technique
- ✅ Meilleure performance
- ✅ Pas de publicités forcées

**Prix:** 3-10€/mois

**Migration depuis InfinityFree:**
1. Exporter la base de données MySQL
2. Télécharger tous les fichiers (WinSCP/FTP)
3. Importer sur le nouveau serveur
4. Les emails fonctionneront immédiatement ✅

---

## 📁 Fichiers à Déployer

### Fichiers Essentiels
```
📦 Racine:
├── *.php (tous les fichiers PHP)
├── config.php (⚠️ MAJ credentials serveur)
├── composer.json
├── .htaccess
│
📂 Dossiers à uploader:
├── vendor/ (⭐ IMPORTANT pour emails)
├── css/
├── js/
├── img/
├── includes/
├── sessions/ (créer, chmod 777)
├── uploads/ (créer, chmod 777)
├── souvenirs_pic/ (créer, chmod 777)
└── sql/ (pour référence)
```

### Dossiers à Créer sur le Serveur
```bash
# Avec permissions d'écriture
mkdir sessions
chmod 777 sessions

mkdir uploads
chmod 777 uploads

mkdir souvenirs_pic
chmod 777 souvenirs_pic
mkdir souvenirs_pic/2025
chmod 777 souvenirs_pic/2025
```

---

## ⚙️ Configuration par Environnement

### config.php - Section à Modifier

```php
// 🔧 SELON VOTRE HÉBERGEMENT

// ===== LOCALHOST (XAMPP) =====
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'sigma_alumni');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('BASE_URL', 'http://localhost/Sigma-Website');
}

// ===== INFINITYFREE =====
elseif (strpos($_SERVER['HTTP_HOST'], 'rf.gd') !== false) {
    define('DB_HOST', 'sql123.infinityfree.com');
    define('DB_NAME', 'ifxxxxx_sigma');
    define('DB_USER', 'ifxxxxx_user');
    define('DB_PASS', 'votre_mot_de_passe');
    define('BASE_URL', 'https://sigmawebsite.rf.gd');
    
    // ⚠️ Emails ne fonctionnent pas sur InfinityFree
    // Utilisez SendGrid API ou désactivez
}

// ===== PRODUCTION (Hébergement Payant) =====
else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'votre_db');
    define('DB_USER', 'votre_user');
    define('DB_PASS', 'votre_password');
    define('BASE_URL', 'https://votre-domaine.com');
}

// ===== SMTP (fonctionne partout sauf InfinityFree) =====
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'gojomeh137@gmail.com');
define('SMTP_PASSWORD', 'vvvc qbzg sfey jkvi');
define('SMTP_PORT', 587);
```

---

## 📤 Déploiement avec WinSCP (InfinityFree)

### Étape 1: Connexion FTP
```
Host: ftpupload.net
Username: votre_username_infinityfree
Password: votre_password_infinityfree
Port: 21
```

### Étape 2: Upload des Fichiers
1. **Uploader d'abord:**
   - Tous les fichiers PHP
   - config.php (avec credentials InfinityFree)
   - .htaccess

2. **Uploader ensuite:**
   - vendor/ (⚠️ peut prendre du temps, 50MB+)
   - css/, js/, img/, includes/

3. **Créer les dossiers:**
   - sessions/ (chmod 777)
   - uploads/ (chmod 777)
   - souvenirs_pic/ (chmod 777)

### Étape 3: Base de Données
1. Aller dans le panel InfinityFree
2. Créer une base MySQL
3. Importer `sql/schema.sql`
4. Noter les credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)

### Étape 4: Tester
- Accéder à: `https://votresite.rf.gd`
- Vérifier la connexion
- Créer un compte test
- ⚠️ Les emails ne seront pas envoyés (normal sur InfinityFree)

---

## 🔐 Sécurité en Production

### .htaccess Recommandé
```apache
# Désactiver l'affichage des erreurs
php_flag display_errors Off

# Protection des fichiers
<Files "config.php">
    Order Deny,Allow
    Deny from all
</Files>

# Forcer HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### config.php en Production
```php
// Masquer les erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Logs sécurisés
ini_set('log_errors', 1);
ini_set('error_log', '/home/path/logs/php-error.log');
```

---

## 🧪 Checklist de Déploiement

### Avant le Déploiement
- [ ] Tester localement sur XAMPP
- [ ] Exporter la base de données
- [ ] Vérifier tous les fichiers uploadés
- [ ] Backup complet du site

### Pendant le Déploiement
- [ ] Upload tous les fichiers PHP
- [ ] Upload vendor/ (pour emails)
- [ ] Upload css/, js/, img/, includes/
- [ ] Créer sessions/, uploads/, souvenirs_pic/
- [ ] Configurer permissions (755 fichiers, 777 dossiers upload)
- [ ] Importer base de données
- [ ] Mettre à jour config.php

### Après le Déploiement
- [ ] Tester la connexion
- [ ] Tester la création de compte
- [ ] Tester l'upload de photos
- [ ] Vérifier les emails (si hébergement payant)
- [ ] Tester le yearbook
- [ ] Tester la messagerie
- [ ] Vérifier les permissions des dossiers

---

## 🆘 Résolution de Problèmes

### Page Blanche (White Screen)
**Causes:**
- vendor/ manquant → Upload vendor/
- Erreur PHP → Activer error_display temporairement
- Permissions incorrectes → chmod 755 fichiers

### Emails ne fonctionnent pas
**Sur InfinityFree:**
- Normal, ports SMTP bloqués
- Solutions: SendGrid API ou Mailgun API

**Sur hébergement payant:**
- Vérifier SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD dans config.php
- Tester avec un email simple
- Vérifier que vendor/phpmailer/ existe

### Uploads ne fonctionnent pas
**Solution:**
```bash
chmod 777 uploads/
chmod 777 souvenirs_pic/
chmod 777 souvenirs_pic/2025/
```

### Base de données ne se connecte pas
**Vérifier config.php:**
- DB_HOST (localhost ou domaine InfinityFree)
- DB_NAME, DB_USER, DB_PASS corrects
- Base importée correctement

---

## 📊 Comparatif: InfinityFree vs Hébergement Payant

| Fonctionnalité | InfinityFree | Hébergement Payant |
|----------------|--------------|-------------------|
| **Prix** | Gratuit | 3-10€/mois |
| **Base de données** | ✅ MySQL | ✅ MySQL |
| **PHP** | ✅ 7.4+ | ✅ 7.4+/8.0+ |
| **Emails SMTP** | ❌ Bloqué | ✅ Fonctionne |
| **CRON jobs** | ❌ Non | ✅ Oui |
| **SSL/HTTPS** | ✅ Gratuit | ✅ Gratuit |
| **Espace disque** | 5GB | 10-100GB |
| **Bande passante** | Limitée | Illimitée |
| **Publicités** | ⚠️ Forcées | ❌ Aucune |
| **Support** | ⚠️ Forum | ✅ 24/7 |
| **Performance** | ⚠️ Lente | ✅ Rapide |

---

## 🎯 Recommandation Finale

### Pour Tester Gratuitement
✅ **InfinityFree** est suffisant
- Le site fonctionne (sauf emails)
- Idéal pour démonstration
- Pas de frais

### Pour Production Sérieuse
✅ **Hébergement Payant** recommandé
- Emails SMTP fonctionnent immédiatement
- CRON jobs pour anniversaires/élections
- Meilleure performance et sécurité
- ~5€/mois (Hostinger, OVH)

**Migration simple:** Exporter/Importer, les emails marcheront instantanément sans modification de code.

---

**Dernière mise à jour:** 6 mars 2026
