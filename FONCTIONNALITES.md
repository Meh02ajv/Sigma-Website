# 📋 FONCTIONNALITÉS DÉTAILLÉES - SIGMA ALUMNI

Ce document décrit en détail toutes les fonctionnalités implémentées dans la plateforme SIGMA Alumni.

---

## Table des Matières

1. [Système d'Authentification](#1-système-dauthentification)
2. [Gestion des Profils](#2-gestion-des-profils)
3. [Yearbook (Trombinoscope)](#3-yearbook-trombinoscope)
4. [Messagerie en Temps Réel](#4-messagerie-en-temps-réel)
5. [Système d'Élections](#5-système-délections)
6. [Albums et Souvenirs](#6-albums-et-souvenirs)
7. [Gestion d'Événements](#7-gestion-dévénements)
8. [Pages Informatives](#8-pages-informatives)
9. [Thèmes Festifs](#9-thèmes-festifs)
10. [Interface d'Administration](#10-interface-dadministration)
11. [Système de Notifications](#11-système-de-notifications)
12. [Sécurité et Protection](#12-sécurité-et-protection)

---

## 1. Système d'Authentification

### 1.1 Inscription (`signup.php`, `creation_compte.php`)

**Fonctionnement** :
- Formulaire d'inscription avec validation côté client et serveur
- Vérification de l'unicité de l'email
- Génération d'un code de vérification à 6 chiffres
- Hashage sécurisé du mot de passe (bcrypt)
- Envoi du code par email via PHPMailer

**Champs requis** :
- Nom complet
- Email (unique dans la BDD)
- Mot de passe (min 8 caractères, 1 majuscule, 1 chiffre, 1 caractère spécial)
- Confirmation du mot de passe

**Tables impliquées** :
- `users` : Stockage des utilisateurs avec `verification_code`

**Code type** :
```php
// Génération du code de vérification
$verification_code = sprintf("%06d", mt_rand(0, 999999));

// Hashage du mot de passe
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insertion en BDD
$stmt = $conn->prepare("INSERT INTO users (email, password, verification_code) VALUES (?, ?, ?)");
```

---

### 1.2 Vérification du Compte (`verification.php`)

**Fonctionnement** :
- L'utilisateur reçoit un code à 6 chiffres par email
- Saisie du code dans le formulaire de vérification
- Comparaison avec le code en base de données
- Activation du compte si le code est correct

**Sécurité** :
- Code valide pendant 24h
- Maximum 5 tentatives avant blocage temporaire
- Possibilité de renvoyer un nouveau code

---

### 1.3 Connexion (`connexion.php`, `login.php`)

**Fonctionnement** :
- Formulaire de connexion (email + mot de passe)
- Vérification des credentials avec `password_verify()`
- Création de session sécurisée
- Régénération de l'ID de session (`session_regenerate_id()`)
- Redirection vers le dashboard

**Variables de session créées** :
```php
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['is_admin'] = $user['is_admin'];
```

**Protection** :
- Rate limiting (max 5 tentatives/minute)
- Protection contre brute force
- Token CSRF pour chaque formulaire

---

### 1.4 Réinitialisation du Mot de Passe

#### 1.4.1 Demande (`password_reset.php`)
- Formulaire avec saisie d'email
- Génération d'un token unique
- Envoi d'un lien de réinitialisation par email
- Token valide pendant 1h

#### 1.4.2 Nouveau Mot de Passe (`reset_password.php`)
- Vérification du token
- Formulaire nouveau mot de passe
- Validation des critères de sécurité
- Hashage et mise à jour en BDD
- Invalidation du token après utilisation

---

### 1.5 Déconnexion (`logout.php`)

**Fonctionnement** :
```php
session_start();
session_unset();
session_destroy();
header("Location: connexion.php");
```

---

## 2. Gestion des Profils

### 2.1 Création du Profil Initial (`creation_profil.php`, `create_profile.php`)

**Déclenchement** :
- Après vérification du compte
- Première connexion d'un utilisateur

**Informations collectées** :
- Nom complet
- Date de naissance
- Année du bac
- Domaine d'études
- Photo de profil (optionnelle)

**Upload de photo** :
- Formats acceptés : JPG, JPEG, PNG, GIF
- Taille max : 2MB
- Redimensionnement automatique : 500x500px
- Nom de fichier sécurisé : `profile_user{id}_{timestamp}.{ext}`
- Stockage dans `img/`

**Table** :
```sql
UPDATE users SET 
  full_name = ?, 
  birth_date = ?, 
  bac_year = ?, 
  studies = ?, 
  profile_picture = ? 
WHERE email = ?
```

---

### 2.2 Consultation de Profil

**Dans le Yearbook** :
- Clic sur une carte de profil
- Ouverture d'un modal avec toutes les infos
- Boutons d'action : Contacter, Signaler

**Informations affichées** :
- Photo de profil
- Nom complet
- Date de naissance (âge calculé)
- Année du bac
- Domaine d'études
- Badge "Anniversaire" si c'est le jour J

---

### 2.3 Modification de Profil (`mod_prof.php`, `update_profile.php`)

**Champs modifiables** :
- Photo de profil
- Nom complet
- Date de naissance
- Année du bac
- Domaine d'études

**Validation** :
- Vérification des formats
- Protection CSRF
- Vérification de l'unicité (email)

**Code type** :
```php
// Traitement de l'upload photo
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Validation
    if (!in_array($file['type'], $allowed_types)) {
        $error = "Type de fichier non autorisé";
    }
    
    // Upload
    $filename = 'profile_user' . $user_id . '_' . time() . '.' . $ext;
    move_uploaded_file($tmp_name, 'img/' . $filename);
}
```

---

### 2.4 Paramètres Utilisateur (`settings.php`)

**Fonctionnalités** :
- Changement de mot de passe
- Modification de l'email
- Préférences de notification (à venir)
- Suppression de compte (à venir)

---

## 3. Yearbook (Trombinoscope)

### 3.1 Affichage des Profils (`yearbook.php`)

**Interface** :
- Grille responsive de cartes de profil
- Chaque carte affiche : photo, nom, année bac, études
- Badge spécial "🎂 Anniversaire" si c'est le jour de l'utilisateur

**Pagination** :
- Chargement initial : 20 profils
- Infinite scroll avec AJAX
- Endpoint : `load_more_profiles.php`

**Code SQL** :
```sql
SELECT id, full_name, profile_picture, birth_date, bac_year, studies
FROM users
WHERE full_name IS NOT NULL
ORDER BY full_name ASC
LIMIT 20 OFFSET ?
```

---

### 3.2 Filtres de Recherche

**Filtres disponibles** :
1. **Année du bac** : Liste déroulante avec toutes les années uniques
2. **Domaine d'études** : Liste déroulante avec tous les domaines

**Fonctionnement** :
```javascript
// AJAX pour recharger les profils filtrés
function reloadProfiles() {
    const bacYear = document.getElementById('bac_year_filter').value;
    const studies = document.getElementById('studies_filter').value;
    
    fetch(`load_more_profiles.php?bac_year=${bacYear}&studies=${studies}`)
        .then(response => response.json())
        .then(data => {
            // Mise à jour de l'affichage
        });
}
```

**Interface mobile** :
- Bouton "Filtres" ouvrant un panneau latéral
- Applique les filtres et ferme le panneau
- Overlay pour fermer

---

### 3.3 Modal de Profil Détaillé

**Ouverture** :
- Clic sur une carte de profil
- Récupération des données via attributs `data-*`

**Affichage** :
- Photo en grand
- Toutes les informations du profil
- Boutons d'action :
  - **Contacter** : Redirige vers `messaging.php` avec pré-sélection
  - **Signaler** : Ouvre un formulaire de signalement

**Fermeture** :
- Bouton X
- Clic sur l'overlay
- Touche Échap (Escape)

---

### 3.4 Notifications d'Anniversaire

**Système automatique** :
- Script PHP vérifie les anniversaires du jour
- Compare `DATE(birth_date)` avec `DATE(NOW())`
- Affiche un badge "🎂" sur les cartes concernées
- (Optionnel) Envoi d'email aux autres membres

**Code** :
```php
$today_md = date('m-d');
$stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE DATE_FORMAT(birth_date, '%m-%d') = ?
");
$stmt->bind_param("s", $today_md);
```

---

### 3.5 WebSocket pour Mises à Jour en Temps Réel

**Fonctionnalité** :
- Connexion WebSocket au serveur (port 8080)
- Réception de messages pour :
  - Nouveaux profils ajoutés
  - Modifications de profil
  - Notifications d'anniversaire

**Code JavaScript** :
```javascript
const socket = new WebSocket('ws://localhost:8080');

socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    if (data.type === 'new_profile') {
        // Ajouter le nouveau profil à l'affichage
    }
};
```

---

## 4. Messagerie en Temps Réel

### 4.1 Architecture WebSocket (`websocket_server.php`)

**Serveur Ratchet** :
- Serveur WebSocket écoutant sur le port 8080
- Gestion des connexions persistantes
- Broadcast des messages aux utilisateurs connectés

**Démarrage** :
```bash
php websocket_server.php
```

**Production** : Utiliser Supervisor pour maintenir le processus actif

---

### 4.2 Interface de Messagerie (`messaging.php`)

**Layout** :
```
+-------------------+-------------------------+
|   Liste des       |     Chat Window        |
|   Utilisateurs    |                        |
|   (sidebar)       |   [Messages]           |
|                   |                        |
|   User 1          |   Bulle expéditeur     |
|   User 2 (3 🔴)   |   Bulle destinataire   |
|   User 3          |                        |
|                   |   [Input + Envoyer]    |
+-------------------+-------------------------+
```

**Mode Desktop** : Affichage côte à côte  
**Mode Mobile** : 
- Liste plein écran par défaut
- Chat plein écran lors de la sélection
- Bouton "←" pour revenir à la liste

---

### 4.3 Envoi de Messages

**Frontend** :
```javascript
function sendMessage() {
    const message = document.getElementById('message-input').value;
    
    // Envoi via WebSocket
    socket.send(JSON.stringify({
        type: 'chat',
        recipient_id: selectedUserId,
        message: message
    }));
    
    // Aussi enregistrer en BDD via AJAX
    fetch('send_message.php', {
        method: 'POST',
        body: JSON.stringify({
            recipient_id: selectedUserId,
            message: message
        })
    });
}
```

**Backend (`send_message.php`)** :
```php
$stmt = $conn->prepare("
    INSERT INTO discussion (sender_id, recipient_id, message, sent_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->bind_param("iis", $sender_id, $recipient_id, $message);
$stmt->execute();
```

---

### 4.4 Réception de Messages

**Chargement initial** :
```php
$stmt = $conn->prepare("
    SELECT * FROM discussion
    WHERE (sender_id = ? AND recipient_id = ?)
       OR (sender_id = ? AND recipient_id = ?)
    ORDER BY sent_at ASC
");
```

**WebSocket temps réel** :
```javascript
socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    if (data.type === 'chat') {
        appendMessage(data);
    }
};
```

---

### 4.5 Notifications de Messages Non Lus

**Badge sur l'icône messagerie** :
```html
<i class="fas fa-envelope"></i>
<span class="unread-count" id="unread-count">3</span>
```

**Comptage** :
```php
$stmt = $conn->prepare("
    SELECT recipient_id, COUNT(*) as count
    FROM discussion
    WHERE recipient_id = ? AND read_at IS NULL
    GROUP BY recipient_id
");
```

**API** : `get_unread_counts.php` appelée toutes les 30 secondes

---

### 4.6 Marquage comme Lu

**Déclencheur** :
- Ouverture d'une conversation
- Lecture d'un message

**Code** :
```php
$stmt = $conn->prepare("
    UPDATE discussion
    SET read_at = NOW()
    WHERE recipient_id = ? AND sender_id = ? AND read_at IS NULL
");
```

---

## 5. Système d'Élections

### 5.1 Création d'une Élection (Admin)

**Interface** : `admin.php` → Section "Élections"

**Champs** :
- Titre de l'élection
- Date de début du vote
- Date de fin du vote
- Statut (brouillon, en cours, terminée)

**Validation** :
- Date de fin > Date de début
- Titre unique

---

### 5.2 Ajout de Candidats

**Méthode 1 : Upload manuel** (Admin)
- Sélection d'un utilisateur
- Poste brigué (Président, Vice-Président, Trésorier, etc.)
- Photo de candidature
- Vidéo de présentation (optionnel, max 2GB)

**Méthode 2 : Candidature ouverte** (À implémenter)
- Formulaire de candidature par les membres
- Validation par les admins

**Tables** :
```sql
-- Table des candidats
CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    user_id INT NOT NULL,
    position VARCHAR(100),
    photo_path VARCHAR(255),
    video_path VARCHAR(255),
    FOREIGN KEY (election_id) REFERENCES elections(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

### 5.3 Page de Vote (`elections.php`)

**Navigation par onglets** :
1. **En cours** : Élections actives où l'utilisateur peut voter
2. **Terminées** : Élections clôturées avec résultats
3. **À venir** : Élections programmées

**Affichage des candidats** :
- Carte avec photo, nom, poste, promotion
- Boutons : "🎥 Voir la vidéo" | "📋 Voir le profil"
- Bouton "Voter" si pas encore voté

---

### 5.4 Processus de Vote

**Vérifications** :
1. Utilisateur connecté
2. Élection en cours
3. L'utilisateur n'a pas déjà voté

**Soumission** :
```php
$stmt = $conn->prepare("
    INSERT INTO votes (election_id, candidate_id, voter_id, voted_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->bind_param("iii", $election_id, $candidate_id, $user_id);
```

**Unicité** : Contrainte UNIQUE sur `(election_id, voter_id)`

---

### 5.5 Comptage des Votes

**Requête** :
```sql
SELECT 
    c.id, 
    u.full_name, 
    c.position,
    COUNT(v.id) as vote_count
FROM candidates c
JOIN users u ON c.user_id = u.id
LEFT JOIN votes v ON c.id = v.candidate_id
WHERE c.election_id = ?
GROUP BY c.id
ORDER BY c.position, vote_count DESC
```

---

### 5.6 Publication des Résultats

**Fonctionnalité admin** :
- Bouton "Publier les résultats"
- Change le statut de l'élection à "terminée"
- Rend les résultats visibles aux membres

**Affichage** :
- Graphique en barres (Chart.js)
- Tableau détaillé avec pourcentages
- Indication du gagnant par poste

---

## 6. Albums et Souvenirs

### 6.1 Galerie Souvenirs (`souvenirs.php`)

**Organisation** :
- Photos classées par année (2023, 2024, 2025...)
- Chaque année a son dossier : `souvenirs_pic/202X/`

**Affichage** :
- Grille responsive (3-4 colonnes desktop, 2 colonnes mobile)
- Lightbox pour agrandissement
- Lazy loading des images

---

### 6.2 Album Photos (`album.php`)

**Similaire à Souvenirs** mais avec :
- Organisation par événement
- Filtres par type d'événement
- Téléchargement d'albums complets

---

### 6.3 Upload de Médias (Admin)

**Interface** :
- Sélection de l'année
- Upload multiple d'images/vidéos
- Formats acceptés : JPG, PNG, MP4, WebM
- Taille max : 50MB par fichier

**Traitement** :
```php
$target_dir = "souvenirs_pic/" . $year . "/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

foreach ($_FILES['media_files']['tmp_name'] as $key => $tmp_name) {
    $filename = uniqid() . '_' . $_FILES['media_files']['name'][$key];
    move_uploaded_file($tmp_name, $target_dir . $filename);
}
```

---

### 6.4 Pagination Dynamique

**Chargement initial** : 20 photos  
**Load more** : Bouton chargeant 20 photos supplémentaires via AJAX

**Endpoint** : `load_more_photos.php`

---

## 7. Gestion d'Événements

### 7.1 Création d'Événement (Admin)

**Champs** :
- Titre de l'événement
- Description
- Date et heure
- Lieu
- Photo de couverture

**Table** :
```sql
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME,
    location VARCHAR(255),
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### 7.2 Affichage des Événements (`evenements.php`)

**Vue liste** :
- Cartes d'événements avec photo, titre, date, lieu
- Badge "🔴 En cours" pour événements du jour
- Badge "✅ Terminé" pour événements passés

**Filtres** :
- À venir
- Passés
- Par type (gala, conférence, sortie, etc.)

---

### 7.3 Page Détail d'un Événement

**Informations** :
- Photo pleine largeur
- Titre, date, lieu
- Description complète
- Galerie photos de l'événement (si passé)

**Actions** :
- Bouton "S'inscrire" (à implémenter)
- Partage sur réseaux sociaux
- Ajout au calendrier (export iCal)

---

## 8. Pages Informatives

### 8.1 Page d'Accueil (`accueil.php`)

**Sections** :
1. **Hero avec vidéo** :
   - Vidéo de fond en autoplay loop
   - Titre principal et slogan
   - Boutons CTA : "Connexion" | "Inscription"

2. **À propos** :
   - Présentation de SIGMA Alumni
   - Mission et valeurs
   - Compteur de membres

3. **Actualités** :
   - Les 3 dernières news
   - Miniature + extrait
   - Lien "Lire plus"

4. **Événements à venir** :
   - Les 3 prochains événements
   - Date + lieu + photo

5. **Footer** :
   - Liens rapides
   - Réseaux sociaux
   - Contact

---

### 8.2 Présentation du Bureau (`bureau.php`)

**Contenu** :
- Photo et nom de chaque membre du bureau
- Poste occupé
- Année de promotion
- Bio courte

**Organisation** :
- Grille responsive
- Possibilité de filtrer par mandat (2023-2024, 2024-2025)

**Source des données** :
- Table `bureau` ou configuration admin

---

### 8.3 Objectifs (`objectifs.php`)

**Structure** :
- Liste des objectifs de l'association
- Icône + titre + description pour chaque objectif
- Progression (si applicable)

**Exemples d'objectifs** :
- Maintenir le réseau alumni
- Faciliter les échanges professionnels
- Organiser des événements
- Contribuer au développement de SIGMA

---

### 8.4 Règlement Intérieur (`reglement.php`)

**Contenu** :
- Articles numérotés
- Sections : Adhésion, Droits, Devoirs, Sanctions
- Format HTML pour meilleure lisibilité

**Gestion** :
- Éditable depuis l'admin
- Versioning (garder l'historique des modifications)

---

### 8.5 Page À Propos (`info.php`)

**Sections** :
- Histoire de SIGMA
- Mission de l'association
- Valeurs fondamentales
- Équipe actuelle
- Contact

---

### 8.6 Contact (`contact.php`)

**Formulaire** :
- Nom
- Email
- Sujet
- Message

**Traitement** :
- Validation CSRF
- Sanitization des inputs (HTMLPurifier)
- Envoi d'email à l'adresse admin
- Stockage en BDD (table `contact_submissions`)

**Informations affichées** :
- Email de contact
- Téléphone
- Adresse postale
- Horaires d'ouverture
- Carte Google Maps (iframe)

---

## 9. Thèmes Festifs

### 9.1 Système de Thèmes (`festive_themes.css`)

**Thèmes disponibles** :
1. **Aucun** (default)
2. **Noël** (christmas)
3. **Indépendance du Togo** (independence)

**Activation** :
- Un seul thème actif à la fois
- Contrôlé depuis l'admin
- Stocké en BDD : `site_themes` table

---

### 9.2 Thème de Noël

**Couleurs** :
- Rouge : `#c41e3a`
- Vert : `#165b33`
- Or : `#d4af37`

**Effets** :
- Animation de flocons de neige
- Header avec dégradé rouge/vert
- Icônes de Noël : 🎄 ❄️ 🎅

**CSS** :
```css
body.theme-christmas {
    background: linear-gradient(135deg, #c41e3a 0%, #165b33 100%);
}

.snowflake {
    position: fixed;
    top: -10px;
    animation: fall 10s linear infinite;
}
```

---

### 9.3 Thème Indépendance du Togo

**Couleurs du drapeau** :
- Vert : `#006a4e`
- Jaune : `#ffcc00`
- Rouge : `#d21034`

**Effets** :
- Animation de confettis
- Étoile blanche symbolique
- Dégradé aux couleurs du drapeau

---

### 9.4 Gestion des Thèmes (Admin)

**Interface** : `admin.php` → Section "Thèmes Festifs"

**Cartes de thèmes** :
- Aperçu visuel
- Description
- Palette de couleurs
- Bouton "Activer"

**API** : `theme_manager.php`
- GET `/theme_manager.php?action=get_theme` : Récupère le thème actif
- POST `/theme_manager.php` : Change le thème

**Code** :
```javascript
fetch('theme_manager.php', {
    method: 'POST',
    body: JSON.stringify({ theme: 'christmas' })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        location.reload(); // Recharger pour appliquer
    }
});
```

---

## 10. Interface d'Administration

### 10.1 Tableau de Bord Admin (`admin.php`)

**Statistiques** :
- Nombre total d'utilisateurs
- Nombre de médias
- Nombre de messages échangés
- Nombre de signalements en attente
- Nombre de suggestions

**Graphiques** :
- Évolution des inscriptions (Chart.js)
- Répartition par année de bac
- Activité mensuelle

---

### 10.2 Gestion des Utilisateurs

**Liste des utilisateurs** :
- Tableau paginé avec toutes les infos
- Recherche par nom/email
- Filtres : Admins, Membres, Nouveaux

**Actions** :
- **Éditer** : Modifier les infos d'un utilisateur
- **Supprimer** : Suppression complète (avec confirmation)
- **Promouvoir Admin** : Donner les droits admin

**Modal d'édition** :
- Tous les champs modifiables
- Upload de nouvelle photo
- Changement de mot de passe (optionnel)

---

### 10.3 Gestion des Élections

**Fonctionnalités** :
- Créer une nouvelle élection
- Ajouter/modifier/supprimer des candidats
- Clôturer une élection
- Publier les résultats
- Exporter les résultats en PDF

---

### 10.4 Gestion du Contenu

**Sections éditables** :
1. **Règlement intérieur** :
   - Éditeur WYSIWYG (CKEditor)
   - Articles numérotés
   - Footer personnalisable

2. **Objectifs** :
   - Ajout/suppression d'objectifs
   - Ordre d'affichage
   - Icônes FontAwesome

3. **Valeurs** :
   - Similaire aux objectifs
   - Description de chaque valeur

4. **Bureau** :
   - Gestion des membres du bureau
   - Mandats (2023-2024, 2024-2025)

---

### 10.5 Configuration Générale

**Paramètres modifiables** :
- URLs des réseaux sociaux (Instagram, TikTok)
- Informations de contact (email, téléphone, adresse)
- Upload de logos :
  - Logo du header
  - Logo du footer
  - Favicon
  - Logo admin (synchronisé avec header)
- Vidéo de fond de la page d'accueil (max 2GB)

**Upload de vidéo** :
- Formats : MP4, WebM, MOV
- Taille max : 2GB
- Upload par chunks pour éviter les timeouts
- Barre de progression

---

### 10.6 Modération

**Signalements** :
- Liste de tous les signalements
- Détails : Qui a signalé qui, motif
- Actions : Approuver, Rejeter, Bannir l'utilisateur

**Suggestions** :
- Liste des suggestions des membres
- Statut : En attente, Approuvée, Rejetée, Implémentée
- Commentaires admin

---

### 10.7 Envoi d'Emails Groupés

**Interface** :
- Sélection des destinataires :
  - Tous les membres
  - Par année de bac
  - Par domaine d'études
  - Liste manuelle
- Objet et message (HTML supporté)
- Aperçu avant envoi
- Envoi en arrière-plan (éviter timeout)

**Code** :
```php
foreach ($recipients as $recipient) {
    $mail = new PHPMailer(true);
    $mail->setFrom('noreply@sigma-alumni.com');
    $mail->addAddress($recipient['email']);
    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->send();
}
```

---

## 11. Système de Notifications

### 11.1 Notifications d'Anniversaire

**Automatisme** :
- Script cron journalier (ou vérification à chaque visite)
- Détecte les anniversaires du jour
- Affiche un badge sur les profils concernés
- (Optionnel) Envoi d'email de félicitations

**Code** :
```php
$today_md = date('m-d');
$stmt = $conn->prepare("
    SELECT id, full_name FROM users
    WHERE DATE_FORMAT(birth_date, '%m-%d') = ?
");
$stmt->bind_param("s", $today_md);
$stmt->execute();
$birthdays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
```

---

### 11.2 Notifications de Messages

**Badge non lu** :
- Icône messagerie avec compteur rouge
- Mise à jour en temps réel via WebSocket
- Sauvegarde en localStorage pour persistance

---

### 11.3 Notifications d'Élections (À implémenter)

- Nouvelle élection ouverte
- Rappel avant clôture du vote
- Résultats publiés

---

### 11.4 Notifications d'Événements (À implémenter)

- Nouvel événement créé
- Rappel 24h avant l'événement
- Photos de l'événement ajoutées

---

## 12. Sécurité et Protection

### 12.1 Protection des Mots de Passe

**Hashage** :
```php
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
```

**Vérification** :
```php
if (password_verify($input_password, $stored_hash)) {
    // Authentification réussie
}
```

**Critères de force** :
- Minimum 8 caractères
- Au moins 1 majuscule
- Au moins 1 chiffre
- Au moins 1 caractère spécial

---

### 12.2 Protection CSRF

**Génération de token** :
```php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

**Inclusion dans les formulaires** :
```html
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
```

**Vérification** :
```php
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Erreur CSRF");
}
```

---

### 12.3 Protection SQL Injection

**Prepared Statements** :
```php
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
```

**JAMAIS** :
```php
// ❌ VULNÉRABLE
$sql = "SELECT * FROM users WHERE email = '$email'";
```

---

### 12.4 Protection XSS

**Échappement de sortie** :
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

**Nettoyage HTML** (pour contenu riche) :
```php
$purifier = new HTMLPurifier();
$clean_html = $purifier->purify($dirty_html);
```

---

### 12.5 Protection des Sessions

**Configuration** :
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS only
ini_set('session.use_strict_mode', 1);
```

**Régénération de l'ID** :
```php
session_regenerate_id(true);
```

---

### 12.6 Validation des Uploads

**Vérification du type MIME** :
```php
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['file']['tmp_name']);

if (!in_array($mime, $allowed_types)) {
    die("Type de fichier non autorisé");
}
```

**Vérification de la taille** :
```php
$max_size = 2 * 1024 * 1024; // 2MB
if ($_FILES['file']['size'] > $max_size) {
    die("Fichier trop volumineux");
}
```

---

### 12.7 Rate Limiting

**Connexion** :
- Maximum 5 tentatives par minute par IP
- Blocage temporaire de 15 minutes après 5 échecs

**API** :
- Maximum 100 requêtes/heure par utilisateur
- Headers de réponse avec limites restantes

---

### 12.8 Logs de Sécurité

**Événements loggés** :
- Tentatives de connexion échouées
- Modifications de profil
- Actions admin sensibles
- Uploads de fichiers
- Signalements

**Table** :
```sql
CREATE TABLE security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Conclusion

Cette plateforme SIGMA Alumni est un système complet et sécurisé pour gérer une communauté d'anciens élèves. Chaque fonctionnalité a été conçue avec soin pour offrir une expérience utilisateur optimale tout en maintenant un haut niveau de sécurité.

**Prochaines Étapes** :
- Voir [AMELIORATIONS_SUGGEREES.md](AMELIORATIONS_SUGGEREES.md) pour les features à venir
- Consulter [README.md](README.md) pour l'installation et la configuration

---

**Document mis à jour** : Décembre 2025  
**Version** : 1.0.0
