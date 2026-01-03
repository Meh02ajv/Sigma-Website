<?php
// En-têtes anti-cache pour afficher les résultats en temps réel
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'config.php';
require_once __DIR__ . '/includes/election_results_helper.php';

// Définir le fuseau horaire de Lomé
date_default_timezone_set('Africa/Abidjan'); // Lomé utilise ce fuseau
$conn->query("SET time_zone = '+00:00'"); // UTC+0 pour l'Afrique de l'Ouest

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Veuillez vous connecter pour accéder à cette page.";
    header("Location: connexion.php");
    exit;
}

// Générer un token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fonction pour gérer les images de profil
function getProfilePicture($path) {
    if (!empty($path) && file_exists($path)) {
        return htmlspecialchars($path);
    }
    return 'img/profile_pic.jpeg';
}

// Récupérer l'élection en cours avec une logique simplifiée et cohérente
$current_date = date('Y-m-d H:i:s');

// DEBUG: Afficher la date actuelle pour vérification
error_log("Date actuelle serveur: " . $current_date);

// Chercher une élection active (en cours dans n'importe quelle phase)
$stmt = $conn->prepare("SELECT *, 
                        CASE 
                            WHEN ? < campaign_start THEN 'pending'
                            WHEN ? BETWEEN campaign_start AND start_date THEN 'campaign'
                            WHEN ? BETWEEN start_date AND end_date THEN 'voting'
                            WHEN ? > end_date AND ? < results_date THEN 'processing'
                            WHEN ? >= results_date THEN 'completed'
                        END as election_status
                        FROM elections 
                        WHERE ? BETWEEN campaign_start AND results_date
                        ORDER BY start_date DESC LIMIT 1");
$stmt->bind_param('sssssss', 
    $current_date, $current_date, $current_date, $current_date, $current_date, 
    $current_date, $current_date
);
$stmt->execute();
$election = $stmt->get_result()->fetch_assoc();
$stmt->close();

// DEBUG: Afficher l'élection trouvée
if ($election) {
    error_log("Élection trouvée: " . $election['title'] . " - Statut: " . $election['election_status']);
    error_log("Dates - Campagne: " . $election['campaign_start'] . " à " . $election['start_date']);
    error_log("Dates - Vote: " . $election['start_date'] . " à " . $election['end_date']);
    error_log("Dates - Résultats: " . $election['results_date']);
}

// Si aucune élection en cours, prendre la dernière avec résultats publiés
if (!$election) {
    $stmt = $conn->prepare("SELECT *, 'completed' as election_status
                           FROM elections 
                           WHERE results_published = 1 AND ? >= results_date
                           ORDER BY start_date DESC LIMIT 1");
    $stmt->bind_param('s', $current_date);
    $stmt->execute();
    $election = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Vérifier l'affichage des résultats avec validation de date
$show_results = false;
$results_processing = false;
if ($election) {
    $show_results = ($election['results_published'] == 1);
    
    $results_processing = ($election['election_status'] === 'completed' && 
                          $election['results_published'] == 0);
    
    // DEBUG: Afficher le statut des résultats
    error_log("Show results: " . ($show_results ? 'true' : 'false') . " - results_published: " . $election['results_published']);
    error_log("Results processing: " . ($results_processing ? 'true' : 'false'));
}

// Récupérer les candidats groupés par position avec plus d'informations
$candidates_by_position = [];
if ($election) {
    $stmt = $conn->prepare("SELECT c.*, u.full_name, u.studies, u.bac_year, u.profile_picture, 
                           COALESCE(v.vote_count, 0) as vote_count
                           FROM candidates c 
                           JOIN users u ON c.user_id = u.id 
                           LEFT JOIN (
                               SELECT candidate_id, position, COUNT(*) as vote_count 
                               FROM votes 
                               WHERE election_id = ? AND is_blank = 0
                               GROUP BY candidate_id, position
                           ) v ON c.id = v.candidate_id AND c.position = v.position
                           WHERE c.election_id = ? 
                           AND c.position IS NOT NULL 
                           AND c.position != '' 
                           AND c.position != '0'
                           ORDER BY c.position, vote_count DESC, u.full_name");
    $stmt->bind_param("ii", $election['id'], $election['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($candidate = $result->fetch_assoc()) {
        $candidates_by_position[$candidate['position']][] = $candidate;
    }
    $stmt->close();
}

// Vérifier si l'utilisateur a déjà voté avec plus de détails
$voted_positions = [];
$has_voted = false;
if ($election) {
    $stmt = $conn->prepare("SELECT position, candidate_id, is_blank, voted_at FROM votes 
                           WHERE election_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $election['id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $voted_positions[$row['position']] = $row;
    }
    $has_voted = count($voted_positions) > 0;
    $stmt->close();
}

// Gérer la soumission du vote avec validation renforcée des dates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote'])) {
    // LOG DÉTAILLÉ POUR LE DÉBOGAGE
    error_log("=== DÉBUT TRAITEMENT VOTE ===");
    error_log("POST reçu: " . print_r($_POST, true));
    error_log("User ID: " . $_SESSION['user_id']);
    error_log("Election ID: " . ($election ? $election['id'] : 'NULL'));
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("ERREUR: CSRF invalide");
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header("Location: elections.php");
        exit;
    }
    
    error_log("CSRF valide");
    
    // Validation RENFORCÉE des dates - Vérification explicite
    if (!$election) {
        $_SESSION['error'] = "Aucune élection active.";
        header("Location: elections.php");
        exit;
    }
    
    $current_date = date('Y-m-d H:i:s');
    $start_date = $election['start_date'];
    $end_date = $election['end_date'];
    
    // DEBUG: Log des dates pour le débogage
    error_log("Validation vote - Date actuelle: " . $current_date);
    error_log("Validation vote - Début vote: " . $start_date);
    error_log("Validation vote - Fin vote: " . $end_date);
    error_log("Validation vote - Statut: " . $election['election_status']);
    
    if ($election['election_status'] !== 'voting') {
        $_SESSION['error'] = "La période de vote n'est pas active. Statut: " . $election['election_status'];
        header("Location: elections.php");
        exit;
    }
    
    if ($current_date < $start_date) {
        $_SESSION['error'] = "Le vote n'a pas encore commencé. Il débutera le " . date('d/m/Y à H:i', strtotime($start_date));
        header("Location: elections.php");
        exit;
    }
    
    if ($current_date > $end_date) {
        $_SESSION['error'] = "La période de vote est terminée depuis le " . date('d/m/Y à H:i', strtotime($end_date));
        header("Location: elections.php");
        exit;
    }

    // SUPPRIMER CETTE VÉRIFICATION - Elle est trop restrictive
    // if ($has_voted) {
    //     $_SESSION['error'] = "Vous avez déjà voté pour cette élection. Le vote est définitif.";
    //     header("Location: elections.php");
    //     exit;
    // }

    // VÉRIFICATION PRÉLIMINAIRE : Récupérer toutes les positions déjà votées
    $existing_votes_check = $conn->prepare("SELECT DISTINCT position FROM votes 
                                           WHERE election_id = ? AND user_id = ?");
    $existing_votes_check->bind_param("ii", $election['id'], $_SESSION['user_id']);
    $existing_votes_check->execute();
    $existing_result = $existing_votes_check->get_result();
    $already_voted_positions_check = [];
    while ($row = $existing_result->fetch_assoc()) {
        $already_voted_positions_check[] = $row['position'];
    }
    $existing_votes_check->close();
    
    error_log("Positions déjà votées (vérification préliminaire): " . implode(', ', $already_voted_positions_check));
    
    $conn->begin_transaction();
    try {
        // Filtrer les positions valides (non vides, non numériques, non '0')
        $valid_positions = array_filter(array_keys($candidates_by_position), function($pos) {
            return !empty($pos) && $pos !== '0' && !is_numeric($pos);
        });
        
        if (empty($valid_positions)) {
            throw new Exception("Aucun poste valide pour voter.");
        }

        $votes_submitted = 0;
        $positions_to_vote = []; // Positions pour lesquelles l'utilisateur soumet un vote
        $already_voted_positions = []; // Positions déjà votées
        
        foreach ($valid_positions as $position) {
            if ($position === '0' || empty($position) || is_numeric($position)) {
                continue; // Ignorer les positions invalides
            }
            
            $input_name = 'vote_' . $position;
            
            // Si l'utilisateur n'a pas soumis de vote pour cette position, vérifier s'il a déjà voté
            if (!isset($_POST[$input_name])) {
                // Vérifier si l'utilisateur a déjà voté pour cette position
                $check_stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM votes 
                                              WHERE election_id = ? AND user_id = ? AND position = ?");
                $check_stmt->bind_param("iis", $election['id'], $_SESSION['user_id'], $position);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $existing_vote = $check_result->fetch_assoc();
                $check_stmt->close();
                
                if ($existing_vote['vote_count'] > 0) {
                    // Déjà voté, on ignore
                    $already_voted_positions[] = $position;
                    continue;
                } else {
                    // Pas encore voté et pas de soumission pour cette position
                    throw new Exception("Vote manquant pour la position : $position");
                }
            }
            
            // Vérifier immédiatement avec la liste pré-chargée
            if (in_array($position, $already_voted_positions_check)) {
                error_log("Tentative de voter à nouveau pour position déjà votée: $position");
                throw new Exception("Vous avez déjà voté pour la position : $position. Le vote est définitif.");
            }
            
            $positions_to_vote[] = $position;
            
            $value = $_POST[$input_name];
            error_log("Traitement vote - Position: $position, Valeur reçue: $value");
            
            // Déterminer si c'est un vote blanc
            $is_blank = ($value === 'blank' || $value === '0' || empty($value)) ? 1 : 0;
            $candidate_id = NULL;
            
            if (!$is_blank) {
                $candidate_id = (int)$value;
                // Si après conversion c'est 0, c'est un vote blanc
                if ($candidate_id === 0) {
                    $is_blank = 1;
                    $candidate_id = NULL;
                }
            }

            // Vérification de sécurité supplémentaire en DB (double vérification dans la transaction)
            $check_stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM votes 
                                          WHERE election_id = ? AND user_id = ? AND position = ?");
            $check_stmt->bind_param("iis", $election['id'], $_SESSION['user_id'], $position);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $existing_vote = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($existing_vote['vote_count'] > 0) {
                error_log("Vote déjà existant pour position $position, user " . $_SESSION['user_id']);
                throw new Exception("Vous avez déjà voté pour la position : $position. Le vote est définitif.");
            }

            // Valider le candidat
            if (!$is_blank) {
                $valid = false;
                foreach ($candidates_by_position[$position] as $candidate) {
                    if ($candidate['id'] == $candidate_id) {
                        $valid = true;
                        break;
                    }
                }
                if (!$valid) {
                    throw new Exception("Candidat invalide pour la position : $position");
                }
            }

            // Log avant insertion
            error_log("Insertion vote - Election: {$election['id']}, User: {$_SESSION['user_id']}, Position: $position, Candidate: " . ($candidate_id ?? 'NULL') . ", Blank: $is_blank");
            
            // Insérer le vote avec gestion correcte de NULL
            if ($is_blank) {
                // Pour les votes blancs, utiliser NULL explicitement
                $stmt = $conn->prepare("INSERT INTO votes (election_id, user_id, candidate_id, position, is_blank, voted_at)
                                    VALUES (?, ?, NULL, ?, 1, NOW())");
                $stmt->bind_param("iis", $election['id'], $_SESSION['user_id'], $position);
            } else {
                // Pour les votes normaux, utiliser l'ID du candidat
                $stmt = $conn->prepare("INSERT INTO votes (election_id, user_id, candidate_id, position, is_blank, voted_at)
                                    VALUES (?, ?, ?, ?, 0, NOW())");
                $stmt->bind_param("iiis", $election['id'], $_SESSION['user_id'], $candidate_id, $position);
            }
            
            if (!$stmt->execute()) {
                $error_msg = $stmt->error;
                error_log("ERREUR insertion vote: $error_msg");
                $stmt->close();
                throw new Exception("Erreur lors de l'enregistrement du vote pour $position: $error_msg");
            }
            $stmt->close();
            error_log("Vote inséré avec succès pour position: $position");

            $votes_submitted++;
        }

        // Vérifier qu'au moins un vote a été soumis
        if ($votes_submitted === 0) {
            throw new Exception("Aucun vote à enregistrer. Vous avez peut-être déjà voté pour toutes les positions.");
        }

        $conn->commit();
        
        // Message de succès personnalisé
        if (count($already_voted_positions) > 0) {
            $_SESSION['success'] = "Votre vote a été enregistré avec succès pour " . $votes_submitted . " position(s) !";
        } else {
            $_SESSION['success'] = "Votre vote a été enregistré avec succès !";
        }
        
        // Envoyer l'email de confirmation
        require_once 'send_email.php';
        $email_sent = sendVoteConfirmationEmail($_SESSION['user_id'], $election['title'], $positions_to_vote);
        
        if ($email_sent) {
            error_log("Email de confirmation envoyé à l'utilisateur ID: " . $_SESSION['user_id']);
        } else {
            error_log("Échec de l'envoi de l'email de confirmation à l'utilisateur ID: " . $_SESSION['user_id']);
        }

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erreur lors de l'enregistrement du vote : " . $e->getMessage();
    }
    header("Location: elections.php");
    exit;
}

// Récupérer les résultats si l'élection est terminée et les résultats publiés
$results = [];
if ($election && $show_results) {
    $results = getElectionResults($conn, (int)$election['id']);
}

// Récupérer les informations de l'utilisateur pour affichage
$stmt = $conn->prepare("SELECT full_name, profile_picture, email FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Définir les variables pour la photo de profil et le nom complet
$profile_picture = getProfilePicture($user_info['profile_picture'] ?? '');
$full_name = $user_info['full_name'] ?? 'Utilisateur';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Élections - SIGMA ALUMNI</title>
    <?php include 'includes/favicon.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #2563eb;
            --secondary-gold: #d4af37;
            --dark-blue: #1e3a8a;
            --light-gray: #f8fafc;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f7fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .election-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary-blue);
        }
        
        .election-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .candidate-card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .vote-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            transition: all 0.3s ease;
        }
        
        .vote-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .nav-tab {
            position: relative;
            padding-bottom: 8px;
        }
        
        .nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--secondary-gold);
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e2e8f0;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--secondary-gold));
        }
        
        .timeline-item {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            height: 100%;
            width: 2px;
            background-color: var(--primary-blue);
        }
        
        .timeline-dot {
            position: absolute;
            left: 0;
            top: 0;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: var(--secondary-gold);
            border: 3px solid white;
            box-shadow: 0 0 0 2px var(--primary-blue);
        }
        
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .profile-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-avatar img {
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .completed-election {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .processing-election {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <img src="img/image.png" alt="Logo SIGMA" class="h-10">
                <span class="text-xl font-semibold text-gray-800">SIGMA ALUMNI</span>
            </div>
            <nav class="flex items-center space-x-6">
                <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-home mr-1"></i> Accueil
                </a>
                <div class="user-info">
                    <div class="profile-avatar">
                        <img src="<?php echo $profile_picture; ?>" alt="<?php echo htmlspecialchars($full_name); ?>" class="h-8 w-8">
                    </div>
                    <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($full_name); ?></span>
                </div>
            </nav>
        </div>
    </header>

    <!-- Election Navigation -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
        <div class="container mx-auto px-4">
            <div class="flex space-x-8 border-b border-gray-200">
                <a href="#campaign" class="nav-tab py-4 text-gray-600 hover:text-blue-600 font-medium active">
                    <i class="fas fa-bullhorn mr-2"></i>Campagne
                </a>
                <?php if ($election && $election['election_status'] === 'voting'): ?>
                <a href="#vote" class="nav-tab py-4 text-gray-600 hover:text-blue-600 font-medium">
                    <i class="fas fa-vote-yea mr-2"></i>Vote
                </a>
                <?php endif; ?>
                <a href="#results" class="nav-tab py-4 text-gray-600 hover:text-blue-600 font-medium">
                    <i class="fas fa-chart-bar mr-2"></i>Résultats
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 flex-grow">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if ($election): ?>
            <!-- Election Status Banner -->
            <div class="<?php echo $show_results ? 'completed-election' : ($results_processing ? 'processing-election' : 'bg-gradient-to-r from-blue-600 to-blue-800'); ?> text-white rounded-xl p-6 mb-8 shadow-lg">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <h2 class="text-2xl font-bold mb-2"><?php echo $election['title']; ?></h2>
                        <p class="mb-4 opacity-90"><?php echo $election['description'] ?? 'Élection des membres du bureau'; ?></p>
                        
                        <!-- Timeline de l'élection -->
                        <div class="mb-4">
                            <div class="flex items-center space-x-4 mb-2">
                                <div class="text-sm font-medium <?php echo $election['election_status'] === 'campaign' ? 'text-yellow-300 font-bold' : 'opacity-80'; ?>">
                                    <i class="fas fa-bullhorn mr-1"></i> Campagne: <?php echo date('d/m/Y H:i', strtotime($election['campaign_start'])); ?> - <?php echo date('d/m/Y H:i', strtotime($election['start_date'])); ?>
                                </div>
                                <div class="text-sm font-medium <?php echo $election['election_status'] === 'voting' ? 'text-yellow-300 font-bold' : 'opacity-80'; ?>">
                                    <i class="fas fa-vote-yea mr-1"></i> Vote: <?php echo date('d/m/Y H:i', strtotime($election['start_date'])); ?> - <?php echo date('d/m/Y H:i', strtotime($election['end_date'])); ?>
                                </div>
                                <div class="text-sm font-medium <?php echo $election['election_status'] === 'completed' ? 'text-yellow-300 font-bold' : 'opacity-80'; ?>">
                                    <i class="fas fa-chart-bar mr-1"></i> Résultats: <?php echo date('d/m/Y H:i', strtotime($election['results_date'])); ?>
                                </div>
                            </div>
                            
                            <!-- Barre de progression -->
                            <?php
                                $total_duration = strtotime($election['results_date']) - strtotime($election['campaign_start']);
                                $elapsed = $current_date <= $election['results_date'] ? (strtotime($current_date) - strtotime($election['campaign_start'])) : $total_duration;
                                $progress = $total_duration > 0 ? ($elapsed / $total_duration) * 100 : 0;
                            ?>
                            <div class="w-full bg-white bg-opacity-30 rounded-full h-2.5">
                                <div id="progressBar" class="bg-yellow-400 h-2.5 rounded-full transition-all duration-1000 ease-out" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <div id="progressText" class="text-xs mt-1 opacity-80" 
                                 data-campaign-start="<?php echo strtotime($election['campaign_start']); ?>"
                                 data-start-date="<?php echo strtotime($election['start_date']); ?>"
                                 data-end-date="<?php echo strtotime($election['end_date']); ?>"
                                 data-results-date="<?php echo strtotime($election['results_date']); ?>"
                                 data-total-duration="<?php echo $total_duration; ?>"
                                 data-current-status="<?php echo $election['election_status']; ?>"
                                 data-show-results="<?php echo $show_results ? '1' : '0'; ?>">
                                <?php 
                                    $status_text = [
                                        'pending' => 'La campagne débutera bientôt',
                                        'campaign' => 'Période de campagne en cours',
                                        'voting' => 'Période de vote en cours',
                                        'processing' => 'Votes en cours de traitement',
                                        'completed' => 'Élection terminée - Résultats ' . ($show_results ? 'publiés' : 'en attente de publication')
                                    ];
                                    echo $status_text[$election['election_status']] . ' (' . round($progress) . '% écoulé)';
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Badge de statut -->
                    <div class="mt-4 md:mt-0">
                        <?php 
                            $status_classes = [
                                'pending' => 'bg-gray-500',
                                'campaign' => 'bg-purple-600',
                                'voting' => 'bg-green-600',
                                'processing' => 'bg-yellow-600',
                                'completed' => $show_results ? 'bg-green-600' : 'bg-yellow-600'
                            ];
                            $status_texts = [
                                'pending' => 'À venir',
                                'campaign' => 'Campagne',
                                'voting' => 'Vote en cours',
                                'processing' => 'Traitement',
                                'completed' => $show_results ? 'Terminée' : 'En attente'
                            ];
                        ?>
                        <span class="<?php echo $status_classes[$election['election_status']]; ?> text-white px-3 py-1 rounded-full text-sm font-semibold">
                            <?php echo $status_texts[$election['election_status']]; ?>
                            <?php if ($show_results): ?>
                                <i class="fas fa-check ml-1"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Campaign Section -->
            <section id="campaign" class="section active">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-bullhorn text-blue-600 mr-2"></i>Candidats
                    </h2>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <select id="positionFilter" class="appearance-none bg-white border border-gray-300 rounded-lg pl-4 pr-8 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous les postes</option>
                                <?php foreach ($candidates_by_position as $position => $candidates): ?>
                                    <option value="<?php echo htmlspecialchars($position); ?>"><?php echo htmlspecialchars($position); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($candidates_by_position)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($candidates_by_position as $position => $candidates): ?>
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="candidate-card bg-white rounded-xl shadow-md overflow-hidden" data-position="<?php echo htmlspecialchars($position); ?>">
                                <div class="relative">
                                    <img src="<?php echo getProfilePicture($candidate['profile_picture'] ?? ''); ?>" alt="Candidat" class="w-full h-48 object-cover">
                                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                                        <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                        <span class="text-yellow-300 font-medium"><?php echo htmlspecialchars($position); ?></span>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="flex items-center mb-4">
                                        <div class="profile-avatar">
                                            <img src="<?php echo getProfilePicture($candidate['profile_picture'] ?? ''); ?>" alt="Photo profil" class="h-12 w-12 border-2 border-blue-500">
                                        </div>
                                        <div class="ml-3">
                                            <div class="font-medium">Promo <?php echo htmlspecialchars($candidate['bac_year'] ?? 'N/A'); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($candidate['studies'] ?? 'Non spécifié'); ?></div>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 mb-4 line-clamp-3"><?php echo $candidate['description'] ?? 'Aucune description fournie'; ?></p>
                                    <div class="flex space-x-2">
                                        <?php if ($candidate['video_url']): ?>
                                            <button onclick="openVideoModal('<?php echo htmlspecialchars($candidate['video_url']); ?>')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition flex items-center justify-center">
                                                <i class="fas fa-play mr-2"></i> Vidéo
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-12 bg-white rounded-xl shadow-sm">
                    <i class="fas fa-users text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-500">Aucun candidat</h3>
                    <p class="text-gray-400 mt-2">Aucun candidat n'a été enregistré pour cette élection.</p>
                </div>
                <?php endif; ?>
            </section>

            <!-- Vote Section -->
            <?php if ($election['election_status'] === 'voting'): ?>
            <section id="vote" class="section hidden">
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-vote-yea text-blue-600 mr-2"></i>Formulaire de Vote
                        </h2>
                        
                        <?php if ($has_voted): ?>
                            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-green-700">
                                            Vous avez déjà voté pour certaines positions de cette élection.
                                        </p>
                                        <p class="text-sm text-green-700 mt-1">
                                            <strong>Les votes soumis sont définitifs et ne peuvent plus être modifiés.</strong>
                                        </p>
                                        <div class="mt-2 text-sm">
                                            <strong>Positions déjà votées :</strong>
                                            <?php echo implode(', ', array_keys($voted_positions)); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Afficher seulement les positions non encore votées -->
                            <?php 
                            $remaining_positions = array_diff(
                                array_filter(array_keys($candidates_by_position), 
                                            function($pos) { return !empty($pos) && $pos !== '0' && !is_numeric($pos); }),
                                array_keys($voted_positions)
                            );
                            ?>
                            
                            <?php if (empty($remaining_positions)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                                    <h3 class="text-xl font-medium text-gray-700">Vote complet</h3>
                                    <p class="text-gray-500 mt-2">Vous avez déjà voté pour toutes les positions de cette élection.</p>
                                </div>
                            <?php else: ?>
                                <!-- Afficher le formulaire seulement pour les positions restantes -->
                                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-blue-500"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700">
                                                Vous pouvez encore voter pour les positions suivantes : 
                                                <strong><?php echo implode(', ', $remaining_positions); ?></strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <form method="POST" id="voteFormPartial" action="elections.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="submit_vote" value="1">
                                    
                                    <?php $index = 1; foreach ($remaining_positions as $position): ?>
                                        <!-- Afficher seulement les positions non votées -->
                                        <div class="mb-8">
                                            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                                <span class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3"><?php echo $index++; ?></span>
                                                <?php echo htmlspecialchars($position); ?>
                                            </h3>
                                            
                                            <div class="space-y-4">
                                                <?php foreach ($candidates_by_position[$position] as $candidate): ?>
                                                    <label class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg hover:border-blue-400 cursor-pointer">
                                                        <input type="radio" name="vote_<?php echo htmlspecialchars($position); ?>" 
                                                            value="<?php echo $candidate['id']; ?>" 
                                                            class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300" 
                                                            required>
                                                        <div class="flex items-center space-x-3">
                                                            <div class="profile-avatar">
                                                                <img src="<?php echo getProfilePicture($candidate['profile_picture'] ?? ''); ?>" 
                                                                    alt="Candidat" class="h-10 w-10">
                                                            </div>
                                                            <div>
                                                                <div class="font-medium"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                                                                <div class="text-sm text-gray-500">
                                                                    Promo <?php echo htmlspecialchars($candidate['bac_year'] ?? 'Non spécifié'); ?> - <?php echo htmlspecialchars($candidate['studies'] ?? 'Non spécifié'); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php if ($candidate['video_url']): ?>
                                                            <button type="button" onclick="openVideoModal('<?php echo htmlspecialchars($candidate['video_url']); ?>')" 
                                                                    class="ml-auto text-blue-600 hover:text-blue-800">
                                                                <i class="fas fa-play-circle text-lg"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </label>
                                                <?php endforeach; ?>
                                                
                                                <label class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg hover:border-blue-400 cursor-pointer">
                                                    <input type="radio" name="vote_<?php echo htmlspecialchars($position); ?>" 
                                                        value="blank" 
                                                        class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                    <div class="flex items-center space-x-3 text-gray-500">
                                                        <div class="rounded-full h-10 w-10 border-2 border-gray-300 flex items-center justify-center">
                                                            <i class="fas fa-ban"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-medium">Vote blanc</div>
                                                            <div class="text-sm">Je ne souhaite pas voter pour ce poste</div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="mt-8">
                                        <button type="submit" id="submitVoteBtnPartial" 
                                                class="vote-btn w-full py-3 px-6 rounded-lg text-white font-bold text-lg flex items-center justify-center">
                                            <i class="fas fa-paper-plane mr-2"></i> 
                                            Soumettre mes votes pour les positions restantes
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <!-- Afficher le formulaire complet si l'utilisateur n'a jamais voté -->
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            Vous devez voter pour chaque poste. Une fois votre vote soumis, vous ne pourrez plus le modifier.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" id="voteForm" action="elections.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="submit_vote" value="1">
                                
                                <?php $index = 1; foreach ($candidates_by_position as $position => $candidates): ?>
                                    <div class="mb-8">
                                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                            <span class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3"><?php echo $index++; ?></span>
                                            <?php echo htmlspecialchars($position); ?>
                                        </h3>
                                        
                                        <div class="space-y-4">
                                            <?php foreach ($candidates as $candidate): ?>
                                                <label class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg hover:border-blue-400 cursor-pointer">
                                                    <input type="radio" name="vote_<?php echo htmlspecialchars($position); ?>" 
                                                        value="<?php echo $candidate['id']; ?>" 
                                                        class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300" 
                                                        required>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="profile-avatar">
                                                            <img src="<?php echo getProfilePicture($candidate['profile_picture'] ?? ''); ?>" 
                                                                alt="Candidat" class="h-10 w-10">
                                                        </div>
                                                        <div>
                                                            <div class="font-medium"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                                                            <div class="text-sm text-gray-500">
                                                                Promo <?php echo htmlspecialchars($candidate['bac_year'] ?? 'Non spécifié'); ?> - <?php echo htmlspecialchars($candidate['studies'] ?? 'Non spécifié'); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php if ($candidate['video_url']): ?>
                                                        <button type="button" onclick="openVideoModal('<?php echo htmlspecialchars($candidate['video_url']); ?>')" 
                                                                class="ml-auto text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-play-circle text-lg"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                            
                                            <label class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg hover:border-blue-400 cursor-pointer">
                                                <input type="radio" name="vote_<?php echo htmlspecialchars($position); ?>" 
                                                    value="blank" 
                                                    class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                <div class="flex items-center space-x-3 text-gray-500">
                                                    <div class="rounded-full h-10 w-10 border-2 border-gray-300 flex items-center justify-center">
                                                        <i class="fas fa-ban"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium">Vote blanc</div>
                                                        <div class="text-sm">Je ne souhaite pas voter pour ce poste</div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="mt-8">
                                    <button type="submit" name="submit_vote" id="submitVoteBtn" 
                                            class="vote-btn w-full py-3 px-6 rounded-lg text-white font-bold text-lg flex items-center justify-center">
                                        <i class="fas fa-paper-plane mr-2"></i> 
                                        Soumettre mon vote
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
            </section> 
            <?php endif; ?>
                    
            <!-- Results Section -->
            <section id="results" class="section hidden">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">
                                <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Résultats des Élections
                            </h2>
                            <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full" title="Dernière actualisation: <?php echo date('d/m/Y H:i:s'); ?>">
                                🔄 Mis à jour <?php echo date('H:i:s'); ?>
                            </span>
                        </div>
                        
                        <?php if (!$show_results): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-yellow-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            <?php if ($election['election_status'] === 'processing'): ?>
                                                Les votes sont en cours de traitement par le bureau. Les résultats seront disponibles à partir du <?php echo date('d/m/Y H:i', strtotime($election['results_date'])); ?>.
                                            <?php elseif ($election['election_status'] === 'completed' && !$election['results_published']): ?>
                                                L'élection est terminée. Les résultats seront publiés prochainement par le bureau.
                                            <?php else: ?>
                                                Les résultats ne sont pas encore disponibles.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center py-12">
                                <i class="fas fa-lock text-gray-300 text-5xl mb-4"></i>
                                <h3 class="text-xl font-medium text-gray-500">Résultats indisponibles pour le moment</h3>
                                <p class="text-gray-400 mt-2">
                                    <?php if ($election['election_status'] === 'processing'): ?>
                                        Publication prévue le <?php echo date('d/m/Y H:i', strtotime($election['results_date'])); ?>
                                    <?php elseif ($election['election_status'] === 'completed' && !$election['results_published']): ?>
                                        En attente de publication par le bureau
                                    <?php else: ?>
                                        Merci de patienter pendant que nous finalisons les résultats.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- Affichage des résultats -->
                            <?php foreach ($results as $position => $position_results): ?>
                                <div class="mb-8">
                                    <h3 class="text-xl font-semibold text-gray-800 mb-4"><?php echo htmlspecialchars($position); ?></h3>
                                    <div class="bg-white rounded-lg shadow-sm p-4">
                                        <?php
                                            $winner = $position_results[0]['name'] !== 'Votes blancs' ? $position_results[0] : ($position_results[1] ?? null);
                                            $winner_candidate = null;
                                            
                                            if ($winner && $winner['name'] !== 'Votes blancs') {
                                                foreach ($candidates_by_position[$position] as $candidate) {
                                                    if ($candidate['full_name'] === $winner['name']) {
                                                        $winner_candidate = $candidate;
                                                        break;
                                                    }
                                                }
                                            }
                                        ?>
                                        
                                        <?php if ($winner && $winner['name'] !== 'Votes blancs'): ?>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <div class="profile-avatar">
                                                        <img src="<?php echo getProfilePicture($winner_candidate['profile_picture'] ?? ''); ?>" 
                                                            alt="Gagnant" class="h-12 w-12 border-2 border-yellow-400">
                                                    </div>
                                                    <div>
                                                        <div class="font-medium"><?php echo $winner['name']; ?></div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($winner['percentage']); ?>% des votes
                                                            (<?php echo $winner['votes']; ?> vote<?php echo $winner['votes'] > 1 ? 's' : ''; ?>)
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                                    <?php echo ($position === 'Président') ? 'Élu' : 'Élue'; ?>
                                                </span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $winner['percentage']; ?>%"></div>
                                            </div>
                                        <?php elseif ($winner): ?>
                                            <div class="text-center py-4 text-gray-500">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                Les votes blancs ont remporté cette élection avec <?php echo $winner['percentage']; ?>% des votes.
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-6 space-y-3">
                                            <?php foreach ($position_results as $result): ?>
                                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                    <div class="flex items-center space-x-3">
                                                        <?php if ($result['name'] !== 'Votes blancs'): ?>
                                                            <div class="profile-avatar">
                                                                <img src="<?php echo getProfilePicture($result['profile_picture'] ?? ''); ?>" 
                                                                    alt="Candidat" class="h-8 w-8">
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="rounded-full h-8 w-8 border-2 border-gray-300 flex items-center justify-center text-gray-400">
                                                                <i class="fas fa-ban text-sm"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <span><?php echo $result['name']; ?></span>
                                                    </div>
                                                    <div class="flex items-center space-x-4">
                                                        <div class="text-sm font-medium">
                                                            <?php echo $result['votes']; ?> vote<?php echo $result['votes'] > 1 ? 's' : ''; ?>
                                                        </div>
                                                        <div class="text-sm font-bold text-blue-600">
                                                            <?php echo $result['percentage']; ?>%
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Graphique des résultats -->
                            <div class="bg-white rounded-lg shadow-sm p-4 mt-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Répartition des votes par position</h3>
                                <div style="position: relative; height: 400px; max-height: 50vh;">
                                    <canvas id="resultsChart"></canvas>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- Aucune élection en cours -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-yellow-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Aucune élection active pour le moment. Revenez plus tard pour participer aux prochaines élections.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="text-center py-12">
                <i class="fas fa-calendar-times text-gray-300 text-5xl mb-4"></i>
                <h3 class="text-xl font-medium text-gray-500">Pas d'élection en cours</h3>
                <p class="text-gray-400 mt-2">Les prochaines élections seront annoncées par le bureau.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Video Modal -->
    <div id="videoModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden" onclick="if(event.target === this) closeVideoModal();">
        <div class="bg-white rounded-lg p-4 max-w-4xl w-full mx-4 max-h-[90vh] overflow-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Présentation du candidat</h3>
                <button onclick="closeVideoModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="videoContainer" class="w-full" style="min-height: 400px;">
                <!-- Le contenu vidéo sera inséré ici -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation entre les onglets
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    document.querySelectorAll('.section').forEach(section => {
                        section.classList.add('hidden');
                        section.classList.remove('active');
                    });
                    
                    const target = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(target);
                    if (targetElement) {
                        targetElement.classList.remove('hidden');
                        targetElement.classList.add('active');
                    }
                    
                    // Sauvegarder l'onglet actif dans sessionStorage
                    sessionStorage.setItem('activeElectionTab', target);
                });
            });
            
            // Restaurer l'onglet actif s'il existe dans sessionStorage
            const activeTab = sessionStorage.getItem('activeElectionTab');
            if (activeTab && document.getElementById(activeTab)) {
                document.querySelector(`.nav-tab[href="#${activeTab}"]`)?.click();
            }
            
            // Filtrage des candidats par position
            const positionFilter = document.getElementById('positionFilter');
            if (positionFilter) {
                positionFilter.addEventListener('change', function() {
                    const selectedPosition = this.value;
                    document.querySelectorAll('.candidate-card').forEach(card => {
                        if (!selectedPosition || card.dataset.position === selectedPosition) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
            
            // Fonction de validation commune pour les formulaires de vote
            function setupVoteForm(formId, buttonId) {
                const voteForm = document.getElementById(formId);
                if (!voteForm) return;
                
                let isSubmitting = false;
                
                voteForm.addEventListener('submit', function(e) {
                    console.log('Formulaire soumis:', formId);
                    
                    // Empêcher les double-soumissions
                    if (isSubmitting) {
                        console.log('Soumission déjà en cours, blocage');
                        e.preventDefault();
                        return false;
                    }
                    
                    // Vérifier que toutes les positions ont été votées
                    const requiredRadios = Array.from(voteForm.querySelectorAll('input[type="radio"][required]'));
                    const groups = new Set(requiredRadios.map(radio => radio.name));
                    let allValid = true;
                    
                    console.log('Groupes à vérifier:', Array.from(groups));
                    
                    groups.forEach(groupName => {
                        const checked = voteForm.querySelector(`input[name="${groupName}"]:checked`);
                        if (!checked) {
                            allValid = false;
                            console.log('Groupe non voté:', groupName);
                            const firstRadio = voteForm.querySelector(`input[name="${groupName}"]`);
                            if (firstRadio) {
                                firstRadio.focus();
                                firstRadio.closest('label')?.classList.add('border-red-400', 'bg-red-50');
                            }
                        }
                    });
                    
                    if (!allValid) {
                        e.preventDefault();
                        alert('Veuillez voter pour toutes les positions avant de soumettre.');
                        console.log('Validation échouée');
                        return false;
                    }
                    
                    // Confirmation avant soumission
                    if (!confirm('Êtes-vous sûr de vouloir soumettre votre vote ? Cette action est définitive et ne pourra pas être modifiée.')) {
                        e.preventDefault();
                        console.log('Utilisateur a annulé');
                        return false;
                    }
                    
                    // Marquer comme en cours de soumission
                    isSubmitting = true;
                    console.log('Validation réussie, soumission en cours...');
                    
                    // Désactiver le bouton et afficher un message
                    const submitBtn = document.getElementById(buttonId);
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Enregistrement en cours...';
                        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                    }
                    
                    // Permettre la soumission du formulaire
                    return true;
                });
                
                // Highlight les radios lorsqu'ils sont focus ou invalid
                voteForm.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.addEventListener('focus', function() {
                        this.closest('label')?.classList.add('ring-2', 'ring-blue-500');
                    });
                    
                    radio.addEventListener('blur', function() {
                        this.closest('label')?.classList.remove('ring-2', 'ring-blue-500');
                    });
                    
                    radio.addEventListener('invalid', function() {
                        this.closest('label')?.classList.add('border-red-400', 'bg-red-50');
                    });
                    
                    radio.addEventListener('change', function() {
                        voteForm.querySelectorAll('label').forEach(label => {
                            label.classList.remove('border-red-400', 'bg-red-50');
                        });
                    });
                });
            }
            
            // Appliquer la validation aux deux formulaires
            setupVoteForm('voteForm', 'submitVoteBtn');
            setupVoteForm('voteFormPartial', 'submitVoteBtnPartial');
            
            // Initialisation du graphique des résultats
            const ctx = document.getElementById('resultsChart')?.getContext('2d');
            if (ctx && <?php echo $show_results ? 'true' : 'false'; ?>) {
                const positions = <?php echo json_encode(array_keys($results)); ?>;
                const datasets = [];
                const colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6'];
                
                // Créer un dataset pour chaque candidat
                <?php if ($show_results): ?>
                    let colorIndex = 0;
                    <?php foreach ($results as $position => $position_results): ?>
                        <?php foreach ($position_results as $i => $result): ?>
                            <?php if ($result['name'] !== 'Votes blancs'): ?>
                                datasets.push({
                                    label: '<?php echo addslashes($result['name']); ?> (<?php echo addslashes($position); ?>)',
                                    data: positions.map(p => p === '<?php echo $position; ?>' ? <?php echo $result['votes']; ?> : 0),
                                    backgroundColor: colors[colorIndex % colors.length],
                                    stack: 'stack_<?php echo $position; ?>'
                                });
                                colorIndex++;
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    
                    // Ajouter les votes blancs comme un dataset séparé
                    datasets.push({
                        label: 'Votes blancs',
                        data: positions.map(position => {
                            const blankVote = <?php echo json_encode($results); ?>[position].find(r => r.name === 'Votes blancs');
                            return blankVote ? blankVote.votes : 0;
                        }),
                        backgroundColor: '#94a3b8',
                        stack: 'stack_blanc'
                    });
                <?php endif; ?>
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: positions,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 10,
                                bottom: 10
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    maxRotation: 0,
                                    autoSkip: false
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    font: {
                                        size: 11
                                    },
                                    callback: function(value) {
                                        if (Number.isInteger(value)) {
                                            return value;
                                        }
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Nombre de votes',
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    padding: 10,
                                    font: {
                                        size: 11
                                    },
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                },
                                maxHeight: 80
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.raw + ' vote' + (context.raw !== 1 ? 's' : '');
                                        return label;
                                    }
                                }
                            },
                            title: {
                                display: false
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        },
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        }
                    }
                });
            }

            // Ouverture de la modale vidéo
            window.openVideoModal = function(videoUrl) {
                const modal = document.getElementById('videoModal');
                const videoContainer = document.getElementById('videoContainer');
                
                if (!videoUrl) {
                    alert('Aucune vidéo disponible pour ce candidat.');
                    return;
                }
                
                // Nettoyer le conteneur
                videoContainer.innerHTML = '';
                
                // Déterminer le type de vidéo et créer le lecteur approprié
                let videoElement;
                
                // Vérifier si c'est une vidéo YouTube
                if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
                    const match = videoUrl.match(regExp);
                    
                    if (match && match[2] && match[2].length === 11) {
                        const videoId = match[2];
                        videoElement = document.createElement('iframe');
                        videoElement.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1`;
                        videoElement.width = '100%';
                        videoElement.height = '500';
                        videoElement.frameBorder = '0';
                        videoElement.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                        videoElement.allowFullscreen = true;
                        videoElement.className = 'rounded-lg';
                    } else {
                        alert('Lien YouTube invalide. Format attendu: https://www.youtube.com/watch?v=VIDEO_ID');
                        return;
                    }
                }
                // Vérifier si c'est une vidéo locale (fichier uploadé)
                else if (videoUrl.startsWith('uploads/') || videoUrl.includes('.mp4') || videoUrl.includes('.webm') || videoUrl.includes('.ogg')) {
                    videoElement = document.createElement('video');
                    videoElement.controls = true;
                    videoElement.autoplay = true;
                    videoElement.className = 'w-full h-auto max-h-[70vh] rounded-lg';
                    videoElement.style.maxHeight = '500px';
                    
                    const source = document.createElement('source');
                    source.src = videoUrl;
                    
                    // Détecter le type MIME
                    if (videoUrl.endsWith('.mp4')) {
                        source.type = 'video/mp4';
                    } else if (videoUrl.endsWith('.webm')) {
                        source.type = 'video/webm';
                    } else if (videoUrl.endsWith('.ogg')) {
                        source.type = 'video/ogg';
                    }
                    
                    videoElement.appendChild(source);
                    
                    // Gestion des erreurs de chargement
                    videoElement.onerror = function() {
                        videoContainer.innerHTML = `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
                                <i class="fas fa-exclamation-circle text-red-500 text-4xl mb-3"></i>
                                <p class="text-red-700 font-medium mb-2">Impossible de charger la vidéo</p>
                                <p class="text-red-600 text-sm">Le fichier vidéo est introuvable ou le format n'est pas supporté.</p>
                                <p class="text-gray-600 text-xs mt-2">Lien: ${videoUrl}</p>
                            </div>
                        `;
                    };
                    
                    videoElement.onloadeddata = function() {
                        console.log('Vidéo chargée avec succès:', videoUrl);
                    };
                }
                // Autres plateformes (Vimeo, Dailymotion, etc.)
                else if (videoUrl.includes('vimeo.com')) {
                    const vimeoRegex = /vimeo\.com\/(\d+)/;
                    const match = videoUrl.match(vimeoRegex);
                    if (match && match[1]) {
                        videoElement = document.createElement('iframe');
                        videoElement.src = `https://player.vimeo.com/video/${match[1]}?autoplay=1`;
                        videoElement.width = '100%';
                        videoElement.height = '500';
                        videoElement.frameBorder = '0';
                        videoElement.allow = 'autoplay; fullscreen; picture-in-picture';
                        videoElement.allowFullscreen = true;
                        videoElement.className = 'rounded-lg';
                    } else {
                        alert('Lien Vimeo invalide.');
                        return;
                    }
                }
                else {
                    videoContainer.innerHTML = `
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-3"></i>
                            <p class="text-yellow-700 font-medium mb-2">Format de vidéo non supporté</p>
                            <p class="text-gray-600 text-sm mb-3">Seuls YouTube, Vimeo et les fichiers MP4/WebM locaux sont supportés.</p>
                            <a href="${videoUrl}" target="_blank" class="text-blue-600 hover:underline text-sm">
                                <i class="fas fa-external-link-alt mr-1"></i>Ouvrir le lien dans un nouvel onglet
                            </a>
                        </div>
                    `;
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                    return;
                }
                
                if (videoElement) {
                    videoContainer.appendChild(videoElement);
                }
                
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            // Fermeture de la modale vidéo
            window.closeVideoModal = function() {
                const modal = document.getElementById('videoModal');
                const videoContainer = document.getElementById('videoContainer');
                
                // Nettoyer complètement le conteneur
                videoContainer.innerHTML = '';
                
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            // Fermeture au clavier (Escape)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !document.getElementById('videoModal').classList.contains('hidden')) {
                    closeVideoModal();
                }
            });

            // Mise à jour automatique de la barre de progression
            const progressText = document.getElementById('progressText');
            if (progressText) {
                function updateProgress() {
                    const campaignStart = parseInt(progressText.dataset.campaignStart);
                    const startDate = parseInt(progressText.dataset.startDate);
                    const endDate = parseInt(progressText.dataset.endDate);
                    const resultsDate = parseInt(progressText.dataset.resultsDate);
                    const totalDuration = parseInt(progressText.dataset.totalDuration);
                    const showResults = progressText.dataset.showResults === '1';
                    
                    const now = Math.floor(Date.now() / 1000); // Timestamp actuel en secondes
                    let elapsed = now - campaignStart;
                    if (elapsed < 0) elapsed = 0;
                    if (elapsed > totalDuration) elapsed = totalDuration;
                    
                    const progress = totalDuration > 0 ? (elapsed / totalDuration) * 100 : 0;
                    
                    // Déterminer le statut actuel
                    let currentStatus = 'pending';
                    let statusText = 'La campagne débutera bientôt';
                    
                    if (now < campaignStart) {
                        currentStatus = 'pending';
                        statusText = 'La campagne débutera bientôt';
                    } else if (now >= campaignStart && now < startDate) {
                        currentStatus = 'campaign';
                        statusText = 'Période de campagne en cours';
                    } else if (now >= startDate && now < endDate) {
                        currentStatus = 'voting';
                        statusText = 'Période de vote en cours';
                    } else if (now >= endDate && now < resultsDate) {
                        currentStatus = 'processing';
                        statusText = 'Votes en cours de traitement';
                    } else if (now >= resultsDate) {
                        currentStatus = 'completed';
                        statusText = 'Élection terminée - Résultats ' + (showResults ? 'publiés' : 'en attente de publication');
                    }
                    
                    // Mettre à jour la barre de progression
                    const progressBar = document.getElementById('progressBar');
                    if (progressBar) {
                        progressBar.style.width = progress.toFixed(2) + '%';
                    }
                    
                    // Mettre à jour le texte
                    progressText.textContent = statusText + ' (' + Math.round(progress) + '% écoulé)';
                    
                    // Si le statut a changé, recharger la page pour mettre à jour l'interface
                    if (currentStatus !== progressText.dataset.currentStatus) {
                        console.log('Changement de statut détecté:', progressText.dataset.currentStatus, '->', currentStatus);
                        // Sauvegarder l'onglet actif avant le rechargement
                        const activeSection = document.querySelector('.section.active');
                        if (activeSection) {
                            sessionStorage.setItem('activeElectionTab', activeSection.id);
                        }
                        // Recharger après 2 secondes pour laisser l'utilisateur voir le changement
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                }
                
                // Mettre à jour immédiatement
                updateProgress();
                
                // Mettre à jour toutes les 10 secondes
                setInterval(updateProgress, 10000);
            }
        });
    </script>
</body>
</html>