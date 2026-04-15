# 🚀 Guide de Déploiement - SIGMA ALUMNI

Ce document contient les étapes nécessaires pour déployer l'application **Sigma Alumni** sur un serveur de production (InfinityFree, HostGator, LWS, etc.) et les paramètres à configurer.

## 📋 Checklist de Pré-déploiement

1.  **Compression Vidéo** : Le fichier `assets/video/hero_background.mp4` actuel pèse ~825 Mo. **Il est impératif de le compresser** (cible : < 10 Mo) avant de l'uploader pour éviter des temps de chargement infinis et des erreurs 403/404 sur des serveurs mutualisés.
2.  **Base de Données** : Exportez votre base de données locale via PHPMyAdmin (`sigma_db.sql`) et importez-la sur votre interface d'hébergement.
3.  **Nettoyage** : Supprimez les fichiers de log ou les dossiers temporaires (`sessions/*`) avant l'upload.

---

## ⚙️ Paramètres à Modifier dans `config.php`

Lors du passage en production, ouvrez le fichier `config.php` et mettez à jour les sections suivantes :

### 1. Base de Données (Lignes 130-150)
Remplacez les valeurs par celles fournies par votre hébergeur :
```php
$db_host = 'votre_hote_mysql'; // ex: sql303.infinityfree.com
$db_user = 'votre_utilisateur';
$db_pass = 'votre_mot_de_passe';
$db_name = 'votre_nom_de_bdd';
```

### 2. Configuration SMTP (Emails) (Lignes 220-250)
Si vous utilisez Gmail (recommandé pour débuter) :
- `SMTP_USERNAME` : Votre adresse Gmail complète.
- `SMTP_PASSWORD` : **Mot de passe d'application** (16 caractères) généré dans votre compte Google (Sécurité > Validation en 2 étapes > Mots de passe d'application). **Ne pas utiliser votre mot de passe habituel.**

### 3. URLs de Production
Les scripts de CRON (`cron_birthday.php`, `cron_new_year.php`) utilisent actuellement :
`https://sigmawebsite.rf.gd/`
Si votre domaine change (ex: `ma-communaute-sigma.fr`), vous devrez faire un "Chercher et Remplacer" sur cette URL dans ces deux fichiers.

---

## 📁 Permissions de Dossiers (CHMOD)

Une fois les fichiers uploadés via FTP (FileZilla), assurez-vous que les dossiers suivants ont les bonnes permissions pour permettre l'écriture :

| Dossier | Permission Recommandée | Usage |
| :--- | :--- | :--- |
| `/uploads/` | `755` ou `777` | Photos de profil, pièces jointes |
| `/sessions/` | `755` ou `777` | Fichiers de session PHP |
| `/souvenirs_pic/` | `755` ou `777` | Photos de l'album souvenirs |

*Note : Commencez par `755`. Si l'upload ne fonctionne pas, passez à `777`.*

---

## ⏱️ Configuration des Tâches CRON (Automatisations)

Pour que les emails d'anniversaire et de vœux soient envoyés automatiquement, configurez des "Cron Jobs" dans votre panel d'hébergement (cPanel) :

1.  **Anniversaires** (`cron_birthday.php`) : 
    - Fréquence : `Une fois par jour` (ex: `0 8 * * *` pour 8h00 du matin).
    - Commande : `php /home/votreuser/htdocs/cron_birthday.php`
2.  **Nouvel An** (`cron_new_year.php`) :
    - Fréquence : `Une fois par an` (le 1er Janvier à 00:01).

---

## 🛠️ Support & Maintenance
- En cas d'erreur de connexion DB : Vérifiez que l'IP de votre serveur est autorisée dans les accès MySQL distants (si applicable).
- En cas d'email non reçu : Vérifiez que les constantes `SMTP_FROM_EMAIL` et `SMTP_USERNAME` sont identiques.
