-- SwiftChat Admin Schema
-- Version: 1.0.0

USE swiftchat;

-- Admin Users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    role ENUM('super_admin', 'admin', 'moderator', 'viewer') DEFAULT 'moderator',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(64),
    auth_token VARCHAR(255),
    token_expiry DATETIME,
    last_login DATETIME,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_admin_auth (auth_token),
    INDEX idx_admin_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Activity Logs table
CREATE TABLE admin_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    affected_user_id INT DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    severity ENUM('INFO', 'WARNING', 'CRITICAL') DEFAULT 'INFO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (affected_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_admin_time (admin_id, created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Sessions table
CREATE TABLE admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_activity DATETIME,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_admin_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Configuration table
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description TEXT,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system configurations
INSERT INTO system_config (config_key, config_value, description) VALUES
('site_name', 'SwiftChat', 'Website name'),
('site_description', 'Lightning-fast secure messaging', 'Site description'),
('max_users', '10000', 'Maximum allowed users'),
('allow_registration', 'true', 'Allow new registrations'),
('require_email_verification', 'true', 'Require email verification'),
('max_message_length', '5000', 'Maximum message length'),
('enable_file_sharing', 'false', 'Enable file sharing'),
('maintenance_mode', 'false', 'Maintenance mode status'),
('maintenance_message', 'We are currently under maintenance. Please check back later.', 'Maintenance message'),
('admin_notification_email', '', 'Admin notification email');

-- Insert default super admin (password: Admin@123456 - CHANGE IMMEDIATELY)
INSERT INTO admin_users (username, password, email, full_name, role, email_verified) VALUES
('superadmin', '$argon2id$v=19$m=65536,t=4,p=3$c29tZXNhbHR0aGF0aXNzZWN1cmU$R+J8KXH5wY3Zq7mN4vBdFgHhJkLpOuYtReWqAsDfGh', 'admin@swiftchat.com', 'Super Administrator', 'super_admin', TRUE);