<?php
// Retrieve configurations from database
if (!isset($configs)) {
    $configs = [];
    $config_sql = "SELECT setting_key, setting_value FROM general_config LIMIT 20";
    $config_result = $conn->query($config_sql);
    
    if ($config_result) {
        while($row = $config_result->fetch_assoc()) {
            $configs[$row['setting_key']] = htmlspecialchars($row['setting_value']);
        }
    }
}

// Default values
$instagram_url = $configs['instagram_url'] ?? 'https://instagram.com/sigmaofficial';
$tiktok_url = $configs['tiktok_url'] ?? 'https://tiktok.com/@sigmaofficial';
$linkedin_url = $configs['linkedin_url'] ?? 'https://linkedin.com/company/sigma-alumni';
$contact_email = $configs['contact_email'] ?? 'contact@sigma-alumni.org';
$contact_phone = $configs['contact_phone'] ?? '+33 1 23 45 67 89';
$contact_address = $configs['contact_address'] ?? '123 Rue de l\'Éducation, 75001 Paris, France';

// Utiliser le logo de la base de données ou le logo par défaut
$footer_logo_final = $configs['footer_logo'] ?? 'img/image.png';

// Vérifier que le logo existe, sinon utiliser le fallback par défaut
if (!file_exists($footer_logo_final)) {
    $footer_logo_final = 'img/image.png';
}

$current_year = date('Y');
?>

<footer class="modern-footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25"></path>
            <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5"></path>
            <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z"></path>
        </svg>
    </div>

    <div class="footer-container">
        <div class="footer-grid">
            <!-- About Section -->
            <div class="footer-col footer-brand">
                <div class="brand-content">
                    <?php if ($footer_logo_final): ?>
                        <img src="<?php echo htmlspecialchars($footer_logo_final); ?>" alt="SIGMA Logo" class="footer-logo" onerror="this.style.display='none'">
                    <?php else: ?>
                        <div class="logo-text">SIGMA</div>
                    <?php endif; ?>
                    <h3>SIGMA ALUMNI</h3>
                    <p>Unissant science, conscience et méthode depuis 1985</p>
                </div>
                <div class="social-links">
                    <a href="<?php echo htmlspecialchars($linkedin_url); ?>" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="mailto:<?php echo $contact_email; ?>" aria-label="Email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="accueil.php"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="evenements.php"><i class="fas fa-calendar"></i> Événements</a></li>
                    <li><a href="bureau.php"><i class="fas fa-users"></i> Bureau</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>

            <!-- Resources -->
            <div class="footer-col">
                <h4>Ressources</h4>
                <ul>
                    <li><a href="reglement.php"><i class="fas fa-file-alt"></i> Règlement</a></li>
                    <li><a href="connexion.php?redirect=annuaire"><i class="fas fa-address-book"></i> Annuaire</a></li>
                    <li><a href="objectifs.php"><i class="fas fa-bullseye"></i> Objectifs</a></li>
                    <li><a href="elections.php"><i class="fas fa-vote-yea"></i> Élections</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="footer-col footer-contact">
                <h4>Contact</h4>
                <div class="contact-items">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo $contact_email; ?>">
                            <?php echo $contact_email; ?>
                        </a>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $contact_phone); ?>">
                            <?php echo $contact_phone; ?>
                        </a>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo strip_tags($contact_address); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <p>&copy; <?php echo $current_year; ?> SIGMA ALUMNI. Tous droits réservés.</p>
            <div class="footer-links">
                <a href="#">Mentions légales</a>
                <span>•</span>
                <a href="#">Politique de confidentialité</a>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Force FontAwesome icons to display */
    .modern-footer i,
    .modern-footer .fa,
    .modern-footer .fas,
    .modern-footer .far,
    .modern-footer .fab {
        font-family: 'Font Awesome 6 Free', 'Font Awesome 6 Brands' !important;
        font-style: normal !important;
        font-variant: normal !important;
        text-rendering: auto !important;
        line-height: 1 !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        display: inline-block !important;
        font-weight: 900 !important;
    }

    .modern-footer .fa-brands,
    .modern-footer .fab,
    .modern-footer .fa-instagram,
    .modern-footer .fa-tiktok {
        font-family: 'Font Awesome 6 Brands' !important;
        font-weight: 400 !important;
    }

    /* Ensure icons render with correct unicode */
    .modern-footer .fa-instagram::before {
        content: "\f16d";
    }

    .modern-footer .fa-tiktok::before {
        content: "\e07b";
    }

    .modern-footer .fa-envelope::before {
        content: "\f0e0";
    }

    .modern-footer .fa-home::before {
        content: "\f015";
    }

    .modern-footer .fa-calendar::before {
        content: "\f133";
    }

    .modern-footer .fa-users::before {
        content: "\f0c0";
    }

    .modern-footer .fa-phone::before {
        content: "\f095";
    }

    .modern-footer .fa-map-marker-alt::before {
        content: "\f3c5";
    }

    /* Modern Footer Styles */
    .modern-footer {
        position: relative;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #e2e8f0;
        margin-top: auto;
        overflow: hidden;
    }

    .footer-wave {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
        transform: translateY(-1px);
    }

    .footer-wave svg {
        position: relative;
        display: block;
        width: calc(100% + 1.3px);
        height: 80px;
    }

    .footer-wave path {
        fill: var(--white, #fff);
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 5rem 5% 2rem;
        position: relative;
        z-index: 1;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 3rem;
        margin-bottom: 3rem;
    }

    /* Brand Section */
    .footer-brand {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .brand-content {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
        width: 100%;
    }

    .footer-logo {
        height: 50px;
        width: auto;
        max-width: 180px;
        object-fit: contain;
        object-position: left center;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        display: block;
        background: transparent;
        margin: 0;
    }

    .logo-text {
        font-size: 2.5rem;
        font-weight: 900;
        color: #fff;
        letter-spacing: 3px;
        text-shadow: 0 2px 10px rgba(59, 130, 246, 0.5);
        margin-bottom: 0.5rem;
        display: block;
    }

    .footer-brand h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
        letter-spacing: 1px;
    }

    .footer-brand p {
        color: #94a3b8;
        line-height: 1.6;
        margin: 0;
    }

    .social-links {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .social-links a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #e2e8f0 !important;
        font-size: 1.25rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        text-decoration: none;
    }

    .social-links a i {
        color: #e2e8f0 !important;
        font-size: 1.25rem;
        line-height: 1;
        display: block;
    }

    .social-links a:hover {
        background: var(--primary-blue);
        color: #fff !important;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }

    .social-links a:hover i {
        color: #fff !important;
    }

    /* Footer Columns */
    .footer-col {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .footer-col h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 1.25rem;
        position: relative;
        padding-bottom: 0.5rem;
        width: 100%;
        text-align: left;
    }

    .footer-col h4::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 3px;
        background: var(--primary-blue);
        border-radius: 2px;
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .footer-col ul li {
        margin: 0;
        padding: 0;
    }

    .footer-col ul li a {
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0;
        line-height: 1.5;
    }

    .footer-col ul li a i {
        font-size: 0.875rem;
        width: 18px;
        min-width: 18px;
        transition: transform 0.3s ease;
        color: var(--primary-blue);
        display: inline-block;
        text-align: center;
    }

    .footer-col ul li a:hover {
        color: var(--primary-blue);
        padding-left: 0.5rem;
    }

    .footer-col ul li a:hover i {
        transform: translateX(3px);
    }

    /* Contact Section */
    .footer-contact {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }

    .footer-contact h4 {
        text-align: left;
        width: 100%;
    }

    .footer-contact .contact-items {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        width: 100%;
    }

    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        color: #94a3b8;
        line-height: 1.6;
    }

    .contact-item i {
        color: var(--primary-blue) !important;
        font-size: 1.1rem;
        margin-top: 0.15rem;
        min-width: 20px;
        display: inline-block;
        text-align: center;
    }

    .contact-item a {
        color: #94a3b8;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .contact-item a:hover {
        color: var(--primary-blue);
    }

    /* Footer Bottom */
    .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-bottom p {
        margin: 0;
        color: #64748b;
    }

    .footer-links {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .footer-links a {
        color: #64748b;
        text-decoration: none;
        transition: color 0.3s ease;
        font-size: 0.9rem;
    }

    .footer-links a:hover {
        color: var(--primary-blue);
    }

    .footer-links span {
        color: #475569;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .footer-grid {
            grid-template-columns: 2fr 1fr 1fr;
            gap: 2.5rem;
        }

        .footer-contact {
            grid-column: 1 / -1;
            margin-top: 1rem;
        }
    }

    @media (max-width: 768px) {
        .footer-wave svg {
            height: 60px;
        }

        .footer-container {
            padding: 4rem 5% 1.5rem;
        }

        .footer-grid {
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem 2rem;
        }

        .footer-brand {
            grid-column: 1 / -1;
            text-align: center;
            align-items: center;
        }

        .brand-content {
            align-items: center;
        }

        .footer-logo {
            margin: 0 auto;
        }

        .footer-brand h3 {
            font-size: 1.3rem;
            text-align: center;
        }

        .footer-brand p {
            text-align: center;
        }

        .social-links {
            justify-content: center;
        }

        /* Aligner Navigation et Ressources côte à côte */
        .footer-col:nth-child(2) {
            text-align: left;
        }

        .footer-col:nth-child(3) {
            text-align: left;
        }

        .footer-col h4 {
            text-align: left;
        }

        .footer-col h4::after {
            left: 0;
            transform: none;
        }

        .footer-col ul {
            align-items: flex-start;
        }

        /* Contact en pleine largeur en bas */
        .footer-contact {
            grid-column: 1 / -1;
            text-align: left;
            margin-top: 1rem;
        }

        .footer-contact h4 {
            text-align: left;
        }

        .footer-contact h4::after {
            left: 0;
            transform: none;
        }

        .footer-bottom {
            flex-direction: column;
            text-align: center;
            gap: 0.75rem;
        }
    }

    @media (max-width: 650px) and (min-width: 601px) {
        /* Écrans moyens - Layout vertical avec meilleur espacement */
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }

        .footer-brand,
        .footer-col,
        .footer-contact {
            grid-column: 1;
            text-align: center;
            align-items: center;
        }

        .footer-col h4,
        .footer-contact h4 {
            text-align: center;
        }

        .footer-col h4::after,
        .footer-contact h4::after {
            left: 50%;
            transform: translateX(-50%);
            position: relative;
            margin-top: 0.5rem;
        }

        .footer-col ul {
            align-items: center;
        }

        .contact-items {
            align-items: center;
        }

        .contact-item {
            justify-content: center;
        }
    }

    @media (max-width: 600px) {
        .footer-wave svg {
            height: 40px;
        }

        .footer-container {
            padding: 3rem 5% 1.5rem;
        }

        .footer-grid {
            grid-template-columns: 1fr;
            gap: 2.5rem;
            margin-bottom: 2rem;
        }

        /* Tout centré sur petit écran */
        .footer-brand,
        .footer-col,
        .footer-contact {
            grid-column: 1;
            text-align: center;
            align-items: center;
        }

        .brand-content {
            align-items: center;
        }

        .footer-logo {
            margin: 0 auto;
            height: 40px;
            max-width: 150px;
        }

        .footer-brand h3 {
            font-size: 1.2rem;
            text-align: center;
        }

        .footer-brand p {
            font-size: 0.9rem;
            text-align: center;
        }

        .social-links {
            justify-content: center;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            font-size: 1.1rem;
        }

        .social-links a i {
            font-size: 1.1rem;
        }

        /* Titres centrés avec ligne sous le titre */
        .footer-col h4,
        .footer-contact h4 {
            font-size: 1rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-col h4::after,
        .footer-contact h4::after {
            left: 50%;
            transform: translateX(-50%);
            position: relative;
            margin-top: 0.5rem;
        }

        /* Liste centrée */
        .footer-col ul {
            align-items: center;
        }

        .footer-col ul li a {
            font-size: 0.9rem;
            justify-content: center;
        }

        /* Contact items centrés */
        .contact-items {
            align-items: center;
        }

        .contact-item {
            font-size: 0.9rem;
            justify-content: center;
            text-align: center;
        }

        .footer-bottom {
            font-size: 0.85rem;
        }
    }

    @media (max-width: 400px) {
        .footer-container {
            padding: 2.5rem 4% 1.25rem;
        }

        .footer-grid {
            gap: 2rem;
        }

        .footer-brand h3 {
            font-size: 1.1rem;
        }

        .footer-brand p {
            font-size: 0.85rem;
        }

        .footer-logo {
            height: 35px;
            max-width: 120px;
        }

        .logo-text {
            font-size: 1.8rem;
        }

        .social-links a {
            width: 38px;
            height: 38px;
            font-size: 1rem;
        }

        .social-links a i {
            font-size: 1rem;
        }

        .footer-col h4,
        .footer-contact h4 {
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .footer-col ul li a {
            font-size: 0.85rem;
        }

        .contact-item {
            font-size: 0.85rem;
        }

        .footer-bottom {
            font-size: 0.8rem;
        }

        .footer-links {
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-links span {
            display: none;
        }
    }

    /* Touch Optimization */
    @media (hover: none) and (pointer: coarse) {
        .footer-col ul li a,
        .social-links a,
        .contact-item a,
        .footer-links a {
            min-height: 44px;
            display: flex;
            align-items: center;
        }
    }
</style>

<?php
// Close database connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
</body>
</html>