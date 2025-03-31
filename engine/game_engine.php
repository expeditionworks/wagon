<?php
// game_engine.php

// Include the necessary files for database connection and game logic
include_once(__DIR__ . '/db_connection.php'); // Database connection

function getPlayerState($player_id, $conn) {
    // Modify the query to fetch start_date from the players table
    $query = "SELECT ps.*, p.id AS player_id FROM player_state ps
              LEFT JOIN players p ON p.id = ps.player_id
              WHERE ps.player_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerRow = $result->fetch_assoc();

    // If player data exists, populate the player state
    if ($playerRow) {

        // Assuming $playerRow['start_date'] is the value fetched from the database
        $startDate = $playerRow['start_date'] ?? '1849-05-01'; // Default to '1849-05-01' if start_date is NULL
        // Create DateTime object from start date
        $startDateObj = new DateTime($startDate);
        // Add the days passed (from $playerState['day'])
        $startDateObj->modify('+' . ($playerRow['day'] - 1) . ' days'); // Subtract 1 to avoid adding an extra day at the beginning
        // Get the month after adding days
        $month = $startDateObj->format('F');  // This will give the current month based on the updated date
        $currentMile = $playerRow['mile'];
        $inventory = [];
        
        // Initialize inventory if it’s NULL or invalid
        $inventory = !empty($playerRow['inventory']) ? json_decode($playerRow['inventory'], true) : [];
        
        // Initialize the inventory with default values if it’s empty or invalid
        if (empty($playerInventory) || !is_array($playerInventory)) {
            $playerInventory = [
                "Oxen" => ["quantity" => 0, "durability" => 100],
                "Food" => ["quantity" => 0, "durability" => null],
                "Ammunition" => ["quantity" => 0, "durability" => null],
                "Clothes" => ["quantity" => 0, "durability" => 100],
                "Books" => ["quantity" => 0, "durability" => 20],
                "Gold" => ["quantity" => 0, "durability" => null],
                "Traps" => ["quantity" => 0, "durability" => 20],
                "Tools" => ["quantity" => 0, "durability" => 20],
                "Wood" => ["quantity" => 0, "durability" => null],
                "WagonRepairKit" => ["quantity" => 0, "durability" => 20]
            ];
        }



// set terrain from terrain.json        
$terrainPath = __DIR__ . '/../config/terrain.json';
if (file_exists($terrainPath)) {
    $terrainContent = file_get_contents($terrainPath);
    $terrain = $terrainContent !== false ? json_decode($terrainContent, true) : [];

    // Initialize default values
    $terrainType = 'plains';  // Default terrain if none found
    $altitude = 'low';        // Default altitude if none found

    // Check if terrain data is valid
    if (is_array($terrain) && !empty($terrain)) {
        // Loop through the terrain array to find the correct terrain type for the current mile
        foreach ($terrain as $section) {
            if ($currentMile >= $section['start_mile'] && $currentMile <= $section['end_mile']) {
                // Ensure $section['terrain'] is a string
                if (is_string($section['terrain'])) {
                    $terrainType = $section['terrain'];
                } else {
                    echo "Error: Terrain type for mile $currentMile is not a valid string.\n";
                }
                $altitude = $section['altitude'] ?? 'low'; 
                break;  // Exit the loop once the correct terrain is found
            }
        }
    } else {
        echo "Invalid terrain data or empty terrain array.\n";
    }
} else {
    echo "Terrain file not found or not accessible.\n";
    $terrain = []; // Default empty array
}

// Debug: Check the terrain type and altitude
echo "<pre>";
echo "Terrain Type: $terrainType\n";  // Debug the value of terrainType
echo "Altitude: $altitude\n";        // Debug the value of altitude
echo "</pre>";


        

        

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
            "wind_speed" => ["min" => 5, "max" => 15],
            "type" => "default"
        ];



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
        $weatherLastTurn = null;
        if (!empty($playerRow['weather'])) {
            // Attempt to decode the weather data, but check if it’s valid JSON
            $decodedWeather = json_decode($playerRow['weather'], true);
        
            // If json_decode returns null, that means the data isn't valid JSON
            if ($decodedWeather !== null) {
                $weatherLastTurn = $decodedWeather;
            }
        }
        
        // If the weather is still null (invalid or missing), use the default weather
        if ($weatherLastTurn === null) {
            $weatherLastTurn = $defaultWeather;
        }



    
        $playerState = [
            'dollars' => $playerRow['dollars'] ?? 10,
            'day' => $playerRow['day'] ?? 1,
            'mile' => $playerRow['mile'] ?? 0,
            'morale' => $playerRow['morale'] ?? 100,
            'ration'=> $playerRow['ration_size'] ?? full,
            'inventory' => json_decode($playerRow['inventory'], true) ?? [],
            'log' => json_decode($playerRow['log'], true) ?? [],
            'current_trail' => $playerRow['current_trail'] ?? 'oregon', // New field
            'last_log_item' => json_decode($playerRow['last_log_item'], true) ?? [],  // Assuming empty array if NULL
            'terrain' => $terrain,  // Ensure terrain is always set
            'terrainCurrent' => $terrainType, // current terrain is always set
            'altitude' => $altitude, // set altitude
            'milestones' => $milestones,  // Ensure milestones is always set
            'delay_days' => $playerRow['delay_days'] ?? 0,  // Pull delay_days from the database (default to 0)
            'delay_status' => $playerRow['delay_status'] ?? 'completed',  // Pull delay_status from the database (default to completed)
            'difficulty' => $playerRow['difficulty'] ?? 'medium', // Default difficulty to 'medium' if not set
            'oxen' => $playerRow['oxen'] ?? 2, // Default oxen to 2 if not set
            'miles_traveled' => $playerRow['miles_traveled'] ?? 0, // Pull miles_traveled from the database (default to 0)
            'weatherLastTurn' => $weatherLastTurn,  // Initialize weatherLastTurn
            'weatherThisTurn' => $weatherLastTurn,  // Initialize weatherThisTurn
            'start_date' => $playerRow['start_date'] ?? null,  // Adding start_date from the database
            'month' => $month // Adding month to player state
        ];

        return $playerState;  // Return the populated player state



 
    $newDelayState = $playerRow['delay_status'];
echo "{$playerState['terrainCurrent']}";


        
    }

    
    return null;  // Return null if player not found
}




















function moveAndCheckMilestones($playerState, $player_id, $conn) {
    // Player movement: increment miles and days
    $previousMile = $playerState['mile'];
    // Retrieve the current mile
    $currentMile = $playerState['mile'];

    // Example: Decrementing food based on party size and rations
    $itemName = "Food";  // We're working with the 'Food' item
    $foodPerPerson = 2;  // Example: 2 lbs of food per person per day
    
    // Get the party size (e.g., number of family members in $playerState)
    // $partySize = count($playerState['family']);  // Assuming you have a family array in playerState
    $partySize = 3;
    // Calculate total food consumption for the day
    $totalFoodConsumed = $foodPerPerson * $partySize;
    
    // Check if the player has enough food
    if (isset($playerState['inventory'][$itemName])) {
        $foodItem = $playerState['inventory'][$itemName];
        
        // If the player has enough food, decrease the quantity
        if ($foodItem['quantity'] >= $totalFoodConsumed) {
            $foodItem['quantity'] -= $totalFoodConsumed;
        } else {
            // Not enough food, handle accordingly (e.g., set quantity to 0)
            $foodItem['quantity'] = 0;
            // You could also handle consequences of food shortage (e.g., morale impact)
        }
    
        // Update the inventory in playerState
        $playerState['inventory'][$itemName] = $foodItem;
        
        // Optionally, print out the remaining food for the player
        echo "Food remaining: " . $foodItem['quantity'] . " lbs\n";
    } else {
        echo "No food in inventory.\n";
    }





    

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

    // Get weather data for the current month based on the player's month
    $monthData = $weatherMonths[$playerState['month']] ?? $weatherMonths['May'];  // Use $playerState['month'] directly
    // Define the default value for wind speed if something goes wrong
    $defaultWindSpeed = 20;  // You can set your default value here
    
    // Initialize wind speed range and randomly selected wind speed
    $windSpeedRange = null;
    $randomWindSpeed = $defaultWindSpeed;  // Default value in case we can't find a valid range
    
    // Check if the current month exists in the JSON data
    if (isset($monthData['wind_speed_range'])) {
        $windSpeedRange = $monthData['wind_speed_range'];
    
        // Check if min and max values exist and are valid numbers
        if (isset($windSpeedRange['min'], $windSpeedRange['max']) &&
            is_numeric($windSpeedRange['min']) && is_numeric($windSpeedRange['max'])) {
            // Pick a random wind speed within the specified range
            $randomWindSpeed = rand($windSpeedRange['min'], $windSpeedRange['max']); // the random wind speed
        }
    }
    $wind_modifier = 1.0;  // Default to no effect (tailwind)
    $wind_speed = 5; // set default for wind
    $wind_types = ["headwind", "tailwind", "crosswind"];  // Define an array of possible wind types
    $random_index = array_rand($wind_types); // Randomly select a wind type get the index
    $wind_type = $wind_types[$random_index]; // Select the wind type based on the random index
    
    // Determine wind effect based on type
    if ($wind_type == "headwind") {
        $wind_modifier = 1 - ($wind_speed / 50);  // Example: max 40% reduction for very strong headwinds
    } elseif ($wind_type == "tailwind") {
        $wind_modifier = 1 + ($wind_speed / 100);  // Example: max 30% increase for very strong tailwinds
    } elseif ($wind_type == "crosswind") {
        $wind_modifier = 1 - ($wind_speed / 200);  // Minimal effect for crosswinds
    }

    
   // Determine the weather type for the day
    $weatherTypes = $monthData['weather_types'];
    $weatherType = $weatherTypes[array_rand($weatherTypes)];  // Randomly select a weather type from the available types

    // Get the temperature range for the chosen weather type
    $temperatureRange = $monthData['temperature_range'][$weatherType];
    $temperature = rand($temperatureRange[0], $temperatureRange[1]);

    // Chance of snow and rain (based on weather month data)
    $chanceOfSnow = $monthData['chance_of_snow'];
    $chanceOfRain = $monthData['chance_of_rain'];

   // Determine precipitation (snow or rain) based on weather type and probabilities
    $precipitationPenalty = 1.0; // make base percipitation penalty 1
    $precipitation = 'none';
    if ($weatherType == 'snowy' && rand(0, 100) <= $chanceOfSnow) {
        $precipitation = 'snow';
        $precipitationPenalty = 0.8;
    } elseif ($weatherType == 'cloudy' && rand(0, 100) <= $chanceOfRain) {
        $precipitation = 'rain';
        $precipitationPenalty = 1.0;
    }

    
    // Construct the weather data to return
    $weatherData = [
        'weather_type' => $weatherType,
        'temperature' => $temperature,
        'precipitation' => $precipitation,
        'wind_speed' => $randomWindSpeed,
        'date' => date('Y-m-d'), // Store the current date of the weather
    ];    

    // Optionally, add the weather to playerState directly
    $playerState['weatherThisTurn'] = $weatherData;  // Store the weather data in playerState



    
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


    $baseMiles = 15;  // Default miles traveled without adjustments
    
    // Define altitude wind modifiers (can be adjusted as needed)
    $altitudeModifiers = [
        'low' => 0.9,
        'medium' => 1.0,
        'high' => 1.2
    ];
   
    // Adjust based on terrain type
    $terrainModifiers = [
        '' => 1.0,
        'plains' => 1.2,
        'rolling hills' => 1.0,
        'mountains' => 0.8,
        'valleys' => 1.0,
        'river valley' => 1.0,
        'desert' => 0.7
    ];


    // Get the wind modifier for the terrain type (default to 1.0 if terrain type not found)
    $terrainWindModifier = $terrainModifiers[$playerState['terrainCurrent']] ?? 1; // Default to 1.0 if not found

    // Get the wind modifier for altitude (default to 1.0 if altitude type not found)
    $altitudeWindModifier = $altitudeModifiers[$playerState['altitude']] ?? 1.0;
    
    // Introduce some randomness (e.g., between 0.95 and 1.05)
    $randomFactor = mt_rand(95, 105) / 100;  // Random value between 0.95 and 1.05

    // Get the modifier based on the terrain type
    $terrainMod = ($terrainModifiers[$playerState['terrainCurrent']] ?? 1.0) * $randomFactor;  // Default to 1 if terrain is unknown

// You can now use $terrainMod in your movement calculation
// echo "Terrain Type: $terrainType";
    //Modifier: $terrainMod";

    
    // Adjust based on difficulty setting
    $difficultyMod = [
        'easy' => 1.1,
        'medium' => 1.0,
        'hard' => 0.9
    ];
    $difficultyMultiplier = $difficultyMod[$playerState['difficulty']] ?? 1.0;  // Default to 1 if difficulty is unknown
    // Adjust the morale modifier based on morale value
    $morale = $playerRow['morale'] ?? 100;
    $moraleMod = 1.0;
    if ($morale >= 90) {
        $moraleMod *= 1.1;  // Increase speed by 10% if morale is between 90-100
    } elseif ($morale >= 50) {
        $moraleMod *= 1.0;  // No effect on speed if morale is between 50-89
    } else {
        $moraleMod *= 0.8;  // Reduce speed by 20% if morale is below 50
    }

    // oxen
    $oxenNumber = $playerState['oxen'] ?? 6;  // Default to 6 if 'oxen' is not set
    
    // Initialize oxen modifier
    $oxenMod = 1.0;  // Default: no change in distance
    
    // Adjust oxen modifier based on the number of oxen
    if ($oxenNumber >= 8) {
        $oxenMod *= 1.2;  // More than enough oxen, no reduction, possibly increase speed
    } elseif ($oxenNumber >= 6) {
        $oxenMod *= 1.0;  // Adequate number of oxen, no change
    } elseif ($oxenNumber >= 3) {
        $oxenMod *= 0.8;  // Less than enough, some reduction in speed
    } elseif ($oxenNumber >= 1) {
        $oxenMod *= 0.5;  // Very few oxen, significant reduction in speed
    } else {
        $oxenMod *= 0.0;  // No oxen, no movement possible
    }

    // Calculate initial miles traveled with adjustments
    $adjusted_distance = round($baseMiles * $difficultyMultiplier * $terrainMod * $precipitationPenalty * $moraleMod * $oxenMod);
    $milesTraveled = round(max( $adjusted_distance * $wind_modifier, 0)); //add wind modifier

    
    // Debug: Output the miles traveled calculation
echo "<p>Base Miles: $baseMiles</p>";
echo "<p>Terrain Modifier: $terrainMod</p>";
echo "<p>Difficulty Modifier: $difficultyMultiplier</p>";
    echo "<p>Miles Traveled (base + difficult + terrain): $adjusted_distance</p>";
echo "<p>Miles Traveled (Before Adjustments): $milesTraveled</p>";
    echo "<p>Wind type: $wind_type</p>";


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
    $weatherJson = json_encode($playerState['weatherThisTurn']);  // Convert weather to JSON string
    $newDelayState = $playerState['delay_status'];  // Pull delay_status from the database (default to completed)


    
    // Query to update the player state in the database
    $query = "UPDATE player_state SET 
              day = ?, mile = ?, morale = ?, inventory = ?, log = ?, current_trail = ?, last_log_item = ?, delay_days = ?, miles_traveled = ?, weather = ?, delay_status = ? 
              WHERE player_id = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
        return;
    }
    
    // Bind the parameters to the statement
    $stmt->bind_param(
        'iisssssssssi', // Added 's' for delay_status (VARCHAR)
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
        $newDelayState,  // Pass the delay_status value
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
