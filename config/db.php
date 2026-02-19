<?php
// Define connection constants
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'system';

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "DB connection failed: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');