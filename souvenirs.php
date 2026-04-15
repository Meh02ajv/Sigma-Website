<?php
require 'config.php';
$years = [2023, 2024]; // Tableau des années, modifiable pour ajouter de nouvelles années
$referrer = isset($_GET['from']) && $_GET['from'] === 'accueil' ? 'accueil.php' : 'yearbook.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Souvenirs des Années</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #1a3c5e;
            color: white;
        }
        header a {
            color: white;
            text-decoration: none;
            font-size: 24px;
        }
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: #1a3c5e;
            margin-bottom: 30px;
            font-size: 32px;
        }
        .banners {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .banner {
            background-color: white;
            width: 250px;
            border-radius: 10px;
            box-shadow: Ascendancy 0.3s;
        }
        .banner:hover {
            transform: translateY(-10px);
        }
        .banner-header {
            background-color: #1a3c5e;
            color: white;
            padding: 15px;
            font-size: 20px;
            font-weight: bold;
        }
        .banner-photos {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5px;
            padding: 10px;
        }
        .banner-photos img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            transition: opacity 0.3s;
        }
        .banner-photos img:hover {
            opacity: 0.8;
        }
        .banner-footer {
            padding: 10px;
        }
        .view-more-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #1a3c5e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .view-more-btn:hover {
            background-color: #152f4a;
        }
        @media (max-width: 768px) {
            .banner {
                width: 100%;
                max-width: 300px;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="<?php echo htmlspecialchars($referrer); ?>">←</a>
        <h1>Souvenirs des Années</h1>
        <a href="<?php echo isset($_SESSION['user_email']) ? 'settings.php' : 'connexion.php'; ?>">
            <i class="fas fa-cog"></i>
        </a>
    </header>
    <div class="container">
        <h1>Explorez les Souvenirs par Année</h1>
        <div class="banners">
            <?php foreach ($years as $year): ?>
                <div class="banner">
                    <div class="banner-header"><?php echo $year; ?></div>
                    <div class="banner-photos">
                        <?php
                        for ($i = 1; $i <= 4; $i++) {
                            $photo_path = "img/souvenirs/$year/photo$i.jpg";
                            $default_photo = "https://via.placeholder.com/150?text=Photo+$i+$year";
                            ?>
                            <img src="<?php echo file_exists($photo_path) ? $photo_path : $default_photo; ?>" alt="Souvenir <?php echo $i; ?> <?php echo $year; ?>">
                        <?php } ?>
                    </div>
                    <div class="banner-footer">
                        <?php if (isset($_SESSION['user_email'])): ?>
                            <a href="yearbook.php?bac_year=<?php echo $year; ?>" class="view-more-btn">Voir plus</a>
                        <?php else: ?>
                            <a href="connexion.php?redirect=yearbook&bac_year=<?php echo $year; ?>" class="view-more-btn">Voir plus</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>