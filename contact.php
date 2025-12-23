```php
<?php
// Traitement du formulaire AVANT tout affichage HTML
require 'config.php'; // Connexion DB et session

require 'vendor/autoload.php'; // Include Composer autoloader for HTMLPurifier and PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize HTMLPurifier
$purifier = new HTMLPurifier();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch contact information from general_config
$config_sql = "SELECT setting_key, setting_value FROM general_config";
$config_result = $conn->query($config_sql);
$configs = [];
while ($row = $config_result->fetch_assoc()) {
    $configs[$row['setting_key']] = $row['setting_value'];
}

// Default contact information
$contact_info = [
    'email' => $configs['contact_email'] ?? 'contact@sigma-alumni.org',
    'phone' => $configs['contact_phone'] ?? '+33 1 23 45 67 89',
    'address' => $configs['contact_address'] ?? '<p>123 Rue de l\'Éducation<br>75001 Paris, France</p>',
    'hours' => '<p>Lundi - Vendredi : 9h - 18h</p>',
    'instagram_url' => $configs['instagram_url'] ?? 'https://instagram.com/sigmaofficial',
    'tiktok_url' => $configs['tiktok_url'] ?? 'https://tiktok.com/@sigmaofficial',
    'linkedin_url' => $configs['linkedin_url'] ?? 'https://linkedin.com/company/sigmaalumni',
    'facebook_url' => $configs['facebook_url'] ?? 'https://facebook.com/sigmaalumni',
    'map_iframe' => $configs['map_iframe'] ?? '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.292292615509614!3d48.85837007928746!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2964e34e2d%3A0x8ddca9ee380ef7e0!2sTour%20Eiffel!5e0!3m2!1sfr!2sfr!4v1628683204470!5m2!1sfr!2sfr" allowfullscreen="" loading="lazy"></iframe>'
];

// Handle form submission AVANT l'inclusion du header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: contact.php");
        exit;
    }

    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $message = $purifier->purify($_POST['message']);

    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs correctement.";
        header("Location: contact.php");
        exit;
    }

    // Store in database
    $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    $success = $stmt->execute();
    $stmt->close();

    // Send email notification
    if ($success) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            $mail->setFrom($email, $name);
            $mail->addAddress($contact_info['email'], 'SIGMA Alumni');
            $mail->Subject = 'Nouveau message de contact: ' . $subject;
            $mail->Body = "Nom: $name\nEmail: $email\nSujet: $subject\nMessage:\n$message";
            $mail->send();
            $_SESSION['success'] = "Votre message a été envoyé avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de l'envoi de l'email: " . $mail->ErrorInfo;
        }
    } else {
        $_SESSION['error'] = "Erreur lors de l'enregistrement du message.";
    }

    header("Location: contact.php");
    exit;
}

// Inclure le header APRÈS le traitement du formulaire
include 'header.php';
?>

<style>

        .contact-hero {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            color: var(--white);
            text-align: center;
            padding: 8rem 5% 4rem;
            margin-top: 70px;
        }

        .contact-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .contact-hero p {
            max-width: 700px;
            margin: 0 auto;
        }

        .contact-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 5%;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .contact-info {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .contact-form {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .section-title {
            margin-bottom: 2rem;
        }

        .section-title h2 {
            font-size: 1.8rem;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }

        .section-title p {
            color: var(--accent-gray);
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .info-icon {
            font-size: 1.2rem;
            color: var(--primary-blue);
            margin-right: 1rem;
            margin-top: 0.3rem;
        }

        .info-content h3 {
            color: var(--dark-blue);
            margin-bottom: 0.3rem;
        }

        .info-content p, .info-content a {
            color: var(--accent-gray);
            text-decoration: none;
        }

        .info-content a:hover {
            color: var(--primary-blue);
            text-decoration: underline;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-blue);
            color: var(--primary-blue);
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-blue);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background-color: var(--primary-blue);
            color: var(--white);
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background-color: var(--dark-blue);
        }

        .map-container {
            margin-top: 3rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .map-container iframe {
            width: 100%;
            height: 400px;
            border: none;
        }

        .success-message, .error-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .contact-hero {
                padding: 6rem 5% 3rem;
            }

            .contact-hero h1 {
                font-size: 2rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="contact-hero">
        <h1>Contactez SIGMA Alumni</h1>
        <p>Nous sommes à votre écoute pour toute question ou information concernant l'association</p>
    </section>

    <!-- Contact Content -->
    <div class="contact-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <div class="contact-grid">
            <!-- Informations de contact -->
            <div class="contact-info">
                <div class="section-title">
                    <h2>Nos Coordonnées</h2>
                    <p>N'hésitez pas à nous contacter par l'un des moyens suivants</p>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <a href="mailto:<?php echo htmlspecialchars($contact_info['email']); ?>"><?php echo htmlspecialchars($contact_info['email']); ?></a>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Téléphone</h3>
                        <a href="tel:<?php echo htmlspecialchars($contact_info['phone']); ?>"><?php echo htmlspecialchars($contact_info['phone']); ?></a>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Adresse</h3>
                        <?php echo $purifier->purify($contact_info['address']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Horaires d'ouverture</h3>
                        <?php echo $purifier->purify($contact_info['hours']); ?>
                    </div>
                </div>
                <div class="social-links">
                    <?php if (!empty($contact_info['instagram_url'])): ?>
                        <a href="<?php echo htmlspecialchars($contact_info['instagram_url']); ?>" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($contact_info['tiktok_url'])): ?>
                        <a href="<?php echo htmlspecialchars($contact_info['tiktok_url']); ?>" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($contact_info['linkedin_url'])): ?>
                        <a href="<?php echo htmlspecialchars($contact_info['linkedin_url']); ?>" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($contact_info['facebook_url'])): ?>
                        <a href="<?php echo htmlspecialchars($contact_info['facebook_url']); ?>" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Formulaire de contact -->
            <div class="contact-form">
                <div class="section-title">
                    <h2>Envoyez-nous un message</h2>
                    <p>Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais</p>
                </div>
                <form action="contact.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="name">Nom complet</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Sujet</label>
                        <select id="subject" name="subject" required>
                            <option value="" disabled selected>Sélectionnez un sujet</option>
                            <option value="information">Demande d'information</option>
                            <option value="membership">Adhésion à l'association</option>
                            <option value="event">Question sur un événement</option>
                            <option value="other">Autre demande</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    <button type="submit" name="submit_contact" class="submit-btn">Envoyer le message</button>
                </form>
            </div>
        </div>
        <!-- Carte Google Maps -->
        <div class="map-container">
            <?php echo $purifier->purify($contact_info['map_iframe']); ?>
        </div>
    </div>

<?php include 'footer.php'; ?>
```