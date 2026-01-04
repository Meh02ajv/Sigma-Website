# Tutoriel Interactif SIGMA Alumni

## 📋 Vue d'ensemble

Le tutoriel interactif utilise **Driver.js** pour guider les nouveaux utilisateurs à travers toutes les fonctionnalités du site.

## ✨ Fonctionnalités

- ✅ **Lancement automatique** à la première connexion
- ✅ **11 étapes** couvrant toutes les pages principales
- ✅ **Progression sauvegardée** en base de données
- ✅ **Bouton "Aide"** dans Paramètres pour relancer à tout moment
- ✅ **Responsive** - fonctionne sur mobile et desktop

## 🚀 Installation

### 1. Exécuter le script SQL

```bash
mysql -u root -p sigma < sql/add_tutorial_field.sql
```

Ou via phpMyAdmin, exécuter :
```sql
ALTER TABLE users ADD COLUMN tutorial_completed TINYINT(1) DEFAULT 0 AFTER login_count;
```

### 2. Fichiers créés

- `js/tutorial.js` - Logique du tutoriel Driver.js
- `mark_tutorial_completed.php` - API pour sauvegarder la progression
- `sql/add_tutorial_field.sql` - Script de migration

### 3. Fichiers modifiés

- `connexion.php` - Détection première connexion
- `dashboard.php` - Inclusion Driver.js CDN
- `settings.php` - Bouton "Aide"

## 📖 Utilisation

### Pour les nouveaux utilisateurs

1. Connexion au site
2. Le tutoriel se lance automatiquement
3. Suivre les 11 étapes
4. Cliquer sur "Terminer" à la fin

### Pour relancer le tutoriel

1. Aller dans **Paramètres**
2. Cliquer sur **"Aide - Guide du site"**
3. Le tutoriel redémarre

## 🎨 Personnalisation

### Modifier les étapes du tutoriel

Éditer `js/tutorial.js`, section `steps` :

```javascript
{
    element: 'nav a[href="yearbook.php"]',
    popover: {
        title: '📚 Yearbook',
        description: 'Votre description ici',
        side: "bottom",
        align: 'start'
    }
}
```

### Ajouter une nouvelle étape

```javascript
{
    element: '#mon-element',
    popover: {
        title: '🎯 Nouveau titre',
        description: 'Nouvelle description',
        side: "top", // top, bottom, left, right, center
        align: 'start' // start, center, end
    }
}
```

### Changer la langue des boutons

Dans `js/tutorial.js` :

```javascript
showButtons: ['next', 'previous', 'close'],
nextBtnText: 'Suivant',
prevBtnText: 'Précédent',
doneBtnText: 'Terminer',
```

## 🔧 Dépannage

### Le tutoriel ne se lance pas

1. Vérifier que Driver.js est chargé :
   ```javascript
   console.log(window.driver);
   ```

2. Vérifier que le champ `tutorial_completed` existe :
   ```sql
   SHOW COLUMNS FROM users LIKE 'tutorial_completed';
   ```

3. Vérifier les logs console (F12)

### Le tutoriel ne se sauvegarde pas

Vérifier les permissions du fichier `mark_tutorial_completed.php` et que l'utilisateur est bien connecté.

## 📦 Librairies utilisées

- **Driver.js v1.3.1** - https://driverjs.com/
- CDN : `https://cdn.jsdelivr.net/npm/driver.js@1.3.1/`

## 🔐 Sécurité

- Vérification de session avant sauvegarde
- Requête préparée pour mise à jour SQL
- Validation côté serveur

## 📝 Notes

- Le tutoriel s'adapte automatiquement à la navigation
- Les éléments sont mis en surbrillance pendant le tutoriel
- Un overlay sombre est appliqué au reste de la page
- La progression est affichée en haut (ex: "3 sur 11")

## 🎯 Pages couvertes

1. Accueil/Dashboard
2. Yearbook
3. Messagerie
4. Événements
5. Élections
6. Souvenirs
7. Album Photos
8. Notifications
9. Paramètres
10. Introduction
11. Conclusion

## 🔄 Mise à jour

Pour mettre à jour Driver.js :
1. Changer la version dans les liens CDN
2. Tester la compatibilité
3. Mettre à jour ce README
