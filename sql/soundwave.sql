-- SoundWave Music Streaming Platform
-- Database Schema
-- Course: Web Technologies and Secure Websites

CREATE DATABASE IF NOT EXISTS soundwave CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE soundwave;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    avatar_url VARCHAR(255) DEFAULT 'uploads/covers/default_avatar.png',
    bio TEXT,
    role ENUM('listener', 'artist', 'admin') DEFAULT 'listener',
    account_status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Artists table (extends users who are artists)
CREATE TABLE artists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    artist_name VARCHAR(100) NOT NULL,
    genre VARCHAR(50),
    country VARCHAR(50),
    bio TEXT,
    cover_image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Songs table
CREATE TABLE songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artist_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    album VARCHAR(200),
    genre VARCHAR(50),
    duration INT NOT NULL COMMENT 'Duration in seconds',
    file_path VARCHAR(255) NOT NULL,
    cover_image VARCHAR(255),
    play_count INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
);

-- Playlists table
CREATE TABLE playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    is_public TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Playlist songs (junction table)
CREATE TABLE playlist_songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    song_id INT NOT NULL,
    position INT NOT NULL DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_playlist_song (playlist_id, song_id)
);

-- Likes table (users liking songs)
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    liked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, song_id)
);

-- Follows table (users following artists)
CREATE TABLE follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artist_id INT NOT NULL,
    followed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (user_id, artist_id)
);

-- Sessions table for secure session management
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample data
INSERT INTO users (username, email, password_hash, display_name, role) VALUES
('admin', 'admin@admin.co', 'Admin123!', 'Administrator', 'admin'),
('artist', 'artist@example.com', 'Artist123!', 'Artist', 'artist'),
('listener', 'listener@example.com', 'Listener123!', 'Listener', 'listener');

INSERT INTO artists (user_id, artist_name, genre, country, bio) VALUES
(2, 'Artist', 'Electronic', 'Latvia', 'Electronic music producer from Riga.'),
(3, 'Listener', 'N/A', 'N/A', 'Music lover and avid listener.');
