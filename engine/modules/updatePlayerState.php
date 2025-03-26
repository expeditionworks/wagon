<?php
// updatePlayerState.php

function savePlayerState($player_id, $playerState, $conn) {
    // Prepare SQL query to update player state in the database
    $sql = "UPDATE player_state SET 
            day = ?, 
            mile = ?, 
            inventory = ?, 
            conditions = ?, 
            log = ? 
            WHERE player_id = ?";

    // Convert arrays to JSON
    $inventory_json = json_encode($playerState['inventory']);
    $conditions_json = json_encode($playerState['conditions']);
    $log_json = json_encode($playerState['log']);

    // Prepare and bind parameters
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssi", 
        $playerState['day'], 
        $playerState['mile'], 
        $inventory_json, 
        $conditions_json, 
        $log_json, 
        $player_id
    );

    // Execute and check for errors
    if ($stmt->execute()) {
        echo "Player state successfully updated.";
    } else {
        echo "Error updating player state: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}
?>
