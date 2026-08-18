CREATE TABLE IF NOT EXISTS booking_inventory (
  inventory_date DATE PRIMARY KEY,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  rate DECIMAL(10,2) NOT NULL DEFAULT 3500.00,
  min_stay INT NOT NULL DEFAULT 1,
  note VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_reservations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id VARCHAR(40) NOT NULL UNIQUE,
  guest_name VARCHAR(120) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  source VARCHAR(40) NOT NULL DEFAULT 'website',
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_dates (check_in, check_out),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_discounts (
  minimum_nights INT NOT NULL PRIMARY KEY,
  discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  label VARCHAR(80) DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO booking_discounts (minimum_nights, discount_percent, label) VALUES
  (2, 5.00, '2+ night discount'),
  (7, 10.00, '7+ night discount'),
  (15, 25.00, '15+ night discount'),
  (30, 40.00, '30+ night discount')
ON DUPLICATE KEY UPDATE minimum_nights = minimum_nights;