-- migrations/001_init.sql

-- Schema: users
CREATE TABLE IF NOT EXISTS users (
  id BIGINT PRIMARY KEY,
  role ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user',
  state VARCHAR(128) DEFAULT 'none',
  data JSON DEFAULT NULL,
  spam_until INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- files
CREATE TABLE IF NOT EXISTS files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  file_id VARCHAR(255) NOT NULL,
  file_type VARCHAR(32),
  file_size INT DEFAULT 0,
  uploader_id BIGINT,
  dl_count INT DEFAULT 0,
  pass_encrypted LONGBLOB DEFAULT NULL,
  meta_encrypted LONGBLOB DEFAULT NULL,
  msg_id BIGINT DEFAULT NULL,
  channel_posted TINYINT(1) DEFAULT 0,
  zd_filter TINYINT(1) DEFAULT 0,
  ghfl_ch TINYINT(1) DEFAULT 1,
  mahdodl INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (uploader_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- channels
CREATE TABLE IF NOT EXISTS channels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  telegram_id VARCHAR(64) UNIQUE,
  link VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- settings (single row)
CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY DEFAULT 1,
  bot_id BIGINT DEFAULT NULL,
  bot_mode ENUM('on','off') DEFAULT 'on',
  mtn_s_ch_encrypted LONGBLOB DEFAULT NULL,
  chupl BIGINT DEFAULT NULL,
  dearahmadi INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- jobs queue
CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(64) NOT NULL,
  payload LONGBLOB NOT NULL,
  attempts INT DEFAULT 0,
  available_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','processing','done','failed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
