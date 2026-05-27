<?php
echo "<h1>Conestoga Wagon</h1>";
echo "<p>PHP is working.</p>";

$conn = new mysqli('db', 'wagonuser', 'wagonpass', 'wagon');
if ($conn->connect_error) {
    echo "<p style='color:red'>Database failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green'>Database connected.</p>";
}
?>
