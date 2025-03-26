<?php
// game/engine/game_functions.php

// Example: Function to log events for player actions
function addLogEntry(&$playerRow, $logEntry) {
    $playerRow['player_state']['log'][] = ['day' => $playerRow['player_state']['day'], 'notes' => $logEntry];
}

?>
