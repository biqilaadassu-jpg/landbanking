CREATE DATABASE IF NOT EXISTS land_banking
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE land_banking;

CREATE TABLE subcities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE woredas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subcity_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_woreda (subcity_id, name),
    FOREIGN KEY (subcity_id) REFERENCES subcities(id) ON DELETE CASCADE
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('CITY','SUBCITY','WOREDA','ADMIN') NOT NULL,
    subcity_id INT NULL,
    woreda_id INT NULL,
    digital_signature TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcity_id) REFERENCES subcities(id) ON DELETE SET NULL,
    FOREIGN KEY (woreda_id) REFERENCES woredas(id) ON DELETE SET NULL
);

CREATE TABLE land_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(50) NOT NULL UNIQUE,
    subcity_id INT NOT NULL,
    woreda_id INT NOT NULL,
    account_name VARCHAR(150) NOT NULL,
    address TEXT,
    opening_area DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('ACTIVE','CLOSED') NOT NULL DEFAULT 'ACTIVE',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcity_id) REFERENCES subcities(id),
    FOREIGN KEY (woreda_id) REFERENCES woredas(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE land_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) NOT NULL UNIQUE,
    account_id INT NOT NULL,
    transaction_type ENUM('DEPOSIT','WITHDRAW') NOT NULL,
    area_m2 DECIMAL(14,2) NOT NULL,
    x_coordinate DECIMAL(16,8) NULL,
    y_coordinate DECIMAL(16,8) NULL,
    latitude DECIMAL(16,10) NOT NULL,
    longitude DECIMAL(16,10) NOT NULL,
    address TEXT,
    statement TEXT,
    photo_path VARCHAR(255) NULL,
    document_path VARCHAR(255) NULL,
    status ENUM('PENDING_SUBCITY','PENDING_CITY','APPROVED','REJECTED','RETURNED') NOT NULL DEFAULT 'PENDING_SUBCITY',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (account_id) REFERENCES land_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE approval_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    level ENUM('WOREDA','SUBCITY','CITY') NOT NULL,
    action ENUM('APPROVE','REJECT','RETURN') NOT NULL,
    approved_by INT NOT NULL,
    digital_signature TEXT NULL,
    approval_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    comment TEXT,
    FOREIGN KEY (transaction_id) REFERENCES land_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO subcities (name) VALUES
('Addis Ketema'),('Akaki Kaliti'),('Arada'),('Bole'),('Gullele'),
('Kirkos'),('Kolfe Keranio'),('Lideta'),('Nifas Silk Lafto'),
('Yeka'),('Lemi Kura');

-- 10 sample Woredas per Sub-City. Replace/expand with the Authority's official 119-Woreda master data.
INSERT INTO woredas (subcity_id, name)
SELECT s.id, CONCAT('Woreda ', LPAD(n.n, 2, '0'))
FROM subcities s
CROSS JOIN (
    SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) n;

-- Create the default admin through install.php so password_hash() is used safely.
