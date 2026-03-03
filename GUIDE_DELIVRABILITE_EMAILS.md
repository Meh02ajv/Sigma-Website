# 📧 Guide de Délivrabilité des Emails - Éviter les Spams

Ce guide vous explique comment améliorer la délivrabilité de vos emails et éviter qu'ils ne finissent dans les spams.

## ✅ Améliorations Déjà Implémentées

### 1. Configuration PHPMailer Optimisée
- ✓ Encodage UTF-8 avec base64
- ✓ Headers anti-spam personnalisés
- ✓ X-Mailer masqué (pour éviter de révéler la version de PHPMailer)
- ✓ Message-ID personnalisé
- ✓ List-Unsubscribe header (RFC 2369)
- ✓ Return-Path configuré
- ✓ Validation SSL/TLS stricte

### 2. Rate Limiting
- ✓ Pause de 100ms tous les 10 emails
- ✓ Pause de 2 secondes tous les 50 emails
- ✓ Prévention du blacklistage pour envoi massif

### 3. Templates HTML Professionnels
- ✓ Structure HTML5 valide avec DOCTYPE
- ✓ Meta viewport pour responsive
- ✓ Styles inline (meilleure compatibilité)
- ✓ Version texte alternative (AltBody) détaillée
- ✓ Ratio texte/HTML équilibré
- ✓ Lien de désinscription visible dans le footer
- ✓ Échappement HTML (htmlspecialchars) pour prévenir les injections
- ✓ Bon équilibre entre images et texte (pas d'images externes)

### 4. Contenu Anti-Spam
- ✓ Évite les mots-clés déclencheurs de spam
- ✓ Pas de ALL CAPS excessifs
- ✓ Pas de points d'exclamation multiples
- ✓ Contenu personnalisé (nom de l'utilisateur)
- ✓ Signature professionnelle

## 🔧 Étapes Supplémentaires pour Production

### 1. Configurer SPF (Sender Policy Framework)

SPF indique quels serveurs sont autorisés à envoyer des emails pour votre domaine.

**Pour Gmail (configuration actuelle) :**
Ajoutez cet enregistrement TXT dans votre DNS :
```
v=spf1 include:_spf.google.com ~all
```

**Pour votre propre serveur SMTP :**
```
v=spf1 ip4:VOTRE_IP_SERVEUR ~all
```

**Vérification :** Utilisez https://mxtoolbox.com/spf.aspx

### 2. Configurer DKIM (DomainKeys Identified Mail)

DKIM signe cryptographiquement vos emails pour prouver qu'ils viennent bien de vous.

#### Étape A : Générer les clés DKIM

```bash
# Générer la clé privée (1024 bits minimum, 2048 recommandé)
openssl genrsa -out dkim_private.pem 2048

# Extraire la clé publique
openssl rsa -in dkim_private.pem -pubout -out dkim_public.pem

# Afficher la clé publique pour le DNS (sans headers)
openssl rsa -in dkim_private.pem -pubout -outform der 2>/dev/null | openssl base64 -A
```

#### Étape B : Configurer le DNS

Ajoutez un enregistrement TXT avec ces informations :
```
Nom : mail._domainkey.votre-domaine.com
Type : TXT
Valeur : v=DKIM1; k=rsa; p=VOTRE_CLE_PUBLIQUE_ICI
```

#### Étape C : Activer DKIM dans config.php

Dans `config.php`, décommentez et configurez :
```php
define('DKIM_DOMAIN', 'votre-domaine.com');
define('DKIM_SELECTOR', 'mail');
define('DKIM_PRIVATE_KEY', '/chemin/absolu/vers/dkim_private.pem');
```

**⚠️ IMPORTANT :** 
- Ne JAMAIS versionner la clé privée dans Git
- Permissions du fichier : `chmod 400 dkim_private.pem`
- Propriétaire : utilisateur du serveur web

### 3. Configurer DMARC (Domain-based Message Authentication)

DMARC indique aux serveurs destinataires quoi faire si SPF ou DKIM échouent.

Ajoutez un enregistrement TXT :
```
Nom : _dmarc.votre-domaine.com
Type : TXT
Valeur : v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@votre-domaine.com; pct=100
```

**Paramètres :**
- `p=none` : Mode monitoring (recommandé au début)
- `p=quarantine` : Envoyer en spam si échec
- `p=reject` : Rejeter si échec (strict, utilisez après tests)
- `rua` : Adresse pour recevoir les rapports

### 4. Utiliser un Service SMTP Professionnel

**Limites de Gmail :** 500 emails/jour

**Alternatives recommandées pour production :**

| Service | Prix | Limite Gratuite | Avantages |
|---------|------|-----------------|-----------|
| **SendGrid** | Gratuit - $20/mois | 100/jour | Excellente délivrabilité, analytics |
| **Mailgun** | $35/mois | 5000/mois trial | API puissante, logs détaillés |
| **AWS SES** | Pay-as-you-go | 62000/mois (si hébergé sur EC2) | Très économique à grande échelle |
| **Brevo (Sendinblue)** | Gratuit - $25/mois | 300/jour | Interface française, facile |
| **Postmark** | $15/mois | / | Spécialisé emails transactionnels |

#### Configuration SendGrid (Exemple)

1. Créez un compte sur https://sendgrid.com
2. Créez une clé API
3. Modifiez `config.php` :

```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'apikey'); // Littéralement "apikey"
define('SMTP_PASSWORD', 'VOTRE_CLE_API_SENDGRID');
define('SMTP_FROM_EMAIL', 'noreply@votre-domaine.com');
```

### 5. Configuration du Nom de Domaine

**Utilisez votre propre domaine dans les emails :**
- ❌ `gojomeh137@gmail.com`
- ✅ `noreply@sigma-alumni.com`

**Avantages :**
- Meilleure réputation
- Cohérence de marque
- Contrôle total sur SPF/DKIM/DMARC

### 6. Bonnes Pratiques Supplémentaires

#### A. Gestion des Bounces (Emails Rejetés)
```php
// À implémenter : Créer une table pour tracker les bounces
// Désactiver automatiquement les emails invalides après 3 bounces
```

#### B. Warm-up de l'Adresse Email
Si vous utilisez une nouvelle adresse :
- Jour 1-3 : 50 emails/jour
- Jour 4-7 : 100 emails/jour
- Jour 8-14 : 500 emails/jour
- Jour 15+ : Volume normal

#### C. Listes de Désabonnement
- ✓ Déjà implémenté : Header `List-Unsubscribe`
- À faire : Page de gestion des préférences dans `settings.php`

#### D. Engagement des Utilisateurs
- Nettoyez régulièrement les emails inactifs
- Segmentez vos listes
- Personnalisez le contenu

#### E. Tests Avant Envoi
Services pour tester vos emails :
- https://www.mail-tester.com (gratuit, 3 tests/jour)
- https://glockapps.com (payant, très détaillé)
- https://www.emailonacid.com (tests de rendu)

### 7. Monitoring et Analytics

#### A. Logs d'Envoi
✓ Déjà implémenté dans la table `email_logs`

#### B. Métriques à Suivre
- Taux de délivrabilité (delivered/sent)
- Taux d'ouverture
- Taux de clics
- Taux de désabonnement
- Taux de spam complaints

#### C. Alertes
Configurez des alertes si :
- Taux de bounces > 5%
- Taux de spam complaints > 0.1%
- Baisse soudaine de délivrabilité

## 🧪 Checklist de Test

Avant de mettre en production :

### Tests Techniques
- [ ] SPF configuré et valide
- [ ] DKIM configuré et valide
- [ ] DMARC configuré
- [ ] Score Mail-Tester > 8/10
- [ ] Test d'envoi vers Gmail (inbox, pas spam)
- [ ] Test d'envoi vers Outlook (inbox, pas spam)
- [ ] Test d'envoi vers Yahoo (inbox, pas spam)

### Tests Fonctionnels
- [ ] Version texte lisible sans HTML
- [ ] Liens fonctionnels
- [ ] Lien de désinscription fonctionne
- [ ] Responsive (mobile, desktop)
- [ ] Headers personnalisés présents
- [ ] Logs d'envoi enregistrés

### Tests de Performance
- [ ] Temps d'envoi acceptable
- [ ] Rate limiting actif
- [ ] Gestion des erreurs
- [ ] Retry en cas d'échec

## 📊 Commandes Utiles

### Vérifier la Configuration DNS
```bash
# Vérifier SPF
nslookup -type=txt votre-domaine.com

# Vérifier DKIM
nslookup -type=txt mail._domainkey.votre-domaine.com

# Vérifier DMARC
nslookup -type=txt _dmarc.votre-domaine.com
```

### Tester l'Envoi
```php
// Dans un fichier test_email.php
require_once 'config.php';
require_once 'send_email.php';

sendEmail(
    'votre-email@gmail.com',
    'Test User',
    'Test de délivrabilité',
    '<p>Ceci est un test</p>',
    'Ceci est un test'
);
```

Ensuite, vérifiez :
1. L'email arrive-t-il ?
2. Dans quel dossier ? (Inbox / Spam)
3. Inspectez les headers (voir le score spam)

## 🆘 Résolution de Problèmes

### Emails arrivent en Spam
1. Vérifier SPF/DKIM/DMARC
2. Tester sur mail-tester.com
3. Vérifier que l'IP n'est pas blacklistée : https://mxtoolbox.com/blacklists.aspx
4. Réduire la fréquence d'envoi
5. Améliorer le contenu (moins de liens, plus de texte)

### Emails non Reçus
1. Vérifier les logs PHP
2. Vérifier les credentials SMTP
3. Tester la connexion SMTP
4. Vérifier les quotas du service SMTP

### Taux de Bounces Élevé
1. Valider les emails avant envoi (FILTER_VALIDATE_EMAIL)
2. Utiliser un service de vérification d'emails
3. Nettoyer la base de données

## 📚 Ressources Supplémentaires

- [RFC 5321 - SMTP](https://tools.ietf.org/html/rfc5321)
- [RFC 6376 - DKIM](https://tools.ietf.org/html/rfc6376)
- [RFC 7489 - DMARC](https://tools.ietf.org/html/rfc7489)
- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)
- [Google Postmaster Tools](https://postmaster.google.com/)
- [Microsoft SNDS](https://sendersupport.olc.protection.outlook.com/snds/)

## 🎯 Prochaines Étapes Recommandées

1. **Court terme** (Cette semaine)
   - Tester les emails actuels sur mail-tester.com
   - Enregistrer un domaine si pas déjà fait
   - Configurer SPF

2. **Moyen terme** (Ce mois)
   - Migrer vers un service SMTP professionnel
   - Configurer DKIM
   - Configurer DMARC
   - Implémenter une page de gestion des préférences

3. **Long terme** (Ce trimestre)
   - Monitorer les métriques de délivrabilité
   - Implémenter la gestion des bounces
   - A/B testing des templates
   - Segmentation des listes

---

**Note :** Les améliorations déjà implémentées dans `send_email.php` sont un excellent début. Pour une délivrabilité optimale en production, suivez les étapes de ce guide progressivement.
