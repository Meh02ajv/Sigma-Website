<?php
require 'config.php';

// Fonction de sanitisation pour nettoyer les données
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de Vérification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url(img/2023.jpg);
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            background-size: cover;
        }
        .container {
            background: rgba(170, 167, 167, 0.9);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
            position: relative;
            /* Use standard filter property for any potential effects */
            filter: none; /* Explicitly set to avoid any inherited or external -ms-filter */
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
            margin-bottom: 20px;
            border: 1px solid #874141;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            font-size: medium;
            width: 80%;
            padding: 10px 20px;
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
            background-color: #d4af37;
        }
        .error {
            color: #dc2626;
            font-size: 14px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="accueil.php" class="back-arrow" aria-label="Retour"><i class="fas fa-arrow-left"></i></a>
        <img src="img/image.png" alt="Sigma Logo" class="logo">
        <h2>Code de vérification</h2>
        <form id="verificationForm" method="POST" action="verification.php">
            <input type="text" id="verificationCode" name="verificationCode" placeholder="Entrer le code" required>
            <button type="submit">Continuer</button>
        </form>
        <p id="errorMessage" class="error">Ce code ne correspond pas à celui fourni par Sigma.</p>
    </div>

    <script>
        document.getElementById('verificationForm').addEventListener('submit', function(event) {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.style.display = 'none';
            // Allow form submission to proceed to server-side handling
        });
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verificationCode'])) {
        $submitted_code = sanitize($_POST['verificationCode']);
        $stmt = $conn->prepare("SELECT code FROM verification_codes WHERE id = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $correct_code = $result->fetch_assoc()['code'];
            $stmt->close();

            if ($submitted_code === $correct_code) {
                header("Location: creation_compte.php");
                exit;
            } else {
                echo "<script>document.getElementById('errorMessage').style.display = 'block';</script>";
            }
        } else {
            echo "<script>document.getElementById('errorMessage').textContent = 'Erreur: Code de vérification non trouvé.'; document.getElementById('errorMessage').style.display = 'block';</script>";
        }
    }
    ?>
</body>
</html>