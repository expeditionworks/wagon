<?php
// db_connection.php

// Database connection setup
$servername = "localhost";
$username = "root";  // Default username in MAMP
$password = "root";      // Default password for MAMP is usually empty
$dbname = "conestoga_wagon";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
