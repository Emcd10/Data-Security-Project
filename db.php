<?php
$host = "localhost";
$dbname = "project_db";
$username = "root"; 
$password = ""; 
//Connection to DB in its own file for easy inclusion in both pages
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
