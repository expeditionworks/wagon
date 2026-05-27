-- Conestoga Wagon Database Schema

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    delivery_method VARCHAR(20) DEFAULT "console",
    device_token VARCHAR(255),
    turn_interval_minutes INT DEFAULT 1440,
    next_turn_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS player_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    day INT DEFAULT 1,
    mile INT DEFAULT 0,
    morale INT DEFAULT 100,
    dollars DECIMAL(10,2) DEFAULT 800.00,
    ration_size VARCHAR(20) DEFAULT "full",
    inventory JSON,
    family JSON,
    log JSON,
    last_log_item JSON,
    current_trail VARCHAR(50) DEFAULT "oregon",
    difficulty VARCHAR(20) DEFAULT "medium",
    delay_days INT DEFAULT 0,
    delay_status VARCHAR(20) DEFAULT "completed",
    miles_traveled INT DEFAULT 0,
    weather JSON,
    pending_action JSON,
    game_over TINYINT(1) DEFAULT 0,
    profession VARCHAR(50) DEFAULT NULL,
    profession_bonus VARCHAR(50) DEFAULT NULL,
    start_date DATE DEFAULT "1849-05-01",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id)
);

-- Game history table — records completed runs
CREATE TABLE IF NOT EXISTS game_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    outcome ENUM('success','death','abandoned') DEFAULT 'abandoned',
    trail VARCHAR(50),
    days_traveled INT DEFAULT 0,
    miles_reached INT DEFAULT 0,
    cause VARCHAR(100),
    final_dollars DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id)
);

INSERT IGNORE INTO players (id, name, email, delivery_method)
VALUES (1, "Test Player", "test@test.com", "console");
