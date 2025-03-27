<?php
// test.php

// Include the database connection file
include_once(__DIR__ . '/db_connection.php'); // Same directory

// Include the game engine
include_once(__DIR__ . '/../engine/game_engine.php'); // Main game engine

// Test with a specific player ID (for testing purposes)
$player_id = 1;  // Change this to an existing player ID in your database

// Step 1: Check if the "Run Daily Turn" button is pressed
if (isset($_POST['run_daily_turn'])) {
    // Run the game logic for the player (simulating one turn)
    runDailyTurn($player_id, $conn);

    // Fetch the updated player state to confirm the changes
    $playerRow = getPlayerState($player_id, $conn);

    // Check if player data is retrieved successfully
    if ($playerRow) {
        echo "<h3>Updated Game State for Player ID: $player_id</h3>";

        // Display the player's state (basic info)
        echo "<p><strong>Trail Name:</strong> " . $playerRow['trail_name'] . "</p>";
        echo "<p><strong>Days on Trail:</strong> " . $playerRow['player_state']['day'] . "</p>";
        echo "<p><strong>Miles Traveled:</strong> " . $playerRow['player_state']['mile'] . "</p>";

        // Display inventory (if any)
        if (isset($playerRow['player_state']['inventory'])) {
            echo "<p><strong>Inventory:</strong> " . json_encode($playerRow['player_state']['inventory']) . "</p>";
        }

        // Display conditions (if any)
        if (isset($playerRow['player_state']['conditions'])) {
            echo "<p><strong>Conditions:</strong> " . json_encode($playerRow['player_state']['conditions']) . "</p>";
        }

        // Display log (if any)
        if (isset($playerRow['player_state']['log'])) {
            echo "<p><strong>Log:</strong><br>" . implode("<br>", array_map(fn($log) => $log['notes'], $playerRow['player_state']['log'])) . "</p>";
        }
    } else {
        echo "<p>No player data found for Player ID: $player_id. Please ensure the player exists in the database.</p>";
    }
}

?>

<!-- Button to trigger the daily turn -->
<form method="post">
    <input type="submit" name="run_daily_turn" value="Run Daily Turn">
</form>
