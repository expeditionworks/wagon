<?php
// handleMilestones.php

function checkMilestone($player_id, $conn) {
    // Get player state
    $playerRow = getPlayerState($player_id, $conn);

    // Check for a milestone based on miles traveled (this is just an example)
    if ($playerRow['player_state']['mile'] >= 100) {
        $playerRow['player_state']['log'][] = ['notes' => 'You reached a major milestone!'];
        updatePlayerState($player_id, $playerRow['player_state'], $conn);
    }
}
?>
