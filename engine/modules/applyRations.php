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
    $family = $playerState['family'];
    if (is_string($family)) {
        $family = json_decode($family, true) ?? [];
    }
    $familyCount = is_array($family) && count($family) > 0 ? count($family) : 2;
    $totalFoodConsumed = $foodPerPerson * $familyCount;

    // Deduct food from inventory
    if (isset($playerState['inventory'][$itemName])) {
        if ($playerState['inventory'][$itemName]['quantity'] >= $totalFoodConsumed) {
            // Enough food
            $playerState['inventory'][$itemName]['quantity'] -= $totalFoodConsumed;
        } else {
            // Not enough food — take what's left
            $totalFoodConsumed = $playerState['inventory'][$itemName]['quantity'];
            $playerState['inventory'][$itemName]['quantity'] = 0;
            $foodMoraleMod = -5;
            debugLog($playerState, "Warning: Not enough food. Party goes hungry.");
        }
    }

    // Starvation check — runs every turn when food is zero
    if (($playerState['inventory'][$itemName]['quantity'] ?? 0) == 0 && $totalFoodConsumed == 0) {
        $foodMoraleMod = -5;
        $family = is_string($playerState['family'])
            ? json_decode($playerState['family'], true) ?? []
            : $playerState['family'];
        if (is_array($family)) {
            foreach ($family as &$familyMember) {
                if (!($familyMember['deceased'] ?? false)) {
                    $familyMember['health'] -= 10;
                    $familyMember['condition'] = 'malnourished';
                    if ($familyMember['health'] <= 0) {
                        $familyMember['health'] = 0;
                        $familyMember['deceased'] = true;
                        debugLog($playerState, $familyMember['first_name'] . " has died of starvation.");
                    }
                }
            }
            $playerState['family'] = $family;
        }
        debugLog($playerState, "Warning: No food. Party starving.");
    }

    // Store for other modules to use
    $playerState['foodConsumedToday'] = $totalFoodConsumed;
    $playerState['foodMoraleMod'] = $foodMoraleMod;
    $playerState['familyCount'] = $familyCount;

    debugLog($playerState, "Rations: " . $playerState['ration'] . ", Food consumed: " . $totalFoodConsumed . " lbs, Family: " . $familyCount);
}
?>