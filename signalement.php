<?php
require 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit;
}
$reporter_email = $_SESSION['user_email'];
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
    <title>Signaler un utilisateur - Sigma Yearbook</title>
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

        .report-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            position: relative;
        }

        .report-header {
            background: var(--accent-color);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .report-header h1 {
            font-weight: 600;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .report-header p {
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
            color: var(--light-color);
            transform: translateX(-3px);
        }

        .report-content {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
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

        .btn-danger {
            background: var(--accent-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
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

        .report-warning {
            background: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--accent-color);
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: var(--dark-color);
        }

        @media (max-width: 576px) {
            .report-header {
                padding: 20px 15px;
            }
            
            .report-header h1 {
                font-size: 1.5rem;
            }
            
            .report-content {
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
    <div class="report-container">
        <a href="settings.php" class="back-arrow" aria-label="Retour aux paramètres">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="report-header">
            <h1>Signaler un utilisateur</h1>
            <p>Signalez un comportement inapproprié</p>
        </div>
        
        <div class="report-content">
            <?php if ($success || $error): ?>
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($success ?: $error); ?>
                </div>
            <?php endif; ?>
            
            <div class="report-warning">
                <i class="fas fa-exclamation-triangle"></i> Veuillez n'utiliser cette fonctionnalité qu'en cas de comportement inapproprié. Les signalements abusifs peuvent entraîner des sanctions.
            </div>
            
            <form method="POST" action="submit_report.php" id="reportForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="reporter_email">Votre adresse e-mail</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="reporter_email" name="reporter_email" class="form-control" value="<?php echo htmlspecialchars($reporter_email); ?>" readonly required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="reported_user">Nom ou e-mail de l'utilisateur à signaler</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="reported_user" name="reported_user" class="form-control" placeholder="Identifiant de l'utilisateur concerné" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="reason">Motif du signalement</label>
                    <textarea id="reason" name="reason" class="form-control" placeholder="Décrivez en détail le comportement problématique..." required></textarea>
                    <div class="char-counter"><span id="charCount">0</span>/1000 caractères</div>
                </div>
                
                <button type="submit" class="btn btn-danger" id="submitBtn">
                    <i class="fas fa-exclamation-circle"></i> Envoyer le signalement
                </button>
            </form>
        </div>
    </div>

    <script>
        const submitBtn = document.getElementById('submitBtn');
        const reportForm = document.getElementById('reportForm');
        const reasonTextarea = document.getElementById('reason');
        const charCount = document.getElementById('charCount');
        const maxChars = 1000;

        // Compteur de caractères
        reasonTextarea.addEventListener('input', function() {
            const currentChars = this.value.length;
            charCount.textContent = currentChars;
            
            if (currentChars > maxChars) {
                charCount.style.color = 'var(--error-color)';
            } else {
                charCount.style.color = '#7f8c8d';
            }
        });

        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('reporter_email').value;
            const reportedUser = document.getElementById('reported_user').value.trim();
            const reason = reasonTextarea.value.trim();
            const now = new Date();
            const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const date = now.toLocaleDateString('fr-FR');

            // Validation
            if (!reportedUser) {
                alert('Veuillez indiquer l\'utilisateur que vous souhaitez signaler.');
                document.getElementById('reported_user').focus();
                return;
            }

            if (!reason) {
                alert('Veuillez décrire le motif de votre signalement.');
                reasonTextarea.focus();
                return;
            }

            if (reason.length > maxChars) {
                alert(`Votre description dépasse la limite de ${maxChars} caractères.`);
                return;
            }

            // Confirmation avant envoi
            const confirmationMessage = `Confirmez-vous l'envoi de ce signalement ?\n\n` +
                                     `Email : ${email}\n` +
                                     `Utilisateur signalé : ${reportedUser}\n` +
                                     `Date : ${date} à ${time}\n\n` +
                                     `Motif :\n${reason.substring(0, 100)}${reason.length > 100 ? '...' : ''}`;

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