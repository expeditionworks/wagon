<?php
// test.php

// Include the database connection and the game engine module
include_once(__DIR__ . '/../engine/game_engine.php'); // Main game engine (which already includes the necessary modules)

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
    echo "<ul>";    
   
    echo "<li><strong>Current Trail:</strong> " . $updatedPlayerState['current_trail'] . "</li>";
    echo "<li><strong>Days on Trail:</strong> " . $updatedPlayerState['day'] . "</li>";
    echo "<li><strong>Month</strong> " . $updatedPlayerState['month'] . "</li>";
    echo "<li><strong>Money</strong> " . $updatedPlayerState['dollars'] . "</li>";
    echo "<li><strong>Ration</strong> " . $updatedPlayerState['ration'] . "</li>";
    echo "</ul>";    


if (isset($updatedPlayerState['weatherThisTurn'])) {
    $weatherData = $updatedPlayerState['weatherThisTurn'];

    // Format the weather data into a readable string
    $weatherString = "Today's weather is " . ucfirst($weatherData['weather_type']) . ". ";

    // Check if 'temperature' is an array and handle it
    if (is_array($weatherData['temperature'])) {
        // Get a random temperature between the min and max values
        $temperature = rand($weatherData['temperature']['min'], $weatherData['temperature']['max']);
    } else {
        // Use the temperature directly if it's not an array
        $temperature = $weatherData['temperature'];
    }

    // Format temperature as a string
    $weatherString .= "The temperature is around " . $temperature . "°F. ";

    // Add precipitation info if available
    if ($weatherData['precipitation'] !== 'none') {
        $weatherString .= "Expect " . ucfirst($weatherData['precipitation']) . ". ";
    } else {
        $weatherString .= "No precipitation today. ";
    }

    // Add wind speed info
    $weatherString .= "Wind speeds are around " . $weatherData['wind_speed'] . " mph. ";

    // Display the formatted weather string
    echo "<p><strong>Weather for Today:</strong> " . $weatherString . "</p>";
} else {
    echo "<p>Weather data is not available for today.</p>";
}



    echo "<ul>";    
    echo "<li><strong>Miles Traveled:</strong> " . $updatedPlayerState['miles_traveled'] . "</li>";
    echo "<li><strong>Miles Marker:</strong> " . $updatedPlayerState['mile'] . "</li>";
    echo "</ul>";

    echo "<ul>";    

    echo "<li>Food durability: " . $updatedPlayerState['inventory']['Food']['quantity'] . "</li>";
    echo "</ul>";


    // Get the terrain type based on the current mile
    $currentTerrain = 'Unknown';  // Default value if terrain is not found
    foreach ($updatedPlayerState['terrain'] as $terrainSegment) {
        if ($updatedPlayerState['mile'] >= $terrainSegment['start_mile'] && $updatedPlayerState['mile'] <= $terrainSegment['end_mile']) {
            $currentTerrain = $terrainSegment['terrain'];  // Assign the terrain if the mile is within the segment
            break;
        }
    }

    // Display the current terrain
    echo "<ul>";
    echo "<li><strong>Current Terrain:</strong> " . $currentTerrain . "</li>";
    echo "<li><strong>Current altitude:</strong> " . $updatedPlayerState['altitude'] . "</li>";
    echo "</ul>";


    // Display morale only if it exists
    echo "<p><strong>Morale:</strong> " . $updatedPlayerState['morale'] . "</p>";

    // Display inventory if available
    if (isset($updatedPlayerState['inventory'])) {
        echo "<p><strong>Inventory:</strong> " . json_encode($updatedPlayerState['inventory']) . "</p>"; // Encode the array to a string
    }
    
    // Display Delay Days
    if (isset($updatedPlayerState['delay_days'])) {
        echo "<p><strong>Delay Days:</strong> " . json_encode($updatedPlayerState['delay_days']) . "</p>"; // Encode the array to a string
    }
    echo "<p><strong>Delay Status</strong> " . $updatedPlayerState['delay_status'] . "</p>";
    

    // Display the last log item
    if (isset($updatedPlayerState['last_log_item'])) {
        // Check if the last_log_item is already an array or a JSON string
        $lastLogItem = is_array($updatedPlayerState['last_log_item']) ? $updatedPlayerState['last_log_item'] : json_decode($updatedPlayerState['last_log_item'], true);
        echo "<p><strong>Last Log Item:</strong><br>" . $lastLogItem['notes'] . "</p>";
    }

    // Display log if any milestones are reached
    if (!empty($updatedPlayerState['log'])) {
        echo "<p><strong>Log:</strong><br>" . implode("<br>", array_map(fn($log) => $log['notes'], $updatedPlayerState['log'])) . "</p>"; // Convert array to string for display
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
