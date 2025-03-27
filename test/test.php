<?php
// test.php

// Include the database connection and the game engine modules
include_once(__DIR__ . '/db_connection.php'); // Database connection
include_once(__DIR__ . '/../engine/game_engine.php'); // Main game engine
include_once(__DIR__ . '/../engine/modules/movePlayer.php'); // Move player module
include_once(__DIR__ . '/../engine/modules/handleMilestones.php'); // Handle milestones module

// Test with a specific player ID (for testing purposes)
$player_id = 1;  // Make sure this player exists in your database

// Step 1: Run the game engine for the player (simulating one turn)
$playerRow = getPlayerState($player_id, $conn);

// If player state is retrieved successfully
if ($playerRow) {
    // Step 2: Process the player's movement (e.g., 10 miles traveled in one turn)
    $playerRow = movePlayer($player_id, 10, $playerRow);  // Pass the player state here, not the connection

    // Step 3: Check if the player has reached any milestones
    $milestoneHtml = checkMilestones($player_id, $conn);

    // Step 4: Fetch the updated player state after running the game logic
    $updatedPlayerRow = getPlayerState($player_id, $conn);

    // Display the updated player state and milestones
    echo "<h3>Updated Game State for Player ID: $player_id</h3>";

    // Show basic info
    echo "<p><strong>Trail Name:</strong> " . $updatedPlayerRow['trail_name'] . "</p>";
    echo "<p><strong>Days on Trail:</strong> " . $updatedPlayerRow['player_state']['day'] . "</p>";
    echo "<p><strong>Miles Traveled:</strong> " . $updatedPlayerRow['player_state']['mile'] . "</p>";

    // Display morale only if it exists
    if (isset($updatedPlayerRow['player_state']['morale'])) {
        echo "<p><strong>Morale:</strong> " . $updatedPlayerRow['player_state']['morale'] . "</p>";
    } else {
        echo "<p><strong>Morale:</strong> Not set</p>";
    }

    // Display inventory if available
    if (isset($updatedPlayerRow['player_state']['inventory'])) {
        echo "<p><strong>Inventory:</strong> " . json_encode($updatedPlayerRow['player_state']['inventory']) . "</p>";
    }

    // Display log if any milestones are reached
    if (!empty($updatedPlayerRow['player_state']['log'])) {
        echo "<p><strong>Log:</strong><br>" . implode("<br>", array_map(fn($log) => $log['notes'], $updatedPlayerRow['player_state']['log'])) . "</p>";
    }

    // Display milestone-specific information
    if (!empty($milestoneHtml)) {
        echo "<p><strong>Milestones Reached:</strong><br>" . $milestoneHtml . "</p>";
    }
} else {
    echo "<p>No player data found for Player ID: $player_id. Please ensure the player exists in the database.</p>";
}
?>
