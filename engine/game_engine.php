<?php
// game_engine.php

// Include necessary modules
include_once(__DIR__ . '/modules/getPlayerState.php');
include_once(__DIR__ . '/modules/moveAndCheckMilestones.php');
include_once(__DIR__ . '/modules/updatePlayerState.php');  // To save the player state

function runDailyTurn($player_id, $conn) {
    // Step 1: Get the player’s state from the database
    $playerState = getPlayerState($player_id, $conn);

    if ($playerState) {
        // Step 2: Move the player and check milestones
        $playerState = moveAndCheckMilestones($playerState, $player_id, $conn);

        // Step 3: Save the updated player state back to the database
        updatePlayerState($player_id, $playerState, $conn);

        // Return success message or milestone-related HTML for reporting
        return "Turn processed successfully.";
    }

    return "Player not found.";
}
?>
