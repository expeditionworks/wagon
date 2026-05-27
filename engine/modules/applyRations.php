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

    // Count living family members only
    $family = $playerState['family'];
    if (is_string($family)) {
        $family = json_decode($family, true) ?? [];
    }
    $livingMembers = array_filter($family, fn($m) => !($m['deceased'] ?? false));
    $familyCount = count($livingMembers) > 0 ? count($livingMembers) : 0;
    $totalFoodConsumed = $foodPerPerson * $familyCount;

    // Deduct food from inventory
    $starving = false;
    if (isset($playerState['inventory'][$itemName])) {
        if ($playerState['inventory'][$itemName]['quantity'] >= $totalFoodConsumed) {
            // Enough food
            $playerState['inventory'][$itemName]['quantity'] -= $totalFoodConsumed;
        } else {
            // Not enough food — take what's left
            $totalFoodConsumed = $playerState['inventory'][$itemName]['quantity'];
            $playerState['inventory'][$itemName]['quantity'] = 0;
            $foodMoraleMod = -5;
            $starving = true;
            debugLog($playerState, "Warning: Not enough food. Party goes hungry.");
        }
    }

    // Mark starving if food is zero
    if (($playerState['inventory'][$itemName]['quantity'] ?? 1) == 0) {
        $starving = true;
    }

    // Apply starvation effects to living family members
    if ($starving && $familyCount > 0) {
        foreach ($family as &$familyMember) {
            if (!($familyMember['deceased'] ?? false)) {
                $familyMember['health'] -= 10;
                $familyMember['condition'] = 'malnourished';
                if ($familyMember['health'] <= 0) {
                    $familyMember['health'] = 0;
                    $familyMember['deceased'] = true;
                    $playerState['log'][] = [
                        'day'            => $playerState['day'],
                        'miles_traveled' => 0,
                        'total_miles'    => $playerState['mile'],
                        'milestone'      => null,
                        'notes'          => $familyMember['first_name'] . " has died of starvation. The loss weighs heavily on everyone."
                    ];
                    $playerState['morale'] = max(0, ($playerState['morale'] ?? 100) - 20);
                    debugLog($playerState, $familyMember['first_name'] . " has died of starvation.");
                }
            }
        }
        $playerState['family'] = $family;
        debugLog($playerState, "Warning: Party starving.");
    }

    // Store for other modules to use
    $playerState['foodConsumedToday'] = $totalFoodConsumed;
    $playerState['foodMoraleMod'] = $foodMoraleMod;
    $playerState['familyCount'] = $familyCount;

    debugLog($playerState, "Rations: " . $playerState['ration'] . ", Food consumed: " . $totalFoodConsumed . " lbs, Living family: " . $familyCount);
}
?>