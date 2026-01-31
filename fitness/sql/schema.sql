CREATE DATABASE IF NOT EXISTS fitness_club;
USE fitness_club;

-- USERS TABLE (Authentication)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'trainer', 'member') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- MEMBERS TABLE
CREATE TABLE members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    emergency_contact VARCHAR(100),
    profile_image VARCHAR(255),
    join_date DATE DEFAULT CURRENT_DATE,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    fitness_goals TEXT,
    medical_notes TEXT,
    height_cm DECIMAL(5,2),
    weight_kg DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_name (first_name, last_name)
);

-- TRAINERS TABLE
CREATE TABLE trainers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(100),
    certification TEXT,
    experience_years INT DEFAULT 0,
    hourly_rate DECIMAL(10,2),
    availability ENUM('Full-time', 'Part-time', 'Weekends') DEFAULT 'Full-time',
    bio TEXT,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- MEMBERSHIPS TABLE
CREATE TABLE memberships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    plan_type ENUM('Monthly', 'Quarterly', 'Annual', 'Pay-as-you-go') DEFAULT 'Monthly',
    price DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    payment_status ENUM('Paid', 'Pending', 'Overdue') DEFAULT 'Pending',
    payment_method VARCHAR(50),
    auto_renew BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_expiry (expiry_date),
    INDEX idx_status (payment_status)
);

-- ATTENDANCE TABLE
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    check_in DATETIME DEFAULT CURRENT_TIMESTAMP,
    check_out DATETIME,
    workout_type VARCHAR(50),
    trainer_id INT,
    notes TEXT,
    duration_minutes INT,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id),
    INDEX idx_date (check_in),
    INDEX idx_member (member_id)
);

-- WORKOUT PLANS TABLE
CREATE TABLE workout_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_name VARCHAR(100) NOT NULL,
    description TEXT,
    difficulty_level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
    duration_weeks INT DEFAULT 4,
    target_area VARCHAR(100),
    equipment_needed TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_difficulty (difficulty_level)
);

-- WORKOUT EXERCISES TABLE
CREATE TABLE workout_exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workout_plan_id INT NOT NULL,
    exercise_name VARCHAR(100) NOT NULL,
    sets INT DEFAULT 3,
    reps VARCHAR(50),
    rest_seconds INT DEFAULT 60,
    instructions TEXT,
    muscle_group VARCHAR(50),
    day_number INT,
    FOREIGN KEY (workout_plan_id) REFERENCES workout_plans(id) ON DELETE CASCADE,
    INDEX idx_plan (workout_plan_id)
);

-- MEMBER WORKOUT ASSIGNMENTS
CREATE TABLE member_workouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    workout_plan_id INT NOT NULL,
    assigned_by INT,
    start_date DATE NOT NULL,
    end_date DATE,
    progress_notes TEXT,
    status ENUM('Active', 'Completed', 'Paused') DEFAULT 'Active',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (workout_plan_id) REFERENCES workout_plans(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id),
    INDEX idx_member_status (member_id, status)
);

-- NUTRITION LOGS TABLE
CREATE TABLE nutrition_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    meal_type ENUM('Breakfast', 'Lunch', 'Dinner', 'Snack', 'Pre-workout', 'Post-workout') DEFAULT 'Snack',
    food_name VARCHAR(100) NOT NULL,
    calories INT NOT NULL,
    protein_g DECIMAL(5,2),
    carbs_g DECIMAL(5,2),
    fat_g DECIMAL(5,2),
    serving_size VARCHAR(50),
    log_date DATE DEFAULT CURRENT_DATE,
    log_time TIME DEFAULT CURRENT_TIME,
    notes TEXT,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_member_date (member_id, log_date)
);

-- PROGRESS TRACKING TABLE
CREATE TABLE progress_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    weight_kg DECIMAL(5,2),
    body_fat_percentage DECIMAL(4,2),
    chest_cm DECIMAL(5,2),
    waist_cm DECIMAL(5,2),
    hips_cm DECIMAL(5,2),
    biceps_cm DECIMAL(5,2),
    thighs_cm DECIMAL(5,2),
    notes TEXT,
    logged_by INT,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (logged_by) REFERENCES users(id),
    INDEX idx_member (member_id)
);

-- PAYMENTS TABLE
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    membership_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE DEFAULT CURRENT_DATE,
    payment_method ENUM('Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Online') DEFAULT 'Cash',
    transaction_id VARCHAR(100),
    status ENUM('Completed', 'Pending', 'Failed', 'Refunded') DEFAULT 'Completed',
    notes TEXT,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id),
    INDEX idx_date (payment_date)
);

-- NOTIFICATIONS TABLE (New)
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
);

-- CSRF TOKENS TABLE (Security)
CREATE TABLE csrf_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(255) NOT NULL UNIQUE,
    user_id INT,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expiry (expires_at)
);

-- AUDIT LOG TABLE (Security)
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);
