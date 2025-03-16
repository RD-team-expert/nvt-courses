-- This SQL file outlines the necessary tables for the Attendance & Course Management System
-- The statements are provided in MySQL syntax. Adjust data types and constraints as needed.

-- 1. users Table
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 2. courses Table
CREATE TABLE IF NOT EXISTS courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
    level VARCHAR(50) NULL,
    duration INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 3. course_user (Pivot Table)
CREATE TABLE IF NOT EXISTS course_user (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    user_status ENUM('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
    admin_rating TINYINT NULL,
    admin_comment TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_course_user_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_course_user_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE
);

-- 4. clockings Table
CREATE TABLE IF NOT EXISTS clockings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NULL,
    clock_in DATETIME NOT NULL,
    clock_out DATETIME NULL,
    rating TINYINT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_clockings_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_clockings_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE SET NULL
);

-- 5. notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
);

-- Additional Laravel Default Tables (Optional)
-- 6. password_resets, jobs, failed_jobs, personal_access_tokens, etc.
-- If you use these features, create them accordingly (often included by default with Laravel).
