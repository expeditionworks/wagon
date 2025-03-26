<?php
// game_engine.php

// Include all modules
include_once(__DIR__ . '/modules/getPlayerState.php');
include_once(__DIR__ . '/modules/updatePlayerState.php');
include_once(__DIR__ . '/modules/movePlayer.php');
include_once(__DIR__ . '/modules/manageInventory.php');
include_once(__DIR__ . '/modules/handleMilestones.php');

// Central function to run the daily game logic
function runDailyTurn($player_id, $conn) {
    // Fetch player data
    $playerRow = getPlayerState($player_id, $conn);
    if (!$playerRow) {
        echo "No player data found!";
        return;
    }

    // Process the day's activities (e.g., move, consume food, check milestones)
    movePlayer($playerRow, $conn);
    manageInventory($playerRow);
    handleMilestones($playerRow);
    
    // Save the updated player state
    updatePlayerState($player_id, $playerRow['player_state'], $conn);
}
?>
