# Configuration pour uploads de vidéos 2GB

## Modifications effectuées

### 1. Code PHP (admin.php)
- ✅ Limite de vidéo augmentée de 50MB à **2GB**
- ✅ Messages d'erreur améliorés avec affichage en GB
- ✅ Affichage de la taille du fichier vidéo actuel
- ✅ Indication du temps d'upload pour gros fichiers

### 2. Fichier .htaccess créé
Le fichier `.htaccess` configure automatiquement :
- `upload_max_filesize = 2048M` (2GB)
- `post_max_size = 2048M` (2GB)
- `memory_limit = 512M`
- `max_execution_time = 600` (10 minutes)
- `max_input_time = 600` (10 minutes)

### 3. Configuration PHP (php.ini)

**⚠️ IMPORTANT:** Si le `.htaccess` ne suffit pas, modifiez directement le fichier `php.ini`

#### Emplacement du php.ini dans XAMPP :
```
C:\xampp\php\php.ini
```

#### Lignes à modifier :
Recherchez et modifiez ces valeurs (retirez le `;` au début si présent) :

```ini
upload_max_filesize = 2048M
post_max_size = 2048M
memory_limit = 512M
max_execution_time = 600
max_input_time = 600
```

#### Redémarrage d'Apache :
1. Ouvrez le **XAMPP Control Panel**
2. Cliquez sur **Stop** pour Apache
3. Attendez 2-3 secondes
4. Cliquez sur **Start** pour Apache

## Test de la configuration

### Option 1 : Script de diagnostic
Ouvrez dans votre navigateur :
```
http://localhost/Sigma-Website/test_upload.php
```

Ce script vous indiquera :
- ✅ Configuration PHP actuelle
- ✅ Permissions des dossiers
- ✅ Test d'upload en direct

### Option 2 : Vérification manuelle
Créez un fichier `info.php` à la racine :
```php
<?php phpinfo(); ?>
```

Ouvrez `http://localhost/Sigma-Website/info.php` et cherchez :
- `upload_max_filesize`
- `post_max_size`
- `memory_limit`

**⚠️ Supprimez ce fichier après vérification pour la sécurité !**

## Utilisation dans l'admin

1. Connectez-vous à l'admin : `/admin.php`
2. Allez dans **Configuration Générale**
3. Section **Médias et Logos**
4. Choisissez votre vidéo (jusqu'à 2GB)
5. Cliquez sur **Mettre à jour la configuration**
6. ⏱️ **Patience !** L'upload peut prendre 5-10 minutes pour une vidéo de 2GB

## Résolution de problèmes

### Erreur "Fichier trop volumineux"
- Vérifiez que le php.ini a bien été modifié
- Redémarrez Apache
- Testez avec `test_upload.php`

### Timeout pendant l'upload
- Augmentez `max_execution_time` à 1200 (20 minutes)
- Augmentez `max_input_time` à 1200

### Erreur de mémoire
- Augmentez `memory_limit` à 1024M ou plus

### L'upload s'arrête à 50%
- Vérifiez l'espace disque disponible
- Vérifiez que le dossier `uploads/videos/` est accessible en écriture

## Formats vidéo recommandés

Pour des uploads plus rapides et une meilleure compatibilité web :

| Format | Codec | Taille estimée | Qualité |
|--------|-------|----------------|---------|
| MP4 | H.264 | 50-200MB/min | Excellente |
| WebM | VP9 | 30-150MB/min | Très bonne |
| MP4 | H.265 | 25-100MB/min | Excellente |

### Compression recommandée
Si votre vidéo dépasse 500MB, utilisez **HandBrake** pour la compresser :
1. Téléchargez HandBrake (gratuit)
2. Preset : "Web" → "Gmail Large 3 Minutes 720p30"
3. Codec : H.264
4. Qualité : RF 23-25
5. Résolution : 1920x1080 maximum

## Sécurité

✅ Seuls les formats `.mp4`, `.webm`, `.mov` sont acceptés
✅ Vérification du type MIME
✅ Protection CSRF
✅ Accès admin uniquement

---

**Date de configuration :** 2 décembre 2025
**Limite maximale :** 2 GB (2048 MB)
**Temps d'upload estimé :** 
- 500 MB : ~2-3 minutes
- 1 GB : ~4-6 minutes  
- 2 GB : ~8-12 minutes

*(Selon vitesse réseau locale)*
