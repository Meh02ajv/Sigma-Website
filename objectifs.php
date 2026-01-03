```php
<?php
require 'config.php'; // Include database connection
require 'vendor/autoload.php'; // Include Composer autoloader for HTMLPurifier

// Initialize HTMLPurifier
$purifier = new HTMLPurifier();

// Fetch mission content
$stmt = $conn->prepare("SELECT content FROM objectifs_mission WHERE id = 1");
$stmt->execute();
$mission_content = $purifier->purify($stmt->get_result()->fetch_assoc()['content'] ?? '<p>SIGMA Alumni s\'engage à créer un réseau dynamique et solidaire entre les anciens élèves de l\'établissement SIGMA, tout en contribuant au rayonnement de notre alma mater.</p><p>Notre association agit comme un pont entre les générations d\'élèves, favorisant l\'entraide, le partage d\'expériences et le développement professionnel de ses membres.</p>');
$stmt->close();

// Fetch strategic objectives
$stmt = $conn->prepare("SELECT title, description, icon, order_index FROM objectifs_strategic ORDER BY order_index ASC");
$stmt->execute();
$objectifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch fundamental values
$stmt = $conn->prepare("SELECT title, description, icon, order_index FROM objectifs_values ORDER BY order_index ASC");
$stmt->execute();
$values = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMA Alumni - Objectifs</title>
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
        .objectifs-hero {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            color: var(--white);
            text-align: center;
            padding: 8rem 5% 4rem;
            margin-top: 70px;
        }

        .objectifs-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .objectifs-hero p {
            max-width: 700px;
            margin: 0 auto;
        }

        /* Objectifs Content */
        .objectifs-container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 5%;
        }

        .objectifs-content {
            background: var(--white);
            border-radius: 10px;
            padding: 3rem;
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
        }

        .mission-section {
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--light-blue);
        }

        .mission-section h2 {
            color: var(--dark-blue);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .mission-section p {
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .objectifs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .objectif-card {
            background: var(--light-blue);
            border-radius: 10px;
            padding: 2rem;
            transition: transform 0.3s;
            border-left: 5px solid var(--primary-blue);
        }

        .objectif-card:hover {
            transform: translateY(-5px);
        }

        .objectif-icon {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .objectif-card h3 {
            color: var(--dark-blue);
            margin-bottom: 1rem;
        }

        .objectif-card p {
            margin-bottom: 0;
        }

        .valeurs-section {
            margin-top: 3rem;
        }

        .valeurs-section h2 {
            color: var(--dark-blue);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .valeurs-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .valeur-item {
            text-align: center;
            padding: 1.5rem;
            background: var(--white);
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .valeur-item i {
            font-size: 1.8rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .valeur-item h3 {
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .objectifs-hero {
                padding: 6rem 5% 3rem;
            }

            .objectifs-hero h1 {
                font-size: 2rem;
            }

            .objectifs-content {
                padding: 2rem;
            }

            .objectifs-grid {
                grid-template-columns: 1fr;
            }

            .valeurs-list {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .objectifs-content {
                padding: 1.5rem;
            }

            .valeurs-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="objectifs-hero">
        <h1>Nos Objectifs et Valeurs</h1>
        <p>Découvrez les missions qui animent notre communauté et les principes qui nous guident</p>
    </section>

    <!-- Objectifs Content -->
    <div class="objectifs-container">
        <div class="objectifs-content">
            <div class="mission-section">
                <h2>Notre Mission</h2>
                <?php echo $mission_content; ?>
            </div>

            <h2 style="color: var(--dark-blue); text-align: center; margin-bottom: 1.5rem;">Nos Objectifs Stratégiques</h2>
            <div class="objectifs-grid">
                <?php foreach ($objectifs as $objectif): ?>
                    <div class="objectif-card">
                        <div class="objectif-icon">
                            <i class="<?php echo htmlspecialchars($objectif['icon']); ?>"></i>
                        </div>
                        <h3><?php echo $objectif['title']; ?></h3>
                        <?php echo $purifier->purify($objectif['description']); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="valeurs-section">
                <h2>Nos Valeurs Fondamentales</h2>
                <p style="text-align: center; margin-bottom: 2rem;">Ces valeurs représentent l'ADN de notre communauté et guident toutes nos actions</p>
                <div class="valeurs-list">
                    <?php foreach ($values as $value): ?>
                        <div class="valeur-item">
                            <i class="<?php echo htmlspecialchars($value['icon']); ?>"></i>
                            <h3><?php echo $value['title']; ?></h3>
                            <?php echo $purifier->purify($value['description']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
```