<?php
// game_engine.php

// Function to get player state by player ID
function getPlayerState($player_id, $conn) {
    $sql = "
        SELECT players.trail_name, players.family, player_state.*
        FROM players
        JOIN player_state ON players.id = player_state.player_id
        WHERE players.id = $player_id
    ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Decode JSON fields into PHP arrays
        $row['family'] = json_decode($row['family'], true); // Decode family field
        $row['inventory'] = json_decode($row['inventory'], true); // Decode inventory field
        $row['conditions'] = json_decode($row['conditions'], true); // Decode conditions field
        $row['log'] = json_decode($row['log'], true); // Decode log field

        // If any field is missing, initialize with default values
        $row['player_state'] = [
            'day' => $row['day'] ?? 1,
            'mile' => $row['mile'] ?? 0,
            'inventory' => $row['inventory'],
            'conditions' => $row['conditions'],
            'log' => $row['log']
        ];

        return $row;  // Return the player data with player_state
    } else {
        return null;  // No player data found
    }
}

// Function to save player state back to the database
function savePlayerState($player_id, $player_state, $conn) {
    $day = $player_state['day'];
    $mile = $player_state['mile'];
    $inventory = json_encode($player_state['inventory']);
    $conditions = json_encode($player_state['conditions']);
    $log = json_encode($player_state['log']);

    $sql = "UPDATE player_state SET day = $day, mile = $mile, inventory = '$inventory', conditions = '$conditions', log = '$log' WHERE player_id = $player_id";
    $conn->query($sql);
}

// Function to simulate a new day (run all game logic)
function runDailyTurn($playerRow, $milestones) {
    // Get the current state
    $playerState = $playerRow['player_state'];
    
    // Simulate the passage of one day
    $playerState['day'] += 1;  // Increment day by 1
    $playerState['mile'] += rand(10, 20);  // Randomly move the player forward by 10-20 miles

    // Simulate food consumption
    $foodConsumed = rand(10, 20);  // Random food consumption per day
    $playerState['inventory']['food_lbs'] -= $foodConsumed;

    // Ensure food doesn't go below zero
    if ($playerState['inventory']['food_lbs'] < 0) {
        $playerState['inventory']['food_lbs'] = 0;
    }

    // Simulate morale change (can be affected by various factors like food, events)
    $moraleChange = rand(-10, 5);  // Random morale change (-10 to +5)
    $playerState['morale'] += $moraleChange;

    // Ensure morale stays within bounds (0 to 100)
    if ($playerState['morale'] > 100) {
        $playerState['morale'] = 100;
    } elseif ($playerState['morale'] < 0) {
        $playerState['morale'] = 0;
    }

    // Check if the player has reached a milestone
    $milestoneReached = checkForMilestone($playerState['mile'], $milestones);
    if ($milestoneReached) {
        $playerState['log'][] = ['milestone' => $milestoneReached, 'notes' => "Reached $milestoneReached milestone"];
    }

    // Log the daily events (food consumed, miles traveled, morale change)
    $playerState['log'][] = [
        'day' => $playerState['day'],
        'notes' => "Traveled {$playerState['mile']} miles, consumed $foodConsumed lbs of food, morale adjusted by $moraleChange."
    ];

    // Save the updated player state
    $playerRow['player_state'] = $playerState;
}

// Check if the player has reached a milestone based on their mileage
function checkForMilestone($milesTraveled, $milestones) {
    foreach ($milestones as $milestone) {
        if ($milestone['mile'] == $milesTraveled) {
            return $milestone['title'];  // Return milestone title if reached
        }
    }
    return null;  // No milestone reached
}
?>
