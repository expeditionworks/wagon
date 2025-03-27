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
        ];

        return $playerState;  // Return the populated player state
    }

    return null;  // Return null if player not found
}



function moveAndCheckMilestones($playerState, $player_id, $conn, $milestones, $terrain) {
    // Handle any delay days from a previous milestone (e.g., river crossing delay)
    if (isset($playerState['delay_days']) && $playerState['delay_days'] > 0) {
        $playerState['delay_days'] -= 1;
        $playerState['day'] += 1;
        $playerState['log'][] = [
            'day' => $playerState['day'],
            'miles_traveled' => 0,
            'total_miles' => $playerState['mile'],
            'notes' => "Paused at a milestone (delay in progress)."
        ];
        // Update player state in database
        updatePlayerState($player_id, $playerState, $conn);
        return $playerState; // Return player state if paused
    }

    // Movement logic (increment miles and days)
    $previousMile = $playerState['mile'];
    $baseMiles = 15;

    // Modify baseMiles according to the terrain type
    $terrainType = $terrain[$playerState['mile']] ?? 'plains'; // Assuming terrain is indexed by mile for simplicity
    $terrainModifiers = [
        'plains' => 1.2,
        'rolling hills' => 1.0,
        'mountains' => 0.8,
        'valleys' => 1.0,
        'river valley' => 1.0,
        'desert' => 0.7
    ];
    $terrainMod = $terrainModifiers[$terrainType] ?? 1.0;

    // Modify miles based on terrain and player difficulty
    $difficultyMod = [
        'easy' => 1.1,
        'medium' => 1.0,
        'hard' => 0.9
    ];
    $mod = $difficultyMod[$playerState['difficulty']] ?? 1.0;
    $milesTraveled = round($baseMiles * $mod * $terrainMod);

    // Adjust for player conditions (e.g., morale, oxen, health)
    if ($playerState['morale'] < 50) {
        $milesTraveled *= 0.8; // Decrease miles if morale is low
    }
    if ($playerState['oxen'] < 2) {
        $milesTraveled *= 0.7; // Decrease miles if not enough oxen
    }

    $newMile = $previousMile + $milesTraveled;

    // Check milestones along the path
    $milestoneToday = null;
    foreach ($milestones as $milestone) {
        if ($milestone['mile'] > $previousMile && $milestone['mile'] <= $newMile) {
            $milestoneToday = $milestone;
            $newMile = $milestone['mile'];
            break;
        }
    }

    // If the milestone forces a stop (e.g., river crossing), apply the stop logic
    if ($milestoneToday && ($milestoneToday['force_stop'] ?? false)) {
        $crossing = $milestoneToday['crossing'] ?? null;
        $choice = $playerState['last_choice'] ?? null;

        if ($crossing && $choice) {
            if ($choice === "ford") {
                $playerState['delay_days'] = $crossing['ford_delay'] ?? 0;
            } elseif ($choice === "float") {
                $playerState['delay_days'] = $crossing['float_delay'] ?? 0;
            } elseif ($choice === "ferry") {
                $playerState['delay_days'] = $crossing['ferry_delay'] ?? 0;
            }
            $playerState['paused'] = true;

            // After delay, apply crossing outcome
            if ($playerState['delay_days'] === 0) {
                applyCrossingOutcome($playerState, $milestoneToday);
            }
        } else {
            unset($playerState['paused']);
        }
    }

    // Update player state and log for the day
    $playerState['mile'] = $newMile;
    $playerState['day'] += 1;
    $playerState['log'][] = [
        'day' => $playerState['day'],
        'miles_traveled' => $newMile - $previousMile,
        'total_miles' => $newMile,
        'milestone' => $milestoneToday['title'] ?? null,
        'notes' => $milestoneToday ? ("Today, you reached " . $milestoneToday['title'] . ".") : null
    ];

    // Update player state in the database
    updatePlayerState($player_id, $playerState, $conn);

    return $playerState;
}





function updatePlayerState($player_id, $playerState, $conn) {
    // Prepare the updated player state for storage
    $inventoryJson = json_encode($playerState['inventory']);
    $logJson = json_encode($playerState['log']);
    
    // Ensure the last_log_item is properly set, even if the log is empty
    $lastLogItem = !empty($playerState['log']) ? json_encode(end($playerState['log'])) : json_encode(['notes' => 'No log for this turn']); // Default message if no logs

    $currentTrail = $playerState['current_trail'];

    // Query to update the player state in the database
    $query = "UPDATE player_state SET 
              day = ?, mile = ?, morale = ?, inventory = ?, log = ?, current_trail = ?, last_log_item = ? 
              WHERE player_id = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
        return;
    }

    // Bind the parameters to the statement
    $stmt->bind_param(
        'iisssssi', 
        $playerState['day'], 
        $playerState['mile'], 
        $playerState['morale'], 
        $inventoryJson,  
        $logJson,        
        $currentTrail,   
        $lastLogItem,    // Pass the last log item
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


} else {
    echo "<p>No player data found for Player ID: $player_id. Please ensure the player exists in the database.</p>";
}
?>
