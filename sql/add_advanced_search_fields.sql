-- Script SQL pour ajouter les champs de recherche avancée dans la table users
-- Exécuter ce script dans phpMyAdmin ou via MySQL CLI

-- Vérifier et ajouter la colonne profession
ALTER TABLE users ADD COLUMN IF NOT EXISTS profession VARCHAR(200) NULL AFTER studies;

-- Vérifier et ajouter la colonne company
ALTER TABLE users ADD COLUMN IF NOT EXISTS company VARCHAR(200) NULL AFTER profession;

-- Vérifier et ajouter la colonne city
ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL AFTER company;

-- Vérifier et ajouter la colonne country
ALTER TABLE users ADD COLUMN IF NOT EXISTS country VARCHAR(100) NULL AFTER city;

-- Vérifier et ajouter la colonne skills (pour future implémentation)
ALTER TABLE users ADD COLUMN IF NOT EXISTS skills TEXT NULL AFTER country;

-- Vérifier et ajouter la colonne interests (pour future implémentation)
ALTER TABLE users ADD COLUMN IF NOT EXISTS interests TEXT NULL AFTER skills;

-- Créer des index pour améliorer les performances de recherche
CREATE INDEX IF NOT EXISTS idx_profession ON users(profession);
CREATE INDEX IF NOT EXISTS idx_company ON users(company);
CREATE INDEX IF NOT EXISTS idx_city ON users(city);
CREATE INDEX IF NOT EXISTS idx_country ON users(country);
CREATE INDEX IF NOT EXISTS idx_full_name ON users(full_name);

-- Afficher le résultat
SELECT 'Les colonnes ont été ajoutées avec succès!' AS Status;
