-- =====================================================
-- SYSTÈME DE NOTIFICATIONS EN TEMPS RÉEL
-- =====================================================

-- Table principale pour les notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    icon VARCHAR(50) DEFAULT 'bell',
    is_read BOOLEAN DEFAULT FALSE,
    related_type VARCHAR(50),
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les préférences de notifications
CREATE TABLE IF NOT EXISTS notification_preferences (
    user_id INT PRIMARY KEY,
    email_events BOOLEAN DEFAULT TRUE,
    email_elections BOOLEAN DEFAULT TRUE,
    email_messages BOOLEAN DEFAULT TRUE,
    email_suggestions BOOLEAN DEFAULT TRUE,
    email_mentions BOOLEAN DEFAULT TRUE,
    push_events BOOLEAN DEFAULT TRUE,
    push_elections BOOLEAN DEFAULT TRUE,
    push_messages BOOLEAN DEFAULT TRUE,
    push_suggestions BOOLEAN DEFAULT TRUE,
    push_mentions BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les préférences par défaut pour les utilisateurs existants
INSERT IGNORE INTO notification_preferences (user_id)
SELECT id FROM users;
