-- Fayez OS schema, v0.5.0: accounts/files/settings active; entity tables reserved.
-- Target MySQL 8+ or MariaDB 10.4+ (XAMPP). UTC timestamps, utf8mb4.
-- Required for Phase 5 accounts. Existing databases must run migrate-phase5.php.
-- No sample users, balances, passwords, weather, news, or exchange rates are inserted.
CREATE DATABASE IF NOT EXISTS fayez_os CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fayez_os;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id CHAR(32) NOT NULL,
    session_version INT UNSIGNED NOT NULL DEFAULT 1,
    display_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    pin_hash VARCHAR(255) NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Kabul',
    preferred_currency CHAR(3) NOT NULL DEFAULT 'AFN',
    email_verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_public (public_id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    scope ENUM('expense','income','note','task','event','contact') NOT NULL,
    name VARCHAR(80) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#B9E9D0',
    icon VARCHAR(32) NOT NULL DEFAULT 'folder',
    archived_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_scope (user_id,scope,name),
    UNIQUE KEY uq_categories_owner (id,user_id),
    CONSTRAINT fk_categories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    amount DECIMAL(18,4) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'AFN',
    transaction_date DATE NOT NULL,
    description VARCHAR(1000) NOT NULL DEFAULT '',
    payment_method ENUM('cash','bank','card','mobile','other') NOT NULL DEFAULT 'cash',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_expenses_user_date (user_id,transaction_date,id),
    KEY ix_expenses_category (category_id,user_id),
    CONSTRAINT ck_expenses_amount CHECK (amount > 0),
    CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_expenses_category FOREIGN KEY (category_id,user_id) REFERENCES categories(id,user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS income (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    amount DECIMAL(18,4) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'AFN',
    transaction_date DATE NOT NULL,
    description VARCHAR(1000) NOT NULL DEFAULT '',
    payment_method ENUM('cash','bank','card','mobile','other') NOT NULL DEFAULT 'cash',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_income_user_date (user_id,transaction_date,id),
    KEY ix_income_category (category_id,user_id),
    CONSTRAINT ck_income_amount CHECK (amount > 0),
    CONSTRAINT fk_income_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_income_category FOREIGN KEY (category_id,user_id) REFERENCES categories(id,user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS budgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    month_start DATE NOT NULL COMMENT 'First day of month',
    amount DECIMAL(18,4) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'AFN',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_budgets_user_month (user_id,month_start,currency),
    CONSTRAINT ck_budgets_amount CHECK (amount >= 0),
    CONSTRAINT fk_budgets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    client_id CHAR(36) NOT NULL COMMENT 'UUID generated offline; idempotent sync key',
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    content MEDIUMTEXT NOT NULL COMMENT 'Plain text or restricted structured formatting; not trusted HTML',
    checklist JSON NULL,
    note_type ENUM('text','checklist') NOT NULL DEFAULT 'text',
    color CHAR(7) NOT NULL DEFAULT '#B9E9D0',
    is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    version BIGINT UNSIGNED NOT NULL DEFAULT 1,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notes_client (user_id,client_id),
    KEY ix_notes_user_updated (user_id,updated_at,id),
    KEY ix_notes_user_pin (user_id,is_pinned,deleted_at),
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_category FOREIGN KEY (category_id,user_id) REFERENCES categories(id,user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    client_id CHAR(36) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    due_at DATETIME NULL,
    completed_at DATETIME NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 1,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tasks_client (user_id,client_id),
    KEY ix_tasks_user_due (user_id,completed_at,due_at),
    KEY ix_tasks_user_updated (user_id,updated_at,id),
    CONSTRAINT fk_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_category FOREIGN KEY (category_id,user_id) REFERENCES categories(id,user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Kabul',
    is_all_day BOOLEAN NOT NULL DEFAULT FALSE,
    reminder_minutes SMALLINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_events_user_start (user_id,starts_at),
    CONSTRAINT ck_events_time CHECK (ends_at >= starts_at),
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_events_category FOREIGN KEY (category_id,user_id) REFERENCES categories(id,user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    display_name VARCHAR(150) NOT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    email VARCHAR(254) NOT NULL DEFAULT '',
    address VARCHAR(1000) NOT NULL DEFAULT '',
    avatar_storage_key VARCHAR(255) NULL COMMENT 'Validated local key, never arbitrary URL',
    is_favorite BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_contacts_user_name (user_id,display_name),
    KEY ix_contacts_user_favorite (user_id,is_favorite),
    CONSTRAINT fk_contacts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_contacts_category FOREIGN KEY (category_id,user_id) REFERENCES categories(id,user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookmarks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(2048) NOT NULL,
    url_hash CHAR(64) NOT NULL COMMENT 'SHA256 for deduplication',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bookmarks_user_url (user_id,url_hash),
    CONSTRAINT fk_bookmarks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS saved_news (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_article_id VARCHAR(191) NOT NULL,
    title VARCHAR(500) NOT NULL,
    source_url VARCHAR(2048) NOT NULL,
    summary TEXT NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saved_news_article (user_id,provider,provider_article_id),
    KEY ix_saved_news_date (user_id,created_at),
    CONSTRAINT fk_saved_news_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reading_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    article_url VARCHAR(2048) NOT NULL,
    url_hash CHAR(64) NOT NULL,
    title VARCHAR(500) NOT NULL,
    read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reading_history_url (user_id,url_hash),
    KEY ix_reading_history_user_date (user_id,read_at),
    CONSTRAINT fk_reading_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 1,
    original_name VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    storage_key VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_category ENUM('document','image','video','audio','other') NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_files_storage (storage_key),
    KEY ix_files_user_category (user_id,file_category,created_at),
    CONSTRAINT fk_files_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('system','task','expense','event','news') NOT NULL DEFAULT 'system',
    title VARCHAR(200) NOT NULL,
    body VARCHAR(2000) NOT NULL,
    route VARCHAR(100) NULL COMMENT 'Internal allowlisted route, not arbitrary JavaScript or URL',
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_notifications_user_read (user_id,read_at,created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    setting_key VARCHAR(64) NOT NULL,
    setting_value JSON NOT NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_settings_user_key (user_id,setting_key),
    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_hash CHAR(64) NOT NULL COMMENT 'Hash only, never store a bearer session ID',
    device_label VARCHAR(150) NOT NULL DEFAULT '',
    last_seen_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sessions_hash (session_hash),
    KEY ix_sessions_user_expiry (user_id,expires_at),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_password_resets_token (token_hash),
    KEY ix_password_resets_expiry (expires_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sync_operations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    operation_id CHAR(36) NOT NULL,
    entity_type ENUM('note','task','setting') NOT NULL,
    entity_client_id VARCHAR(64) NOT NULL,
    result_version BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sync_operation (user_id,operation_id),
    CONSTRAINT fk_sync_operations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed per-user categories during registration in Phase 5, inside a transaction:
-- Food, Transport, Shopping, Bills, Education, Health, Entertainment, Other.
-- Keep balances derived from income/expenses; do not store a second mutable balance.
