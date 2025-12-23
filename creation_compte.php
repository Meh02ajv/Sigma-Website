<?php require 'config.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Créer un compte</title>
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
            margin-bottom: 20px;
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
            <input type="password" name="password" placeholder="Mot de passe" required>
            <?php if (isset($_SESSION['error']) && $_SESSION['error'] !== "Email déjà utilisé.") { ?>
                <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
            <?php } ?>
            <button type="submit">Créer mon compte</button>
        </form>
    </div>
</body>
</html>