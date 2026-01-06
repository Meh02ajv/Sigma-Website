<?php
include 'header.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);

// Fetch current bureau members
$current_bureau = [];
$bureau_history = [];
try {
    $stmt = $conn->prepare("SELECT bm.*, u.full_name, u.profile_picture
                            FROM bureau_members bm
                            JOIN users u ON bm.user_id = u.id
                            WHERE bm.is_current = 1
                            ORDER BY
                                CASE
                                    WHEN bm.position = 'Président' THEN 1
                                    WHEN bm.position = 'Vice-Président' THEN 2
                                    WHEN bm.position = 'Secrétaire' THEN 3
                                    WHEN bm.position = 'Trésorier' THEN 4
                                    ELSE 5
                                END");
    $stmt->execute();
    $current_bureau = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch bureau history
    $stmt = $conn->prepare("SELECT * FROM bureau_history ORDER BY year_range DESC");
    $stmt->execute();
    $bureau_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    // Handle database errors gracefully
    error_log("Database error: " . $e->getMessage());
}
?>

<style>
        /* Hero Section */
        .bureau-hero {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            color: var(--white);
            text-align: center;
            padding: 8rem 5% 4rem;
            margin-top: 70px;
        }
        .bureau-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .bureau-hero p {
            max-width: 700px;
            margin: 0 auto;
        }
        /* Bureau Content */
        .bureau-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 5%;
        }
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        .section-title h2 {
            font-size: 2rem;
            color: var(--dark-blue);
            margin-bottom: 1rem;
        }
        .section-title p {
            color: var(--accent-gray);
        }
        /* Current Bureau */
        .current-bureau {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
        }
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .member-card {
            text-align: center;
            transition: transform 0.3s;
        }
        .member-card:hover {
            transform: translateY(-5px);
        }
        .member-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 1rem;
            border: 3px solid var(--light-blue);
        }
        .member-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .member-info h3 {
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        .member-position {
            color: var(--primary-blue);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .member-promo {
            font-size: 0.9rem;
            color: var(--accent-gray);
        }
        /* Bureau History */
        .bureau-history {
            background: var(--light-blue);
            border-radius: 10px;
            padding: 2rem;
        }
        .history-item {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        .history-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .history-year {
            font-size: 1.3rem;
            color: var(--dark-blue);
            margin-bottom: 1rem;
        }
        .history-members {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .history-member {
            background: var(--white);
            padding: 0.8rem 1.2rem;
            border-radius: 50px;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .bureau-hero {
                padding: 6rem 5% 3rem;
            }
            .bureau-hero h1 {
                font-size: 2rem;
            }
            .members-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 480px) {
            .members-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="bureau-hero">
        <h1>Notre Bureau</h1>
        <p>Découvrez les membres qui dirigent notre association et contribuent à son succès.</p>
    </section>

    <!-- Bureau Content -->
    <div class="bureau-container">
        <!-- Current Bureau Section -->
        <div class="current-bureau">
            <div class="section-title">
                <h2>Bureau Actuel</h2>
                <p>Les membres élus pour représenter la communauté SIGMA Alumni</p>
            </div>
            <?php if (!empty($current_bureau)): ?>
                <div class="members-grid">
                    <?php foreach ($current_bureau as $member): ?>
                        <div class="member-card">
                            <div class="member-avatar">
                                <?php if (!empty($member['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($member['profile_picture']); ?>" alt="<?php echo htmlspecialchars($member['full_name']); ?>">
                                <?php else: ?>
                                    <img src="img/profile_pic.jpeg" alt="Avatar par défaut">
                                <?php endif; ?>
                            </div>
                            <div class="member-info">
                                <h3><?php echo htmlspecialchars($member['full_name']); ?></h3>
                                <div class="member-position"><?php echo htmlspecialchars($member['position']); ?></div>
                                <div class="member-promo">Promotion <?php echo htmlspecialchars($member['promotion_year']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem;">Aucun membre du bureau n'est actuellement enregistré.</p>
            <?php endif; ?>
        </div>

        <!-- Bureau History Section -->
        <div class="bureau-history">
            <div class="section-title">
                <h2>Historique des Bureaux</h2>
                <p>Retour sur les précédentes équipes qui ont dirigé l'association</p>
            </div>
            <?php if (!empty($bureau_history)): ?>
                <?php foreach ($bureau_history as $history): ?>
                    <div class="history-item">
                        <div class="history-year"><?php echo htmlspecialchars($history['year_range']); ?></div>
                        <div class="history-members">
                            <?php
                            $members = explode(',', $history['members']);
                            foreach ($members as $member):
                                $member_info = explode('-', $member);
                                if (count($member_info) >= 2):
                            ?>
                                <div class="history-member"><?php echo trim($member_info[0]); ?> (<?php echo trim($member_info[1]); ?>)</div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem;">Aucun historique de bureau n'est disponible.</p>
            <?php endif; ?>
        </div>
    </div>

<?php include 'footer.php'; ?>