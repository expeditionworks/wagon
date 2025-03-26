<?php
// movePlayer.php

function movePlayer($player_id, $miles_traveled, $conn) {
    // Get player state
    $playerRow = getPlayerState($player_id, $conn);

    // Update the miles traveled
    $playerRow['player_state']['mile'] += $miles_traveled;

    // Check if player reached a milestone (you could add more logic here for actual milestones)
    if ($playerRow['player_state']['mile'] >= 100) {
        $playerRow['player_state']['log'][] = ['notes' => 'You reached a new milestone!'];
    }

    // Save the updated player state
    updatePlayerState($player_id, $playerRow['player_state'], $conn);
}
?>
