<?php
require 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}
$user_email = $_SESSION['user_email'];

// Capture success/error messages from session
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Check if user already has a complete profile
$stmt = $conn->prepare("SELECT full_name, birth_date, bac_year, studies FROM users WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user && !empty($user['full_name']) && !empty($user['birth_date']) && !empty($user['bac_year']) && !empty($user['studies'])) {
    // User already has a complete profile, redirect to yearbook
    $_SESSION['success'] = "Votre profil est déjà complet.";
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer mon profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('img/2023.jpg');
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            background-size: cover;
            margin: 0;
            background-color: #f4f4f9;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 90%;
            max-width: 400px;
            position: relative;
        }
        .back-arrow {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #2c3e50;
            text-decoration: none;
        }
        .logo {
            width: 100px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .form-group {
            margin: 15px 0;
            text-align: left;
        }
        .form-group label {
            display: flex;
            align-items: center;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group label i {
            margin-right: 10px;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            font-family: inherit;
            min-height: 80px;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #d4af37;
        }
        .form-group input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .photo-btn {
            margin: 15px 0;
        }
        .form-group input[type="file"] {
            display: none;
        }
        .photo-btn label {
            padding: 12px 20px;
            background-color: #ecf0f1;
            color: #2c3e50;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
        }
        .photo-btn label:hover {
            background-color: #dcdde1;
        }
        #preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto;
            display: none;
            border: 2px solid #ddd;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .submit-btn:hover {
            background-color: #34495e;
            transform: scale(1.1);
        }
        .submit-btn:active {
            background-color: #d4af37; /* Color change when pressed */
        }
        .submit-btn:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }
        #message {
            margin-top: 10px;
            font-size: 14px;
        }
        .success {
            color: #2ecc71;
        }
        .error {
            color: #e74c3c;
        }
        @media (max-width: 480px) {
            .container {
                padding: 15px;
                max-width: 300px;
            }
            h1 {
                font-size: 20px;
            }
            .form-group label {
                font-size: 14px;
            }
            .form-group input {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="img/image.png" alt="Sigma Logo" class="logo">
        <h1>Créer mon profil</h1>
        <form id="profileForm" method="POST" action="create_profile.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <label for="full_name"><i class="fas fa-user"></i> Nom complet</label>
                <input type="text" id="full_name" name="full_name" placeholder="Nom complet" required>
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Adresse e-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" readonly required>
            </div>
            <div class="form-group">
                <label for="birth_date"><i class="fas fa-calendar"></i> Date de naissance</label>
                <input type="date" id="birth_date" name="birth_date" placeholder="Date de naissance" required>
            </div>
            <div class="form-group">
                <label for="bac_year"><i class="fas fa-graduation-cap"></i> Année du BAC</label>
                <input type="number" id="bac_year" name="bac_year" placeholder="Année du BAC" required>
            </div>
            <div class="form-group">
                <label for="studies"><i class="fas fa-book"></i> Études</label>
                <input type="text" id="studies" name="studies" placeholder="Études" required>
            </div>
            <div class="form-group">
                <label for="profession"><i class="fas fa-briefcase"></i> Profession</label>
                <input type="text" id="profession" name="profession" placeholder="Profession actuelle">
            </div>
            <div class="form-group">
                <label for="company"><i class="fas fa-building"></i> Entreprise</label>
                <input type="text" id="company" name="company" placeholder="Nom de l'entreprise">
            </div>
            <div class="form-group">
                <label for="city"><i class="fas fa-map-marker-alt"></i> Ville</label>
                <input type="text" id="city" name="city" placeholder="Ville de résidence">
            </div>
            <div class="form-group">
                <label for="country"><i class="fas fa-globe"></i> Pays</label>
                <input type="text" id="country" name="country" placeholder="Pays de résidence">
            </div>
            <div class="form-group">
                <label for="interests"><i class="fas fa-heart"></i> Centres d'intérêt</label>
                <textarea id="interests" name="interests" placeholder="Vos centres d'intérêt (sport, musique, voyages...)" rows="3"></textarea>
            </div>
            <div class="form-group photo-btn">
                <label for="profile_picture"><i class="fas fa-camera"></i> Ajouter une photo</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" onchange="previewImage(event)">
                <img id="preview" src="img/logo.png" alt="Photo de profil" style="display: none;">
            </div>
            <button type="submit" class="submit-btn" id="submitBtn">Créer mon profil</button>
        </form>
        <p id="message" class="<?php echo $success ? 'success' : ($error ? 'error' : ''); ?>">
            <?php echo htmlspecialchars($success ?: $error); ?>
        </p>
    </div>

    <script>
        function previewImage(event) {
            const preview = document.getElementById('preview');
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = 'img/profile_pic.jpeg';
                preview.style.display = 'none';
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const message = document.getElementById('message');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enregistrement...';

            // Client-side validation
            const fullName = document.getElementById('full_name').value;
            const birthDate = document.getElementById('birth_date').value;
            const bacYear = document.getElementById('bac_year').value;
            const studies = document.getElementById('studies').value;
            if (!fullName || !birthDate || !bacYear || !studies) {
                e.preventDefault();
                message.textContent = 'Veuillez remplir tous les champs obligatoires.';
                message.className = 'error';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Créer mon profil';
            }
        });
    </script>
</body>
</html>