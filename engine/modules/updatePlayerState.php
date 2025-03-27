<?php
// updatePlayerState.php

function updatePlayerState($player_id, $playerState, $conn) {
    // Prepare the updated player state for storage
    $inventoryJson = json_encode($playerState['inventory']);  // Assign json_encode() result to a variable
    $logJson = json_encode($playerState['log']);  // Assign json_encode() result to a variable
    $currentTrail = $playerState['current_trail'];  // Get the player's current trail (e.g., 'oregon' or 'california')

    // Debugging: Log player state before updating
    echo "<p>Updating Player State:</p>";
    echo "<p>Day: " . $playerState['day'] . "</p>";
    echo "<p>Mile: " . $playerState['mile'] . "</p>";
    echo "<p>Morale: " . $playerState['morale'] . "</p>";
    echo "<p>Trail: " . $currentTrail . "</p>";

    // Query to update the player state in the database
    $query = "UPDATE player_state SET 
              day = ?, mile = ?, morale = ?, inventory = ?, log = ?, current_trail = ? 
              WHERE player_id = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        // Check for errors in the prepared statement
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
        return;
    }

    // Bind the parameters to the statement
    $stmt->bind_param(
        'iissssi', 
        $playerState['day'], 
        $playerState['mile'], 
        $playerState['morale'], 
        $inventoryJson,  // Pass the inventory JSON
        $logJson,        // Pass the log JSON
        $currentTrail,   // Pass the current trail to be updated in the DB
        $player_id
    );

    // Execute the query to update the player state in the database
    if ($stmt->execute()) {
        echo "<p>Player state updated successfully!</p>";
    } else {
        // If the execution fails, show an error
        echo "<p>Error executing query: " . $stmt->error . "</p>";
    }
}
?>
