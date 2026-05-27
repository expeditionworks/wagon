<?php
// manageInventory.php
// Helper functions for modifying inventory in $playerState.
// Modifies ONLY $playerState — no DB writes, no HTML output.

// Add quantity to an inventory item
function addToInventory(&$playerState, $itemName, $quantity) {
    if (!isset($playerState['inventory'][$itemName])) {
        $playerState['inventory'][$itemName] = ['quantity' => 0, 'durability' => null];
    }
    $playerState['inventory'][$itemName]['quantity'] += $quantity;
    debugLog($playerState, "Added $quantity $itemName to inventory. New total: " . $playerState['inventory'][$itemName]['quantity']);
}

// Remove quantity from an inventory item
function removeFromInventory(&$playerState, $itemName, $quantity) {
    if (!isset($playerState['inventory'][$itemName])) {
        debugLog($playerState, "Error: $itemName not found in inventory.");
        return false;
    }
    if ($playerState['inventory'][$itemName]['quantity'] < $quantity) {
        debugLog($playerState, "Error: Not enough $itemName in inventory.");
        return false;
    }
    $playerState['inventory'][$itemName]['quantity'] -= $quantity;
    debugLog($playerState, "Removed $quantity $itemName from inventory. New total: " . $playerState['inventory'][$itemName]['quantity']);
    return true;
}

// Check if player has enough of an item
function hasInventory($playerState, $itemName, $quantity) {
    return ($playerState['inventory'][$itemName]['quantity'] ?? 0) >= $quantity;
}
?>