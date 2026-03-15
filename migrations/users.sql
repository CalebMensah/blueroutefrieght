-- ══════════════════════════════════════════
--  BlueRoute — Users Table Migration
--  Run: mysql -u blueuser -p'BlueRoute2025!' blueroute < migrations/users.sql
-- ══════════════════════════════════════════

USE blueroute;

CREATE TABLE IF NOT EXISTS users (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  first_name       VARCHAR(80)  NOT NULL,
  last_name        VARCHAR(80)  NOT NULL,
  email            VARCHAR(180) NOT NULL UNIQUE,
  phone            VARCHAR(30),
  country          VARCHAR(80),
  state            VARCHAR(80),
  city             VARCHAR(80),
  zip_code         VARCHAR(20),
  address          TEXT,
  username         VARCHAR(60)  NOT NULL UNIQUE,
  password_hash    VARCHAR(255) NOT NULL,
  email_verified   TINYINT(1)   DEFAULT 0,
  verify_token     VARCHAR(64)  DEFAULT NULL,
  reset_token      VARCHAR(64)  DEFAULT NULL,
  reset_expires    DATETIME     DEFAULT NULL,
  status           ENUM('active','suspended','pending') DEFAULT 'pending',
  last_login       DATETIME     DEFAULT NULL,
  login_attempts   INT          DEFAULT 0,
  locked_until     DATETIME     DEFAULT NULL,
  created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting table (brute force protection)
CREATE TABLE IF NOT EXISTS rate_limits (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45)  NOT NULL,
  action     VARCHAR(40)  NOT NULL,
  attempts   INT          DEFAULT 1,
  last_attempt DATETIME   DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_action (ip_address, action)
) ENGINE=InnoDB;

-- User sessions
CREATE TABLE IF NOT EXISTS user_sessions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT         NOT NULL,
  token        VARCHAR(64) NOT NULL UNIQUE,
  ip_address   VARCHAR(45),
  user_agent   VARCHAR(255),
  expires_at   DATETIME    NOT NULL,
  created_at   DATETIME    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;