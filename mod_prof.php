<?php
require 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}
$user_email = $_SESSION['user_email'];
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Fetch user data
$stmt = $conn->prepare("SELECT full_name, birth_date, bac_year, studies, profile_picture FROM users WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$default_image = 'img/profile_pic.jpeg';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Profil - Sigma Yearbook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --border-radius: 8px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-color);
            line-height: 1.6;
            padding: 20px;
        }

        .profile-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            position: relative;
        }

        .profile-header {
            background: var(--primary-color);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .profile-header h1 {
            font-weight: 600;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .profile-header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .back-arrow {
            position: absolute;
            top: 25px;
            left: 25px;
            color: white;
            font-size: 1.2rem;
            text-decoration: none;
            transition: var(--transition);
            z-index: 10;
        }

        .back-arrow:hover {
            color: var(--secondary-color);
            transform: translateX(-3px);
        }

        .profile-content {
            padding: 30px;
        }

        .profile-pic-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            margin-bottom: 15px;
        }

        .profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-pic-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--secondary-color);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .profile-pic-edit:hover {
            background: #2980b9;
            transform: scale(1.1);
        }

        .photo-upload-text {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
        }

        .form-control[readonly] {
            background-color: #eee;
            cursor: not-allowed;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }

        .input-icon input {
            padding-left: 40px;
        }

        .toggle-email {
            font-size: 0.8rem;
            color: var(--secondary-color);
            cursor: pointer;
            margin-left: 10px;
            transition: var(--transition);
        }

        .toggle-email:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: var(--border-radius);
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            width: 100%;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }

        .btn-danger {
            background: var(--accent-color);
            color: white;
            margin-top: 15px;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .message {
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
        }

        .success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }

        .error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .file-input {
            display: none;
        }

        .password-help {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-top: 5px;
        }

        @media (max-width: 576px) {
            .profile-header {
                padding: 20px 15px;
            }
            
            .profile-header h1 {
                font-size: 1.5rem;
            }
            
            .profile-content {
                padding: 20px;
            }
            
            .profile-pic {
                width: 100px;
                height: 100px;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="settings.php" class="back-arrow" aria-label="Retour aux paramètres">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="profile-header">
            <h1>Modifier le profil</h1>
            <p>Mettez à jour vos informations personnelles</p>
        </div>
        
        <div class="profile-content">
            <?php if ($success || $error): ?>
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($success ?: $error); ?>
                </div>
            <?php endif; ?>
            
            <form id="profileForm" method="POST" action="update_profile.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="profile-pic-container">
                    <div class="profile-pic" id="profilePic">
                        <img src="<?php echo $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : $default_image; ?>" alt="Photo de profil" id="profileImage">
                        <div class="profile-pic-edit" onclick="document.getElementById('profile_picture').click()">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <span class="photo-upload-text">Cliquez sur l'icône pour changer de photo</span>
                    <input type="file" id="profile_picture" name="profile_picture" class="file-input" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(event)">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Nom complet</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Votre nom complet" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Adresse e-mail <span class="toggle-email" onclick="toggleEmail()">Afficher</span></label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly required>
                    </div>
                </div>
                
                <?php if (isset($_GET['reset'])): ?>
                    <div class="form-group">
                        <label for="password">Nouveau mot de passe</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Nouveau mot de passe" minlength="8">
                        </div>
                        <p class="password-help">Minimum 8 caractères</p>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="birth_date">Date de naissance</label>
                    <div class="input-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" id="birth_date" name="birth_date" class="form-control" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bac_year">Année du bac</label>
                    <div class="input-icon">
                        <i class="fas fa-graduation-cap"></i>
                        <input type="number" id="bac_year" name="bac_year" class="form-control" placeholder="Année d'obtention" value="<?php echo htmlspecialchars($user['bac_year'] ?? ''); ?>" min="1900" max="<?php echo date('Y'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="studies">Études actuelles</label>
                    <div class="input-icon">
                        <i class="fas fa-book"></i>
                        <input type="text" id="studies" name="studies" class="form-control" placeholder="Votre parcours académique" value="<?php echo htmlspecialchars($user['studies'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
            </form>
            
            <?php if ($user['profile_picture']): ?>
                <form id="deletePhotoForm" method="POST" action="update_profile.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="delete_picture" value="1">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')">
                        <i class="fas fa-trash-alt"></i> Supprimer la photo
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const profileImage = document.getElementById('profileImage');
        const imageUpload = document.getElementById('profile_picture');
        const submitBtn = document.getElementById('submitBtn');
        const defaultImage = '<?php echo $default_image; ?>';
        let hasImage = <?php echo $user['profile_picture'] ? 'true' : 'false'; ?>;

        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                // Vérification du type de fichier
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Veuillez sélectionner une image valide (JPEG, PNG, GIF ou WebP).');
                    imageUpload.value = '';
                    return;
                }
                
                // Vérification de la taille du fichier (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('La taille de l\'image ne doit pas dépasser 5MB. Taille actuelle: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
                    imageUpload.value = '';
                    return;
                }
                
                console.log('Fichier sélectionné:', file.name, 'Type:', file.type, 'Taille:', (file.size / 1024).toFixed(2) + 'KB');
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                    hasImage = true;
                    
                    // Ajouter un indicateur visuel
                    const photoText = document.querySelector('.photo-upload-text');
                    if (photoText) {
                        photoText.textContent = '✓ Nouvelle photo sélectionnée: ' + file.name;
                        photoText.style.color = '#27ae60';
                        photoText.style.fontWeight = 'bold';
                    }
                };
                reader.onerror = function() {
                    alert('Erreur lors de la lecture du fichier.');
                };
                reader.readAsDataURL(file);
            }
        }

        function toggleEmail() {
            const emailInput = document.getElementById('email');
            const toggleSpan = document.querySelector('.toggle-email');
            if (emailInput.type === 'email') {
                emailInput.type = 'text';
                toggleSpan.textContent = 'Masquer';
            } else {
                emailInput.type = 'email';
                toggleSpan.textContent = 'Afficher';
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const birthDate = document.getElementById('birth_date').value;
            const bacYear = document.getElementById('bac_year').value;
            const studies = document.getElementById('studies').value.trim();
            const currentYear = new Date().getFullYear();

            // Validation des champs
            if (!fullName || !birthDate || !bacYear || !studies) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }

            if (bacYear && (bacYear < 1900 || bacYear > currentYear)) {
                e.preventDefault();
                alert(`L'année du bac doit être comprise entre 1900 et ${currentYear}.`);
                return;
            }

            // Vérification de la date de naissance
            const birthDateObj = new Date(birthDate);
            const minDate = new Date();
            minDate.setFullYear(minDate.getFullYear() - 100); // 100 ans maximum
            
            if (birthDateObj > new Date() || birthDateObj < minDate) {
                e.preventDefault();
                alert('Veuillez entrer une date de naissance valide.');
                return;
            }

            // Changement de l'état du bouton pendant l'envoi
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
        });

        // Animation pour les champs invalides
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('invalid', () => {
                input.style.borderColor = 'var(--error-color)';
                setTimeout(() => {
                    input.style.borderColor = '#ddd';
                }, 2000);
            });
        });
    </script>
</body>
</html>