# 🎨 Système de Thèmes Festifs - SIGMA Alumni

## 📋 Vue d'ensemble

Le système de thèmes festifs permet d'activer des designs spéciaux pour célébrer différentes occasions :

- 🎄 **Thème de Noël / Fêtes de fin d'année** : Design festif avec flocons de neige, couleurs rouge/vert/or
- 🇹🇬 **Thème Indépendance du Togo** : Design patriotique aux couleurs nationales (vert/jaune/rouge)

## ✨ Caractéristiques

### Thème de Noël
- Palette de couleurs festives (rouge #c41e3a, vert #165b33, or #d4af37)
- Animation de flocons de neige tombant
- Décorations de Noël (sapins 🎄, étoiles ✨)
- Guirlandes lumineuses animées
- Effets de clignotement et de balancement

### Thème Indépendance du Togo
- Couleurs du drapeau togolais (vert #006a4e, jaune #ffcc00, rouge #d21034)
- Étoile blanche animée
- Header aux bandes horizontales vertes et jaunes avec rectangle rouge
- Confettis tricolores animés
- Message patriotique "Vive le Togo libre et indépendant!"
- Effet de drapeau qui flotte

## 🔧 Installation

### 1. Base de données
La table `site_themes` a été créée automatiquement :

```sql
CREATE TABLE IF NOT EXISTS site_themes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    theme_name VARCHAR(50) NOT NULL DEFAULT 'none',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. Fichiers créés
- `festive_themes.css` : Tous les styles des thèmes festifs
- `theme_manager.php` : API pour gérer l'activation/désactivation
- `create_themes_table.sql` : Script SQL de création de table

### 3. Fichiers modifiés
- `admin.php` : Interface d'administration des thèmes
- `header.php` : Chargement automatique du CSS et classe body

## 📖 Utilisation

### Activation depuis l'interface admin

1. Connectez-vous en tant qu'administrateur
2. Allez dans **Paramètres > Thèmes Festifs**
3. Cliquez sur le thème souhaité ou sur son bouton d'activation
4. La page se rechargera automatiquement pour appliquer le thème

### Règle exclusive
⚠️ **Important** : Un seul thème peut être actif à la fois. L'activation d'un nouveau thème désactive automatiquement le précédent.

## 🎯 Thèmes disponibles

| Thème | Valeur | Occasions recommandées |
|-------|--------|------------------------|
| Aucun thème | `none` | Design standard du site |
| Fêtes de fin d'année | `christmas` | Décembre - Janvier |
| Indépendance du Togo | `independence` | 27 avril (Jour de l'indépendance) |

## 💻 API Technique

### Endpoints (theme_manager.php)

#### Activer un thème
```javascript
fetch('theme_manager.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=set_theme&theme=christmas'
})
```

#### Récupérer le thème actif
```javascript
fetch('theme_manager.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_theme'
})
```

### Thèmes valides
- `none` : Pas de thème
- `christmas` : Thème de Noël
- `independence` : Thème Indépendance

## 🎨 Personnalisation

### Modifier les couleurs
Éditez `festive_themes.css` et modifiez les variables CSS :

```css
/* Thème Noël */
body.theme-christmas {
    --primary-blue: #c41e3a;  /* Rouge Noël */
    --dark-blue: #165b33;     /* Vert sapin */
    --accent-gold: #d4af37;   /* Or festif */
}

/* Thème Indépendance */
body.theme-independence {
    --primary-blue: #006a4e;   /* Vert Togo */
    --dark-blue: #ffcc00;      /* Jaune Togo */
    --accent-red: #d21034;     /* Rouge Togo */
}
```

### Ajouter un nouveau thème

1. **Base de données** : Ajouter la valeur du thème dans la validation
   ```php
   // theme_manager.php
   $valid_themes = ['none', 'christmas', 'independence', 'nouveau_theme'];
   ```

2. **CSS** : Ajouter les styles dans `festive_themes.css`
   ```css
   body.theme-nouveau_theme {
       /* Vos styles ici */
   }
   ```

3. **Interface admin** : Ajouter une card dans `admin.php`
   ```html
   <div class="theme-card" data-theme="nouveau_theme">
       <!-- Contenu de la card -->
   </div>
   ```

## 📱 Responsive

Les thèmes sont entièrement responsifs avec des breakpoints à :
- 768px (tablettes)
- 480px (mobiles)

Les animations et décorations s'adaptent automatiquement à la taille de l'écran.

## 🔒 Sécurité

- Accès restreint aux administrateurs uniquement
- Validation CSRF pour toutes les opérations
- Validation des valeurs de thèmes côté serveur
- Échappement HTML pour toutes les sorties

## 🐛 Dépannage

### Le thème ne s'applique pas
1. Vérifiez que `festive_themes.css` est accessible
2. Videz le cache du navigateur (Ctrl + F5)
3. Vérifiez que la table `site_themes` existe dans la base de données

### Les animations sont saccadées
- Les animations utilisent des transformations CSS optimisées
- Sur les appareils moins performants, certaines animations peuvent être désactivées

### Le thème persiste après désactivation
- Rechargez la page avec Ctrl + F5
- Vérifiez la valeur dans la base : `SELECT * FROM site_themes`

## 📅 Calendrier suggéré

| Période | Thème recommandé |
|---------|------------------|
| 1er décembre - 5 janvier | Fêtes de fin d'année |
| 20-30 avril | Indépendance du Togo |
| Reste de l'année | Aucun thème |

## 🎉 Crédits

Développé pour SIGMA Alumni
Système de thèmes festifs v1.0
