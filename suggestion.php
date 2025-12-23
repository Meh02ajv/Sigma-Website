<?php
require 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}
$user_email = $_SESSION['user_email'];
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggestion - Sigma Yearbook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --border-radius: 8px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-color);
            line-height: 1.6;
            padding: 20px;
        }

        .suggestion-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            position: relative;
        }

        .suggestion-header {
            background: var(--primary-color);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .suggestion-header h1 {
            font-weight: 600;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .suggestion-header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .back-arrow {
            position: absolute;
            top: 25px;
            left: 25px;
            color: white;
            font-size: 1.2rem;
            text-decoration: none;
            transition: var(--transition);
            z-index: 10;
        }

        .back-arrow:hover {
            color: var(--secondary-color);
            transform: translateX(-3px);
        }

        .suggestion-content {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
            font-size: 0.95rem;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f9f9f9;
        }

        .input-icon input {
            padding-left: 40px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
        }

        .form-control[readonly] {
            background-color: #eee;
            cursor: not-allowed;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
            padding: 15px;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: var(--border-radius);
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            width: 100%;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }

        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .message {
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
        }

        .success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }

        .error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .char-counter {
            font-size: 0.8rem;
            color: #7f8c8d;
            text-align: right;
            margin-top: 5px;
        }

        @media (max-width: 576px) {
            .suggestion-header {
                padding: 20px 15px;
            }
            
            .suggestion-header h1 {
                font-size: 1.5rem;
            }
            
            .suggestion-content {
                padding: 20px;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            textarea.form-control {
                min-height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="suggestion-container">
        <a href="settings.php" class="back-arrow" aria-label="Retour aux paramètres">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="suggestion-header">
            <h1>Faire une suggestion</h1>
            <p>Partagez vos idées pour améliorer Sigma Yearbook</p>
        </div>
        
        <div class="suggestion-content">
            <?php if ($success || $error): ?>
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($success ?: $error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="submit_suggestion.php" id="suggestionForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="email">Votre adresse e-mail</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="suggestion">Votre suggestion</label>
                    <textarea id="suggestion" name="suggestion" class="form-control" placeholder="Décrivez votre suggestion en détail..." required></textarea>
                    <div class="char-counter"><span id="charCount">0</span>/1000 caractères</div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Envoyer la suggestion
                </button>
            </form>
        </div>
    </div>

    <script>
        const submitBtn = document.getElementById('submitBtn');
        const suggestionForm = document.getElementById('suggestionForm');
        const suggestionTextarea = document.getElementById('suggestion');
        const charCount = document.getElementById('charCount');
        const maxChars = 1000;

        // Compteur de caractères
        suggestionTextarea.addEventListener('input', function() {
            const currentChars = this.value.length;
            charCount.textContent = currentChars;
            
            if (currentChars > maxChars) {
                charCount.style.color = 'var(--error-color)';
            } else {
                charCount.style.color = '#7f8c8d';
            }
        });

        suggestionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const suggestion = suggestionTextarea.value.trim();
            const now = new Date();
            const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const date = now.toLocaleDateString('fr-FR');

            // Validation
            if (!suggestion) {
                alert('Veuillez entrer votre suggestion avant de soumettre.');
                suggestionTextarea.focus();
                return;
            }

            if (suggestion.length > maxChars) {
                alert(`Votre suggestion dépasse la limite de ${maxChars} caractères.`);
                return;
            }

            // Confirmation avant envoi
            const confirmationMessage = `Confirmez-vous l'envoi de cette suggestion ?\n\n` +
                                     `Email : ${email}\n` +
                                     `Date : ${date} à ${time}\n\n` +
                                     `Suggestion :\n${suggestion.substring(0, 100)}${suggestion.length > 100 ? '...' : ''}`;

            if (confirm(confirmationMessage)) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
                this.submit();
            }
        });

        // Animation pour les champs invalides
        document.querySelectorAll('[required]').forEach(input => {
            input.addEventListener('invalid', () => {
                input.style.borderColor = 'var(--error-color)';
                setTimeout(() => {
                    input.style.borderColor = '#ddd';
                }, 2000);
            });
        });
    </script>
</body>
</html>