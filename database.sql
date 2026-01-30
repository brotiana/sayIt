

-- Create database
CREATE DATABASE IF NOT EXISTS anonymous_messages CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE anonymous_messages;

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_id INT NOT NULL,
    content TEXT NOT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    sender_ip VARCHAR(45) DEFAULT NULL,
    sender_user_agent TEXT DEFAULT NULL,
    sender_device VARCHAR(100) DEFAULT NULL,
    sender_browser VARCHAR(100) DEFAULT NULL,
    sender_os VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_recipient (recipient_id),
    INDEX idx_received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE messages ADD COLUMN sender_ip VARCHAR(45) DEFAULT NULL;
ALTER TABLE messages ADD COLUMN sender_user_agent TEXT DEFAULT NULL;
ALTER TABLE messages ADD COLUMN sender_device VARCHAR(100) DEFAULT NULL;
ALTER TABLE messages ADD COLUMN sender_browser VARCHAR(100) DEFAULT NULL;
ALTER TABLE messages ADD COLUMN sender_os VARCHAR(100) DEFAULT NULL;
