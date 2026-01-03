<?php include 'header.php'; ?>

<?php
// Fetch upcoming events (events with date in the future)
$stmt = $conn->prepare("SELECT * FROM events WHERE event_date > NOW() ORDER BY event_date ASC");
$stmt->execute();
$upcoming_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch past events (events with date in the past)
$stmt = $conn->prepare("SELECT * FROM events WHERE event_date <= NOW() ORDER BY event_date DESC");
$stmt->execute();
$past_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<style>
    /* Styles spécifiques à la page événements */
    .events-hero {
        background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
        color: var(--white);
        text-align: center;
        padding: 8rem 5% 4rem;
    }

    .events-hero h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .events-hero p {
        max-width: 700px;
        margin: 0 auto 2rem;
    }

    /* Main Events Content */
    .events-container {
        max-width: 1200px;
        margin: 3rem auto;
        padding: 0 5%;
    }

    .events-tabs {
        display: flex;
        margin-bottom: 2rem;
        border-bottom: 1px solid #ddd;
    }

    .tab-btn {
        padding: 0.8rem 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        color: var(--accent-gray);
        position: relative;
    }

    .tab-btn.active {
        color: var(--primary-blue);
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--primary-blue);
    }

    .events-content {
        display: none;
    }

    .events-content.active {
        display: block;
    }

    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
    }

    .event-card {
        background: var(--white);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: transform 0.3s;
    }

    .event-card:hover {
        transform: translateY(-5px);
    }

    .event-image {
        height: 200px;
        overflow: hidden;
    }

    .event-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }

    .event-card:hover .event-image img {
        transform: scale(1.05);
    }

    .event-details {
        padding: 1.5rem;
    }

    .event-date {
        display: inline-block;
        background: var(--light-blue);
        color: var(--primary-blue);
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.8rem;
    }

    .event-title {
        font-size: 1.3rem;
        margin-bottom: 0.8rem;
        color: var(--dark-blue);
    }

    .event-description {
        margin-bottom: 1.2rem;
        color: var(--accent-gray);
    }

    .event-meta {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .event-meta i {
        margin-right: 0.5rem;
        color: var(--primary-blue);
    }

    .no-events {
        text-align: center;
        padding: 2rem;
        color: var(--accent-gray);
    }

    /* Responsive Design pour la page événements */
    @media (max-width: 768px) {
        .events-hero {
            padding: 6rem 5% 3rem;
        }

        .events-hero h1 {
            font-size: 2rem;
        }

        .events-tabs {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 5px;
        }

        .events-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .events-hero h1 {
            font-size: 1.8rem;
        }
        
        .event-card {
            margin-bottom: 1.5rem;
        }
        
        .event-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
</style>

<!-- Hero Section -->
<section class="events-hero">
    <h1>Événements SIGMA Alumni</h1>
    <p>Découvrez tous les événements à venir et revivez les moments forts de notre communauté.</p>
</section>

<!-- Main Events Content -->
<div class="events-container">
    <!-- Onglets de navigation -->
    <div class="events-tabs">
        <button class="tab-btn active" data-tab="upcoming">À venir (<?php echo count($upcoming_events); ?>)</button>
        <button class="tab-btn" data-tab="past">Passés (<?php echo count($past_events); ?>)</button>
    </div>

    <!-- Contenu des onglets -->
    <div class="events-content active" id="upcoming-events-content">
        <div class="events-grid">
            <?php if (empty($upcoming_events)): ?>
                <div class="no-events">
                    <p>Aucun événement à venir pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming_events as $event): ?>
                    <div class="event-card">
                        <div class="event-image">
                            <?php if ($event['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo $event['title']; ?>">
                            <?php else: ?>
                                <img src="images/event-default.jpg" alt="Événement SIGMA Alumni">
                            <?php endif; ?>
                        </div>
                        <div class="event-details">
                            <span class="event-date"><?php echo date('d M Y', strtotime($event['event_date'])); ?></span>
                            <h3 class="event-title"><?php echo $event['title']; ?></h3>
                            <p class="event-description"><?php echo $event['description']; ?></p>
                            <div class="event-meta">
                                <div><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($event['event_date'])); ?></div>
                                <div><i class="fas fa-map-marker-alt"></i> <?php echo $event['location']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="events-content" id="past-events-content">
        <div class="events-grid">
            <?php if (empty($past_events)): ?>
                <div class="no-events">
                    <p>Aucun événement passé pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($past_events as $event): ?>
                    <div class="event-card">
                        <div class="event-image">
                            <?php if ($event['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            <?php else: ?>
                                <img src="images/event-default.jpg" alt="Événement SIGMA Alumni">
                            <?php endif; ?>
                        </div>
                        <div class="event-details">
                            <span class="event-date"><?php echo date('d M Y', strtotime($event['event_date'])); ?></span>
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="event-description"><?php echo nl2br($event['description']); ?></p>
                            <div class="event-meta">
                                <div><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($event['event_date'])); ?></div>
                                <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Gestion des onglets
    const tabBtns = document.querySelectorAll('.tab-btn');
    const eventContents = document.querySelectorAll('.events-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Retirer la classe active de tous les boutons et contenus
            tabBtns.forEach(btn => btn.classList.remove('active'));
            eventContents.forEach(content => content.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            btn.classList.add('active');
            
            // Afficher le contenu correspondant
            const tabId = btn.getAttribute('data-tab');
            document.getElementById(`${tabId}-events-content`).classList.add('active');
        });
    });
</script>

<?php include 'footer.php'; ?>