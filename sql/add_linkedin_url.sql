-- Ajouter la colonne linkedin_url à la table users
ALTER TABLE users 
ADD COLUMN linkedin_url VARCHAR(255) NULL 
AFTER interests;

-- Vérifier que la colonne a été ajoutée
DESCRIBE users;
