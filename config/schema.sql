-- ============================================================
-- ShStorage — Database Schema
-- Run this file once to initialize all tables.
-- ============================================================

CREATE DATABASE IF NOT EXISTS shstorage
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE shstorage;

-- ============================================================
-- 1. USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(80)  NOT NULL UNIQUE,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin', 'branch') NOT NULL DEFAULT 'branch',
    branch_name VARCHAR(150) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. BRANDS
-- ============================================================
CREATE TABLE IF NOT EXISTS brands (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    logo       VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO brands (name) VALUES ('Nike'), ('Adidas'), ('Converse');

-- ============================================================
-- 3. SHOES
-- ============================================================
CREATE TABLE IF NOT EXISTS shoes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    brand_id    INT NOT NULL,
    name        VARCHAR(150) NOT NULL,
    model_code  VARCHAR(100) DEFAULT NULL,
    gender      ENUM('male', 'female', 'unisex') NOT NULL DEFAULT 'unisex',
    price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT DEFAULT NULL,
    is_trending TINYINT(1) NOT NULL DEFAULT 0,
    image       VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shoe_brand FOREIGN KEY (brand_id)
        REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. SHOE COLORWAYS  (NEW)
-- ============================================================
CREATE TABLE IF NOT EXISTS shoe_colorways (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    shoe_id    INT NOT NULL,
    name       VARCHAR(100) NOT NULL,
    image      VARCHAR(255) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cw_shoe FOREIGN KEY (shoe_id)
        REFERENCES shoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. INVENTORY  (colorway_id added)
-- ============================================================
CREATE TABLE IF NOT EXISTS inventory (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    shoe_id      INT NOT NULL,
    colorway_id  INT DEFAULT NULL,
    size         VARCHAR(10) NOT NULL,
    quantity     INT NOT NULL DEFAULT 0,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shoe_cw_size (shoe_id, colorway_id, size),
    CONSTRAINT fk_inv_shoe FOREIGN KEY (shoe_id)
        REFERENCES shoes(id) ON DELETE CASCADE,
    CONSTRAINT fk_inv_colorway FOREIGN KEY (colorway_id)
        REFERENCES shoe_colorways(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 6. STOCK REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_requests (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    branch_id  INT NOT NULL,
    status     ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    notes      TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_req_branch FOREIGN KEY (branch_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. REQUEST ITEMS  (colorway_id + colorway label added)
-- ============================================================
CREATE TABLE IF NOT EXISTS request_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    request_id   INT NOT NULL,
    shoe_id      INT NOT NULL,
    colorway_id  INT DEFAULT NULL,
    colorway     VARCHAR(100) DEFAULT NULL,
    size         VARCHAR(10) NOT NULL,
    quantity     INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_ri_request FOREIGN KEY (request_id)
        REFERENCES stock_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_ri_shoe FOREIGN KEY (shoe_id)
        REFERENCES shoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DEFAULT ADMIN ACCOUNT  (password: admin123)
-- ============================================================
INSERT IGNORE INTO users (username, email, password, role, branch_name)
VALUES (
    'admin',
    'admin@shstorage.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    NULL
);
