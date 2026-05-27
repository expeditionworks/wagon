<?php
// db_connection.php
// Database connection setup for Docker environment
$servername = "db";           // Docker service name — not localhost
$username = "wagonuser";
$password = "wagonpass";
$dbname = "wagon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}