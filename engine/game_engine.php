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
    echo "File not found or not accessible.";
}
        $milestones = json_decode(file_get_contents(__DIR__ . '/../config/milestones.json'), true);

        // Populate player state or set default values if missing
        $playerState = [
            'day' => $playerRow['day'] ?? 1,
            'mile' => $playerRow['mile'] ?? 0,
            'morale' => $playerRow['morale'] ?? 100,
            'inventory' => json_decode($playerRow['inventory'], true) ?? [],
            'log' => json_decode($playerRow['log'], true) ?? [],
            'terrain' => $terrain,
            'milestones' => $milestones,
            'current_trail' => $playerRow['current_trail'] ?? 'oregon', // New field
        ];

        return $playerState;  // Return the populated player state
    }

    return null;  // Return null if player not found
}

function moveAndCheckMilestones($playerState, $player_id, $conn) {
    // Player movement: increment miles and days
    $miles_traveled = 10;  // Example: 10 miles traveled in this turn
    $playerState['mile'] += $miles_traveled;
    $playerState['day'] += 1;  // Increment day by 1 (each turn represents a day)

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

    // Return the updated player state
    return $playerState;
}

function updatePlayerState($player_id, $playerState, $conn) {
    // Prepare the updated player state for storage
    $inventoryJson = json_encode($playerState['inventory']);
    $logJson = json_encode($playerState['log']);
    $currentTrail = $playerState['current_trail'];

    // Query to update the player state in the database
    $query = "UPDATE player_state SET 
              day = ?, mile = ?, morale = ?, inventory = ?, log = ?, current_trail = ? 
              WHERE player_id = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
        return;
    }

    // Bind the parameters to the statement
    $stmt->bind_param(
        'iissssi', 
        $playerState['day'], 
        $playerState['mile'], 
        $playerState['morale'], 
        $inventoryJson,  
        $logJson,        
        $currentTrail,   
        $player_id
    );

    // Execute the query to update the player state in the database
    if ($stmt->execute()) {
        echo "<p>Player state updated successfully!</p>";
    } else {
        echo "<p>Error executing query: " . $stmt->error . "</p>";
    }
}

// Main game logic starts here
$player_id = 1;  // The player ID for testing

// Get player state from the database
$playerState = getPlayerState($player_id, $conn);

// If player state is retrieved successfully
if ($playerState) {
    // Process the player's movement and check milestones
    $playerState = moveAndCheckMilestones($playerState, $player_id, $conn);

    // Finally, update the player state in the database
    updatePlayerState($player_id, $playerState, $conn);

    // Display the updated player state for verification
    echo "<h3>Updated Game State for Player ID: $player_id</h3>";

    echo "<p><strong>Current Trail:</strong> " . $playerState['current_trail'] . "</p>";
    echo "<p><strong>Days on Trail:</strong> " . $playerState['day'] . "</p>";
    echo "<p><strong>Miles Traveled:</strong> " . $playerState['mile'] . "</p>";

    // Display morale only if it exists
    echo "<p><strong>Morale:</strong> " . $playerState['morale'] . "</p>";

    // Display log if any milestones are reached
    if (!empty($playerState['log'])) {
        echo "<p><strong>Log:</strong><br>" . implode("<br>", array_map(fn($log) => $log['notes'], $playerState['log'])) . "</p>";
    }

    // Display milestone-specific information
    if (!empty($playerState['milestones'])) {
        echo "<p><strong>Milestones Reached:</strong><br>";
        foreach ($playerState['milestones'] as $milestone) {
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
