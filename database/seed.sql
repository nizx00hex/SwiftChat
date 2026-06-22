-- SwiftChat Test Data
-- For development purposes only

USE swiftchat;

-- Insert test users
INSERT INTO users (username, password, email, email_verified) VALUES
('john_doe', '$argon2id$v=19$m=65536,t=4,p=3$hashhashhashhashhashhash$hashhashhashhashhashhashhashhash', 'john@example.com', TRUE),
('jane_smith', '$argon2id$v=19$m=65536,t=4,p=3$hashhashhashhashhashhash$hashhashhashhashhashhashhashhash', 'jane@example.com', TRUE),
('bob_wilson', '$argon2id$v=19$m=65536,t=4,p=3$hashhashhashhashhashhash$hashhashhashhashhashhashhashhash', 'bob@example.com', TRUE),
('alice_brown', '$argon2id$v=19$m=65536,t=4,p=3$hashhashhashhashhashhash$hashhashhashhashhashhashhashhash', 'alice@example.com', TRUE),
('mike_jones', '$argon2id$v=19$m=65536,t=4,p=3$hashhashhashhashhashhash$hashhashhashhashhashhashhashhash', 'mike@example.com', FALSE);

-- Insert test messages
INSERT INTO messages (from_user, to_user, message, created_at) VALUES
(1, 2, 'Hey Jane! How are you?', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 1, 'Hi John! I am good, thanks!', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 2, 'Want to chat later?', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(3, 4, 'Hello Alice!', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(4, 3, 'Hi Bob! Long time no see!', DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Insert test contacts
INSERT INTO contacts (user_id, contact_id) VALUES
(1, 2), (2, 1),
(3, 4), (4, 3);