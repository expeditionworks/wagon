<?php
require_once '../createNewPlayer.php'; // Adjust path as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $difficulty = strtolower(trim($_POST['difficulty'] ?? 'medium'));

    // Validate phone and email
    if (!preg_match('/^[\\d\\+\\-\\(\\)\\s]+$/', $phone)) {
        die("<p>Invalid phone number format.</p>");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("<p>Invalid email address.</p>");
    }

    $allowedDifficulties = ['easy', 'medium', 'hard'];
    if (!in_array($difficulty, $allowedDifficulties)) {
        $difficulty = 'medium';
    }

    // Create the player
    $result = createNewPlayer($phone, $email, $difficulty);

    if ($result['success']) {
        $safeMessage = htmlspecialchars($result['intro_message'], ENT_QUOTES, 'UTF-8');
        echo "<h1>Welcome to Conestoga Wagon</h1>";
        echo "<p>$safeMessage</p>";
    } else {
        echo "<p>Something went wrong. Please try again.</p>";
    }
} else {
    echo "<p>Invalid access. Please return to the <a href='../signup.html'>signup page</a>.</p>";
}
?>
