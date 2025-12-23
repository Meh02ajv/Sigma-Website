
<?php
require 'config.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['full_name'])) {
    header("Location: connexion.php");
    exit;
}

// Get user data including profile picture
$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];
$profile_picture = 'img/profile_pic.jpeg'; // Default profile picture

$sql = "SELECT login_count, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $login_count = $user['login_count'];
    if (!empty($user['profile_picture'])) {
        $profile_picture = $user['profile_picture'];
    }
} else {
    $login_count = 0;
}
$stmt->close();

$welcome_message = ($login_count <= 1) ? "Bienvenue parmi nous, $full_name" : "Salut, $full_name";

// Member count
$sql = "SELECT COUNT(*) as member_count FROM users";
$result = $conn->query($sql);
$member_count = $result->fetch_assoc()['member_count'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMA Alumni - Tableau de bord</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-blue: #0056b3;
            --dark-blue: #003366;
            --light-blue: #e6f0ff;
            --accent-gray: #4a4a4a;
            --light-gray: #f5f5f5;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--accent-gray);
            line-height: 1.6;
        }

        /* Hero Section */
        .dashboard-hero {
            position: relative;
            height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white);
            overflow: hidden;
            margin-top: 70px;
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
        }

        .dashboard-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .user-welcome {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }

        /* User Info Section */
        .user-info {
            max-width: 1200px;
            margin: -50px auto 3rem;
            padding: 0 5%;
            position: relative;
            z-index: 2;
        }

        .profile-card {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 2rem;
            border: 5px solid var(--light-blue);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-details h2 {
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }

        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
        }

        .meta-item i {
            color: var(--primary-blue);
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        /* Quick Actions */
        .quick-actions {
            max-width: 1200px;
            margin: 0 auto 3rem;
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

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .action-card {
            background: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .action-card h3 {
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }

        /* Member Counter */
        .member-counter {
            max-width: 1200px;
            margin: 0 auto 3rem;
            padding: 0 5%;
            text-align: center;
        }

        .counter-box {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
            display: inline-block;
        }

        .counter-box h3 {
            color: var(--dark-blue);
            margin-bottom: 1rem;
        }

        .counter-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-blue);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-hero {
                height: auto;
                padding: 6rem 5% 3rem;
            }

            .dashboard-hero h1 {
                font-size: 2rem;
            }

            .profile-card {
                flex-direction: column;
                text-align: center;
            }

            .profile-avatar {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }

            .profile-meta {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="dashboard-hero">
        <div>
            <h1>Bienvenue sur votre espace membre</h1>
            <div class="user-welcome"><?php echo htmlspecialchars($welcome_message); ?></div>
        </div>
    </section>

    <!-- User Info Section -->
    <div class="user-info">
        <div class="profile-card">
            <div class="profile-avatar">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="<?php echo htmlspecialchars($full_name); ?>">
            </div>
            <div class="profile-details">
                <h2><?php echo htmlspecialchars($full_name); ?></h2>
                <p>Membre de SIGMA Alumni</p>
                <div class="profile-meta">
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $member_count; ?> membres</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="quick-actions">
        <div class="section-title">
            <h2>Accès rapide</h2>
            <p>Accédez rapidement aux fonctionnalités principales</p>
        </div>
        <div class="actions-grid">
            <a href="yearbook.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-address-book"></i>
                </div>
                <h3>Annuaire</h3>
                <p>Consultez le répertoire des anciens élèves</p>
            </a>
            <a href="reglement.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>Règlement</h3>
                <p>Consultez les règles de l'association</p>
            </a>
            <a href="objectifs.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3>Objectifs</h3>
                <p>Découvrez nos missions et objectifs</p>
            </a>
            <a href="elections.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <h3>Élections</h3>
                <p>Participez aux élections du bureau</p>
            </a>
        </div>
    </div>

    <!-- Member Counter -->
    <div class="member-counter">
        <div class="counter-box">
            <h3>Nombre de membres inscrits</h3>
            <div class="counter-number"><?php echo $member_count; ?></div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
```