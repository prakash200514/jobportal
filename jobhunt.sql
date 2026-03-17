-- JobHunt SQL Schema and Sample Data
-- Compatible with MySQL Workbench

-- Create database
CREATE DATABASE IF NOT EXISTS jobhunt_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jobhunt_db;

-- Drop existing tables (for idempotent imports)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS job_applications;
DROP TABLE IF EXISTS job_quiz_questions;
DROP TABLE IF EXISTS testimonials;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS job_categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('job_seeker', 'employer') DEFAULT 'job_seeker',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job categories table
CREATE TABLE job_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    job_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Companies table
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    location VARCHAR(100),
    description TEXT,
    website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jobs table
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    company_id INT,
    category_id INT,
    location VARCHAR(100),
    job_type ENUM('Full Time', 'Part Time', 'Contract', 'Internship') DEFAULT 'Full Time',
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    salary_currency VARCHAR(10) DEFAULT 'USD',
    positions_available INT DEFAULT 1,
    requirements TEXT,
    benefits TEXT,
    status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_jobs_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_jobs_category FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_jobs_posted_by FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job applications table
CREATE TABLE job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    resume_path VARCHAR(255),
    cover_letter TEXT,
    status ENUM('pending', 'reviewed', 'shortlisted', 'rejected', 'hired') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Testimonials table
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    content TEXT NOT NULL,
    rating INT DEFAULT 5,
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_testimonials_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job quiz questions table
CREATE TABLE job_quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option ENUM('A','B','C','D') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data
START TRANSACTION;

-- Job categories
INSERT INTO job_categories (name, description, icon, job_count) VALUES
('Design', 'Creative and visual design positions', 'ri-pencil-ruler-2-fill', 200),
('Sales', 'Sales and business development roles', 'ri-bar-chart-box-fill', 350),
('Marketing', 'Digital marketing and advertising', 'ri-megaphone-fill', 500),
('Finance', 'Financial and accounting positions', 'ri-wallet-3-fill', 200),
('Automobile', 'Automotive industry jobs', 'ri-car-fill', 250),
('Logistics / Delivery', 'Transportation and logistics', 'ri-truck-fill', 1000),
('Admin', 'Administrative and support roles', 'ri-computer-fill', 100),
('Construction', 'Construction and engineering', 'ri-building-fill', 500);

-- Companies
INSERT INTO companies (name, logo, location, description) VALUES
('Figma', 'assets/figma.png', 'USA', 'Design and collaboration platform'),
('Google', 'assets/google.png', 'USA', 'Technology and internet services'),
('LinkedIn', 'assets/linkedin.png', 'Germany', 'Professional networking platform'),
('Amazon', 'assets/amazon.png', 'USA', 'E-commerce and cloud computing'),
('Twitter', 'assets/twitter.png', 'USA', 'Social media platform'),
('Microsoft', 'assets/microsoft.png', 'USA', 'Technology and software company');

-- Jobs
INSERT INTO jobs (
    title, description, company_id, category_id, location, job_type,
    salary_min, salary_max, salary_currency, positions_available,
    requirements, benefits, status, posted_by
) VALUES
('Senior Product Engineer', 'Lead the development of innovative product solutions, leveraging your expertise in engineering and product management to drive success.', 1, 1, 'USA', 'Full Time', 145000, 145000, 'USD', 12, '5+ years experience in product development', 'Health insurance, 401k, flexible hours', 'active', 1),
('Project Manager', 'Manage project timelines and budgets to ensure successful delivery of projects on schedule, while maintaining clear communication with stakeholders.', 2, 2, 'USA', 'Full Time', 95000, 95000, 'USD', 2, 'PMP certification preferred', 'Health insurance, 401k, remote work', 'active', 1),
('Full Stack Developer', 'Develop and maintain both front-end and back-end components of web applications, utilizing a wide range of programming languages and frameworks.', 3, 1, 'Germany', 'Full Time', 35000, 35000, 'USD', 10, 'Experience with React, Node.js, and databases', 'Health insurance, professional development', 'active', 1),
('Front-end Developer', 'Design and implement user interfaces using HTML, CSS, and JavaScript, collaborating closely with designers and back-end developers.', 4, 1, 'USA', 'Full Time', 101000, 101000, 'USD', 20, 'Strong HTML, CSS, JavaScript skills', 'Health insurance, 401k, stock options', 'active', 1),
('ReactJS Developer', 'Specialize in building dynamic and interactive user interfaces using the ReactJS library, leveraging your expertise in JavaScript and front-end development.', 5, 1, 'USA', 'Full Time', 98000, 98000, 'USD', 6, '3+ years React experience', 'Health insurance, 401k, flexible schedule', 'active', 1),
('Python Developer', 'Develop scalable and efficient backend systems and applications using Python, utilizing your proficiency in Python programming and software development.', 6, 1, 'USA', 'Full Time', 80000, 80000, 'USD', 9, 'Strong Python and Django/Flask experience', 'Health insurance, 401k, remote work', 'active', 1);

-- Testimonials
INSERT INTO testimonials (user_id, name, position, content, rating, image) VALUES
(NULL, 'Sarah Patel', 'Graphic Designer', 'Searching for a job can be overwhelming, but this platform made it simple and efficient. I uploaded my resume, applied to a few positions, and soon enough, I was hired! Thank you for helping me kickstart my career!', 5, 'assets/client-1.jpg'),
(NULL, 'Michael Brown', 'Recent Graduate', 'As a recent graduate, I was unsure where to start my job search. This website guided me through the process step by step. From creating my profile to receiving job offers, everything was seamless. I\'m now happily employed thanks to this platform!', 5, 'assets/client-2.jpg'),
(NULL, 'David Smith', 'Software Engineer', 'Creating an account was a breeze, and I was amazed by the number of job opportunities available. Thanks to this website, I found the perfect position that aligned perfectly with my career goals.', 5, 'assets/client-3.jpg');

COMMIT;

-- Done.
