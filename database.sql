-- CBM • Criado para MySQL 8.0 / MariaDB do XAMPP
-- Importe no phpMyAdmin: http://localhost:8080/phpmyadmin/ (a URL depende da configuração Apache)
CREATE DATABASE IF NOT EXISTS cbm_criadouro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cbm_criadouro;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS birds (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    scientific_name VARCHAR(180) NOT NULL,
    sex ENUM('Macho','Fêmea','Indefinido') NOT NULL DEFAULT 'Indefinido',
    age_months SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    hatch_date DATE NULL,
    ring_code VARCHAR(100) NULL,
    registry_code VARCHAR(120) NULL,
    color VARCHAR(100) NULL,
    origin_info VARCHAR(250) NULL,
    feeding TEXT NULL,
    description TEXT NULL,
    care_notes TEXT NULL,
    photo_path VARCHAR(255) NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_catalog (is_visible, is_available, is_featured),
    INDEX idx_bird_name (name),
    CONSTRAINT fk_bird_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_bird_editor FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS interests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    bird_id INT UNSIGNED NOT NULL,
    message VARCHAR(500) NULL,
    status ENUM('Novo','Em atendimento','Encerrado') NOT NULL DEFAULT 'Novo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_interest (user_id, bird_id),
    INDEX idx_interest_status (status, created_at),
    CONSTRAINT fk_interest_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_interest_bird FOREIGN KEY (bird_id) REFERENCES birds(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Após importar, cadastre-se na interface, então promova a conta admin pelo script CLI
-- tools/promover_admin.php. Nunca defina o papel admin no formulário público.
