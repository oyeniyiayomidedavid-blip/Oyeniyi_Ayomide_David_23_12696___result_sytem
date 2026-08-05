CREATE DATABASE result_db;

USE result_db;

CREATE TABLE admin(
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(50)
);

CREATE TABLE students(
    id INT AUTO_INCREMENT PRIMARY KEY,
    matric_no VARCHAR(50),
    fullname VARCHAR(100),
    department VARCHAR(100),
    level VARCHAR(20),
    password VARCHAR(50)
);

CREATE TABLE lecturers(
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(50)
);

CREATE TABLE courses(
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20),
    course_title VARCHAR(100),
    unit_load INT
);

CREATE TABLE results(
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    lecturer_id INT,
    ca_score INT,
    exam_score INT,
    total INT,
    grade VARCHAR(2),
    semester VARCHAR(20),
    session VARCHAR(20),
    status VARCHAR(20) DEFAULT 'Pending'
);

INSERT INTO admin(username, password)
VALUES('admin','admin123');