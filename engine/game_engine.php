<?php
// game/engine/game_engine.php

function simulateDay(&$playerRow, $milestones) {
    // Increment day count
    $playerRow['player_state']['day']++;

    // Simulate food consumption
    $foodConsumed = rand(10, 20);  // Random food consumption per day
    $playerRow['player_state']['inventory']['food_lbs'] -= $foodConsumed;

    // Ensure food doesn't go below zero
    if ($playerRow['player_state']['inventory']['food_lbs'] < 0) {
        $playerRow['player_state']['inventory']['food_lbs'] = 0;
    }

    // Update morale based on random events or conditions
    $moraleChange = rand(-10, 5);  // Random morale change (-10 to +5)
    $playerRow['player_state']['morale'] += $moraleChange;

    // Ensure morale stays within bounds (0 to 100)
    if ($playerRow['player_state']['morale'] > 100) {
        $playerRow['player_state']['morale'] = 100;
    } elseif ($playerRow['player_state']['morale'] < 0) {
        $playerRow['player_state']['morale'] = 0;
    }

    // Simulate travel (miles)
    $travelDistance = rand(10, 20);  // Random distance (can adjust based on difficulty)
    $playerRow['player_state']['mile'] += $travelDistance;

    // Check for milestones reached
    $milestoneReached = checkForMilestone($playerRow['player_state']['mile'], $milestones);
    if ($milestoneReached) {
        $playerRow['player_state']['log'][] = ['milestone' => $milestoneReached, 'notes' => "Reached $milestoneReached milestone"];
    }

    // Add a log entry for the day's actions
    $playerRow['player_state']['log'][] = ['day' => $playerRow['player_state']['day'], 'notes' => "Traveled $travelDistance miles, consumed $foodConsumed lbs of food, morale adjusted by $moraleChange."];
}

// Check if the player has reached a milestone based on their mileage
function checkForMilestone($milesTraveled, $milestones) {
    foreach ($milestones as $milestone) {
        if ($milestone['mile'] == $milesTraveled) {
            return $milestone['title'];
        }
    }
    return null;  // No milestone reached
}

?>
