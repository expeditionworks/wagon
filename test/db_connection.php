<?php
// Database connection setup
$servername = "localhost";
$username = "root";  // Default username in MAMP
$password = "root";  // Default password for MAMP is usually empty
$dbname = "conestoga_wagon";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Connection was successful
// You can now use $conn for your database operations

// (Remember to close the connection when done)
function closeConnection() {
    global $conn;
    $conn->close();
}
?>
