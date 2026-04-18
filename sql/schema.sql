CREATE DATABASE IF NOT EXISTS hokm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hokm_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_online TINYINT(1) NOT NULL DEFAULT 0,
  last_seen_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_online (is_online, last_seen_at)
) ENGINE=InnoDB;

CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  owner_id INT NOT NULL,
  is_private TINYINT(1) NOT NULL DEFAULT 0,
  invite_code VARCHAR(16) NOT NULL UNIQUE,
  status ENUM('waiting','playing','finished') NOT NULL DEFAULT 'waiting',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_rooms_public (is_private, status, created_at)
) ENGINE=InnoDB;

CREATE TABLE room_players (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  user_id INT NOT NULL,
  seat_position TINYINT NOT NULL,
  role ENUM('leader','player','spectator') NOT NULL DEFAULT 'player',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_room_user (room_id, user_id),
  UNIQUE KEY uq_room_seat (room_id, seat_position),
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  status ENUM('active','finished') NOT NULL DEFAULT 'active',
  phase ENUM('team_naming','trump_selection','playing','finished') NOT NULL DEFAULT 'team_naming',
  dealer_position TINYINT NOT NULL DEFAULT 0,
  trick_leader_position TINYINT NOT NULL DEFAULT 0,
  current_turn TINYINT NOT NULL DEFAULT 0,
  trump_suit ENUM('hearts','diamonds','clubs','spades') NULL,
  team_a_name VARCHAR(50) NOT NULL DEFAULT 'تیم آبی',
  team_b_name VARCHAR(50) NOT NULL DEFAULT 'تیم قرمز',
  team_a_points INT NOT NULL DEFAULT 0,
  team_b_points INT NOT NULL DEFAULT 0,
  target_points INT NOT NULL DEFAULT 7,
  team_a_tricks INT NOT NULL DEFAULT 0,
  team_b_tricks INT NOT NULL DEFAULT 0,
  hands_json JSON NULL,
  current_trick_json JSON NULL,
  revision INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  INDEX idx_games_room (room_id, id),
  INDEX idx_games_revision (room_id, revision)
) ENGINE=InnoDB;

CREATE TABLE game_moves (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  game_id INT NOT NULL,
  user_id INT NULL,
  action VARCHAR(50) NOT NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_moves_game (game_id, id)
) ENGINE=InnoDB;

CREATE TABLE chat_messages (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  user_id INT NOT NULL,
  message VARCHAR(300) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_chat_room (room_id, id)
) ENGINE=InnoDB;

CREATE TABLE leaderboard (
  user_id INT PRIMARY KEY,
  games_played INT NOT NULL DEFAULT 0,
  wins INT NOT NULL DEFAULT 0,
  losses INT NOT NULL DEFAULT 0,
  points INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_leaderboard_score (points, wins)
) ENGINE=InnoDB;
