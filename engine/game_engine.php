<?php
// game_engine.php

// Include the necessary files for database connection and game logic
include_once(__DIR__ . '/db_connection.php'); // Database connection

function getPlayerState($player_id, $conn) {
    // Query to fetch player state from the database
    $query = "SELECT * FROM player_state WHERE player_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerRow = $result->fetch_assoc();

    // If player data exists, populate the player state
    if ($playerRow) {
        // Load JSON configurations for terrain and milestones
        $terrainPath = __DIR__ . '/../config/terrain.json';
        if (file_exists($terrainPath)) {
            $terrain = json_decode(file_get_contents($terrainPath), true);
        } else {
            echo "Terrain file not found or not accessible.";
            $terrain = []; // Default empty array
        }

        $milestonesPath = __DIR__ . '/../config/milestones.json';
        if (file_exists($milestonesPath)) {
            $milestones = json_decode(file_get_contents($milestonesPath), true);
        } else {
            echo "Milestones file not found or not accessible.";
            $milestones = []; // Default empty array
        }

        // Populate player state or set default values if missing
        $playerState = [
            'day' => $playerRow['day'] ?? 1,
            'mile' => $playerRow['mile'] ?? 0,
            'morale' => $playerRow['morale'] ?? 100,
            'inventory' => json_decode($playerRow['inventory'], true) ?? [],
            'log' => json_decode($playerRow['log'], true) ?? [],
            'current_trail' => $playerRow['current_trail'] ?? 'oregon', // New field
            'last_log_item' => json_decode($playerRow['last_log_item'], true) ?? [],  // Assuming empty array if NULL
            'terrain' => $terrain,  // Ensure terrain is always set
            'milestones' => $milestones,  // Ensure milestones is always set
            'delay_days' => $playerRow['delay_days'] ?? 0,  // Pull delay_days from the database (default to 0)
        ];

        return $playerState;  // Return the populated player state
    }

    return null;  // Return null if player not found
}



function moveAndCheckMilestones($playerState, $player_id, $conn) {
    // If delay days > 0, handle the delay logic first
    if ($playerState['delay_days'] > 0) {
        $playerState['delay_days'] -= 1;  // Decrease delay_days
        $playerState['log'][] = [
            'day' => $playerState['day'],
            'miles_traveled' => 0,
            'total_miles' => $playerState['mile'],
            'notes' => "Paused at a milestone (delay in progress)."
        ];
        $playerState['day'] += 1;  // Increment the day even when paused
        return $playerState;  // Skip the rest of the day's movement
    }

    // Player movement logic (this runs if delay_days is 0)
    $miles_traveled = 10;  // Example: 10 miles traveled in this turn
    $playerState['mile'] += $miles_traveled;
    $playerState['day'] += 1;  // Increment the day by 1 (each turn represents a day)

    // Check milestones: see if the player has reached any milestones
    $mile = $playerState['mile'];
    $milestones = $playerState['milestones'];

    // Iterate through milestones and check if player has reached any
    foreach ($milestones as &$milestone) {
        if ($mile >= $milestone['mile'] && !isset($milestone['reached'])) {
            $milestone['reached'] = true;

            // Log milestone in player state
            $playerState['log'][] = [
                'notes' => "You reached the milestone: " . $milestone['title'] . ". " . $milestone['extended_description']
            ];
        }
    }

    return $playerState;
}









function updatePlayerState($player_id, $playerState, $conn) {
    // Prepare the updated player state for storage
    $inventoryJson = json_encode($playerState['inventory']);
    $logJson = json_encode($playerState['log']);
    
    // Ensure the last_log_item is properly set, even if the log is empty
    $lastLogItem = !empty($playerState['log']) ? json_encode(end($playerState['log'])) : json_encode(['notes' => 'No log for this turn']); // Default message if no logs

    $currentTrail = $playerState['current_trail'];
    $delayDays = $playerState['delay_days']; // Get the current delay_days value from player state

    // Query to update the player state in the database
    $query = "UPDATE player_state SET 
              day = ?, mile = ?, morale = ?, inventory = ?, log = ?, current_trail = ?, last_log_item = ?, delay_days = ? 
              WHERE player_id = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
        return;
    }

    // Bind the parameters to the statement
    $stmt->bind_param(
        'iissssssi', 
        $playerState['day'], 
        $playerState['mile'], 
        $playerState['morale'], 
        $inventoryJson,  
        $logJson,        
        $currentTrail,   
        $lastLogItem,    // Pass the last log item
        $playerState['delay_days'],      // Pass the delay_days value
        $player_id
    );

    // Execute the query to update the player state in the database
    if ($stmt->execute()) {
        echo "<p>Player state updated successfully!</p>";
    } else {
        echo "<p>Error executing query: " . $stmt->error . "</p>";
    }
}
?>
