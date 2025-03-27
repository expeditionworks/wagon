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
        // Ensure 'player_state' exists in the row and decode it if needed
        $playerState = json_decode($playerRow['player_state'], true) ?? [];  // Decode player_state JSON or set an empty array if it's null

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

        // Ensure the player state has the necessary properties
        $playerState = [
            'day' => $playerState['day'] ?? 1,
            'mile' => $playerState['mile'] ?? 0,
            'morale' => $playerState['morale'] ?? 100,
            'inventory' => json_decode($playerState['inventory'], true) ?? [],
            'log' => json_decode($playerState['log'], true) ?? [],
            'current_trail' => $playerState['current_trail'] ?? 'oregon',
            'last_log_item' => json_decode($playerState['last_log_item'], true) ?? [],  // Assuming empty array if NULL
            'delay_days' => $playerState['delay_days'] ?? 0, // Handle delay days
            'terrain' => $terrain,  // Ensure terrain is always set
            'milestones' => $milestones,  // Ensure milestones is always set
        ];

        // Add player_state back into playerRow for returning
        $playerRow['player_state'] = $playerState;

        return $playerRow;  // Return the updated player state in the playerRow
    }

    return null;  // Return null if player not found
}




function runDailyTurn($playerRow, $milestones, $terrain) {
  $state = $playerRow['player_state'];

  // Handle any delay days from a previous milestone (e.g., river crossing delay)
  if (isset($state['delay_days']) && $state['delay_days'] > 0) {
    $state['delay_days'] -= 1;
    $state['day'] += 1;
    $state['log'][] = [
      'day' => $state['day'],
      'miles_traveled' => 0,
      'total_miles' => $state['mile'],
      'notes' => "Paused at a milestone (delay in progress)."
    ];
    $playerRow['player_state'] = $state;
    return $playerRow;
  }

  $previousMile = $state['mile'];
  $baseMiles = 15;

  // Modify baseMiles according to the terrain type
  $terrainType = $terrain[$state['mile']] ?? 'plains'; // Assuming terrain is indexed by mile for simplicity
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
  $mod = $difficultyMod[$state['difficulty']] ?? 1.0;
  $milesTraveled = round($baseMiles * $mod * $terrainMod);

  // Adjust for player conditions (e.g., morale, oxen, health)
  if ($state['morale'] < 50) {
    $milesTraveled *= 0.8; // Decrease miles if morale is low
  }
  if ($state['oxen'] < 2) {
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
    $choice = $state['last_choice'] ?? null;

    if ($crossing && $choice) {
      if ($choice === "ford") {
        $state['delay_days'] = $crossing['ford_delay'] ?? 0;
      } elseif ($choice === "float") {
        $state['delay_days'] = $crossing['float_delay'] ?? 0;
      } elseif ($choice === "ferry") {
        $state['delay_days'] = $crossing['ferry_delay'] ?? 0;
      }
      $state['paused'] = true;

      // After delay, apply crossing outcome
      if ($state['delay_days'] === 0) {
        applyCrossingOutcome($state, $milestoneToday);
      }
    } else {
      unset($state['paused']);
    }
  }

  // Update player state and log for the day
  $state['mile'] = $newMile;
  $state['day'] += 1;
  $state['log'][] = [
    'day' => $state['day'],
    'miles_traveled' => $newMile - $previousMile,
    'total_miles' => $newMile,
    'milestone' => $milestoneToday['title'] ?? null,
    'notes' => $milestoneToday ? ("Today, you reached " . $milestoneToday['title'] . ".") : null
  ];

  $playerRow['player_state'] = $state;
  return $playerRow;
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
              day = ?, mile = ?, morale = ?, inventory = ?, log = ?, current_trail = ?, last_log_item = ?, delay_days = ? 
              WHERE player_id = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
        return;
    }

    // Bind the parameters to the statement
    $stmt->bind_param(
        'iisssssii', 
        $playerState['day'], 
        $playerState['mile'], 
        $playerState['morale'], 
        $inventoryJson,  
        $logJson,        
        $currentTrail,   
        $lastLogItem,    // Pass the last log item
        $playerState['delay_days'],  // Bind the delay_days
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
    // Process the player's movement and check milestones using the runDailyTurn function
    $playerState = runDailyTurn($playerState, $playerState['milestones'], $playerState['terrain']);

    // Finally, update the player state in the database
    updatePlayerState($player_id, $playerState, $conn);


} else {
    echo "<p>No player data found for Player ID: $player_id. Please ensure the player exists in the database.</p>";
}
?>
