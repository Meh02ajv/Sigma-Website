<?php
require 'config.php';
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Version publique - pas de vérification de session

// Filtres limités : nom et année seulement
$bac_year = isset($_GET['bac_year']) ? sanitize($_GET['bac_year']) : '';
$search_name = isset($_GET['search_name']) ? sanitize($_GET['search_name']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sort_by = 'full_name';
$sort_order = 'ASC';
$limit = 12;
$offset = ($page - 1) * $limit;

// Build SQL query
$query = "SELECT id, full_name, email, studies, bac_year, profile_picture
          FROM users WHERE 1=1";
$params = [];
$types = '';

if ($search_name) {
    $query .= " AND (full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search_name%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($bac_year) {
    $query .= " AND bac_year = ?";
    $params[] = $bac_year;
    $types .= 'i';
}

// Compter le total
$count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
if ($search_name) {
    $count_query .= " AND (full_name LIKE '%$search_name%' OR email LIKE '%$search_name%')";
}
if ($bac_year) {
    $count_query .= " AND bac_year = $bac_year";
}
$count_result = $conn->query($count_query);
$total_users = $count_result->fetch_assoc()['total'];
$has_more = ($offset + $limit) < $total_users;

$query .= " ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Si requête AJAX, renvoyer JSON
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'users' => $users,
        'has_more' => $has_more
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yearbook Sigma - Annuaire Public</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --error-color: #e74c3c;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            height: 40px;
            width: auto;
        }
        
        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
            color: white;
        }
        
        .nav-icons {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .nav-icons a {
            color: white;
            font-size: 1.2rem;
            transition: color 0.3s;
            position: relative;
            text-decoration: none;
        }
        
        .nav-icons a:hover {
            color: var(--secondary-color);
        }
        
        .filters-container {
            background-color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            position: sticky;
            top: 78px;
            z-index: 900;
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
            margin-right: 1rem;
        }
        
        .filter-group:last-child {
            margin-right: 0;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            background-color: white;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .clear-btn, .apply-filters-btn {
            background-color: var(--light-color);
            color: var(--dark-color);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            align-self: flex-end;
        }
        
        .apply-filters-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .clear-btn:hover {
            background-color: #e0e0e0;
        }
        
        .apply-filters-btn:hover {
            background-color: #2980b9;
        }
        
        .main-content {
            max-width: 1200px;
            margin: 2rem auto 0;
            padding: 0 2rem 2rem;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .profile-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            pointer-events: none;
            opacity: 0.95;
        }
        
        .profile-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        
        .profile-info {
            padding: 1.5rem;
        }
        
        .profile-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0 0 0.5rem;
            font-size: 1.1rem;
        }
        
        .profile-email {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            word-break: break-all;
        }
        
        .profile-detail {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .profile-detail i {
            margin-right: 0.5rem;
            color: var(--secondary-color);
            width: 20px;
            text-align: center;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            grid-column: 1 / -1;
            font-style: italic;
            color: #7f8c8d;
        }
        
        .not-found {
            text-align: center;
            padding: 2rem;
            grid-column: 1 / -1;
            color: #7f8c8d;
        }
        
        .filter-toggle {
            display: none;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            margin: 1rem auto;
            width: auto;
            max-width: 300px;
            font-weight: 500;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.3);
            transition: all 0.3s;
        }
        
        .filter-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.4);
        }
        
        .filter-toggle i {
            margin-right: 0.5rem;
        }

        .hidden {
            display: none !important;
        }
        
        /* NOUVEAU : Bouton de fermeture des filtres pour desktop */
        .close-filters-desktop {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #ddd;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: var(--dark-color);
            cursor: pointer;
            z-index: 2001;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .close-filters-desktop:hover {
            background: #f0f0f0;
            color: var(--accent-color);
            transform: rotate(90deg);
        }
        
        .filters-container.hidden {
            display: none;
        }
        
        .show-filters-btn {
            display: block;
            margin: 1rem auto;
            padding: 0.8rem 1.5rem;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .show-filters-btn:hover {
            background-color: #2980b9;
        }
        
        .show-filters-btn.hidden {
            display: none;
        }
        
        .load-more-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.3);
            transition: all 0.3s;
        }
        
        .load-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.4);
        }
        
        .load-more-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .load-more-btn i {
            margin-right: 0.5rem;
        }

        /* Mobile interface */
        @media (max-width: 768px) {
            .logo-text {
                font-size: 1.2rem;
            }
            
            .nav-icons {
                gap: 1rem;
            }
            
            .filters-container {
                position: fixed;
                top: 0;
                left: -100%;
                width: 85%;
                height: 100vh;
                overflow-y: auto;
                padding: 2rem 1.5rem;
                transition: left 0.3s ease;
                z-index: 2000;
                background-color: white;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .filters-container.active {
                left: 0;
            }
            
            .filters {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .filter-toggle {
                display: block;
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 0 1rem;
            }
            
            .profile-card {
                margin-bottom: 0.5rem;
            }
            
            .profile-image {
                height: 200px;
            }
            
            /* Overlay pour les filtres */
            .filter-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 1999;
            }
            
            .filter-overlay.active {
                display: block;
            }
            
            /* Bouton de fermeture pour les filtres mobile */
            .close-filters {
                position: absolute;
                top: 1rem;
                right: 1rem;
                font-size: 1.5rem;
                background: none;
                border: none;
                color: var(--dark-color);
                cursor: pointer;
                z-index: 2001;
            }
            
            /* Cacher le bouton de fermeture desktop sur mobile */
            .close-filters-desktop {
                display: none !important;
            }
            
            .profile-image {
                height: 160px;
            }
        }

        /* Desktop - Afficher le bouton de fermeture */
        @media (min-width: 769px) {
            .close-filters-desktop {
                display: flex;
            }
            
            .close-filters {
                display: none;
            }
            
            .filter-toggle {
                display: none !important;
            }
        }

        /* Améliorations pour très petits écrans */
        @media (max-width: 480px) {
            header {
                padding: 0.8rem 1rem;
            }
            
            .logo {
                height: 32px;
            }
            
            .nav-icons a {
                font-size: 1rem;
            }
            
            .profile-info {
                padding: 1rem;
            }
            
            .profile-name {
                font-size: 1rem;
            }
            
            .profile-email {
                font-size: 0.8rem;
            }
            
            .profile-detail {
                font-size: 0.85rem;
            }
        }

        /* Animation pour le chargement */
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .profile-card {
            animation: slideIn 0.3s ease;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="img/white_logo.png" alt="Logo Sigma" class="logo">
            <span class="logo-text">Yearbook Sigma</span>
        </div>
        <div class="nav-icons">
            <a href="accueil.php" aria-label="Quitter"><i class="fas fa-times"></i> Quitter</a>
        </div>
    </header>
    
    <!-- Bouton pour afficher les filtres quand ils sont masqués -->
    <button class="show-filters-btn hidden" id="showFiltersBtn">
        <i class="fas fa-filter"></i> Afficher les filtres
    </button>
    
    <!-- Bouton pour ouvrir les filtres sur mobile -->
    <button class="filter-toggle" id="filterToggle">
        <i class="fas fa-filter"></i> Afficher les filtres
    </button>
    
    <!-- Overlay pour les filtres -->
    <div class="filter-overlay" id="filterOverlay"></div>
    
    <div class="filters-container" id="filtersContainer">
        <!-- Bouton de fermeture pour desktop -->
        <button class="close-filters-desktop" id="closeFiltersDesktop">&times;</button>
        <!-- Bouton de fermeture pour mobile -->
        <button class="close-filters" id="closeFilters">&times;</button>
        
        <div class="filters">
            <!-- Recherche par nom/prénom -->
            <div class="filter-group">
                <label for="searchName"><i class="fas fa-search"></i> Rechercher par nom</label>
                <input type="text" id="searchName" placeholder="Nom ou prénom..." value="<?php echo htmlspecialchars($search_name); ?>">
            </div>
            
            <div class="filter-group">
                <label for="yearFilter"><i class="fas fa-graduation-cap"></i> Année du BAC</label>
                <select id="yearFilter">
                    <option value="">Toutes les années</option>
                    <?php
                    $stmt = $conn->query("SELECT DISTINCT bac_year FROM users WHERE bac_year IS NOT NULL ORDER BY bac_year DESC");
                    while ($row = $stmt->fetch_assoc()) {
                        $selected = $bac_year == $row['bac_year'] ? 'selected' : '';
                        echo "<option value='{$row['bac_year']}' $selected>{$row['bac_year']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
            </div>
            
            <button class="clear-btn" id="clearFilters"><i class="fas fa-eraser"></i> Réinitialiser</button>
            <button class="apply-filters-btn" id="applyFilters"><i class="fas fa-check"></i> Appliquer</button>
        </div>
    </div>
    
    <div class="main-content">
        <div class="profile-grid" id="profileGrid">
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $user): ?>
                    <div class="profile-card">
                        <img src="<?php echo $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : 'img/profile_pic.jpeg'; ?>" alt="Photo de profil" class="profile-image" onerror="this.src='img/profile_pic.jpeg'">
                        <div class="profile-info">
                            <h3 class="profile-name"><?php echo $user['full_name']; ?></h3>
                            <div class="profile-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo $user['bac_year'] ?? 'Non spécifié'; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="not-found" id="notFound">
                    <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Aucun utilisateur trouvé avec ces critères</p>
                </div>
            <?php endif; ?>
            <div class="loading hidden" id="loading">
                <p>Intégration sans Dérivation n'est que ruine de l'âme</p>
            </div>
        </div>
        
        <?php if ($has_more): ?>
        <div style="text-align: center; margin: 2rem 0;">
            <button id="loadMoreBtn" class="load-more-btn">
                <i class="fas fa-chevron-down"></i> Afficher plus
            </button>
        </div>
        <?php endif; ?>
    </div>

    <script>
        let currentPage = <?php echo $page; ?>;
        let isLoading = false;
        let hasMore = <?php echo $has_more ? 'true' : 'false'; ?>;
        // Gestion des filtres mobile et desktop
        const filterToggle = document.getElementById('filterToggle');
        const filtersContainer = document.getElementById('filtersContainer');
        const filterOverlay = document.getElementById('filterOverlay');
        const closeFilters = document.getElementById('closeFilters');
        const closeFiltersDesktop = document.getElementById('closeFiltersDesktop');
        
        // Fonction unique de fermeture des filtres
        function closeFilterMenu() {
            const isDesktop = window.innerWidth >= 769;
            const showFiltersBtn = document.getElementById('showFiltersBtn');
            if (isDesktop) {
                // Sur desktop, masquer complètement les filtres
                filtersContainer.classList.add('hidden');
                if (showFiltersBtn) showFiltersBtn.classList.remove('hidden');
            } else {
                // Sur mobile, utiliser le comportement de slide
                filtersContainer.classList.remove('active');
                filterOverlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        }
        
        // Fonction pour ouvrir les filtres
        function openFilterMenu() {
            const isDesktop = window.innerWidth >= 769;
            const showFiltersBtn = document.getElementById('showFiltersBtn');
            if (isDesktop) {
                filtersContainer.classList.remove('hidden');
                if (showFiltersBtn) showFiltersBtn.classList.add('hidden');
            } else {
                filtersContainer.classList.add('active');
                filterOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        // Écouteurs pour tous les boutons
        if (filterToggle) {
            filterToggle.addEventListener('click', openFilterMenu);
        }
        
        if (closeFilters) {
            closeFilters.addEventListener('click', closeFilterMenu);
        }
        
        if (closeFiltersDesktop) {
            closeFiltersDesktop.addEventListener('click', closeFilterMenu);
        }
        
        if (filterOverlay) {
            filterOverlay.addEventListener('click', closeFilterMenu);
        }
        
        // Bouton pour réafficher les filtres
        const showFiltersBtn = document.getElementById('showFiltersBtn');
        if (showFiltersBtn) {
            showFiltersBtn.addEventListener('click', openFilterMenu);
        }
        
        // Application des filtres
        document.getElementById('applyFilters').addEventListener('click', () => {
            const searchName = document.getElementById('searchName').value;
            const bacYear = document.getElementById('yearFilter').value;
            
            const params = new URLSearchParams();
            if (searchName) params.append('search_name', searchName);
            if (bacYear) params.append('bac_year', bacYear);
            
            window.location.href = 'yearbook_public.php?' + params.toString();
        });
        
        // Réinitialisation des filtres
        document.getElementById('clearFilters').addEventListener('click', () => {
            window.location.href = 'yearbook_public.php';
        });
        
        // Charger plus de profils
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', async () => {
                if (isLoading || !hasMore) return;
                
                isLoading = true;
                loadMoreBtn.disabled = true;
                loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
                
                try {
                    currentPage++;
                    const searchName = document.getElementById('searchName').value;
                    const bacYear = document.getElementById('yearFilter').value;
                    
                    const params = new URLSearchParams();
                    params.append('page', currentPage);
                    if (searchName) params.append('search_name', searchName);
                    if (bacYear) params.append('bac_year', bacYear);
                    params.append('ajax', '1');
                    
                    const response = await fetch('yearbook_public.php?' + params.toString());
                    const data = await response.json();
                    
                    const profileGrid = document.getElementById('profileGrid');
                    const loading = document.getElementById('loading');
                    
                    data.users.forEach(user => {
                        const card = document.createElement('div');
                        card.className = 'profile-card';
                        card.innerHTML = `
                            <img src="${user.profile_picture || 'img/profile_pic.jpeg'}" alt="Photo de profil" class="profile-image" onerror="this.src='img/profile_pic.jpeg'">
                            <div class="profile-info">
                                <h3 class="profile-name">${user.full_name}</h3>
                                <div class="profile-detail">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>${user.bac_year || 'Non spécifié'}</span>
                                </div>
                            </div>
                        `;
                        profileGrid.insertBefore(card, loading);
                    });
                    
                    hasMore = data.has_more;
                    if (!hasMore) {
                        loadMoreBtn.style.display = 'none';
                    } else {
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Afficher plus';
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Afficher plus';
                }
                
                isLoading = false;
            });
        }
    </script>
</body>
</html>
