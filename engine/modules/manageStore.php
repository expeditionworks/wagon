<?php
// manageStore.php
// Processes store purchases against $playerState.
// Modifies ONLY $playerState — no DB writes, no HTML output.

function processPurchase(&$playerState, $itemName, $quantity, $storeItems) {
    // Check item exists in store
    if (!isset($storeItems[$itemName])) {
        debugLog($playerState, "Error: $itemName not found in store.");
        return ['success' => false, 'message' => "$itemName is not available in this store."];
    }

    $itemDetails = $storeItems[$itemName];
    $totalCost = $itemDetails['base_price'] * $quantity;

    // Check player can afford it
    if ($playerState['dollars'] < $totalCost) {
        debugLog($playerState, "Error: Not enough money to buy $quantity $itemName. Need $$totalCost, have $" . $playerState['dollars']);
        return ['success' => false, 'message' => "You can't afford $quantity $itemName. Cost: \$$totalCost, You have: \$" . $playerState['dollars']];
    }

    // Check stock available
    if ($itemDetails['stock_limit'] < $quantity) {
        debugLog($playerState, "Error: Not enough stock for $itemName. Need $quantity, have " . $itemDetails['stock_limit']);
        return ['success' => false, 'message' => "Not enough stock. Only " . $itemDetails['stock_limit'] . " $itemName available."];
    }

    // Complete the purchase
    $playerState['dollars'] -= $totalCost;
    addToInventory($playerState, $itemName, $quantity);

    debugLog($playerState, "Purchased $quantity $itemName for \$$totalCost. Dollars remaining: " . $playerState['dollars']);
    return ['success' => true, 'message' => "Purchased $quantity $itemName for \$$totalCost. You have \$" . $playerState['dollars'] . " left."];
}

function getStoreItems($playerState) {
    // Get store items from current pending_action
    if (!empty($playerState['pending_action']) && 
        $playerState['pending_action']['type'] === 'store') {
        return $playerState['pending_action']['items'] ?? [];
    }
    return [];
}
?>