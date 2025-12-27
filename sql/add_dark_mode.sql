-- =====================================================
-- AJOUT DU MODE SOMBRE POUR LES UTILISATEURS
-- =====================================================

-- Ajouter une colonne pour stocker la préférence de thème
ALTER TABLE users 
ADD COLUMN dark_mode BOOLEAN DEFAULT FALSE 
COMMENT 'Préférence de thème: FALSE=clair, TRUE=sombre';

-- Ajouter un index pour optimiser les requêtes
CREATE INDEX idx_dark_mode ON users(dark_mode);

-- Optionnel: Mettre à jour les utilisateurs existants avec la préférence système détectée
-- (Vous pouvez personnaliser cette requête selon vos besoins)
UPDATE users 
SET dark_mode = FALSE 
WHERE dark_mode IS NULL;
