# 📸 Guide de Modification de Profil

## Vue d'ensemble

Le système de modification de profil permet aux utilisateurs de gérer leurs informations personnelles et leur photo de profil de manière sécurisée et intuitive.

---

## ✨ Fonctionnalités

### 1. **Upload de Photo de Profil**
- ✅ Formats supportés : JPEG, PNG, GIF, WebP
- ✅ Taille maximale : 5 MB
- ✅ Prévisualisation en temps réel avant upload
- ✅ Validation côté client et serveur
- ✅ Redimensionnement et optimisation automatiques
- ✅ Noms de fichiers sécurisés (MD5 hash)

### 2. **Suppression de Photo**
- 🗑️ Bouton de suppression visible uniquement si une photo existe
- ✅ Confirmation avant suppression
- ✅ Suppression physique du fichier du serveur
- ✅ Restauration automatique de l'image par défaut

### 3. **Modification des Informations**
- 📝 Nom complet
- 📅 Date de naissance
- 🎓 Année du bac (avec validation 1900 - année actuelle)
- 📚 Études actuelles
- 🔒 Changement de mot de passe (optionnel, min 8 caractères)

### 4. **Sécurité**
- 🔐 Protection CSRF avec tokens
- 🛡️ Validation stricte des types de fichiers (MIME + extension)
- 🔍 Vérification getimagesize() pour détecter les faux fichiers images
- 🧹 Sanitisation de tous les inputs
- 🚫 Protection contre les injections SQL (prepared statements)
- 🔒 Session sécurisée requise

---

## 📁 Structure des Fichiers

```
├── mod_prof.php              # Page de modification de profil (interface)
├── update_profile.php        # Script de traitement des modifications
├── config.php                # Configuration de la base de données
├── img/                      # Répertoire des photos de profil
│   ├── profile_pic.jpeg      # Image par défaut
│   └── profile_*.{jpg,png,gif,webp}  # Photos uploadées
└── test_profile_upload.php   # Fichier de test et diagnostic
```

---

## 🔧 Configuration Requise

### Extensions PHP
- ✅ `mysqli` - Connexion à la base de données
- ✅ `gd` - Manipulation d'images
- ✅ `fileinfo` - Détection du type MIME

### Configuration PHP (php.ini)
```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20
memory_limit = 128M
```

### Base de Données
Table `users` doit contenir le champ :
```sql
profile_picture VARCHAR(255) NULL DEFAULT NULL
```

### Permissions Fichiers
- Répertoire `img/` : **0755** (rwxr-xr-x)
- Fichiers images : **0644** (rw-r--r--)

---

## 🚀 Utilisation

### Pour l'Utilisateur

1. **Se connecter** au compte
2. Accéder à **Paramètres** > **Modifier le profil**
3. **Cliquer sur l'icône caméra** sur la photo de profil
4. **Sélectionner une image** depuis l'ordinateur
5. **Prévisualiser** l'image avant validation
6. **Enregistrer les modifications**

### Suppression de Photo

1. Si une photo existe, le bouton **"Supprimer la photo"** apparaît
2. Cliquer et **confirmer la suppression**
3. La **photo par défaut** est automatiquement restaurée

---

## 🔍 Processus de Validation

### Côté Client (JavaScript)

```javascript
// Vérifications effectuées avant l'envoi
✓ Type de fichier (JPEG, PNG, WebP)
✓ Taille < 5 MB
✓ Prévisualisation de l'image
✓ Validation des champs obligatoires
```

### Côté Serveur (PHP)

```php
// Validations de sécurité
✓ Vérification CSRF token
✓ Validation MIME type
✓ Validation extension de fichier
✓ Vérification getimagesize() (vraie image)
✓ Taille < 5 MB
✓ Nom de fichier sécurisé avec MD5
✓ Suppression de l'ancienne photo
```

---

## 🛡️ Mesures de Sécurité

### 1. **Protection CSRF**
```php
// Génération du token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Validation
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Erreur CSRF");
}
```

### 2. **Validation Triple des Images**
```php
// 1. Vérification MIME type
$valid_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// 2. Vérification extension
$valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// 3. Vérification getimagesize (vraie image)
$image_info = getimagesize($file['tmp_name']);
```

### 3. **Nom de Fichier Sécurisé**
```php
// Évite les injections de chemin et conflits de noms
$new_filename = 'profile_' . md5($user_email . time()) . '.' . $ext;
```

### 4. **Suppression Sécurisée**
```php
// Supprime l'ancienne photo mais pas l'image par défaut
if ($current_picture && $current_picture !== $default_image) {
    if (file_exists($current_picture)) {
        unlink($current_picture);
    }
}
```

---

## 🐛 Dépannage

### Problème : "Erreur lors du téléchargement de l'image"

**Solutions :**
1. Vérifier que le répertoire `img/` existe
2. Vérifier les permissions : `chmod 755 img/`
3. Vérifier l'espace disque disponible
4. Vérifier `upload_max_filesize` dans php.ini

### Problème : "Le fichier n'est pas une image valide"

**Solutions :**
1. Vérifier que l'extension GD est activée
2. Essayer un autre format d'image
3. Vérifier que l'image n'est pas corrompue
4. Utiliser un outil de conversion en ligne

### Problème : "La photo n'apparaît pas après upload"

**Solutions :**
1. Vider le cache du navigateur (Ctrl+F5)
2. Vérifier que le fichier existe dans `img/`
3. Vérifier les permissions du fichier : `chmod 644`
4. Vérifier la valeur dans la base de données

### Problème : "Session expirée / Non connecté"

**Solutions :**
1. Se reconnecter au compte
2. Vérifier que les cookies sont activés
3. Vérifier `session.save_path` dans php.ini

---

## 📊 Tests et Diagnostic

### Exécuter le fichier de test

```bash
# Accéder via navigateur
http://localhost/Sigma-Website/test_profile_upload.php
```

Le script de test vérifie :
- ✅ Existence et permissions du répertoire `img/`
- ✅ Présence de l'image par défaut
- ✅ Configuration PHP pour l'upload
- ✅ Connexion à la base de données
- ✅ Structure de la table `users`
- ✅ Présence de tous les fichiers nécessaires
- ✅ Extensions PHP requises

---

## 📝 Logs et Débogage

### Activer les erreurs PHP (développement uniquement)

```php
// Dans update_profile.php (première ligne)
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Vérifier les uploads PHP

```php
// Informations sur le dernier upload
var_dump($_FILES['profile_picture']);
```

### Vérifier la base de données

```sql
-- Voir toutes les photos de profil
SELECT email, profile_picture FROM users WHERE profile_picture IS NOT NULL;

-- Compter les utilisateurs avec photo
SELECT COUNT(*) as total_with_photo FROM users WHERE profile_picture IS NOT NULL;
```

---

## 🎨 Personnalisation

### Changer l'image par défaut

```php
// Dans mod_prof.php et update_profile.php
$default_image = 'img/votre_nouvelle_image.jpg';
```

### Modifier la taille maximale

```php
// Dans update_profile.php
if ($file['size'] > 10 * 1024 * 1024) { // 10 MB au lieu de 5 MB
    $_SESSION['error'] = "L'image est trop volumineuse (max 10MB).";
    exit;
}
```

### Ajouter des formats supplémentaires

```php
// Dans update_profile.php
$valid_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
```

---

## 📱 Responsive Design

Le système est entièrement responsive avec des breakpoints à :
- 📱 Mobile : < 576px
- 📱 Tablette : 576px - 768px
- 💻 Desktop : > 768px

---

## ✅ Checklist de Vérification

Avant de considérer le système fonctionnel :

- [ ] Le répertoire `img/` existe et est accessible en écriture
- [ ] L'image par défaut `img/profile_pic.jpeg` existe
- [ ] MySQL est démarré dans XAMPP
- [ ] La table `users` a le champ `profile_picture`
- [ ] Les extensions PHP (mysqli, gd, fileinfo) sont activées
- [ ] `upload_max_filesize` est configuré à au moins 5M
- [ ] Les fichiers mod_prof.php et update_profile.php existent
- [ ] Le test (test_profile_upload.php) passe tous les contrôles
- [ ] L'upload d'une image fonctionne
- [ ] La prévisualisation fonctionne
- [ ] La suppression fonctionne
- [ ] L'image s'affiche partout où le profil apparaît

---

## 🎯 Conclusion

Le système de modification de profil est **robuste, sécurisé et user-friendly**. Il inclut :

✅ **Validation complète** (client + serveur)  
✅ **Sécurité renforcée** (CSRF, validation triple)  
✅ **Interface moderne** et responsive  
✅ **Gestion automatique** des anciennes photos  
✅ **Messages d'erreur** clairs et utiles  
✅ **Performance optimisée**  

Pour toute question ou problème, consultez le fichier de test ou les logs du serveur.

---

**Dernière mise à jour :** 30 novembre 2025  
**Version :** 2.0  
**Auteur :** Équipe Sigma Yearbook
```
