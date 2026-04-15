<?php 
require 'config.php';

// Récupérer la configuration générale
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM general_config");
$stmt->execute();
$result = $stmt->get_result();
$general_config = [];
while ($row = $result->fetch_assoc()) {
    $general_config[$row['setting_key']] = $row['setting_value'];
}
$stmt->close();

// Image de fond avec fallback
$bg_image = (!empty($general_config['bg_creation_compte']) && file_exists($general_config['bg_creation_compte'])) 
    ? $general_config['bg_creation_compte'] 
    : 'img/2023.jpg';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Créer un compte</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url(<?php echo htmlspecialchars($bg_image); ?>);
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            background-size: cover;
        }
        .container {
            background: rgba(155, 152, 152, 0.9);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .back-arrow {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #1e3a8a;
            text-decoration: none;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .back-arrow:hover {
            color: #d4af37;
            transform: scale(1.1);
        }
        .logo {
            width: 100px;
            margin-bottom: 20px;
        }
        h2 {
            color: #1e3a8a;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        input[type="password"] {
            margin-bottom: 0;
        }
        .password-wrapper {
            position: relative;
            margin-bottom: 20px;
            width: 100%;
        }
        .password-wrapper input {
            width: 100%;
            padding: 10px 45px 10px 10px;
            margin-bottom: 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
            z-index: 10;
        }
        .toggle-password:hover {
            color: #1e3a8a;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #1e3a8a;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        button:hover {
            background-color: #163172;
            transform: scale(1.1);
        }
        button:active {
            background-color: #d4af37; /* Color change when pressed */
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="verification.php" class="back-arrow" aria-label="Retour"><i class="fas fa-arrow-left"></i></a>
        <img src="img/image.png" alt="Sigma Logo" class="logo">
        <h2>Créer un compte</h2>
        <form method="POST" action="signup.php">
            <input type="email" name="email" placeholder="Votre adresse email" required>
            <?php if (isset($_SESSION['error']) && $_SESSION['error'] === "Email déjà utilisé.") { ?>
                <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
            <?php } ?>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Mot de passe" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
            </div>
            <?php if (isset($_SESSION['error']) && $_SESSION['error'] !== "Email déjà utilisé.") { ?>
                <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
            <?php } ?>
            <button type="submit">Créer mon compte</button>
        </form>
    </div>
    
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>