/**
 * GESTIONNAIRE DE THÈME (DARK MODE)
 * 
 * Ce script gère:
 * - Application du thème clair/sombre
 * - Sauvegarde dans localStorage
 * - Synchronisation avec la base de données
 * - Détection de la préférence système
 * - Toggle manuel par l'utilisateur
 */

class ThemeManager {
    constructor() {
        this.STORAGE_KEY = 'sigma-theme';
        this.currentTheme = null;
        this.init();
    }

    /**
     * Initialisation du gestionnaire de thème
     */
    init() {
        // 1. Charger le thème depuis localStorage
        const savedTheme = localStorage.getItem(this.STORAGE_KEY);
        
        // 2. Sinon, détecter la préférence système
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // 3. Sinon, charger depuis PHP si disponible
        const phpTheme = document.documentElement.getAttribute('data-user-theme');
        
        // 4. Déterminer le thème initial
        let initialTheme = savedTheme || (phpTheme !== null ? phpTheme : (systemPrefersDark ? 'dark' : 'light'));
        
        // 5. Appliquer le thème
        this.setTheme(initialTheme, false); // false = ne pas sauvegarder car c'est l'init
        
        // 6. Écouter les changements de préférence système
        this.watchSystemPreference();
        
        // 7. Initialiser les boutons toggle
        this.initToggleButtons();
    }

    /**
     * Appliquer un thème
     * @param {string} theme - 'light' ou 'dark'
     * @param {boolean} save - Sauvegarder dans localStorage et BD
     */
    setTheme(theme, save = true) {
        this.currentTheme = theme;
        
        // Appliquer l'attribut data-theme sur le HTML
        document.documentElement.setAttribute('data-theme', theme);
        
        // Ajouter une classe pour faciliter le ciblage CSS
        document.body.classList.remove('theme-light', 'theme-dark');
        document.body.classList.add(`theme-${theme}`);
        
        // Sauvegarder si demandé
        if (save) {
            this.saveTheme(theme);
        }
        
        // Émettre un événement personnalisé
        window.dispatchEvent(new CustomEvent('themeChanged', { 
            detail: { theme: theme }
        }));
        
        // Log pour debug
        console.log(`✨ Thème appliqué: ${theme}`);
    }

    /**
     * Sauvegarder le thème dans localStorage et base de données
     * @param {string} theme
     */
    saveTheme(theme) {
        // 1. Sauvegarder dans localStorage
        localStorage.setItem(this.STORAGE_KEY, theme);
        
        // 2. Sauvegarder dans la base de données via AJAX
        this.saveToDatabase(theme);
    }

    /**
     * Sauvegarder la préférence dans la base de données
     * @param {string} theme
     */
    async saveToDatabase(theme) {
        try {
            const response = await fetch('update_theme_preference.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    theme: theme,
                    dark_mode: theme === 'dark'
                })
            });

            const data = await response.json();
            
            if (data.success) {
                console.log('💾 Préférence sauvegardée en base de données');
            } else {
                console.warn('⚠️ Erreur sauvegarde BD:', data.message);
            }
        } catch (error) {
            console.error('❌ Erreur lors de la sauvegarde:', error);
        }
    }

    /**
     * Basculer entre les thèmes
     */
    toggle() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme, true);
        
        // Animation du bouton
        this.animateToggleButton();
        
        return newTheme;
    }

    /**
     * Obtenir le thème actuel
     * @returns {string} 'light' ou 'dark'
     */
    getTheme() {
        return this.currentTheme;
    }

    /**
     * Vérifier si le mode sombre est actif
     * @returns {boolean}
     */
    isDark() {
        return this.currentTheme === 'dark';
    }

    /**
     * Vérifier si le mode clair est actif
     * @returns {boolean}
     */
    isLight() {
        return this.currentTheme === 'light';
    }

    /**
     * Surveiller les changements de préférence système
     */
    watchSystemPreference() {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        darkModeQuery.addEventListener('change', (e) => {
            // Ne changer que si l'utilisateur n'a pas de préférence manuelle
            const savedTheme = localStorage.getItem(this.STORAGE_KEY);
            
            if (!savedTheme) {
                const newTheme = e.matches ? 'dark' : 'light';
                this.setTheme(newTheme, false);
                console.log('🌓 Thème système changé:', newTheme);
            }
        });
    }

    /**
     * Initialiser tous les boutons toggle sur la page
     */
    initToggleButtons() {
        const buttons = document.querySelectorAll('.theme-toggle-btn, [data-theme-toggle]');
        
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
                this.updateToggleButtonState(button);
            });
            
            // État initial
            this.updateToggleButtonState(button);
        });
    }

    /**
     * Mettre à jour l'état visuel du bouton
     * @param {HTMLElement} button
     */
    updateToggleButtonState(button) {
        const isDark = this.isDark();
        
        // Mettre à jour l'icône
        const icon = button.querySelector('.theme-icon, i');
        if (icon) {
            icon.classList.remove('fa-sun', 'fa-moon');
            icon.classList.add(isDark ? 'fa-sun' : 'fa-moon');
        }
        
        // Mettre à jour le texte
        const text = button.querySelector('.theme-text');
        if (text) {
            text.textContent = isDark ? 'Mode Clair' : 'Mode Sombre';
        }
        
        // Mettre à jour l'attribut aria
        button.setAttribute('aria-label', isDark ? 'Activer le mode clair' : 'Activer le mode sombre');
        button.setAttribute('data-theme', isDark ? 'dark' : 'light');
    }

    /**
     * Animer le bouton lors du toggle
     */
    animateToggleButton() {
        const buttons = document.querySelectorAll('.theme-toggle-btn, [data-theme-toggle]');
        
        buttons.forEach(button => {
            button.classList.add('animating');
            setTimeout(() => {
                button.classList.remove('animating');
            }, 500);
        });
    }

    /**
     * Forcer un thème spécifique
     * @param {string} theme - 'light' ou 'dark'
     */
    forceTheme(theme) {
        if (theme === 'light' || theme === 'dark') {
            this.setTheme(theme, true);
        } else {
            console.error('❌ Thème invalide. Utilisez "light" ou "dark".');
        }
    }

    /**
     * Réinitialiser au thème par défaut (préférence système)
     */
    reset() {
        localStorage.removeItem(this.STORAGE_KEY);
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const defaultTheme = systemPrefersDark ? 'dark' : 'light';
        this.setTheme(defaultTheme, false);
        console.log('🔄 Thème réinitialisé à la préférence système:', defaultTheme);
    }

    /**
     * Obtenir des statistiques sur le thème
     * @returns {object}
     */
    getStats() {
        return {
            current: this.currentTheme,
            saved: localStorage.getItem(this.STORAGE_KEY),
            system: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
            isDark: this.isDark(),
            isLight: this.isLight()
        };
    }
}

// ==========================================
// INITIALISATION AUTOMATIQUE
// ==========================================

// Créer une instance globale dès le chargement du DOM
let themeManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        themeManager = new ThemeManager();
        window.themeManager = themeManager; // Exposer globalement
    });
} else {
    // DOM déjà chargé
    themeManager = new ThemeManager();
    window.themeManager = themeManager;
}

// ==========================================
// FONCTIONS HELPER GLOBALES
// ==========================================

/**
 * Basculer le thème (raccourci)
 */
function toggleTheme() {
    return window.themeManager?.toggle();
}

/**
 * Obtenir le thème actuel (raccourci)
 */
function getCurrentTheme() {
    return window.themeManager?.getTheme();
}

/**
 * Définir un thème (raccourci)
 */
function setTheme(theme) {
    window.themeManager?.forceTheme(theme);
}

/**
 * Vérifier si mode sombre (raccourci)
 */
function isDarkMode() {
    return window.themeManager?.isDark();
}

// ==========================================
// ÉVÉNEMENTS PERSONNALISÉS
// ==========================================

// Exemple d'utilisation:
// window.addEventListener('themeChanged', (e) => {
//     console.log('Nouveau thème:', e.detail.theme);
// });

// ==========================================
// SUPPORT DES ANCIENS NAVIGATEURS
// ==========================================

// Polyfill pour CustomEvent si nécessaire
if (typeof window.CustomEvent !== 'function') {
    window.CustomEvent = function(event, params) {
        params = params || { bubbles: false, cancelable: false, detail: null };
        const evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    };
}

// ==========================================
// EXPORT POUR MODULES (si utilisé avec bundler)
// ==========================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
