<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1a365d;
            --primary-medium: #2c5282;
            --primary-light: #4299e1;
            --accent-gold: #d4af37;
            --light-bg: #f8fafc;
            --text-dark: #2d3748;
            --text-light: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.98);
            --touch-min-size: 44px;
        }
        
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, var(--light-bg) 0%, #e2e8f0 100%);
            color: var(--text-dark);
            background-color: #fff;
            background-image: url(img/sigma\ build.jpg);
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            background-size: cover;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            padding: 15px;
            position: relative;
        }
        
        .back-arrow {
            position: fixed;
            top: 20px;
            left: 20px;
            font-size: 24px;
            color: var(--primary-dark);
            text-decoration: none;
            transition: all 0.3s ease;
            background: var(--card-bg);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 100;
            border: 2px solid var(--primary-light);
        }
        
        .back-arrow:hover, .back-arrow:active {
            color: var(--accent-gold);
            transform: translateX(-3px);
            background: var(--primary-dark);
        }
        
        .settings-card {
            width: 100%;
            margin: 40px auto 20px;
            background: var(--card-bg);
            border-radius: 16px;
            padding: 30px 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .settings-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-dark), var(--accent-gold));
        }
        
        .settings-header {
            text-align: center;
            margin-bottom: 35px;
            padding: 0 10px;
        }
        
        .settings-header .logo {
            margin-bottom: 20px;
        }
        
        .settings-header .logo img {
            width: 80px;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 3px solid var(--accent-gold);
        }
        
        .settings-header h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 12px 0;
            position: relative;
            display: inline-block;
        }
        
        .settings-header h3::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 4px;
            background: var(--accent-gold);
            border-radius: 4px;
        }
        
        .settings-header p {
            font-size: 16px;
            color: #718096;
            margin: 15px 0 0 0;
            line-height: 1.5;
        }
        
        .settings-menu {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .settings-btn {
            display: flex;
            align-items: center;
            padding: 20px 25px;
            background: white;
            border: none;
            border-radius: 12px;
            color: var(--primary-dark);
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            text-decoration: none;
            min-height: var(--touch-min-size);
            position: relative;
            overflow: hidden;
        }
        
        .settings-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .settings-btn:active::before {
            left: 100%;
        }
        
        .settings-btn i {
            margin-right: 15px;
            color: var(--primary-medium);
            font-size: 22px;
            transition: all 0.3s ease;
            min-width: 25px;
            text-align: center;
        }
        
        .settings-btn:hover, .settings-btn:active {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            background: var(--primary-dark);
            color: white;
        }
        
        .settings-btn:hover i, .settings-btn:active i {
            color: var(--accent-gold);
            transform: scale(1.1);
        }
        
        .settings-btn:active {
            transform: translateY(-1px);
        }
        
        .sigma-watermark {
            position: fixed;
            bottom: 15px;
            right: 15px;
            font-size: 14px;
            color: rgba(0,0,0,0.3);
            font-weight: 700;
            letter-spacing: 1px;
            background: rgba(255,255,255,0.8);
            padding: 8px 12px;
            border-radius: 20px;
        }
        
        /* Styles spécifiques pour mobile */
        @media (max-width: 768px) {
            body {
                padding: 15px;
                justify-content: flex-start;
                min-height: 100vh;
                background-attachment: scroll;
            }
            
            .container {
                padding: 10px;
                max-width: 100%;
            }
            
            .back-arrow {
                position: fixed;
                top: 15px;
                left: 15px;
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
            
            .settings-card {
                margin: 60px auto 30px;
                padding: 25px 20px;
                border-radius: 20px;
            }
            
            .settings-header {
                margin-bottom: 30px;
            }
            
            .settings-header .logo img {
                width: 70px;
            }
            
            .settings-header h3 {
                font-size: 24px;
            }
            
            .settings-header p {
                font-size: 15px;
            }
            
            .settings-btn {
                padding: 18px 20px;
                font-size: 16px;
                border-radius: 10px;
            }
            
            .settings-btn i {
                font-size: 20px;
                margin-right: 12px;
            }
            
            .settings-menu {
                gap: 15px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .back-arrow {
                top: 10px;
                left: 10px;
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            
            .settings-card {
                margin: 50px auto 20px;
                padding: 20px 15px;
            }
            
            .settings-header .logo img {
                width: 60px;
            }
            
            .settings-header h3 {
                font-size: 22px;
            }
            
            .settings-header p {
                font-size: 14px;
            }
            
            .settings-btn {
                padding: 16px 18px;
                font-size: 15px;
            }
            
            .settings-btn i {
                font-size: 18px;
                margin-right: 10px;
            }
        }
        
        /* Animation d'entrée */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .settings-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .settings-btn {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }
        
        .settings-btn:nth-child(1) { animation-delay: 0.1s; }
        .settings-btn:nth-child(2) { animation-delay: 0.2s; }
        .settings-btn:nth-child(3) { animation-delay: 0.3s; }
        
        /* Effet de pression pour mobile */
        @media (hover: none) {
            .settings-btn:hover {
                transform: none;
                box-shadow: 0 4px 10px rgba(0,0,0,0.08);
                background: white;
                color: var(--primary-dark);
            }
            
            .settings-btn:hover i {
                color: var(--primary-medium);
                transform: none;
            }
            
            .settings-btn:active {
                transform: scale(0.98);
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                background: var(--primary-dark);
                color: white;
            }
            
            .settings-btn:active i {
                color: var(--accent-gold);
            }
        }
    </style>
</head>
<body>
    <a href="yearbook.php" class="back-arrow" aria-label="Retour au Yearbook">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <div class="container">
        <div class="settings-card">
            <div class="settings-header">
                <div class="logo">
                    <img src="img/image.png" alt="Sigma Logo">
                </div>
                <h3>PARAMÈTRES</h3>
                <p>Gérez vos préférences et options</p>
            </div>
            
            <div class="settings-menu">
                <a href="mod_prof.php?from=settings.php" class="settings-btn">
                    <i class="fas fa-user-edit"></i>
                    Modifier mon profil
                </a>
                
                <a href="suggestion.php" class="settings-btn">
                    <i class="fas fa-lightbulb"></i>
                    Faire une suggestion
                </a>
                
                <a href="signalement.php" class="settings-btn">
                    <i class="fas fa-exclamation-triangle"></i>
                    Signaler un utilisateur
                </a>
            </div>
        </div>
    </div>
    
    <div class="sigma-watermark">SIGMA</div>

    <script>
        // Détection du toucher pour améliorer les interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Optimisation pour les appareils tactiles
            if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
                document.body.classList.add('touch-device');
                
                // Ajouter des écouteurs pour les effets de toucher
                const buttons = document.querySelectorAll('.settings-btn');
                buttons.forEach(btn => {
                    btn.addEventListener('touchstart', function() {
                        this.classList.add('active');
                    });
                    
                    btn.addEventListener('touchend', function() {
                        this.classList.remove('active');
                    });
                });
            }
            
            // Préchargement des images de fond pour de meilleures performances
            const bgImage = new Image();
            bgImage.src = 'img/sigma build.jpg';
            
            // Animation de chargement
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>