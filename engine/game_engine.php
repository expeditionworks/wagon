<?php
// game_engine.php

// Include the necessary files for database connection and game logic
include_once(__DIR__ . '/db_connection.php'); // Database connection

function getPlayerState($player_id, $conn) {
    // Query to fetch player state from the database
    $query = "SELECT * FROM player_state WHERE player_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerRow = $result->fetch_assoc();

    // If player data exists, populate the player state
    if ($playerRow) {
        // Load JSON configurations for terrain and milestones
        $terrainPath = __DIR__ . '/../config/terrain.json';
        if (file_exists($terrainPath)) {
            $terrainContent = file_get_contents($terrainPath);
            $terrain = $terrainContent !== false ? json_decode($terrainContent, true) : [];
        } else {
            echo "Terrain file not found or not accessible.";
            $terrain = []; // Default empty array
        }

        $milestonesPath = __DIR__ . '/../config/milestones.json';
        if (file_exists($milestonesPath)) {
            $milestonesContent = file_get_contents($milestonesPath);
            $milestones = $milestonesContent !== false ? json_decode($milestonesContent, true) : [];
        } else {
            echo "Milestones file not found or not accessible.";
            $milestones = []; // Default empty array
        }

        // Default weather if not set in the database
        $defaultWeather = [
            "weather_type" => "sunny",
            "temperature" => ["min" => 20, "max" => 40],
            "precipitation" => "none",
            "wind_speed" => ["min" => 5, "max" => 15]
        ];

        // Load weather_months.json
        $weatherMonthsPath = __DIR__ . '/../config/weather_months.json';
        if (file_exists($weatherMonthsPath)) {
            $weatherMonthsContent = file_get_contents($weatherMonthsPath);
            $weatherMonths = $weatherMonthsContent !== false ? json_decode($weatherMonthsContent, true) : [];
        } else {
            echo "Weather Months file not found or not accessible.";
            // Default fallback to May if the file doesn't exist
            $weatherMonths = [
                "May" => [
                    "weather_types" => ["sunny", "cloudy", "snowy"],
                    "temperature_range" => [
                        "sunny" => [25, 45],
                        "cloudy" => [20, 40],
                        "snowy" => [15, 30]
                    ],
                    "chance_of_snow" => 45,
                    "chance_of_rain" => 15,
                    "wind_speed_range" => ["min" => 5, "max" => 25],
                    "description" => "Spring is in full swing with mild temperatures and occasional rain showers. Snow is rare during this period."
                ]
            ];
        }

        // Load weather_types.json
        $weatherTypesPath = __DIR__ . '/../config/weather_types.json';
        if (file_exists($weatherTypesPath)) {
            $weatherTypesContent = file_get_contents($weatherTypesPath);
            $weatherTypes = $weatherTypesContent !== false ? json_decode($weatherTypesContent, true) : [];
        } else {
            echo "Weather Types file not found or not accessible.";
            // Default fallback if the file doesn't exist
            $weatherTypes = [
                "sunny" => [
                    "descriptions" => [
                        "The sun is shining brightly in a clear sky.",
                        "A warm, sunny day with no clouds in sight.",
                        "Clear skies and radiant sunlight fill the air."
                    ]
                ]
            ];
        }

        // Now you can access $weatherMonths and $weatherTypes as needed

        // Check if 'weather' exists in the player row and is a valid JSON string
        $weather = null;
        if (!empty($playerRow['weather'])) {
            // Attempt to decode the weather data, but check if it’s valid JSON
            $decodedWeather = json_decode($playerRow['weather'], true);
        
            // If json_decode returns null, that means the data isn't valid JSON
            if ($decodedWeather !== null) {
                $weather = $decodedWeather;
            }
        }
        
        // If the weather is still null (invalid or missing), use the default weather
        if ($weather === null) {
            $weather = $defaultWeather;
        }

        // Get the start_date and calculate the current month
        $startDate = isset($playerRow['start_date']) ? $playerRow['start_date'] : null;
        $month = 'Unknown'; // Default month if no start date exists

        if ($startDate) {
            $startTimestamp = strtotime($startDate); // Convert start date to timestamp
            $currentDay = isset($playerRow['day']) ? $playerRow['day'] : 0; // Get the current day from player state
            $currentDate = strtotime("+$currentDay days", $startTimestamp); // Add days to start date
            $month = date('F', $currentDate); // Get the current month
        }

        // Populate player state or set default values if missing
        $playerState = [
            'day' => $playerRow['day'] ?? 1,
            'mile' => $playerRow['mile'] ?? 0,
            'morale' => $playerRow['morale'] ?? 100,
            'inventory' => json_decode($playerRow['inventory'], true) ?? [],
            'log' => json_decode($playerRow['log'], true) ?? [],
            'current_trail' => $playerRow['current_trail'] ?? 'oregon', // New field
            'last_log_item' => json_decode($playerRow['last_log_item'], true) ?? [],  // Assuming empty array if NULL
            'terrain' => $terrain,  // Ensure terrain is always set
            'milestones' => $milestones,  // Ensure milestones is always set
            'delay_days' => $playerRow['delay_days'] ?? 0,  // Pull delay_days from the database (default to 0)
            'difficulty' => $playerRow['difficulty'] ?? 'medium', // Default difficulty to 'medium' if not set
            'oxen' => $playerRow['oxen'] ?? 2, // Default oxen to 2 if not set
            'miles_traveled' => $playerRow['miles_traveled'] ?? 0, // Pull miles_traveled from the database (default to 0)
            'weather' => $weather,  // Include weather data (either from DB or default)
            'start_date' => $startDate, // Adding start_date from the database
            'month' => $month // Add the calculated month
        ];

        return $playerState;  // Return the populated player state
    }

    return null;  // Return null if player not found
}











function moveAndCheckMilestones($playerState, $player_id, $conn) {
    // Check if delay_days is greater than 0
    if ($playerState['delay_days'] > 0) {
        // Decrease the delay_days and log the delay message
        $playerState['delay_days'] -= 1;

        $playerState['log'][] = [
            'day' => $playerState['day'],
            'miles_traveled' => 0,
            'total_miles' => $playerState['mile'],
            'notes' => "Paused at a milestone (delay in progress)."
        ];

        $playerState['day'] += 1; // Increment the day even when paused
        updatePlayerState($player_id, $playerState, $conn);  // Update player state in DB with the new delay_days value
        return $playerState;  // Skip further movement and milestone checks
    }

    // Player movement: increment miles and days
    $previousMile = $playerState['mile'];
    // Retrieve the current mile
    $currentMile = $playerState['mile'];
    $baseMiles = 15;  // Default miles traveled without adjustments
    
      // Retrieve the terrain for the current mile
    $terrainType = 'plains';  // Default terrain if none found

    // Loop through the terrain array to find the correct terrain type for the current mile
    foreach ($playerState['terrain'] as $section) {
        if ($currentMile >= $section['start_mile'] && $currentMile <= $section['end_mile']) {
            $terrainType = $section['terrain'];
            break;  // Exit the loop once the correct terrain is found
        }
    }
    
    // Adjust based on terrain type
    $terrainModifiers = [
        'plains' => 1.2,
        'rolling hills' => 1.0,
        'mountains' => 0.8,
        'valleys' => 1.0,
        'river valley' => 1.0,
        'desert' => 0.7
    ];
    // Introduce some randomness (e.g., between 0.95 and 1.05)
    $randomFactor = mt_rand(95, 105) / 100;  // Random value between 0.95 and 1.05

    // Get the modifier based on the terrain type
    $terrainMod = ($terrainModifiers[$terrainType] ?? 1.0) * $randomFactor;  // Default to 1 if terrain is unknown

// You can now use $terrainMod in your movement calculation
echo "Terrain Type: $terrainType, Modifier: $terrainMod";
    
    // Adjust based on difficulty setting
    $difficultyMod = [
        'easy' => 1.1,
        'medium' => 1.0,
        'hard' => 0.9
    ];
    $difficultyMultiplier = $difficultyMod[$playerState['difficulty']] ?? 1.0;  // Default to 1 if difficulty is unknown

    // Calculate initial miles traveled with adjustments
    $milesTraveled = round($baseMiles * $difficultyMultiplier * $terrainMod);
    
    // Debug: Output the miles traveled calculation
echo "<p>Base Miles: $baseMiles</p>";
echo "<p>Terrain Modifier: $terrainMod</p>";
echo "<p>Difficulty Modifier: $difficultyMultiplier</p>";
echo "<p>Miles Traveled (Before Adjustments): $milesTraveled</p>";

    // Adjust for player conditions (e.g., morale, oxen)
    if ($playerState['morale'] < 50) {
        $milesTraveled *= 0.8;  // Reduce miles if morale is low
    }
    if ($playerState['oxen'] < 2) {
        $milesTraveled *= 0.7;  // Reduce miles if not enough oxen
    }

    // Store miles traveled in playerState
    $playerState['miles_traveled'] = $milesTraveled;  // Save miles traveled in playerState

    // Calculate new mile
    $newMile = $previousMile + $milesTraveled;

    // Check milestones along the path
    $milestoneToday = null;
    foreach ($playerState['milestones'] as $milestone) {
        if ($milestone['mile'] > $previousMile && $milestone['mile'] <= $newMile) {
            $milestoneToday = $milestone;
            $newMile = $milestone['mile'];
            break;
        }
    }

    // Log milestone if reached
    if ($milestoneToday) {
        $playerState['log'][] = [
            'notes' => "You reached the milestone: " . $milestoneToday['title'] . ". " . $milestoneToday['extended_description']
        ];
    }

    // Update player state
    $playerState['mile'] = $newMile;
    $playerState['day'] += 1;
    $playerState['log'][] = [
        'day' => $playerState['day'],
        'miles_traveled' => $milesTraveled,  // Record the miles_traveled here
        'total_miles' => $newMile,
        'milestone' => $milestoneToday['title'] ?? null,
        'notes' => $milestoneToday ? "Today, you reached " . $milestoneToday['title'] . "." : null
    ];

    updatePlayerState($player_id, $playerState, $conn);  // Update player state in DB with new values
    return $playerState;
}













function updatePlayerState($player_id, $playerState, $conn) {
    // Prepare the updated player state for storage
    $inventoryJson = json_encode($playerState['inventory']);
    $logJson = json_encode($playerState['log']);
    $lastLogItem = !empty($playerState['log']) ? json_encode(end($playerState['log'])) : json_encode(['notes' => 'No log for this turn']); 

    $currentTrail = $playerState['current_trail'];
    $delayDays = $playerState['delay_days']; // Ensure the delay_days is passed
    $milesTraveled = $playerState['miles_traveled'] ?? 0;  // Get miles_traveled from playerState
    $weatherJson = json_encode($playerState['weather']);  // Convert weather to JSON string

    // Query to update the player state in the database
    $query = "UPDATE player_state SET 
              day = ?, mile = ?, morale = ?, inventory = ?, log = ?, current_trail = ?, last_log_item = ?, delay_days = ?, miles_traveled = ?, weather = ? 
              WHERE player_id = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
        return;
    }

    // Bind the parameters to the statement
    $stmt->bind_param(
        'iissssssssi', 
        $playerState['day'], 
        $playerState['mile'], 
        $playerState['morale'], 
        $inventoryJson,  
        $logJson,        
        $currentTrail,   
        $lastLogItem,    
        $delayDays,      // Pass the delay_days value
        $milesTraveled,  // Pass the miles_traveled value
        $weatherJson,    // Pass the weather as JSON string
        $player_id
    );

    // Execute the query to update the player state in the database
    if ($stmt->execute()) {
        echo "<p>Player state updated successfully!</p>";
    } else {
        echo "<p>Error executing query: " . $stmt->error . "</p>";
    }
}



?>
