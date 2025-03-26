<?php
// manageStore.php

function purchaseItem($player_id, $item, $quantity, $price, $conn) {
    // Get player state
    $playerRow = getPlayerState($player_id, $conn);

    // Check if player can afford the item
    if ($playerRow['player_state']['inventory']['money'] >= ($price * $quantity)) {
        $playerRow['player_state']['inventory'][$item] += $quantity;
        $playerRow['player_state']['inventory']['money'] -= ($price * $quantity);
        
        // Save updated state
        updatePlayerState($player_id, $playerRow['player_state'], $conn);
        return true; // Purchase successful
    }

    return false; // Insufficient funds
}
?>
