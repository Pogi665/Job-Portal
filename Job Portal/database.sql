-- Create the database
CREATE DATABASE IF NOT EXISTS job_portal;
USE job_portal;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('job_seeker', 'employer', 'admin') NOT NULL,
    headline VARCHAR(255),
    location VARCHAR(255),
    avatar_url VARCHAR(255),
    portfolio_url VARCHAR(255),
    summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Companies table
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    industry VARCHAR(255),
    logo_url VARCHAR(255),
    website VARCHAR(255),
    description TEXT,
    size VARCHAR(100),
    location VARCHAR(255),
    culture_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Jobs table
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    company_id INT NOT NULL,
    location VARCHAR(255) NOT NULL,
    type ENUM('Full-time', 'Part-time', 'Contract', 'Internship') NOT NULL,
    category VARCHAR(255),
    salary_min INT DEFAULT NULL,
    salary_max INT DEFAULT NULL,
    salary_currency VARCHAR(10) DEFAULT 'USD',
    salary_period ENUM('hourly', 'monthly', 'annually') DEFAULT 'annually',
    description TEXT NOT NULL,
    requirements TEXT,
    benefits TEXT,
    experience_level ENUM('Entry Level', 'Mid Level', 'Senior Level') NOT NULL,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline DATE,
    status ENUM('Active', 'Closed', 'Draft', 'Paused') DEFAULT 'Active',
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Job skills
CREATE TABLE job_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    skill VARCHAR(255) NOT NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Applications
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    resume_id INT,
    status ENUM('Pending', 'Reviewed', 'Shortlisted', 'Interview', 'Rejected', 'Hired') DEFAULT 'Pending',
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(job_id, user_id)
);

-- Saved jobs table
CREATE TABLE saved_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    saved_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    UNIQUE(user_id, job_id)
);

-- Add more tables as needed for other features

-- Update existing indexes if needed
CREATE INDEX idx_jobs_salary ON jobs(salary_min, salary_max);

-- Job alerts table
CREATE TABLE job_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    keywords VARCHAR(255),
    location VARCHAR(255),
    category VARCHAR(255),
    job_type VARCHAR(50),
    experience_level VARCHAR(50),
    min_salary INT,
    frequency ENUM('daily', 'weekly', 'instant') NOT NULL DEFAULT 'weekly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_sent_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Job seeker profiles
CREATE TABLE job_seeker_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    job_title VARCHAR(255),
    experience TEXT,
    education TEXT,
    salary_expectation VARCHAR(255),
    resume_url VARCHAR(255),
    is_available TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User skills
CREATE TABLE user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Job seeker preferences
CREATE TABLE job_seeker_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_type ENUM('job_type', 'location', 'category', 'salary') NOT NULL,
    preference_value VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
