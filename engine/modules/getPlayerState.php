<?php
// getPlayerState.php
// Loads player state from DB into memory at the START of each turn.
// Nothing else should read from the DB during a turn.

function getPlayerState($player_id, $conn) {
    $query = "SELECT ps.*, p.id AS player_id FROM player_state ps
              LEFT JOIN players p ON p.id = ps.player_id
              WHERE ps.player_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerRow = $result->fetch_assoc();

    if (!$playerRow) {
        return null;
    }

    // Calculate current month based on start date and days traveled
    $startDate = $playerRow['start_date'] ?? '1849-05-01';
    $startDateObj = new DateTime($startDate);
    $startDateObj->modify('+' . ($playerRow['day'] - 1) . ' days');
    $month = $startDateObj->format('F');

    $currentMile = $playerRow['mile'];

    // Load terrain config and find current terrain
    $terrainType = 'plains';
    $altitude = 'low';
    $terrainPath = __DIR__ . '/../../config/terrain.json';
    if (file_exists($terrainPath)) {
        $terrain = json_decode(file_get_contents($terrainPath), true) ?? [];
        foreach ($terrain as $section) {
            if ($currentMile >= $section['start_mile'] && $currentMile <= $section['end_mile']) {
                $terrainType = is_string($section['terrain']) ? $section['terrain'] : 'plains';
                $altitude = $section['altitude'] ?? 'low';
                break;
            }
        }
    } else {
        $terrain = [];
    }

    // Load milestones config
    $milestonesPath = __DIR__ . '/../../config/milestones.json';
    $milestones = file_exists($milestonesPath)
        ? json_decode(file_get_contents($milestonesPath), true) ?? []
        : [];

    // Load weather types config
    $weatherTypesPath = __DIR__ . '/../../config/weather_types.json';
    $weatherTypes = file_exists($weatherTypesPath)
        ? json_decode(file_get_contents($weatherTypesPath), true) ?? []
        : [];

    // Default weather
    $defaultWeather = [
        'weather_type'  => 'sunny',
        'temperature'   => ['min' => 20, 'max' => 40],
        'precipitation' => 'none',
        'wind_speed'    => ['min' => 5, 'max' => 15],
        'type'          => 'default'
    ];

    // Load last turn's weather from DB
    $weatherLastTurn = null;
    if (!empty($playerRow['weather'])) {
        $decoded = json_decode($playerRow['weather'], true);
        if ($decoded !== null) {
            $weatherLastTurn = $decoded;
        }
    }
    if ($weatherLastTurn === null) {
        $weatherLastTurn = $defaultWeather;
    }

    // Build and return player state
    return [
        'family'         => !empty($playerRow['family']) ? json_decode($playerRow['family'], true) : [],
        'dollars'        => $playerRow['dollars'] ?? 10,
        'day'            => $playerRow['day'] ?? 1,
        'mile'           => $playerRow['mile'] ?? 0,
        'morale'         => $playerRow['morale'] ?? 100,
        'ration'         => $playerRow['ration_size'] ?? 'full',
        'inventory'      => !empty($playerRow['inventory']) ? json_decode($playerRow['inventory'], true) : [],
        'log'            => !empty($playerRow['log']) ? json_decode($playerRow['log'], true) : [],
        'last_log_item'  => !empty($playerRow['last_log_item']) ? json_decode($playerRow['last_log_item'], true) : [],
        'current_trail'  => $playerRow['current_trail'] ?? 'oregon',
        'terrain'        => $terrain,
        'terrainCurrent' => $terrainType,
        'altitude'       => $altitude,
        'milestones'     => $milestones,
        'delay_days'     => $playerRow['delay_days'] ?? 0,
        'delay_status'   => $playerRow['delay_status'] ?? 'completed',
        'difficulty'     => $playerRow['difficulty'] ?? 'medium',
        'miles_traveled' => $playerRow['miles_traveled'] ?? 0,
        'weatherLastTurn'=> $weatherLastTurn,
        'weatherThisTurn'=> $weatherLastTurn,
        'start_date'     => $playerRow['start_date'] ?? null,
        'month'          => $month,
        'pending_action' => !empty($playerRow['pending_action'])
                            ? json_decode($playerRow['pending_action'], true)
                            : null,
        'game_over'      => $playerRow['game_over'] ?? 0,    
        'debug'          => []
    ];
}
?>