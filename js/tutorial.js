// Tutoriel interactif avec Driver.js
function startTutorial() {
    const driver = window.driver.js.driver({
        showProgress: true,
        showButtons: ['next', 'previous', 'close'],
        nextBtnText: 'Suivant',
        prevBtnText: 'Précédent',
        doneBtnText: 'Terminer',
        progressText: '{{current}} sur {{total}}',
        onDestroyStarted: () => {
            // Marquer le tutoriel comme complété
            markTutorialCompleted();
            driver.destroy();
        },
        steps: [
            {
                popover: {
                    title: '👋 Bienvenue sur SIGMA Alumni !',
                    description: 'Découvrez les fonctionnalités de votre plateforme réseau. Ce guide vous prendra environ 2 minutes.',
                }
            },
            {
                element: 'nav a[href*="dashboard"], nav a[href*="accueil"]',
                popover: {
                    title: '🏠 Accueil',
                    description: 'Votre tableau de bord principal. Retrouvez ici un aperçu de toutes vos activités et actualités.',
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: 'nav a[href*="evenements"]',
                popover: {
                    title: '📅 Événements',
                    description: 'Découvrez les événements à venir, inscrivez-vous et participez à la vie de la communauté SIGMA.',
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: 'nav a[href*="bureau"]',
                popover: {
                    title: '👥 Bureau',
                    description: 'Découvrez les membres du bureau et leurs rôles dans l\'association.',
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: 'nav a[href*="contact"]',
                popover: {
                    title: '✉️ Contact',
                    description: 'Contactez l\'administration pour toute question ou suggestion.',
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: '#messaging-nav-link, nav a[href*="messaging"]',
                popover: {
                    title: '💬 Messagerie',
                    description: 'Communiquez avec les autres membres du réseau. Messagerie instantanée et sécurisée avec notifications.',
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: 'nav a[href*="mod_prof"], nav a[href*="profil"]',
                popover: {
                    title: '👤 Profil',
                    description: 'Gérez votre profil : photo, informations personnelles, études, profession et centres d\'intérêt.',
                    side: "bottom",
                    align: 'end'
                }
            },
            {
                popover: {
                    title: '🎓 Autres fonctionnalités',
                    description: 'Le site offre également : Yearbook (annuaire complet), Élections, Souvenirs (partage de photos), Album et bien plus. Explorez le menu pour tout découvrir !',
                }
            },
            {
                popover: {
                    title: '✨ C\'est parti !',
                    description: 'Vous êtes maintenant prêt à explorer SIGMA Alumni. Bonne navigation ! Pour revoir ce guide, rendez-vous dans Paramètres.',
                }
            }
        ]
    });

    driver.drive();
}

// Marquer le tutoriel comme complété
async function markTutorialCompleted() {
    try {
        await fetch('mark_tutorial_completed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ completed: true })
        });
    } catch (error) {
        console.error('Erreur lors de la sauvegarde du tutoriel:', error);
    }
}

// Vérifier si c'est la première connexion et lancer le tutoriel
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si le paramètre URL indique qu'il faut lancer le tutoriel
    const urlParams = new URLSearchParams(window.location.search);
    const shouldShowTutorial = urlParams.get('tutorial') === '1';
    
    if (shouldShowTutorial) {
        // Attendre un peu que la page soit complètement chargée
        setTimeout(() => {
            startTutorial();
        }, 500);
        
        // Nettoyer l'URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
