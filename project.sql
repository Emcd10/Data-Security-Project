CREATE DATABASE project_db;

USE project_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_group ENUM('H', 'R') NOT NULL
);

CREATE TABLE healthcare (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    gender BOOLEAN,
    age INT,
    weight FLOAT,
    height FLOAT,
    health_history TEXT
);