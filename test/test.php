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
     // Display Delay Days
    if (isset($updatedPlayerState['delay_days'])) {
        echo "<li>Delay Days:</strong> " . json_encode($updatedPlayerState['delay_days']) . "</li>"; // Encode the array to a string
    }
    echo "<li>Delay Status</strong> " . $updatedPlayerState['delay_status'] . "</li>";
    echo "</ul>";    


    // Ensure family data exists and is an array
if (isset($updatedPlayerState['family']) && is_array($updatedPlayerState['family'])) {
        echo "<ul>";
    // Loop through each family member
    foreach ($updatedPlayerState['family'] as $familyMember) {
        // Check if necessary keys exist for each family member
        $firstName = isset($familyMember['first_name']) ? $familyMember['first_name'] : 'Unknown';
        $role = isset($familyMember['role']) ? $familyMember['role'] : 'Unknown';
        $condition = isset($familyMember['condition']) ? $familyMember['condition'] : 'Unknown';
        $health = isset($familyMember['health']) ? $familyMember['health'] : 'N/A';
        $skills = isset($familyMember['skills']) ? implode(", ", $familyMember['skills']) : 'None';
        $deceased = isset($familyMember['deceased']) ? ($familyMember['deceased'] ? 'Yes' : 'No') : 'No';
        $morale = isset($familyMember['morale']) ? $familyMember['morale'] : 'N/A';
        switch (isset($familyMember['deceased']) ? $familyMember['deceased'] : false) {
            case true:
                $deceasedStatus = 'are living their best lives, man';
                break;
            case false:
                $deceasedStatus = 'are deceased';
                break;
            default:
                $deceasedStatus = 'They abide';
        }
        // Display family member's details
        echo "<li>Name: $firstName, $role is feeling $condition and have $morale moral and has $health health and hhave $skills skills. They $deceasedStatus.";
    }
        echo "</ul>";
    
} else {
    // Handle the case where 'family' is not set or is not an array
    echo "Error: Family data is missing or corrupted. Unable to display family details.";
}

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

        // Display the last log item
    if (isset($updatedPlayerState['last_log_item'])) {
        // Check if the last_log_item is already an array or a JSON string
        $lastLogItem = is_array($updatedPlayerState['last_log_item']) ? $updatedPlayerState['last_log_item'] : json_decode($updatedPlayerState['last_log_item'], true);
        echo "<p><strong>Last Log Item:</strong><br>" . $lastLogItem['notes'] . "</p>";
    }



    echo "<ul>";    
    echo "<li><strong>Miles Traveled:</strong> " . $updatedPlayerState['miles_traveled'] . "</li>";
    echo "<li><strong>Miles Marker:</strong> " . $updatedPlayerState['mile'] . "</li>";
    echo "</ul>";

    // Assuming your inventory data is in $playerState['inventory']
    echo "<h4>Player Inventory</h4>";
    echo "<ul>";
    
    // Iterate through the inventory
    foreach ($updatedPlayerState['inventory'] as $itemName => $itemData) {
        // Display the item name and its details (e.g., quantity and durability)
        echo "<li>";
        echo "$itemName: " . $itemData['quantity'] . "</li>";
    }
    echo "</ul>";

    
  
    // echo "<ul>";    
    // echo "<li>Food left: " . $updatedPlayerState['inventory']['Food']['quantity'] . "</li>";
    // echo "<li>Ammunition: " . $updatedPlayerState['inventory']['Ammunition']['quantity'] . "</li>";
    // echo "</ul>";


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
