<?php
require 'config.php';
if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}

// Directory for yearbook media
$uploads_dir = 'uploads/';
$media_extensions = ['jpg', 'jpeg', 'png', 'mp4', 'webm'];

// Get list of year folders
$year_folders = glob($uploads_dir . '*_pic', GLOB_ONLYDIR);
$years = array_map(function($folder) {
    return preg_replace('/.*\/(\d{4})_pic$/', '$1', $folder);
}, $year_folders);
rsort($years); // Sort years in descending order

// Initial filter and pagination
$bac_year = isset($_GET['bac_year']) && in_array($_GET['bac_year'], $years) ? $_GET['bac_year'] : '';
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$offset = $page * $limit;

// Fetch media from folder
$media = [];
if ($bac_year) {
    $year_dir = $uploads_dir . $bac_year . '_pic/';
    $files = glob($year_dir . '*.{jpg,jpeg,png,mp4,webm}', GLOB_BRACE);
    foreach ($files as $index => $file) {
        if ($index < $offset) continue;
        if (count($media) >= $limit) break;
        $media[] = [
            'id' => $index + 1,
            'media_path' => $file,
            'bac_year' => $bac_year,
            'type' => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['mp4', 'webm']) ? 'video' : 'image'
        ];
    }
} else {
    foreach ($years as $year) {
        $year_dir = $uploads_dir . $year . '_pic/';
        $files = glob($year_dir . '*.{jpg,jpeg,png,mp4,webm}', GLOB_BRACE);
        foreach ($files as $index => $file) {
            if ($index < $offset) continue;
            if (count($media) >= $limit) break;
            $media[] = [
                'id' => $index + 1,
                'media_path' => $file,
                'bac_year' => $year,
                'type' => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['mp4', 'webm']) ? 'video' : 'image'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Album</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Times+New+Roman:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }
        :root {
            --wood-brown: #4A2F1A;
            --leather-dark: #3C2F2F;
            --parchment: #F4E8C1;
            --text-dark: #2C1F0F;
            --border-gold: #D4A017;
            --shadow: rgba(0, 0, 0, 0.3);
            --accent-gold: #FFD700;
            --hover-gold: #FFA500;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #E8D9A9 0%, #F4E8C1 50%, #DCC695 100%);
            color: var(--text-dark);
            touch-action: manipulation;
            min-height: 100vh;
            background-attachment: fixed;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 25px;
            background: linear-gradient(135deg, var(--wood-brown) 0%, #5A3F2A 100%);
            border-bottom: 4px solid var(--border-gold);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px var(--shadow);
        }
        header a {
            color: var(--border-gold);
            text-decoration: none;
            font-size: 24px;
            transition: all 0.3s ease;
            padding: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
        }
        header a:hover, header a:focus {
            color: var(--accent-gold);
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.1);
        }
        header h1 {
            font-size: 32px;
            color: var(--border-gold);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            letter-spacing: 1px;
        }
        .bookshelf {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 20px;
            background: url('https://www.transparenttextures.com/patterns/wood-pattern.png');
            perspective: 1200px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            min-height: calc(100vh - 80px);
        }
        
        /* Navigation mobile pour les années */
        .mobile-year-selector {
            display: none;
            width: 100%;
            max-width: 500px;
            margin: 0 auto 25px;
            position: relative;
        }
        
        .mobile-year-selector select {
            width: 100%;
            padding: 14px 45px 14px 18px;
            font-size: 17px;
            font-weight: 600;
            background: linear-gradient(135deg, var(--leather-dark) 0%, #2c2222 100%);
            color: var(--border-gold);
            border: 3px solid var(--border-gold);
            border-radius: 10px;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            box-shadow: 0 4px 10px var(--shadow);
            transition: all 0.3s ease;
        }
        
        .mobile-year-selector select:focus {
            outline: none;
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 160, 23, 0.3);
        }
        
        .mobile-year-selector::after {
            content: "▼";
            font-size: 16px;
            font-weight: bold;
            color: var(--border-gold);
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            transition: transform 0.3s ease;
        }
        
        .mobile-year-selector select:focus + .mobile-year-selector::after {
            transform: translateY(-50%) rotate(180deg);
        }
        
        .book-stack {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
            max-width: 1200px;
        }
        .book {
            width: 240px;
            height: 55px;
            background: linear-gradient(135deg, var(--leather-dark) 0%, #2c2222 100%);
            color: var(--border-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            border: 3px solid var(--border-gold);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 4px 4px 10px var(--shadow);
            position: relative;
            overflow: hidden;
        }
        .book::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.3), transparent);
            transition: left 0.5s;
        }
        .book:hover::before, .book:active::before {
            left: 100%;
        }
        .book:hover, .book:active {
            transform: translateX(15px) scale(1.05);
            background: linear-gradient(135deg, #2c2222 0%, #1a1515 100%);
            border-color: var(--accent-gold);
            box-shadow: 6px 6px 15px var(--shadow);
        }
        .book.selected {
            transform: translateX(25px) scale(1.1);
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--border-gold) 100%);
            color: var(--wood-brown);
            border-color: var(--accent-gold);
            box-shadow: 8px 8px 20px rgba(212, 160, 23, 0.5);
        }
        .book-container {
            display: none;
            width: 90%;
            max-width: 1100px;
            margin: 0 auto;
            animation: slideIn 0.5s ease-out;
        }
        .book-container.open {
            display: block;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .book-open {
            position: relative;
            width: 100%;
            height: 650px;
            background: linear-gradient(135deg, var(--leather-dark) 0%, #2c2222 100%);
            border: 4px solid var(--border-gold);
            border-radius: 8px;
            box-shadow: 0 10px 30px var(--shadow);
        }
        .page {
            position: absolute;
            width: 50%;
            height: 100%;
            backface-visibility: hidden;
            background: linear-gradient(to bottom, var(--parchment) 0%, #EDD9A8 100%);
            border: 2px solid var(--border-gold);
            display: flex;
            flex-wrap: wrap;
            padding: 25px;
            gap: 20px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--border-gold) var(--parchment);
            align-content: flex-start;
        }
        .page::-webkit-scrollbar {
            width: 8px;
        }
        .page::-webkit-scrollbar-track {
            background: var(--parchment);
            border-radius: 4px;
        }
        .page::-webkit-scrollbar-thumb {
            background: var(--border-gold);
            border-radius: 4px;
        }
        .page::-webkit-scrollbar-thumb:hover {
            background: var(--accent-gold);
        }
        .page.left {
            transform-origin: right;
            left: 0;
            border-radius: 8px 0 0 8px;
        }
        .page.right {
            transform-origin: left;
            right: 0;
            transform: rotateY(180deg);
            border-radius: 0 8px 8px 0;
        }
        .page.turn {
            transition: transform 0.8s cubic-bezier(0.645, 0.045, 0.355, 1);
        }
        .page.left.turn {
            transform: rotateY(-180deg);
        }
        .page.right.turn {
            transform: rotateY(0deg);
        }
        .media-card {
            width: 160px;
            height: 200px;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 12px var(--shadow);
            background: #FFF;
            cursor: pointer;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 3px solid var(--border-gold);
        }
        .media-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(212, 160, 23, 0.2) 100%);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        .media-card:hover::after, .media-card:focus::after {
            opacity: 1;
        }
        .media-card:hover, .media-card:focus {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            border-color: var(--accent-gold);
        }
        .media-card:active {
            transform: translateY(-4px) scale(1.02);
        }
        .media-card img, .media-card video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.3s ease;
        }
        .media-card:hover img, .media-card:hover video {
            transform: scale(1.1);
        }
        .media-card .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 40px;
            color: #FFF;
            opacity: 0.9;
            pointer-events: none;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);
            background: rgba(0, 0, 0, 0.5);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .media-card:hover .play-icon {
            transform: translate(-50%, -50%) scale(1.2);
            background: rgba(212, 160, 23, 0.8);
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            gap: 15px;
        }
        .nav-buttons button {
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--leather-dark) 0%, #2c2222 100%);
            color: var(--border-gold);
            border: 3px solid var(--border-gold);
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Times New Roman', Times, serif;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            min-height: 54px;
            box-shadow: 0 4px 10px var(--shadow);
            position: relative;
            overflow: hidden;
        }
        .nav-buttons button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.3), transparent);
            transition: left 0.5s;
        }
        .nav-buttons button:hover::before, .nav-buttons button:active::before {
            left: 100%;
        }
        .nav-buttons button:hover, .nav-buttons button:active {
            background: linear-gradient(135deg, var(--border-gold) 0%, var(--accent-gold) 100%);
            color: var(--wood-brown);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(212, 160, 23, 0.4);
        }
        .nav-buttons button:active {
            transform: translateY(0);
        }
        .nav-buttons button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }
        .nav-buttons button:disabled:hover {
            background: linear-gradient(135deg, var(--leather-dark) 0%, #2c2222 100%);
            color: var(--border-gold);
            box-shadow: 0 4px 10px var(--shadow);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.96);
            justify-content: center;
            align-items: center;
            z-index: 2000;
            touch-action: pinch-zoom;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        .modal-content {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal .close {
            position: fixed;
            top: 25px;
            right: 25px;
            font-size: 42px;
            cursor: pointer;
            color: var(--accent-gold);
            z-index: 2001;
            width: 54px;
            height: 54px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            border: 3px solid var(--border-gold);
            transition: all 0.3s ease;
            font-weight: bold;
        }
        .modal .close:hover, .modal .close:focus {
            transform: rotate(90deg) scale(1.1);
            background: var(--border-gold);
            color: var(--wood-brown);
        }
        .modal img, .modal video {
            max-width: 90%;
            max-height: 85%;
            object-fit: contain;
            border: 4px solid var(--border-gold);
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(212, 160, 23, 0.5);
            display: block;
            margin: auto;
            touch-action: manipulation;
            animation: zoomIn 0.4s ease;
        }
        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .modal video {
            width: auto;
            height: auto;
            max-width: 90%;
            max-height: 85%;
        }
        .modal .info {
            position: fixed;
            bottom: 30px;
            background: linear-gradient(135deg, rgba(74, 47, 26, 0.95), rgba(44, 31, 15, 0.95));
            color: var(--accent-gold);
            padding: 14px 28px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            border: 2px solid var(--border-gold);
            box-shadow: 0 4px 15px var(--shadow);
        }
        
        /* Navigation par gestes pour mobile */
        .swipe-area {
            position: fixed;
            top: 0;
            width: 50px;
            height: 100%;
            z-index: 2002;
            display: none;
        }
        .swipe-area.left {
            left: 0;
        }
        .swipe-area.right {
            right: 0;
        }
        
        /* Styles pour tablette */
        @media (max-width: 1024px) {
            .bookshelf {
                padding: 25px 15px;
            }
            
            .book-stack {
                gap: 12px;
            }
            
            .book {
                width: 200px;
                height: 50px;
                font-size: 18px;
            }
            
            .book-open {
                height: 600px;
            }
            
            .media-card {
                width: 145px;
                height: 185px;
            }
        }
        
        /* Styles pour mobile */
        @media (max-width: 768px) {
            header {
                padding: 14px 18px;
            }
            
            header h1 {
                font-size: 24px;
            }
            
            header a {
                font-size: 22px;
                width: 40px;
                height: 40px;
            }
            
            .mobile-year-selector {
                display: block;
            }
            
            .book-stack {
                display: none;
            }
            
            .bookshelf {
                padding: 20px 12px;
            }
            
            .book-container {
                width: 98%;
            }
            
            .book-open {
                height: 550px;
            }
            
            .page {
                padding: 18px;
                gap: 15px;
                justify-content: center;
            }
            
            .media-card {
                width: calc(50% - 12px);
                height: 180px;
            }
            
            .nav-buttons {
                flex-direction: row;
                gap: 12px;
            }
            
            .nav-buttons button {
                padding: 12px 20px;
                font-size: 16px;
                min-height: 50px;
            }
            
            .modal img, .modal video {
                max-width: 94%;
                max-height: 75%;
            }
            
            .modal .info {
                bottom: 20px;
                padding: 12px 24px;
                font-size: 15px;
            }
            
            .swipe-area {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            header {
                padding: 12px 15px;
            }
            
            header h1 {
                font-size: 20px;
            }
            
            header a {
                font-size: 20px;
                width: 38px;
                height: 38px;
            }
            
            .bookshelf {
                padding: 15px 8px;
                min-height: calc(100vh - 70px);
            }
            
            .book-container {
                width: 100%;
            }
            
            .book-open {
                height: 450px;
                border-width: 3px;
            }
            
            .page {
                padding: 12px;
                gap: 10px;
            }
            
            .media-card {
                width: calc(50% - 8px);
                height: 150px;
                border-width: 2px;
            }
            
            .media-card .play-icon {
                font-size: 32px;
                width: 50px;
                height: 50px;
            }
            
            .nav-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-buttons button {
                padding: 12px 18px;
                font-size: 15px;
                min-height: 48px;
                width: 100%;
            }
            
            .modal .close {
                top: 15px;
                right: 15px;
                font-size: 38px;
                width: 48px;
                height: 48px;
                border-width: 2px;
            }
            
            .modal img, .modal video {
                max-width: 96%;
                max-height: 72%;
                border-width: 3px;
            }
            
            .modal .info {
                bottom: 15px;
                font-size: 14px;
                padding: 10px 20px;
            }
            
            .mobile-year-selector select {
                padding: 12px 40px 12px 15px;
                font-size: 16px;
            }
        }
        
        /* Styles pour très petits écrans */
        @media (max-width: 360px) {
            header h1 {
                font-size: 18px;
            }
            
            .book-open {
                height: 400px;
            }
            
            .page {
                padding: 10px;
                gap: 8px;
            }
            
            .media-card {
                width: calc(50% - 6px);
                height: 130px;
            }
            
            .nav-buttons button {
                font-size: 14px;
                padding: 10px 15px;
            }
        }
        
        /* Animation pour le chargement */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .media-card {
            animation: fadeIn 0.3s ease;
        }
        
        /* Indicateur de chargement */
        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, rgba(74, 47, 26, 0.95), rgba(44, 31, 15, 0.95));
            color: var(--accent-gold);
            padding: 20px 35px;
            border-radius: 15px;
            z-index: 3000;
            border: 3px solid var(--border-gold);
            box-shadow: 0 8px 25px var(--shadow);
            font-size: 18px;
            font-weight: 600;
            text-align: center;
        }
        
        .loading::after {
            content: '';
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid var(--border-gold);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Amélioration de l'accessibilité */
        .book:focus-visible,
        .media-card:focus-visible,
        .nav-buttons button:focus-visible,
        header a:focus-visible,
        .modal .close:focus-visible {
            outline: 3px solid var(--accent-gold);
            outline-offset: 3px;
        }
        
        /* Styles pour l'impression */
        @media print {
            header,
            .mobile-year-selector,
            .book-stack,
            .nav-buttons,
            .modal {
                display: none !important;
            }
            
            .book-container {
                width: 100%;
                max-width: none;
            }
            
            .book-open {
                height: auto;
                border: 2px solid #000;
            }
            
            .page {
                position: static;
                width: 100%;
                page-break-inside: avoid;
            }
            
            .media-card {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">Chargement...</div>
    
    <header>
        <div>
            <a href="yearbook.php" aria-label="Aller au Yearbook"><i class="fas fa-book-open"></i></a>
        </div>
        <h1>Album</h1>
    </header>
    
    <div class="bookshelf">
        <!-- Sélecteur d'année pour mobile -->
        <div class="mobile-year-selector">
            <select id="yearSelect">
                <option value="">Sélectionnez une année</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $bac_year == $year ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="book-stack">
            <?php foreach ($years as $year): ?>
                <div class="book <?php echo $bac_year == $year ? 'selected' : ''; ?>" data-year="<?php echo $year; ?>">
                    <?php echo $year; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="book-container <?php echo $bac_year ? 'open' : ''; ?>">
            <div class="book-open" id="bookOpen">
                <div class="page left" id="leftPage">
                    <?php for ($i = 0; $i < min(6, count($media)); $i++): ?>
                        <div class="media-card" data-id="<?php echo $media[$i]['id']; ?>" data-year="<?php echo $media[$i]['bac_year']; ?>" data-type="<?php echo $media[$i]['type']; ?>">
                            <?php if ($media[$i]['type'] === 'image'): ?>
                                <img src="<?php echo htmlspecialchars($media[$i]['media_path']); ?>" alt="Photo de l'année <?php echo $media[$i]['bac_year']; ?>">
                            <?php else: ?>
                                <video preload="metadata">
                                    <source src="<?php echo htmlspecialchars($media[$i]['media_path']); ?>" type="video/<?php echo pathinfo($media[$i]['media_path'], PATHINFO_EXTENSION); ?>">
                                    Votre navigateur ne supporte pas la lecture de vidéos.
                                </video>
                                <i class="fas fa-play play-icon"></i>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="page right" id="rightPage">
                    <?php for ($i = 6; $i < min(12, count($media)); $i++): ?>
                        <div class="media-card" data-id="<?php echo $media[$i]['id']; ?>" data-year="<?php echo $media[$i]['bac_year']; ?>" data-type="<?php echo $media[$i]['type']; ?>">
                            <?php if ($media[$i]['type'] === 'image'): ?>
                                <img src="<?php echo htmlspecialchars($media[$i]['media_path']); ?>" alt="Photo de l'année <?php echo $media[$i]['bac_year']; ?>">
                            <?php else: ?>
                                <video preload="metadata">
                                    <source src="<?php echo htmlspecialchars($media[$i]['media_path']); ?>" type="video/<?php echo pathinfo($media[$i]['media_path'], PATHINFO_EXTENSION); ?>">
                                    Votre navigateur ne supporte pas la lecture de vidéos.
                                </video>
                                <i class="fas fa-play play-icon"></i>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="nav-buttons">
                <button id="prevPage" <?php echo $page == 0 ? 'disabled' : ''; ?>>Page Précédente</button>
                <button id="nextPage" <?php echo count($media) < $limit ? 'disabled' : ''; ?>>Page Suivante</button>
            </div>
        </div>
    </div>
    
    <div class="modal" id="modal" role="dialog" aria-labelledby="modal-year">
        <div class="swipe-area left" id="swipeLeft"></div>
        <div class="swipe-area right" id="swipeRight"></div>
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <div id="modal-media"></div>
            <div class="info">
                <p>Année : <span id="modal-year"></span></p>
            </div>
        </div>
    </div>

    <script>
        let currentPage = <?php echo $page; ?>;
        let media = <?php echo json_encode($media); ?>;
        const mediaPerPage = 12;
        let touchStartX = 0;
        let touchStartY = 0;
        let currentMediaIndex = 0;
        let allMediaElements = [];

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initializeMediaCards();
            addKeyboardNavigation();
            optimizeForTouchDevices();
        });

        // Initialiser les cartes média
        function initializeMediaCards() {
            allMediaElements = Array.from(document.querySelectorAll('.media-card'));
            allMediaElements.forEach((card, index) => {
                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');
                card.setAttribute('aria-label', `Voir ${card.dataset.type === 'image' ? 'la photo' : 'la vidéo'} de ${card.dataset.year}`);
            });
        }

        // Gestion du sélecteur d'année mobile
        document.getElementById('yearSelect').addEventListener('change', function() {
            if (this.value) {
                showLoading();
                setTimeout(() => {
                    window.location.href = `album.php?bac_year=${encodeURIComponent(this.value)}`;
                }, 300);
            }
        });

        // Gestion des livres (années) pour desktop avec effet sonore visuel
        document.querySelectorAll('.book').forEach(book => {
            book.setAttribute('tabindex', '0');
            book.setAttribute('role', 'button');
            
            book.addEventListener('click', () => selectYear(book));
            book.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectYear(book);
                }
            });
        });

        function selectYear(book) {
            showLoading();
            document.querySelectorAll('.book').forEach(b => b.classList.remove('selected'));
            book.classList.add('selected');
            setTimeout(() => {
                window.location.href = `album.php?bac_year=${encodeURIComponent(book.dataset.year)}`;
            }, 400);
        }

        // Navigation entre les pages
        document.getElementById('nextPage').addEventListener('click', () => {
            if (media.length < mediaPerPage) return;
            navigateToPage(currentPage + 1);
        });

        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage === 0) return;
            navigateToPage(currentPage - 1);
        });

        function navigateToPage(page) {
            document.getElementById('leftPage').classList.add('turn');
            document.getElementById('rightPage').classList.add('turn');
            showLoading();
            setTimeout(() => {
                window.location.href = `album.php?bac_year=${encodeURIComponent('<?php echo $bac_year; ?>')}&page=${page}`;
            }, 500);
        }

        // Fonction pour afficher le modal
        function openModal(element, index = -1) {
            if (index >= 0) currentMediaIndex = index;
            
            const year = element.dataset.year;
            const type = element.dataset.type;
            const mediaPath = element.querySelector(type === 'image' ? 'img' : 'video source').getAttribute(type === 'image' ? 'src' : 'src');
            const modalMedia = document.getElementById('modal-media');
            
            modalMedia.innerHTML = '';
            if (type === 'image') {
                const img = document.createElement('img');
                img.src = mediaPath;
                img.alt = `Photo de l'année ${year}`;
                img.loading = 'eager';
                modalMedia.appendChild(img);
                
                // Permettre le zoom sur image
                img.addEventListener('touchstart', handleTouchStart, false);
                img.addEventListener('touchmove', handleTouchMove, false);
            } else {
                const video = document.createElement('video');
                video.controls = true;
                video.autoplay = true;
                video.preload = 'auto';
                const source = document.createElement('source');
                source.src = mediaPath;
                source.type = `video/${mediaPath.split('.').pop()}`;
                video.appendChild(source);
                video.innerHTML += 'Votre navigateur ne supporte pas la lecture de vidéos.';
                modalMedia.appendChild(video);
            }
            
            document.getElementById('modal-year').textContent = year;
            const modal = document.getElementById('modal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Focus sur le bouton de fermeture pour l'accessibilité
            setTimeout(() => {
                modal.querySelector('.close').focus();
            }, 100);
        }

        // Fonction pour fermer le modal
        function closeModal() {
            const modal = document.getElementById('modal');
            const modalMedia = document.getElementById('modal-media');
            
            // Arrêter les vidéos en cours
            const video = modalMedia.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
            
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            modalMedia.innerHTML = '';
            
            // Retourner le focus à la carte qui a ouvert le modal
            if (allMediaElements[currentMediaIndex]) {
                allMediaElements[currentMediaIndex].focus();
            }
        }

        // Navigation entre les médias dans le modal
        function showNextMedia() {
            if (currentMediaIndex < allMediaElements.length - 1) {
                currentMediaIndex++;
                openModal(allMediaElements[currentMediaIndex], currentMediaIndex);
            }
        }

        function showPreviousMedia() {
            if (currentMediaIndex > 0) {
                currentMediaIndex--;
                openModal(allMediaElements[currentMediaIndex], currentMediaIndex);
            }
        }

        // Gestion des cartes média
        document.querySelectorAll('.media-card').forEach((card, index) => {
            card.addEventListener('click', () => openModal(card, index));
            card.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openModal(card, index);
                }
            });
        });

        // Navigation au clavier améliorée
        function addKeyboardNavigation() {
            document.addEventListener('keydown', e => {
                const modal = document.getElementById('modal');
                const isModalOpen = modal.style.display === 'flex';
                
                if (e.key === 'Escape' && isModalOpen) {
                    closeModal();
                } else if (isModalOpen) {
                    if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        showNextMedia();
                    } else if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        showPreviousMedia();
                    }
                }
            });
        }

        // Fermeture du modal en cliquant à l'extérieur
        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('modal-content')) {
                closeModal();
            }
        });

        // Fonction pour afficher l'indicateur de chargement
        function showLoading() {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            setTimeout(() => {
                loading.style.opacity = '1';
            }, 10);
        }

        // Gestion des gestes de navigation pour le modal
        function handleTouchStart(e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        }

        function handleTouchMove(e) {
            if (!touchStartX || !touchStartY) return;
            
            const touchEndX = e.touches[0].clientX;
            const touchEndY = e.touches[0].clientY;
            
            const diffX = touchStartX - touchEndX;
            const diffY = touchStartY - touchEndY;
            
            // Seulement si le mouvement est principalement horizontal
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 70) {
                if (diffX > 0) {
                    // Swipe gauche - prochaine image
                    showNextMedia();
                } else {
                    // Swipe droite - image précédente
                    showPreviousMedia();
                }
                
                // Réinitialiser pour le prochain geste
                touchStartX = 0;
                touchStartY = 0;
            }
        }

        // Gestion des gestes sur le modal lui-même
        const modal = document.getElementById('modal');
        modal.addEventListener('touchstart', function(e) {
            if (e.target === modal || e.target.classList.contains('modal-content')) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
            }
        }, { passive: true });

        modal.addEventListener('touchend', function(e) {
            if (!touchStartX || !touchStartY) return;
            
            const touchEndX = e.changedTouches[0].clientX;
            const touchEndY = e.changedTouches[0].clientY;
            
            const diffX = touchStartX - touchEndX;
            const diffY = touchStartY - touchEndY;
            
            // Swipe horizontal pour naviguer
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 70) {
                if (diffX > 0) {
                    showNextMedia();
                } else {
                    showPreviousMedia();
                }
            }
            // Swipe vertical vers le bas pour fermer
            else if (diffY < -100 && Math.abs(diffX) < 50) {
                closeModal();
            }
            
            touchStartX = 0;
            touchStartY = 0;
        }, { passive: true });

        // Optimiser pour les appareils tactiles
        function optimizeForTouchDevices() {
            if (isMobileDevice()) {
                document.body.classList.add('mobile');
                
                // Ajouter un délai pour éviter les clics accidentels
                let lastTap = 0;
                document.querySelectorAll('.media-card').forEach(card => {
                    card.addEventListener('touchend', function(e) {
                        const currentTime = new Date().getTime();
                        const tapLength = currentTime - lastTap;
                        if (tapLength < 500 && tapLength > 0) {
                            e.preventDefault();
                        }
                        lastTap = currentTime;
                    });
                });
            }
        }

        // Détection de l'appareil
        function isMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                   (typeof window.orientation !== "undefined") || 
                   (navigator.userAgent.indexOf('IEMobile') !== -1);
        }

        // Lazy loading pour les images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    </script>
</body>
</html>