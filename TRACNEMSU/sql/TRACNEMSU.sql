-- TRACNEMSU database (database name: tracnemsu) (XAMPP / MySQL)
-- How to use: open http://localhost/phpmyadmin > Import > choose this file > Go.
-- It creates the database, all tables and the sample accounts.
-- Teacher: teacher / teacher123    Students: student1..student5 / student123

CREATE DATABASE IF NOT EXISTS tracnemsu CHARACTER SET utf8mb4;
USE tracnemsu;

CREATE TABLE IF NOT EXISTS users(
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) UNIQUE,
  name VARCHAR(100),
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  role ENUM('admin','teacher','student'),
  status ENUM('active','pending') DEFAULT 'active',
  email VARCHAR(100) NULL,
  contact VARCHAR(30) NULL,
  course VARCHAR(60) NULL,
  year_level VARCHAR(20) NULL,
  section VARCHAR(20) NULL,
  parent_name VARCHAR(100) NULL,
  parent_phone VARCHAR(30) NULL,
  branch_id INT NULL
);

-- NEW v2: branches and complaints (Admin / Staff / Student use cases)
CREATE TABLE IF NOT EXISTS branches(
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE,
  address VARCHAR(200) NULL,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS complaints(
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  subject VARCHAR(120),
  message TEXT,
  status ENUM('pending','in_review','resolved') DEFAULT 'pending',
  response VARCHAR(500) NULL,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO branches(name,address) VALUES('Main Campus','Tandag City'),('Cantilan Campus','Cantilan, Surigao del Sur');

CREATE TABLE IF NOT EXISTS attendance(
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  att_date DATE,
  time_in TIME NULL,
  status ENUM('present','late','excused','absent'),
  UNIQUE(student_id,att_date),
  FOREIGN KEY(student_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS leaves(
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  date_from DATE,
  date_to DATE,
  reason VARCHAR(255),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS parent_log(
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  message VARCHAR(255),
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- NEW: teacher's attendance post (students click Present for 30 minutes)
CREATE TABLE IF NOT EXISTS att_posts(
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_date DATE,
  message VARCHAR(200) NULL,
  opens DATETIME,
  closes DATETIME,
  closed TINYINT DEFAULT 0
);

-- Sample accounts (only added if the users table is empty)
INSERT INTO users(code,name,username,password,role,status)
SELECT * FROM (SELECT NULL,'Teacher','teacher','$2b$10$Y7MYWASeP5p7dEXrzICWN.dnVEMOQp4xv/L5aiWOi0oJiNkwQO70C','teacher','active') x
WHERE NOT EXISTS (SELECT 1 FROM users);
INSERT IGNORE INTO users(code,name,username,password,role,status) VALUES
('STU001','Juan Dela Cruz','student1','$2b$10$RTJKjHcPwgcyyFJ1wYt1juwn73z5/cwYt/RsUUfSekzdcEFjgFcsi','student','active'),
('STU002','Maria Santos','student2','$2b$10$RTJKjHcPwgcyyFJ1wYt1juwn73z5/cwYt/RsUUfSekzdcEFjgFcsi','student','active'),
('STU003','Pedro Reyes','student3','$2b$10$RTJKjHcPwgcyyFJ1wYt1juwn73z5/cwYt/RsUUfSekzdcEFjgFcsi','student','active'),
('STU004','Ana Lopez','student4','$2b$10$RTJKjHcPwgcyyFJ1wYt1juwn73z5/cwYt/RsUUfSekzdcEFjgFcsi','student','active'),
('STU005','Jose Ramos','student5','$2b$10$RTJKjHcPwgcyyFJ1wYt1juwn73z5/cwYt/RsUUfSekzdcEFjgFcsi','student','active');
