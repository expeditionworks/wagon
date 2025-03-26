<?php
// handleDecisions.php

function handleRiverCrossing($player_id, $decision, $conn) {
    // Get player state
    $playerRow = getPlayerState($player_id, $conn);

    // Handle river crossing decision (e.g., crossing method: ford, ferry, etc.)
    if ($decision == 'ferry') {
        $playerRow['player_state']['log'][] = ['notes' => 'You crossed a river using the ferry.'];
    } elseif ($decision == 'ford') {
        $playerRow['player_state']['log'][] = ['notes' => 'You crossed a river by fording it.'];
    }

    // Save updated state
    updatePlayerState($player_id, $playerRow['player_state'], $conn);
}
?>
