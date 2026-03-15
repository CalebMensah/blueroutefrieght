-- ══════════════════════════════════════════
--  BlueRoute Security & Shipping — Database
-- ══════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS blueroute CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blueroute;

-- ── Shipments ──────────────────────────────
CREATE TABLE IF NOT EXISTS shipments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  reference     VARCHAR(30)  NOT NULL UNIQUE,
  client_name   VARCHAR(120) NOT NULL,
  client_email  VARCHAR(180),
  origin        VARCHAR(120) NOT NULL,
  destination   VARCHAR(120) NOT NULL,
  service_type  ENUM('Secure Air Freight','Armored Ground Transport','Ocean Cargo Security') NOT NULL DEFAULT 'Secure Air Freight',
  status        ENUM('processing','transit','delivered','pending','cancelled') NOT NULL DEFAULT 'processing',
  current_location VARCHAR(180),
  weight_kg     DECIMAL(8,2),
  description   TEXT,
  notes         TEXT,
  eta           DATE,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Tracking events ────────────────────────
CREATE TABLE IF NOT EXISTS tracking_events (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  shipment_id   INT NOT NULL,
  event_title   VARCHAR(200) NOT NULL,
  location      VARCHAR(180),
  event_time    DATETIME NOT NULL,
  is_done       TINYINT(1) DEFAULT 0,
  is_active     TINYINT(1) DEFAULT 0,
  sort_order    INT DEFAULT 0,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Vault records ──────────────────────────
CREATE TABLE IF NOT EXISTS vaults (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  reference     VARCHAR(30)  NOT NULL UNIQUE,
  client_name   VARCHAR(120) NOT NULL,
  client_email  VARCHAR(180),
  facility      VARCHAR(180) NOT NULL,
  contents      TEXT,
  status        ENUM('verified','review','pending') NOT NULL DEFAULT 'verified',
  last_audit    DATE,
  next_audit    DATE,
  notes         TEXT,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Admin users ────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(60)  NOT NULL UNIQUE,
  email         VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('super','editor') DEFAULT 'editor',
  last_login    DATETIME,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Activity log ───────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  type          VARCHAR(30),
  message       TEXT,
  admin_id      INT,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ══════════════════════════════════════════
--  SEED DATA
-- ══════════════════════════════════════════

-- Admin user (password: admin123)
INSERT IGNORE INTO admin_users (username, email, password_hash, role) VALUES
('admin', 'admin@blueroute.uk', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super');

-- Sample shipments
INSERT IGNORE INTO shipments (reference, client_name, client_email, origin, destination, service_type, status, current_location, weight_kg, description, eta) VALUES
('BLR100000001', 'Marcus Webb',    'marcus@example.com', 'London, UK',          'Dubai, UAE',            'Secure Air Freight',       'transit',    'Frankfurt Hub, Germany',    12.50, 'Gold bullion bars',           '2026-03-14'),
('BLR100000002', 'Yuki Tanaka',    'yuki@example.com',   'Singapore',           'Frankfurt, Germany',    'Ocean Cargo Security',     'processing', 'Singapore Port',             8.00, 'Artwork and collectibles',    '2026-03-20'),
('BLR100000003', 'Amara Diallo',   'amara@example.com',  'Dubai, UAE',          'London, UK',            'Armored Ground Transport', 'delivered',  'London, UK',                 5.20, 'Jewelry collection',          '2026-03-08'),
('BLR100000004', 'Stefan Bauer',   'stefan@example.com', 'Frankfurt, Germany',  'Singapore',             'Secure Air Freight',       'transit',    'Dubai Layover Hub',          3.00, 'Document archive',            '2026-03-16'),
('BLR100000005', 'Helen Park',     'helen@example.com',  'London, UK',          'Singapore',             'Secure Air Freight',       'pending',    'Newmarket HQ',               7.80, 'Precious metals',             '2026-03-22');

-- Tracking events for BLR100000001
SET @s1 = (SELECT id FROM shipments WHERE reference = 'BLR100000001');
INSERT IGNORE INTO tracking_events (shipment_id, event_title, location, event_time, is_done, is_active, sort_order) VALUES
(@s1, 'Shipment Collected',       'Newmarket, UK',             '2026-03-08 09:15:00', 1, 0, 1),
(@s1, 'Security Check Passed',    'Heathrow Hub, UK',          '2026-03-08 11:30:00', 1, 0, 2),
(@s1, 'Departed London',          'London Heathrow Airport',   '2026-03-09 02:45:00', 1, 0, 3),
(@s1, 'In Transit — Frankfurt',   'Frankfurt Hub, Germany',    '2026-03-10 06:00:00', 0, 1, 4),
(@s1, 'Arrived Destination Hub',  'Dubai DIFC, UAE',           '2026-03-12 00:00:00', 0, 0, 5),
(@s1, 'Delivered & Signed',       'Recipient Address, Dubai',  '2026-03-14 00:00:00', 0, 0, 6);

-- Tracking events for BLR100000002
SET @s2 = (SELECT id FROM shipments WHERE reference = 'BLR100000002');
INSERT IGNORE INTO tracking_events (shipment_id, event_title, location, event_time, is_done, is_active, sort_order) VALUES
(@s2, 'Booking Confirmed',        'Singapore',                 '2026-03-09 09:00:00', 1, 0, 1),
(@s2, 'Awaiting Collection',      'Singapore Port',            '2026-03-11 00:00:00', 0, 1, 2),
(@s2, 'Vessel Departure',         'Singapore Port',            '2026-03-13 00:00:00', 0, 0, 3),
(@s2, 'Arrived Frankfurt',        'Frankfurt Port, Germany',   '2026-03-20 00:00:00', 0, 0, 4);

-- Tracking events for BLR100000003
SET @s3 = (SELECT id FROM shipments WHERE reference = 'BLR100000003');
INSERT IGNORE INTO tracking_events (shipment_id, event_title, location, event_time, is_done, is_active, sort_order) VALUES
(@s3, 'Shipment Collected',       'Dubai, UAE',                '2026-03-05 10:00:00', 1, 0, 1),
(@s3, 'Security Cleared',         'Dubai International',       '2026-03-05 14:00:00', 1, 0, 2),
(@s3, 'In Transit',               'Airspace',                  '2026-03-06 03:00:00', 1, 0, 3),
(@s3, 'Arrived London',           'Heathrow Hub, UK',          '2026-03-07 07:00:00', 1, 0, 4),
(@s3, 'Out for Delivery',         'London, UK',                '2026-03-08 09:00:00', 1, 0, 5),
(@s3, 'Delivered & Signed',       'London, UK',                '2026-03-08 11:00:00', 1, 0, 6);

-- Tracking events for BLR100000004
SET @s4 = (SELECT id FROM shipments WHERE reference = 'BLR100000004');
INSERT IGNORE INTO tracking_events (shipment_id, event_title, location, event_time, is_done, is_active, sort_order) VALUES
(@s4, 'Shipment Collected',       'Frankfurt, Germany',        '2026-03-10 06:00:00', 1, 0, 1),
(@s4, 'Departed Frankfurt',       'Frankfurt Airport',         '2026-03-10 10:00:00', 1, 0, 2),
(@s4, 'Dubai Layover',            'Dubai International Hub',   '2026-03-10 18:00:00', 0, 1, 3),
(@s4, 'Departed Dubai',           'Dubai International Hub',   '2026-03-11 08:00:00', 0, 0, 4),
(@s4, 'Arrived Singapore',        'Singapore Changi',          '2026-03-11 20:00:00', 0, 0, 5),
(@s4, 'Delivered & Signed',       'Singapore',                 '2026-03-16 00:00:00', 0, 0, 6);