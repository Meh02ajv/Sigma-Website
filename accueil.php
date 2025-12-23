<?php 
include 'header.php';

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$login_error = '';

// Login processing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $login_error = "Erreur de validation du formulaire. Veuillez réessayer.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $login_error = "Veuillez remplir tous les champs.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $login_error = "Veuillez entrer une adresse email valide.";
        } else {
            $sql = "SELECT id, email, password, full_name, is_admin FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                $login_error = "Erreur de connexion à la base de données.";
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $login_error = "Email ou mot de passe incorrect.";
                }
            }
        }
    }
}

// Member count
$sql = "SELECT COUNT(*) as member_count FROM users";
$result = $conn->query($sql);
$member_count = $result ? $result->fetch_assoc()['member_count'] : 0;
?>

<style>
    /* Styles spécifiques à la page d'accueil */
    .news-section .news-item {
        display: flex;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
    }

    .news-section .news-image {
        width: 120px;
        height: 80px;
        border-radius: 5px;
        overflow: hidden;
        margin-right: 1.5rem;
        flex-shrink: 0;
    }

    .news-section .news-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .news-section .news-content {
        flex: 1;
    }

    .news-section .news-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .news-section .news-item h3 {
        color: var(--primary-blue);
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
    }

    .news-date {
        font-size: 0.9rem;
        color: #777;
        margin-bottom: 0.5rem;
    }

    .hero {
        position: relative;
        height: 100vh;
        min-height: 500px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--white);
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 51, 102, 0.7);
        z-index: 1;
    }

    .hero video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 800px;
        padding: 2rem;
    }

    .hero h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .hero p {
        font-size: 1.1rem;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .hero-buttons {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .btn {
        background: var(--primary-blue);
        color: var(--white);
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        cursor: pointer;
        font-size: 0.9375rem;
        text-align: center;
        font-family: inherit;
        box-shadow: var(--shadow);
    }

    .btn i {
        font-size: 0.875rem;
        transition: var(--transition);
    }

    .btn-primary {
        background: #f97316;
        color: var(--white);
    }

    .btn-primary:hover {
        background: #ea580c;
        box-shadow: var(--shadow-lg);
    }

    .features {
        padding: 5rem 5%;
        max-width: 1400px;
        margin: 0 auto;
    }

    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }

    .section-title h2 {
        font-size: 2.2rem;
        color: var(--dark-blue);
        margin-bottom: 1rem;
    }

    .section-title p {
        color: var(--accent-gray);
        max-width: 700px;
        margin: 0 auto;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
    }

    .feature-card {
        background: var(--white);
        border-radius: 10px;
        padding: 2rem;
        box-shadow: var(--shadow);
        transition: transform 0.3s, box-shadow 0.3s;
        text-align: center;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }

    .feature-icon {
        font-size: 2.5rem;
        color: var(--primary-blue);
        margin-bottom: 1.5rem;
    }

    .feature-card h3 {
        font-size: 1.4rem;
        margin-bottom: 1rem;
        color: var(--dark-blue);
    }

    .news-events {
        background-color: var(--light-blue);
        padding: 5rem 5%;
    }

    .news-container {
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .news-section, .events-section {
        background: var(--white);
        border-radius: 10px;
        padding: 2rem;
        box-shadow: var(--shadow);
    }

    .news-section h2, .events-section h2 {
        color: var(--dark-blue);
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--light-blue);
    }

    .news-item {
        display: flex;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
    }

    .news-image {
        width: 120px;
        height: 80px;
        border-radius: 5px;
        overflow: hidden;
        margin-right: 1.5rem;
        flex-shrink: 0;
    }

    .news-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .news-content {
        flex: 1;
    }

    .news-item:last-child, .event-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .news-item h3 {
        color: var(--primary-blue);
        margin-bottom: 0.5rem;
    }

    .news-date {
        font-size: 0.9rem;
        color: #777;
        margin-bottom: 0.5rem;
    }

    .event-item {
        margin-bottom: 1.5rem;
    }

    .event-date {
        font-weight: bold;
        color: var(--primary-blue);
    }

    .counter-section {
        background-color: var(--dark-blue);
        color: var(--white);
        text-align: center;
        padding: 3rem 5%;
    }

    .counter-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .counter-section h2 {
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
    }

    .counter {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 1rem 0;
    }

    /* Events Section Styles */
    .events-section .event-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid var(--primary-blue);
    }

    .events-section .event-date {
        color: var(--primary-blue);
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
    }

    .events-section .event-item h3 {
        color: var(--dark-blue);
        margin-bottom: 0.8rem;
        font-size: 1.1rem;
    }

    .events-section .event-location {
        color: var(--accent-gray);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .reminder-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    .btn-reminder {
        background: var(--primary-blue);
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-reminder:hover:not(:disabled) {
        background: var(--dark-blue);
        transform: translateY(-2px);
    }

    .btn-reminder:disabled {
        background: #28a745;
        cursor: not-allowed;
        transform: none;
    }

    .btn-reminder-added {
        background: #28a745 !important;
    }

    .btn-events-more {
        display: inline-block;
        background: transparent;
        color: var(--primary-blue);
        border: 2px solid var(--primary-blue);
        padding: 0.7rem 1.5rem;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        text-align: center;
        margin-top: 1rem;
    }

    .btn-events-more:hover {
        background: var(--primary-blue);
        color: white;
    }

    /* Alert styles */
    .alert {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 5px;
        color: white;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        max-width: 90%;
    }

    .alert-success {
        background: #28a745;
    }

    .alert-error {
        background: #dc3545;
    }

    .login-error {
        color: #dc3545;
        font-size: 0.9rem;
        margin-top: 1rem;
        padding: 0.75rem;
        background-color: rgba(220, 38, 38, 0.1);
        border-left: 4px solid #dc3545;
        border-radius: 4px;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Responsive Design pour l'accueil */
    @media (max-width: 1024px) {
        .hero h1 {
            font-size: 2.2rem;
        }
        
        .hero p {
            font-size: 1rem;
        }
        
        .features-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .news-container {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .hero {
            margin-top: 60px;
            height: auto;
            min-height: 400px;
            padding: 2rem 0;
        }

        .hero::before {
            background: rgba(0, 51, 102, 0.8);
        }

        .hero video {
            object-fit: cover;
            object-position: center;
        }

        .hero-content {
            padding: 1.5rem;
        }

        .hero h1 {
            font-size: 1.8rem;
            margin-bottom: 0.75rem;
        }

        .hero p {
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .hero-buttons {
            gap: 0.75rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            font-size: 0.95rem;
        }

        .features {
            padding: 3rem 5%;
        }

        .features-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .feature-card {
            padding: 1.5rem;
        }

        .section-title h2 {
            font-size: 1.8rem;
        }

        .section-title p {
            font-size: 0.95rem;
        }

        .news-events {
            padding: 3rem 5%;
        }

        .news-container {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .news-section, .events-section {
            padding: 1.5rem;
        }

        .news-item {
            flex-direction: column;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
        }

        .news-image {
            width: 100%;
            height: 150px;
            margin-right: 0;
            margin-bottom: 1rem;
        }

        .counter {
            font-size: 2rem;
        }

        .counter-section {
            padding: 2.5rem 5%;
        }

        .counter-section h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .counter-section p {
            font-size: 0.95rem;
        }
    }

    @media (max-width: 480px) {
        .hero {
            margin-top: 50px;
            min-height: 350px;
        }

        .hero video {
            object-fit: cover;
            object-position: center;
            min-height: 350px;
        }

        .hero-content {
            padding: 1rem;
        }

        .hero h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .hero p {
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }

        .features, .news-events, .counter-section {
            padding: 2rem 4%;
        }

        .features-grid {
            gap: 1.25rem;
        }

        .feature-card {
            padding: 1.25rem;
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.1rem;
        }

        .section-title h2 {
            font-size: 1.5rem;
        }

        .news-section h2, .events-section h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
        }

        .news-item {
            padding-bottom: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .news-image {
            height: 120px;
            margin-bottom: 0.75rem;
        }

        .news-item h3 {
            font-size: 1rem;
        }

        .news-item p, .news-date {
            font-size: 0.85rem;
        }

        .event-item {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .events-section .event-date {
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }

        .events-section .event-item h3 {
            font-size: 1rem;
            margin-bottom: 0.6rem;
        }

        .events-section .event-location {
            font-size: 0.8rem;
            margin-bottom: 0.75rem;
        }

        .btn-reminder {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
            gap: 0.3rem;
        }

        .btn-events-more {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }

        .counter {
            font-size: 1.8rem;
        }

        .counter-section h2 {
            font-size: 1.3rem;
        }

        .alert {
            top: 10px;
            right: 10px;
            left: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }
    }

    /* Touch-friendly adjustments */
    @media (hover: none) and (pointer: coarse) {
        .btn, .btn-reminder, .feature-card {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        input, button {
            min-height: 44px;
        }
    }
</style>

<section class="hero">
    <?php
    // Récupérer la vidéo de fond depuis la base de données
    require_once 'config.php';
    $hero_video = 'img/hero-video.mp4'; // Valeur par défaut
    
    $stmt = $conn->prepare("SELECT setting_value FROM general_config WHERE setting_key = 'hero_video'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $hero_video = $row['setting_value'];
        }
        $stmt->close();
    }
    
    // Déterminer le type MIME correct
    $ext = strtolower(pathinfo($hero_video, PATHINFO_EXTENSION));
    $mime_types = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime'
    ];
    $video_type = $mime_types[$ext] ?? 'video/mp4';
    ?>
    <video id="heroVideo" aria-hidden="true" autoplay muted loop playsinline preload="auto">
        <source src="<?php echo htmlspecialchars($hero_video); ?>" type="<?php echo $video_type; ?>">
        Votre navigateur ne supporte pas les vidéos HTML5.
    </video>
    
    <script>
        // Garantir la lecture automatique et la boucle de la vidéo
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('heroVideo');
            if (video) {
                // Forcer la lecture automatique
                video.play().catch(function(error) {
                    console.log('Autoplay bloqué:', error);
                });
                
                // S'assurer que la vidéo recommence après la fin
                video.addEventListener('ended', function() {
                    video.currentTime = 0;
                    video.play();
                });
                
                // Relancer si la vidéo se met en pause
                video.addEventListener('pause', function() {
                    if (!video.ended) {
                        video.play();
                    }
                });
            }
        });
    </script>
    
    <div class="hero-content">
        <h1>Bienvenue à SIGMA</h1>
        <p>Reconnectez-vous avec vos anciens camarades, découvrez les événements à venir et contribuez à notre communauté dynamique.</p>
        <div class="hero-buttons">
            <?php if (!isset($_SESSION['full_name'])): ?>
                <a href="verification.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Rejoindre la communauté
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="features" id="features">
    <div class="section-title">
        <h2>Nos Services</h2>
        <p>Découvrez ce que notre plateforme offre à la communauté SIGMA Alumni</p>
    </div>
    <div class="features-grid">
        <a href="connexion.php" class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-address-book" aria-hidden="true"></i>
            </div>
            <h3>Annuaire</h3>
            <p>Accédez au répertoire complet des anciens élèves et restez en contact avec votre réseau.</p>
        </a>
        <a href="connexion.php" class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-vote-yea" aria-hidden="true"></i>
            </div>
            <h3>Vote</h3>
            <p>Participez aux élections du bureau de l'association.</p>
        </a>
        <a href="reglement.php" class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-clipboard-list" aria-hidden="true"></i>
            </div>
            <h3>Règlement</h3>
            <p>Consultez les règles et valeurs qui guident notre association.</p>
        </a>
        <a href="objectifs.php" class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-bullseye" aria-hidden="true"></i>
            </div>
            <h3>Objectifs</h3>
            <p>Découvrez les missions et objectifs de notre communauté alumni.</p>
        </a>
    </div>
</section>

<section class="news-events">
    <div class="news-container">
        <div class="news-section">
            <h2>Actualités</h2>
            <?php
            // Fetch active news
            $news_sql = "SELECT * FROM news WHERE is_active = 1 ORDER BY order_index ASC, created_at DESC LIMIT 3";
            $news_result = $conn->query($news_sql);
            
            if ($news_result && $news_result->num_rows > 0) {
                while($news_item = $news_result->fetch_assoc()) {
                    echo '<div class="news-item">';
                    if (!empty($news_item['image_path'])) {
                        echo '<div class="news-image">';
                        echo '<img src="' . htmlspecialchars($news_item['image_path']) . '" alt="' . htmlspecialchars($news_item['title']) . '">';
                        echo '</div>';
                    }
                    echo '<div class="news-content">';
                    echo '<h3>' . htmlspecialchars($news_item['title']) . '</h3>';
                    echo '<div class="news-date">' . date('d/m/Y', strtotime($news_item['created_at'])) . '</div>';
                    echo '<p>' . htmlspecialchars($news_item['excerpt']) . '</p>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>Aucune actualité pour le moment.</p>';
            }
            ?>
        </div>
        <div class="events-section">
            <h2>Événements à venir</h2>
            <?php
            // Fetch upcoming events (next 3 events)
            $events_sql = "SELECT * FROM events WHERE event_date > NOW() ORDER BY event_date ASC LIMIT 3";
            $events_result = $conn->query($events_sql);
            
            if ($events_result && $events_result->num_rows > 0) {
                while($event = $events_result->fetch_assoc()) {
                    echo '<div class="event-item">';
                    echo '<div class="event-date">';
                    echo '<i class="fas fa-calendar-alt" aria-hidden="true"></i> ';
                    echo date('d/m/Y à H:i', strtotime($event['event_date']));
                    echo '</div>';
                    echo '<h3>' . htmlspecialchars($event['title']) . '</h3>';
                    echo '<p>' . htmlspecialchars($event['description']) . '</p>';
                    echo '<div class="event-location">';
                    echo '<i class="fas fa-map-marker-alt" aria-hidden="true"></i> ';
                    echo htmlspecialchars($event['location']);
                    echo '</div>';
                    
                    // Add reminder button
                    echo '<div class="reminder-section">';
                    if (isset($_SESSION['user_id'])) {
                        // Check if user already set a reminder
                        $reminder_check_sql = "SELECT id FROM event_reminders WHERE user_id = ? AND event_id = ?";
                        $stmt = $conn->prepare($reminder_check_sql);
                        
                        if ($stmt) {
                            $stmt->bind_param("ii", $_SESSION['user_id'], $event['id']);
                            $stmt->execute();
                            $reminder_exists = $stmt->get_result()->num_rows > 0;
                            $stmt->close();
                            
                            if ($reminder_exists) {
                                echo '<button class="btn-reminder btn-reminder-added" data-event-id="' . $event['id'] . '" disabled>';
                                echo '<i class="fas fa-bell"></i> Rappel ajouté';
                                echo '</button>';
                            } else {
                                echo '<button class="btn-reminder" data-event-id="' . $event['id'] . '">';
                                echo '<i class="far fa-bell"></i> Ajouter un rappel';
                                echo '</button>';
                            }
                        }
                    } else {
                        echo '<button class="btn-reminder" onclick="showLoginAlert()">';
                        echo '<i class="far fa-bell"></i> Ajouter un rappel';
                        echo '</button>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>Aucun événement à venir pour le moment.</p>';
            }
            ?>
            <div class="events-more">
                <a href="evenements.php" class="btn-events-more">Voir tous les événements</a>
            </div>
        </div>
    </div>
</section>

<section class="counter-section">
    <div class="counter-container">
        <h2>Rejoignez notre communauté grandissante</h2>
        <div class="counter" id="member-counter"><?php echo $member_count; ?></div>
        <p>Anciens élèves déjà inscrits</p>
    </div>
</section>

<script>
    function animateCounter(target, start, end, duration) {
        let startTime = null;
        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            target.textContent = value.toLocaleString('fr-FR');
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    const counterSection = document.querySelector('.counter-section');
    let counterAnimated = false;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !counterAnimated) {
                const counter = document.getElementById('member-counter');
                const targetCount = parseInt(counter.textContent.replace(/\s/g, ''));
                animateCounter(counter, 0, targetCount, 2000);
                counterAnimated = true;
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    observer.observe(counterSection);
    
    // Function to show login alert
    function showLoginAlert() {
        showAlert('Veuillez vous connecter pour ajouter un rappel', 'error');
    }

    // Function to show alerts
    function showAlert(message, type = 'success') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }

    // Handle reminder buttons
    document.addEventListener('DOMContentLoaded', function() {
        const reminderButtons = document.querySelectorAll('.btn-reminder:not(:disabled)');
        
        reminderButtons.forEach(button => {
            button.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');
                
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajout...';
                this.disabled = true;
                
                // Send AJAX request
                fetch('add_reminder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'event_id=' + encodeURIComponent(eventId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.innerHTML = '<i class="fas fa-bell"></i> Rappel ajouté';
                        this.classList.add('btn-reminder-added');
                        this.disabled = true;
                        showAlert('Rappel ajouté avec succès!');
                    } else {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        showAlert(data.message || 'Erreur lors de l\'ajout du rappel', 'error');
                    }
                })
                .catch(error => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    showAlert('Erreur de connexion', 'error');
                    console.error('Error:', error);
                });
            });
        });
    });
</script>

<?php include 'footer.php'; ?>