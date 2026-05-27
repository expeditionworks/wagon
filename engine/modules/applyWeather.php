<?php
// applyWeather.php
// Calculates weather for this turn and stores it in $playerState.
// Modifies ONLY $playerState — no DB writes, no HTML output.

function applyWeather(&$playerState) {
    $weatherMonthsPath = __DIR__ . '/../../config/weather_months.json';
    if (!file_exists($weatherMonthsPath)) {
        debugLog($playerState, "Error: Weather Months file not found.");
        $weatherMonths = [];
    } else {
        $weatherMonths = json_decode(file_get_contents($weatherMonthsPath), true) ?? [];
    }

    // Get data for current month, fall back to May
    $monthData = $weatherMonths[$playerState['month']] ?? $weatherMonths['May'];

    // Pick random weather type for today
    $weatherTypes = $monthData['weather_types'];
    $weatherType = $weatherTypes[array_rand($weatherTypes)];

    // Pick temperature for that weather type
    $temperatureRange = $monthData['temperature_range'][$weatherType];
    $temperature = rand($temperatureRange[0], $temperatureRange[1]);

    // Wind speed
    $windSpeedRange = $monthData['wind_speed_range'] ?? ['min' => 5, 'max' => 25];
    $randomWindSpeed = rand($windSpeedRange['min'], $windSpeedRange['max']);

    // Wind type and modifier
    $windTypes = ['headwind', 'tailwind', 'crosswind'];
    $windType = $windTypes[array_rand($windTypes)];
    if ($windType === 'headwind') {
        $windModifier = 1 - ($randomWindSpeed / 50);
    } elseif ($windType === 'tailwind') {
        $windModifier = 1 + ($randomWindSpeed / 100);
    } else {
        $windModifier = 1 - ($randomWindSpeed / 200);
    }

    // Precipitation
    $chanceOfSnow = $monthData['chance_of_snow'] ?? 0;
    $chanceOfRain = $monthData['chance_of_rain'] ?? 0;
    $precipitation = 'none';
    $precipitationPenalty = 1.0;
    if ($weatherType === 'snowy' && rand(0, 100) <= $chanceOfSnow) {
        $precipitation = 'snow';
        $precipitationPenalty = 0.8;
    } elseif ($weatherType === 'cloudy' && rand(0, 100) <= $chanceOfRain) {
        $precipitation = 'rain';
        $precipitationPenalty = 1.0;
    }

    // Store results in playerState
    $playerState['weatherThisTurn'] = [
        'weather_type'  => $weatherType,
        'temperature'   => $temperature,
        'precipitation' => $precipitation,
        'wind_speed'    => $randomWindSpeed,
        'wind_type'     => $windType,
        'date'          => date('Y-m-d'),
    ];

    // Store modifiers for movePlayer to use
    $playerState['windModifier']          = $windModifier;
    $playerState['precipitationPenalty']  = $precipitationPenalty;

    debugLog($playerState, "Weather: " . $weatherType . ", Temp: " . $temperature . "F, Wind: " . $windType . " at " . $randomWindSpeed . " mph, Precip: " . $precipitation);
}
?>