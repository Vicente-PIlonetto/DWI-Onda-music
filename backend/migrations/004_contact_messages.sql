CREATE TABLE contact_messages (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(254) NOT NULL,
 phone VARCHAR(20) NULL,
 subject ENUM('suporte','sugestao','artista','outro') NOT NULL,
 message TEXT NOT NULL,
 read_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY contact_messages_email_created (email,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
