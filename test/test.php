<?php
// test.php

// Include the database connection and the game engine modules
include_once(__DIR__ . '/db_connection.php'); // Database connection
include_once(__DIR__ . '/../engine/game_engine.php'); // Main game engine
include_once(__DIR__ . '/../engine/modules/getPlayerState.php'); // Get player state module
include_once(__DIR__ . '/../engine/modules/moveAndCheckMilestones.php'); // Combined move and milestone check module
include_once(__DIR__ . '/../engine/modules/updatePlayerState.php'); // Update player state module

// Test with a specific player ID (for testing purposes)
$player_id = 1;  // Make sure this player exists in your database

// Step 1: Run the game engine for the player (simulating one turn)
$playerState = getPlayerState($player_id, $conn);

// If player state is retrieved successfully
if ($playerState) {
    // Step 2: Process the player's movement and check milestones
    $playerState = moveAndCheckMilestones($playerState, $player_id, $conn);

    // Step 3: Fetch the updated player state after running the game logic
    $updatedPlayerState = getPlayerState($player_id, $conn);

    // Display the updated player state and milestones
    echo "<h3>Updated Game State for Player ID: $player_id</h3>";

    // Show basic info
    echo "<p><strong>Trail Name:</strong> " . $updatedPlayerState['trail_name'] . "</p>";
    echo "<p><strong>Days on Trail:</strong> " . $updatedPlayerState['day'] . "</p>";
    echo "<p><strong>Miles Traveled:</strong> " . $updatedPlayerState['mile'] . "</p>";

    // Display morale only if it exists
    if (isset($updatedPlayerState['morale'])) {
        echo "<p><strong>Morale:</strong> " . $updatedPlayerState['morale'] . "</p>";
    } else {
        echo "<p><strong>Morale:</strong> Not set</p>";
    }

    // Display inventory if available
    if (isset($updatedPlayerState['inventory'])) {
        echo "<p><strong>Inventory:</strong> " . json_encode($updatedPlayerState['inventory']) . "</p>";
    }

    // Display log if any milestones are reached
    if (!empty($updatedPlayerState['log'])) {
        echo "<p><strong>Log:</strong><br>" . implode("<br>", array_map(fn($log) => $log['notes'], $updatedPlayerState['log'])) . "</p>";
    }

    // Display milestone-specific information
    if (!empty($updatedPlayerState['milestones'])) {
        echo "<p><strong>Milestones Reached:</strong><br>";
        foreach ($updatedPlayerState['milestones'] as $milestone) {
            if (isset($milestone['reached']) && $milestone['reached']) {
                echo "<strong>📍 {$milestone['title']}</strong> (Mile {$milestone['mile']})<br>";
                echo "{$milestone['extended_description']}<br><br>";
            }
        }
    }

} else {
    echo "<p>No player data found for Player ID: $player_id. Please ensure the player exists in the database.</p>";
}
?>
