<?php
// test.php

// Include the database connection and modules
include_once(__DIR__ . '/db_connection.php'); // Same directory
include_once(__DIR__ . '/../engine/modules/getPlayerState.php'); // Include the getPlayerState module
include_once(__DIR__ . '/../engine/modules/updatePlayerState.php'); // Include the updatePlayerState module

// Test with a specific player ID (for testing purposes)
$player_id = 1;  // Change this to an existing player ID in your database

// Step 1: Fetch the player state from the database
$playerRow = getPlayerState($player_id, $conn);

// Check if player data is retrieved successfully
if ($playerRow) {
    echo "<h3>Initial Game State for Player ID: $player_id</h3>";
    echo "<p><strong>Trail Name:</strong> " . $playerRow['trail_name'] . "</p>";
    echo "<p><strong>Days on Trail:</strong> " . $playerRow['player_state']['day'] . "</p>";
    echo "<p><strong>Miles Traveled:</strong> " . $playerRow['player_state']['mile'] . "</p>";
    echo "<p><strong>Inventory:</strong> " . json_encode($playerRow['player_state']['inventory']) . "</p>";

    // Step 2: Modify the player state for testing (update some fields)
    $playerRow['player_state']['day'] += 1; // Increment the day
    $playerRow['player_state']['mile'] += 10; // Increment miles by 10
    $playerRow['player_state']['morale'] = 80; // Set morale to 80

    // Modify the inventory to add 5 units of food for testing
    $inventory = $playerRow['player_state']['inventory'];
    $inventory['food_lbs'] = (isset($inventory['food_lbs']) ? $inventory['food_lbs'] : 0) + 5;
    $playerRow['player_state']['inventory'] = $inventory;

    // Step 3: Update the player state back to the database using updatePlayerState
    updatePlayerState($player_id, $playerRow['player_state'], $conn);

    // Step 4: Fetch the updated player state from the database to confirm changes
    $updatedPlayerRow = getPlayerState($player_id, $conn);

    echo "<h3>Updated Game State for Player ID: $player_id</h3>";
    echo "<p><strong>Trail Name:</strong> " . $updatedPlayerRow['trail_name'] . "</p>";
    echo "<p><strong>Days on Trail:</strong> " . $updatedPlayerRow['player_state']['day'] . "</p>";
    echo "<p><strong>Miles Traveled:</strong> " . $updatedPlayerRow['player_state']['mile'] . "</p>";
    echo "<p><strong>Inventory:</strong> " . json_encode($updatedPlayerRow['player_state']['inventory']) . "</p>";
} else {
    echo "<p>No player data found for Player ID: $player_id. Please ensure the player exists in the database.</p>";
}
?>
