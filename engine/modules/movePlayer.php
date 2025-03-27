<?php
// movePlayer.php

function movePlayer($player_id, $miles_traveled, $playerRow) {
    // Update the miles traveled
    $playerRow['player_state']['mile'] += $miles_traveled;

    // Increment the day by 1 (each turn is a day)
    $playerRow['player_state']['day'] += 1;

    // Check if player reached a milestone (you could add more logic here for actual milestones)
    if ($playerRow['player_state']['mile'] >= 100) {
        $playerRow['player_state']['log'][] = ['notes' => 'You reached a new milestone!'];
    }

    // Return the updated playerRow
    return $playerRow;
}
?>
