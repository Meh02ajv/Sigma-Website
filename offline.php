<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hors Ligne - SIGMA Alumni</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .offline-container {
            text-align: center;
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .offline-icon {
            font-size: 100px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        h1 {
            color: #1e3a8a;
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        p {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .retry-btn {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            display: inline-block;
            text-decoration: none;
        }

        .retry-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .retry-btn:active {
            transform: translateY(0);
        }

        .status {
            margin-top: 20px;
            padding: 15px;
            background: #f1f5f9;
            border-radius: 10px;
            color: #475569;
            font-size: 14px;
            display: none;
        }

        .status.checking {
            display: block;
            background: #fef3c7;
            color: #92400e;
        }

        .status.online {
            display: block;
            background: #d1fae5;
            color: #065f46;
        }

        .status.offline {
            display: block;
            background: #fee2e2;
            color: #991b1b;
        }

        .features {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }

        .features h3 {
            color: #334155;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .features ul {
            list-style: none;
            text-align: left;
        }

        .features li {
            color: #64748b;
            font-size: 14px;
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }

        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 18px;
        }

        @media (max-width: 600px) {
            .offline-container {
                padding: 40px 25px;
            }

            h1 {
                font-size: 26px;
            }

            .offline-icon {
                font-size: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        <h1>Vous êtes hors ligne</h1>
        <p>
            Impossible de se connecter à SIGMA Alumni pour le moment. 
            Vérifiez votre connexion Internet et réessayez.
        </p>
        
        <button class="retry-btn" onclick="retryConnection()">
            🔄 Réessayer
        </button>

        <div id="status" class="status"></div>

        <div class="features">
            <h3>Mode hors ligne</h3>
            <ul>
                <li>Certaines pages sont disponibles hors ligne</li>
                <li>Les données en cache restent accessibles</li>
                <li>Reconnexion automatique dès que possible</li>
            </ul>
        </div>
    </div>

    <script>
        const statusDiv = document.getElementById('status');

        // Vérifier la connexion au chargement
        window.addEventListener('load', () => {
            checkConnection();
        });

        // Écouter les changements de statut de connexion
        window.addEventListener('online', () => {
            showStatus('online', '✅ Connexion rétablie ! Redirection...');
            setTimeout(() => {
                window.location.href = '/dashboard.php';
            }, 1500);
        });

        window.addEventListener('offline', () => {
            showStatus('offline', '❌ Vous êtes toujours hors ligne');
        });

        function retryConnection() {
            showStatus('checking', '🔍 Vérification de la connexion...');
            
            // Tenter de charger une ressource légère pour vérifier la connexion
            fetch('/manifest.json', { 
                method: 'HEAD',
                cache: 'no-cache'
            })
            .then(response => {
                if (response.ok) {
                    showStatus('online', '✅ Connexion rétablie ! Redirection...');
                    setTimeout(() => {
                        window.location.href = '/dashboard.php';
                    }, 1000);
                } else {
                    showStatus('offline', '❌ Toujours hors ligne. Réessayez dans quelques instants.');
                }
            })
            .catch(() => {
                showStatus('offline', '❌ Impossible de se connecter. Vérifiez votre réseau.');
            });
        }

        function checkConnection() {
            if (navigator.onLine) {
                // Le navigateur pense être en ligne, mais vérifier vraiment
                fetch('/manifest.json', { 
                    method: 'HEAD',
                    cache: 'no-cache'
                })
                .then(response => {
                    if (response.ok) {
                        // Connexion OK, rediriger
                        window.location.href = '/dashboard.php';
                    }
                })
                .catch(() => {
                    // Pas vraiment en ligne
                });
            }
        }

        function showStatus(type, message) {
            statusDiv.className = 'status ' + type;
            statusDiv.textContent = message;
        }

        // Vérification périodique en arrière-plan
        setInterval(() => {
            if (navigator.onLine) {
                fetch('/manifest.json', { 
                    method: 'HEAD',
                    cache: 'no-cache'
                })
                .then(response => {
                    if (response.ok) {
                        window.location.href = '/dashboard.php';
                    }
                })
                .catch(() => {});
            }
        }, 10000); // Vérifier toutes les 10 secondes
    </script>
</body>
</html>
