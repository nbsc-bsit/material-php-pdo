-- ==========================================
-- USERS TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS users (

    user_id INT AUTO_INCREMENT PRIMARY KEY,

    user_email VARCHAR(255) UNIQUE NOT NULL,

    user_password VARCHAR(255) NOT NULL,

    user_role ENUM('admin', 'manager', 'user')
        DEFAULT 'user',

    -- Email verification
    user_is_verified TINYINT(1)
        DEFAULT 0,

    user_verification_token VARCHAR(64),

    user_email_verification_expires DATETIME NULL,

    -- Timestamps
    user_created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    user_updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- ACTIVITY LOGS TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS activity_logs (

    activity_log_id INT AUTO_INCREMENT PRIMARY KEY,

    -- NULL for activities where the user
    -- cannot be identified, such as failed login
    user_id INT NULL,

    user_email VARCHAR(255),

    activity_log_action VARCHAR(50) NOT NULL,

    activity_log_status ENUM('success', 'failed')
        DEFAULT 'success',

    activity_log_ip_address VARCHAR(45),

    activity_log_user_agent VARCHAR(255),

    activity_log_created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_user_id (user_id),

    INDEX idx_action (activity_log_action),

    INDEX idx_created_at (activity_log_created_at),

    -- Relationship with users table
    CONSTRAINT fk_activity_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

INSERT INTO users (user_email, user_password, user_role, user_is_verified) VALUES
('admin@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'admin', 1),
('manager@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'manager', 1),
('user@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'user', 1);
