<?php
// applyRations.php
// Calculates food consumption for the day and applies morale modifier.
// Modifies ONLY $playerState — no DB writes, no HTML output.

function applyRations(&$playerState) {
    $itemName = 'Food';

    // Food per person based on ration size
    switch ($playerState['ration']) {
        case 'generous':
            $foodPerPerson = 3;
            $foodMoraleMod = 1;
            break;
        case 'half':
            $foodPerPerson = 1;
            $foodMoraleMod = -1;
            break;
        case 'meager':
            $foodPerPerson = 0.5;
            $foodMoraleMod = -2;
            break;
        case 'full':
        default:
            $foodPerPerson = 2;
            $foodMoraleMod = 0;
            break;
    }

    // Count family members
    // Ensure family is an array before counting
    $family = $playerState['family'];
    if (is_string($family)) {
        $family = json_decode($family, true) ?? [];
    }
    $familyCount = is_array($family) && count($family) > 0 ? count($family) : 2;
    $totalFoodConsumed = $foodPerPerson * $familyCount;

    // Deduct food from inventory
    if (isset($playerState['inventory'][$itemName])) {
        if ($playerState['inventory'][$itemName]['quantity'] >= $totalFoodConsumed) {
            $playerState['inventory'][$itemName]['quantity'] -= $totalFoodConsumed;
        } else {
            // Not enough food
            $totalFoodConsumed = $playerState['inventory'][$itemName]['quantity'];
            $playerState['inventory'][$itemName]['quantity'] = 0;
            $foodMoraleMod = -5; // Morale hit for no food
            debugLog($playerState, "Warning: Not enough food. Party goes hungry.");
        }
    }

    // Store for other modules to use
    $playerState['foodConsumedToday'] = $totalFoodConsumed;
    $playerState['foodMoraleMod'] = $foodMoraleMod;
    $playerState['familyCount'] = $familyCount;

    debugLog($playerState, "Rations: " . $playerState['ration'] . ", Food consumed: " . $totalFoodConsumed . " lbs, Family: " . $familyCount);
}
?>