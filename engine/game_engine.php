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
    // Step 1: Fetch and backup player state
    $playerRow = getPlayerState($player_id, $conn);
    if (!$playerRow) {
        echo "No player data found!";
        return;
    }

    // Backup the initial player state
    $initialState = $playerRow;

    // Step 2: Execute the modules, each modifying a local copy of the player state
    // Move player if the module exists
    if (file_exists('modules/movePlayer.php')) {
        include_once('modules/movePlayer.php');
        $playerRow = movePlayer($player_id, rand(10, 20), $conn); // Random miles traveled for the example
    } else {
        // If the module is missing, log it and skip
        error_log("movePlayer module is missing.");
    }

    // Manage inventory if the module exists
    if (file_exists('modules/manageInventory.php')) {
        include_once('modules/manageInventory.php');
        $playerRow = manageInventory($playerRow);
    } else {
        // If the module is missing, log it and skip
        error_log("manageInventory module is missing.");
    }

    // Handle milestones if the module exists
    if (file_exists('modules/handleMilestones.php')) {
        include_once('modules/handleMilestones.php');
        $playerRow = handleMilestones($playerRow);
    } else {
        // If the module is missing, log it and skip
        error_log("handleMilestones module is missing.");
    }

    // Step 3: If any module failed, revert to the initial state
    if ($playerRow !== $initialState) {
        // If state was modified by a module, validate the fields
        $playerRow = array_merge($initialState, $playerRow); // Revert any changes to missing fields
    }

    // Step 4: Apply updates to the database
    updatePlayerState($player_id, $playerRow['player_state'], $conn);

    // Step 5: Log the daily events and prepare for next turn
    logDailyEvents($playerRow);
}

// Function to log daily events for SMS and weekly digest
function logDailyEvents($playerRow) {
    // For short SMS update (example)
    $smsLog = "Day {$playerRow['player_state']['day']} - Traveled {$playerRow['player_state']['mile']} miles.";
    // Store this log in a way that it can be sent via SMS (database, file, etc.)

    // For weekly digest (example)
    $weeklyLog = "Week {$playerRow['player_state']['day']}/{$playerRow['player_state']['mile']} miles - " . implode(", ", $playerRow['player_state']['log']);
    // Store the full log for email digest or longer form

    // Example: store logs in database or a file system
    // updatePlayerStateLog($playerRow['id'], $smsLog, $weeklyLog);
}
?>
