<?php
// test.php

// Include the game engine and functions
include_once(__DIR__ . '/../wagon/engine/game_engine.php');
include_once(__DIR__ . '/../wagon/engine/game_functions.php');

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

// Fetch player data from database
function getPlayerState($player_id) {
    global $conn;
    $sql = "
        SELECT players.trail_name, players.family, player_state.*
        FROM players
        JOIN player_state ON players.id = player_state.player_id
        WHERE players.id = $player_id
    ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Decode JSON fields into PHP arrays
        $row['family'] = json_decode($row['family'], true); // Decode family field
        $row['inventory'] = json_decode($row['inventory'], true);
        $row['conditions'] = json_decode($row['conditions'], true);
        $row['log'] = json_decode($row['log'], true);

        // Initialize missing fields with default values if not present
        if (!isset($row['morale'])) {
            $row['morale'] = 100;  // Default morale value
        }
        if (!isset($row['day'])) {
            $row['day'] = 1;  // Default to day 1
        }
        if (!isset($row['mile'])) {
            $row['mile'] = 0;  // Default to 0 miles
        }

        return $row;  // Return the player's state
    } else {
        return null;  // No player found
    }
}

// Save player state to database
function savePlayerState($player_id, $player_state) {
    global $conn;
    $day = $player_state['day'];
    $mile = $player_state['mile'];
    $inventory = json_encode($player_state['inventory']);
    $conditions = json_encode($player_state['conditions']);
    $log = json_encode($player_state['log']);
    
    $sql = "UPDATE player_state SET day = $day, mile = $mile, inventory = '$inventory', conditions = '$conditions', log = '$log' WHERE player_id = $player_id";
    $conn->query($sql);
}

// Example: Retrieve the current player state (this could be from a database or session)
$player_id = 1;  // Set to the current player's ID
$playerRow = getPlayerState($player_id);

if (!$playerRow) {
    // Handle case where no player data is found
    echo "No player data found for ID $player_id. Please ensure that player exists in the database.";
    exit;
}

// Handle button click to simulate a new day
if (isset($_POST['continue_day'])) {
    simulateDay($playerRow, $full_milestones);  // Simulate the day using the game engine
    savePlayerState($player_id, $playerRow['player_state']);
}

// Function to output the player's current state
function displayState($playerRow) {
    $state = $playerRow; // player_row now includes player_state data as flat
    echo "<h3>Current Game State</h3>";
    echo "<p><strong>Trail Name:</strong> " . $state['trail_name'] . "</p>";
    echo "<p><strong>Days on Trail:</strong> " . $state['day'] . "</p>";
    echo "<p><strong>Miles Traveled:</strong> " . $state['mile'] . " miles</p>";
    echo "<p><strong>Morale:</strong> " . $state['morale'] . "</p>";
    echo "<p><strong>Food Remaining:</strong> " . $state['inventory']['food_lbs'] . " lbs</p>";
    echo "<p><strong>Oxen:</strong> " . $state['inventory']['oxen'] . "</p>";
    echo "<p><strong>Clothing:</strong> " . $state['inventory']['clothing'] . "</p>";
    echo "<p><strong>Ammunition:</strong> " . $state['inventory']['ammunition'] . "</p>";
    echo "<p><strong>Family:</strong> " . implode(", ", $state['family']) . "</p>";

    // Display the latest log entry
    $latestLog = end($state['log']);
    if ($latestLog) {
        echo "<h4>Latest Log Entry</h4>";
        echo "<p>" . $latestLog['notes'] . "</p>";
    }
}

// Display the state before the user continues
displayState($playerRow);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Game Interface</title>
</head>
<body>
    <h1>Conestoga Wagon Test Interface</h1>

    <form method="post">
        <input type="submit" name="continue_day" value="Continue to Next Day">
    </form>

    <h3>Player Actions:</h3>
    <!-- Example of available choices (you can modify as needed for your game logic) -->
    <form method="post">
        <label for="choice">Choose your action:</label>
        <select name="choice" id="choice">
            <option value="rest">Rest</option>
            <option value="cross_river">Cross River</option>
            <option value="trade">Trade Supplies</option>
        </select>
        <input type="submit" name="action" value="Take Action">
    </form>

    <?php
    // Handle specific actions (e.g., resting, crossing river)
    if (isset($_POST['action'])) {
        $action = $_POST['choice'];
        // Perform the action logic here (update state, log entries, etc.)
        // Example: Resting action would update morale or health
        if ($action == 'rest') {
            $playerRow['morale'] += 10;
            addLogEntry($playerRow, "Rested and morale increased.");
        } elseif ($action == 'cross_river') {
            $playerRow['mile'] += 15; // Advance mile after crossing the river
            addLogEntry($playerRow, "Crossed the river and advanced 15 miles.");
        }
        // Other action logic can be added here (e.g., trade, fight, etc.)
        savePlayerState($player_id, $playerRow);  // Save updated state
    }
    ?>

    <hr>
    <p><a href="/">Back to Main Game</a></p>
</body>
</html>
