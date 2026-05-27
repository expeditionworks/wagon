<?php
// movePlayer.php
// Calculates how far the player moves today based on all modifiers.
// Requires applyWeather and applyConditions to have run first.
// Modifies ONLY $playerState — no DB writes, no HTML output.

function movePlayer(&$playerState) {
    $baseMiles = 15;

    // Terrain modifier
    $terrainModifiers = [
        ''              => 1.0,
        'plains'        => 1.2,
        'rolling hills' => 1.0,
        'mountains'     => 0.8,
        'valleys'       => 1.0,
        'river valley'  => 1.0,
        'desert'        => 0.7
    ];
    $terrainMod = ($terrainModifiers[$playerState['terrainCurrent']] ?? 1.0);

    // Altitude modifier
    $altitudeModifiers = [
        'low'    => 0.9,
        'medium' => 1.0,
        'high'   => 1.2
    ];
    $altitudeMod = $altitudeModifiers[$playerState['altitude']] ?? 1.0;

    // Difficulty modifier
    $difficultyModifiers = [
        'easy'   => 1.1,
        'medium' => 1.0,
        'hard'   => 0.9
    ];
    $difficultyMod = $difficultyModifiers[$playerState['difficulty']] ?? 1.0;

    // Morale modifier
    $morale = $playerState['morale'] ?? 100;
    if ($morale >= 90) {
        $moraleMod = 1.1;
    } elseif ($morale >= 50) {
        $moraleMod = 1.0;
    } else {
        $moraleMod = 0.8;
    }

    // Oxen modifier
    $oxenNumber = $playerState['inventory']['Oxen']['quantity'] ?? 6;
    if ($oxenNumber >= 8) {
        $oxenMod = 1.2;
    } elseif ($oxenNumber >= 6) {
        $oxenMod = 1.0;
    } elseif ($oxenNumber >= 3) {
        $oxenMod = 0.8;
    } elseif ($oxenNumber >= 1) {
        $oxenMod = 0.5;
    } else {
        $oxenMod = 0.0;
    }

    // Random factor
    $randomFactor = mt_rand(95, 105) / 100;

    // Pull weather and condition modifiers set by earlier modules
    $windModifier         = $playerState['windModifier'] ?? 1.0;
    $precipitationPenalty = $playerState['precipitationPenalty'] ?? 1.0;
    $conditionTravelMod   = $playerState['conditionTravelMod'] ?? 1.0;

    // Calculate miles
    $adjustedDistance = round($baseMiles * $difficultyMod * $terrainMod * $altitudeMod * $randomFactor * $conditionTravelMod * $precipitationPenalty * $moraleMod * $oxenMod);
    $milesTraveled = round(max($adjustedDistance * $windModifier, 0));

    // Store results
    $playerState['miles_traveled'] = $milesTraveled;
    $playerState['mile'] += $milesTraveled;

    debugLog($playerState, "Base: $baseMiles, Terrain: $terrainMod, Altitude: $altitudeMod, Difficulty: $difficultyMod, Morale: $moraleMod, Oxen: $oxenMod, Wind: $windModifier, Precip: $precipitationPenalty, Condition: $conditionTravelMod, Random: $randomFactor");
    debugLog($playerState, "Miles traveled today: $milesTraveled, Total mile: " . $playerState['mile']);
}
?>