<?php
// applyConditions.php
// Applies health and morale effects from family conditions.
// Modifies ONLY $playerState — no DB writes, no HTML output.

function applyConditions(&$playerState) {
    $conditionsPath = __DIR__ . '/../../config/conditions.json';
    $conditionsList = [];
    $conditionTravelMod = 1.0;

    if (!file_exists($conditionsPath)) {
        debugLog($playerState, "Error: Conditions file not found.");
        $playerState['conditionTravelMod'] = $conditionTravelMod;
        return;
    }

    $conditionsList = json_decode(file_get_contents($conditionsPath), true);
    if ($conditionsList === null) {
        debugLog($playerState, "Error: Failed to decode conditions.json.");
        $playerState['conditionTravelMod'] = $conditionTravelMod;
        return;
    }

    // Ensure family is an array
    if (!isset($playerState['family']) || !is_array($playerState['family'])) {
        debugLog($playerState, "Error: Family data is missing or not an array.");
        $playerState['conditionTravelMod'] = $conditionTravelMod;
        return;
    }

    // Apply food morale modifier from applyRations
    $foodMoraleMod = $playerState['foodMoraleMod'] ?? 0;

    foreach ($playerState['family'] as &$familyMember) {
        // Apply food morale modifier
        $familyMember['morale'] = max(0, min(100, $familyMember['morale'] + $foodMoraleMod));

        // Apply condition effects if member has a condition
        if (isset($familyMember['condition']) && isset($conditionsList[$familyMember['condition']])) {
            $conditionData = $conditionsList[$familyMember['condition']];

            // Apply health risk
            $healthRisk = $conditionData['health_risk'] ?? 0;
            $familyMember['health'] -= $healthRisk;

            // Apply morale penalty
            $moralePenalty = $conditionData['morale_penalty'] ?? 0;
            $familyMember['morale'] = max(0, min(100, $familyMember['morale'] - $moralePenalty));

            // Apply travel slowdown
            if (!empty($conditionData['slows_travel'])) {
                $conditionTravelMod = 0.9;
            }

            // Decrease condition duration
            if (isset($familyMember['condition_duration']) && $familyMember['condition_duration'] > 0) {
                $familyMember['condition_duration']--;
            } else {
                $familyMember['condition'] = 'healthy';
            }

            debugLog($playerState, $familyMember['first_name'] . " condition: " . $familyMember['condition'] . ", health: " . $familyMember['health'] . ", morale: " . $familyMember['morale']);
        }
    }

    // Store travel modifier for movePlayer to use
    $playerState['conditionTravelMod'] = $conditionTravelMod;
}
?>