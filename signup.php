<?php
require 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic validation instead of sanitization
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email déjà utilisé.";
        header("Location: creation_compte.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $password);

    if ($stmt->execute()) {
        $_SESSION['user_email'] = $email;
        unset($_SESSION['error']); // Effacer l'erreur si l'inscription réussit
        header("Location: creation_profil.php");
    } else {
        $_SESSION['error'] = "Erreur lors de la création du compte.";
        header("Location: creation_compte.php");
    }
    $stmt->close();
}
?>