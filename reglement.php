```php
<?php
// Include database connection
require 'config.php';

// Include Composer autoloader
if (!file_exists('vendor/autoload.php')) {
    die('Composer autoloader not found. Run "composer install" in C:\xampp\htdocs\Sigma-Website');
}
require 'vendor/autoload.php';

// Verify mPDF class exists
if (!class_exists('Mpdf\Mpdf')) {
    die('Mpdf\Mpdf class not found. Ensure mPDF v8.2.x is installed via Composer. Run "composer require mpdf/mpdf ^8.2".');
}

// Fetch regulation articles
$stmt = $conn->prepare("SELECT * FROM regulations ORDER BY order_index ASC");
if (!$stmt) {
    die('Database query error: ' . $conn->error);
}
$stmt->execute();
$regulations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch footer content
$stmt = $conn->prepare("SELECT content FROM regulations_footer WHERE id = 1");
if (!$stmt) {
    die('Database query error: ' . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$footer_content = $result->fetch_assoc()['content'] ?? '<p>Fait à Paris, le 15 juin 2025</p><p>Le Président de SIGMA Alumni</p><p>Jean Dupont</p>';
$stmt->close();

// Handle PDF download
if (isset($_GET['download_pdf'])) {
    try {
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
        $mpdf->SetTitle('Règlement SIGMA Alumni');
        $html = '
            <h1>Règlement Intérieur de SIGMA Alumni</h1>
            <p>Adopté par l\'Assemblée Générale du 15 juin 2025</p>
            <p>Ce règlement a pour objet de préciser les modalités de fonctionnement de l\'association SIGMA Alumni et de définir les droits et obligations de ses membres.</p>';
        foreach ($regulations as $regulation) {
            $html .= '
                <h3>' . htmlspecialchars($regulation['title']) . '</h3>
                ' . $regulation['content'];
        }
        $html .= '<div style="text-align: center; font-style: italic;">' . $footer_content . '</div>';
        $mpdf->WriteHTML($html);
        $mpdf->Output('Reglement_SIGMA_Alumni.pdf', \Mpdf\Output\Destination::DOWNLOAD);
    } catch (\Exception $e) {
        die('PDF generation error: ' . $e->getMessage());
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMA Alumni - Règlement</title>
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
        .reglement-hero {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            color: var(--white);
            text-align: center;
            padding: 8rem 5% 4rem;
            margin-top: 70px;
        }
        .reglement-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .reglement-hero p {
            max-width: 700px;
            margin: 0 auto;
        }
        .reglement-container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 5%;
        }
        .reglement-content {
            background: var(--white);
            border-radius: 10px;
            padding: 3rem;
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
        }
        .reglement-intro {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--light-blue);
        }
        .reglement-intro p {
            margin-bottom: 1rem;
        }
        .reglement-article {
            margin-bottom: 2.5rem;
        }
        .article-title {
            color: var(--dark-blue);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        .article-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background-color: var(--primary-blue);
            color: var(--white);
            border-radius: 50%;
            margin-right: 1rem;
            font-size: 0.9rem;
        }
        .article-content {
            padding-left: 3.5rem;
        }
        .article-content p {
            margin-bottom: 1rem;
        }
        .article-list {
            list-style-type: none;
            margin: 1rem 0;
        }
        .article-list li {
            margin-bottom: 0.8rem;
            position: relative;
            padding-left: 1.5rem;
        }
        .article-list li::before {
            content: "•";
            color: var(--primary-blue);
            font-size: 1.5rem;
            position: absolute;
            left: 0;
            top: -0.3rem;
        }
        .reglement-footer {
            text-align: center;
            font-style: italic;
            color: var(--accent-gray);
            margin-top: 3rem;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 1rem;
            transition: background-color 0.3s;
        }
        .download-btn:hover {
            background-color: var(--dark-blue);
        }
        .download-btn i {
            margin-right: 0.5rem;
        }
        @media (max-width: 768px) {
            .reglement-hero {
                padding: 6rem 5% 3rem;
            }
            .reglement-hero h1 {
                font-size: 2rem;
            }
            .reglement-content {
                padding: 2rem;
            }
            .article-content {
                padding-left: 2rem;
            }
        }
        @media (max-width: 480px) {
            .reglement-content {
                padding: 1.5rem;
            }
            .article-title {
                flex-direction: column;
                align-items: flex-start;
            }
            .article-number {
                margin-bottom: 0.5rem;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="reglement-hero">
        <h1>Règlement de l'Association</h1>
        <p>Consultez les règles et valeurs qui guident notre communauté SIGMA Alumni</p>
    </section>

    <div class="reglement-container">
        <div class="reglement-content">
            <div class="reglement-intro">
                <h2>Règlement Intérieur de SIGMA Alumni</h2>
                <p>Adopté par l'Assemblée Générale du 15 juin 2025</p>
                <p>Ce règlement a pour objet de préciser les modalités de fonctionnement de l'association SIGMA Alumni et de définir les droits et obligations de ses membres.</p>
                <a href="?download_pdf=1" class="download-btn">
                    <i class="fas fa-download"></i>
                    Télécharger le règlement (PDF)
                </a>
            </div>
            <?php foreach ($regulations as $regulation): ?>
                <div class="reglement-article">
                    <h3 class="article-title">
                        <span class="article-number"><?php echo htmlspecialchars($regulation['article_number']); ?></span>
                        <?php echo htmlspecialchars($regulation['title']); ?>
                    </h3>
                    <div class="article-content">
                        <?php echo $regulation['content']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="reglement-footer">
                <?php echo $footer_content; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
```