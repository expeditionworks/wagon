<?php
// updatePlayerState.php

function updatePlayerState($player_id, $playerState, $conn) {
    // Prepare the updated player state for storage
    $query = "UPDATE player_state SET 
              day = ?, mile = ?, morale = ?, inventory = ?, log = ? 
              WHERE player_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        'iisssi', 
        $playerState['day'], 
        $playerState['mile'], 
        $playerState['morale'], 
        json_encode($playerState['inventory']), 
        json_encode($playerState['log']),
        $player_id
    );

    $stmt->execute();
}
?>
