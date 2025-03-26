<?php
// Include the database connection file
include_once(__DIR__ . '/../db_connection.php');

// Include the game engine modules
include_once(__DIR__ . '/../engine/modules/getPlayerState.php');

// Get player state (this will now use the database connection from db_connection.php)
$player_id = 1;  // Set to the current player's ID
$playerRow = getPlayerState($player_id, $conn);

if (!$playerRow) {
    // Handle case where no player data is found
    echo "No player data found for ID $player_id. Please ensure that player exists in the database.";
    exit;
}

// Display the player state
echo "<h3>Current Game State</h3>";
echo "<p><strong>Trail Name:</strong> " . $playerRow['trail_name'] . "</p>";
echo "<p><strong>Family:</strong> " . implode(", ", $playerRow['family']) . "</p>";
echo "<p><strong>Days on Trail:</strong> " . $playerRow['player_state']['day'] . "</p>";
echo "<p><strong>Miles Traveled:</strong> " . $playerRow['player_state']['mile'] . " miles</p>";
echo "<p><strong>Inventory:</strong> Food: " . $playerRow['player_state']['inventory']['food_lbs'] . " lbs</p>";
echo "<p><strong>Conditions:</strong> " . implode(", ", $playerRow['player_state']['conditions']) . "</p>";
echo "<p><strong>Log:</strong> " . implode("<br>", array_map(fn($log) => $log['notes'], $playerRow['player_state']['log'])) . "</p>";

if (isset($_POST['continue_day'])) {
    // Simulate the passing of a day
    $playerState = $playerRow['player_state'];
    $playerState['day'] += 1;  // Increment day by 1
    $playerState['mile'] += rand(10, 20);  // Randomly move the player forward by 10-20 miles

    // Simulate food consumption
    $foodConsumed = rand(10, 20);  // Random food consumption per day
    $playerState['inventory']['food_lbs'] -= $foodConsumed;

    // Ensure food doesn't go below zero
    if ($playerState['inventory']['food_lbs'] < 0) {
        $playerState['inventory']['food_lbs'] = 0;
    }

    // Log the daily events (food consumed, miles traveled)
    $playerState['log'][] = [
        'day' => $playerState['day'],
        'notes' => "Traveled {$playerState['mile']} miles, consumed $foodConsumed lbs of food."
    ];

    // Save the updated player state back to the database
    savePlayerState($player_id, $playerState, $conn);

    // Re-fetch updated player data
    $playerRow = getPlayerState($player_id, $conn);
}
?>

<!-- HTML for the Continue button -->
<form method="post">
    <input type="submit" name="continue_day" value="Continue to Next Day">
</form>
