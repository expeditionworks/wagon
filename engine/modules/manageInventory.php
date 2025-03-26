<?php
// manageInventory.php

function updateInventory($player_id, $item, $quantity, $conn) {
    // Get player state
    $playerRow = getPlayerState($player_id, $conn);
    
    // Update inventory
    if (isset($playerRow['player_state']['inventory'][$item])) {
        $playerRow['player_state']['inventory'][$item] += $quantity;
    }

    // Save updated inventory back to the database
    updatePlayerState($player_id, $playerRow['player_state'], $conn);
}
?>
