-- Ajouter un champ pour tracker si l'utilisateur a complété le tutoriel
ALTER TABLE users ADD COLUMN tutorial_completed TINYINT(1) DEFAULT 0 AFTER login_count;
