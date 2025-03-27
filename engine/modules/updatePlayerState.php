<?php
// updatePlayerState.php

function updatePlayerState($player_id, $playerState, $conn) {
    // Prepare the updated player state for storage
    $inventoryJson = json_encode($playerState['inventory']);  // Assign json_encode() result to a variable
    $logJson = json_encode($playerState['log']);  // Assign json_encode() result to a variable

    $query = "UPDATE player_state SET 
              day = ?, mile = ?, morale = ?, inventory = ?, log = ? 
              WHERE player_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        'iisssi', 
        $playerState['day'], 
        $playerState['mile'], 
        $playerState['morale'], 
        $inventoryJson,  // Pass the variable here
        $logJson,        // Pass the variable here
        $player_id
    );

    $stmt->execute();
}
?>
