<?php
// game_engine.php

// Include necessary modules for player state handling
include_once(__DIR__ . '/modules/movePlayer.php');
include_once(__DIR__ . '/modules/handleMilestones.php');
include_once(__DIR__ . '/modules/updatePlayerState.php');  // Ensure the updatePlayerState function is available

function runDailyTurn($player_id, $conn) {
    // Step 1: Fetch current player state from the database
    $playerRow = getPlayerState($player_id, $conn);
    
    // Step 2: Process player movement and increment the day
    // Call movePlayer to update the player’s temporary state
    $playerRow = movePlayer($player_id, 10, $playerRow);  // Adjust this value to simulate different movement amounts
    
    // Step 3: Check milestones and handle milestone-related logic
    // Call handleMilestones to update milestones and add entries to the player log
    $milestoneHtml = checkMilestones($player_id, $conn);
    
    // Step 4: After all changes to the player state (movement, milestones), update the player state in the database
    updatePlayerState($player_id, $playerRow['player_state'], $conn);

    // Optionally, log milestone HTML or display it elsewhere for reporting purposes
    echo $milestoneHtml;
}

// Function to get player state
function getPlayerState($player_id, $conn) {
    $query = "SELECT * FROM player_state WHERE player_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

?>
