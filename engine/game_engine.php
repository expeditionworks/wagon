<?php
// game_engine.php

// Include the necessary files for database connection and game logic
include_once(__DIR__ . '/db_connection.php'); // Database connection
include_once(__DIR__ . '/game_functions.php'); // Shared helper functions
include_once(__DIR__ . '/modules/getPlayerState.php'); // Load player state from DB
include_once(__DIR__ . '/modules/updatePlayerState.php'); // DB write — end of turn only
include_once(__DIR__ . '/modules/applyWeather.php'); // Weather calculation
include_once(__DIR__ . '/modules/applyRations.php'); // Food consumption
include_once(__DIR__ . '/modules/applyConditions.php'); // Family conditions
include_once(__DIR__ . '/modules/movePlayer.php'); // Movement calculation




function moveAndCheckMilestones($playerState, $player_id, $conn) {
    // Player movement: increment miles and days
    $previousMile = $playerState['mile'];
    // Retrieve the current mile
    $currentMile = $playerState['mile'];






    applyRations($playerState);


// CONDITIONS SYSTEM
    applyConditions($playerState);
    $conditionTravelMod = $playerState['conditionTravelMod'];


// WEATHER SYSTEM
applyWeather($playerState);
$wind_modifier = $playerState['windModifier'];
$wind_type = $playerState['weatherThisTurn']['wind_type'];
$precipitationPenalty = $playerState['precipitationPenalty'];




   

    // check if there's any actions need to be taken 
    if (!empty($playerState['pending_action'])) {

        function processPendingAction(&$playerState, $player_id, $conn) {
              $action = $playerState['pending_action'];
              // Have they sent a reply?
              if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice'])) {
                $choice = (int)$_POST['choice'];
                $options = $action['options'];
                if ($choice < 1 || $choice > count($options)) {
                  echo "Invalid choice. Please pick a number between 1 and " . count($options);
                  renderPrompt($action); return;
                }
                $selected = $options[$choice - 1];
                // Dispatch based on action type
                switch ($action['type']) {
                  case 'river_crossing':
                    // Apply crossing logic with $selected
                    break;
                  case 'store_purchase':
                    // Apply store logic with $selected
                    break;
                  // … other cases …
                }
                // After handling:
                $playerState['pending_action'] = null;
                updatePlayerState($player_id, $playerState, $conn);
                // Then continue with the rest of the turn
                moveAndCheckMilestones($playerState, $player_id, $conn);
                return;
              }
              // No reply yet → render the prompt
              renderPrompt($action);
            }

        function renderPrompt($action) {
          echo "<p>{$action['prompt']}</p><ol>";
          foreach ($action['options'] as $i => $opt) {
            echo "<li>" . ($i + 1) . ") $opt</li>";
          }
          echo "</ol>
                <form method='POST'>
                  <label>Enter choice (1–" . count($action['options']) . "):</label>
                  <input name='choice' type='number' min='1' max='" . count($action['options']) . "' required>
                  <button type='submit'>Submit</button>
                </form>";
        }


        
        
    // check if First day
    } elseif ($playerState['mile'] == 0 && $playerState['day'] == 1) {
        // Handle first day store logic

        // TODO: delivery layer — move all store/UI HTML below this line to templates
        echo "Welcome to Independence, Missouri! Here's your first chance to stock up on supplies.\n";
        $milestoneToday = null;
        $milestoneTodayID = null;
        $milestoneTodayTitle = null;
        $milestoneToday = $playerState['milestones'][0];  // Get the first milestone (Independence)

        // Set the first milestone to Independence, MO (first entry in the milestones array)
        if ($playerState['mile'] == 0 && $playerState['day'] == 1) {
            $milestoneToday = $playerState['milestones'][0];  // Independence, MO is the first milestone
            $milestoneTodayTitle = $milestoneToday['title'];  // Correctly access the 'title' field of the milestone

// we should see if we can modularize this code since it shows up in a few places
            
            // Check if this milestone has a store
            if (isset($milestoneToday['store']) && $milestoneToday['store'] === true) {
                // Display the store items from Independence, MO
                $milestoneStore = $milestoneToday['items_for_sale'];
        
                // Loop through the items for sale and display them
                echo "<h4>" . $milestoneTodayTitle . " Store</h4>\r<ul>";      
                foreach ($milestoneStore as $itemName => $itemDetails) {
                    echo "<li>" . $itemName . "</li>";
                    echo "<ul>";
                    echo "<li>Description: " . $itemDetails['description'] . "</li>";
                    echo "<li>Price: $" . $itemDetails['base_price'] . "</li>";
                    echo "<li>Stock limit: " . $itemDetails['stock_limit'] . "</li>";
                    echo "</ul>";
                }
                echo "</ul>";


                

 // BUY LOGIC                
function processPurchase($itemName, $quantity, &$playerState, &$milestoneStore) {
    // Check if the item exists in the store
    if (isset($milestoneStore[$itemName])) {
        $itemDetails = $milestoneStore[$itemName];
        $totalCost = $itemDetails['base_price'] * $quantity;

        // Check if the player can afford the purchase
        if ($playerState['dollars'] >= $totalCost) {
            // Check if enough stock is available
            if ($itemDetails['stock_limit'] >= $quantity) {
                // Complete the transaction: deduct money and add items to inventory
                $playerState['dollars'] -= $totalCost;  // Deduct money
                $playerState['inventory'][$itemName] += $quantity;  // Add to inventory

                // Update the stock in the store
                $milestoneStore[$itemName]['stock_limit'] -= $quantity;

                return "Purchase successful! You bought $quantity $itemName(s) for $$totalCost. You have $" . $playerState['dollars'] . " left.";
            } else {
                return "Sorry, not enough stock for $itemName.";
            }
        } else {
            return "You don't have enough money to buy $quantity $itemName(s).";
        }
    } else {
        return "Item not found.";
    }
}

// Display the store items and handle the purchase form
function displayStoreAndProcessPurchase(&$playerState, &$milestoneStore) {
    // Assuming $milestoneStore is already set with items for sale (and $playerState contains the player's data)
    
    // Check if the milestone has a store
    if (isset($milestoneStore) && !empty($milestoneStore)) {
        echo "<h3>Store Items</h3><ul>";

        // Display the store items
        foreach ($milestoneStore as $itemName => $itemDetails) {
            echo "<li><strong>{$itemName}</strong><br>{$itemDetails['description']}<br>Price: \${$itemDetails['base_price']}<br>Stock: {$itemDetails['stock_limit']}</li>";
        }
        echo "</ul>";

        // Process purchase if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $itemName = $_POST['itemName'];  // Item selected by the player
            $quantity = $_POST['quantity'];  // Quantity the player wants to buy

            // Call the purchase function to process the transaction
            $purchaseMessage = processPurchase($itemName, $quantity, $playerState, $milestoneStore);

            // Display the result message
            echo "<p>$purchaseMessage</p>";
        }

        // Display the purchase form
        echo '<h4>Make a Purchase</h4>';
        echo '<form method="POST" action="test.php">
            <label for="itemName">Select Item:</label>
            <select name="itemName" id="itemName">';
        foreach ($milestoneStore as $itemName => $itemDetails) {
            echo "<option value=\"$itemName\">$itemName</option>";
        }
        echo '</select><br>';

        echo '<label for="quantity">Quantity:</label>
            <input type="number" name="quantity" id="quantity" min="1" value="1" required><br>
            <input type="submit" value="Buy Item">
        </form>';
    } else {
        echo "<p>No store available at this milestone.</p>";
    }
}            


                
            } else {
                echo "No store available at this milestone.\n";
            }
        }

            $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => 0,
                'total_miles' => $playerState['mile'],
                'notes' => "You started your day at " . $milestoneTodayTitle . " with nothing, and now you are ready to go on the trail. Today was a good day purchasing"
            ];
        
            $playerState['day'] += 1; // Increment the day even when paused
            updatePlayerState($player_id, $playerState, $conn);  // Update player state in DB with the new delay_days value
            return $playerState;  // Skip further movement and milestone checks       
    
    } elseif ($playerState['delay_days'] > 0) {
    // Check if delay_days is greater than 0

        // Decrease the delay_days and log the delay message
        $playerState['delay_days'] -= 1;
        $playerState['delay_status'] = 'active';
        $milestones = $playerState['milestones'];
        if (is_array($milestones) && !empty($milestones)) {
        foreach ($milestones as $milestone) {
        if ($milestone['mile'] === $playerState['mile']) {
            debugLog($playerState, "Delay stop at: " . $milestone['title']);
            debugLog($playerState, "Description: " . $milestone['description']);
            debugLog($playerState, "Extended description: " . $milestone['extended_description']);



         // possible delay day store action 



            

            $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => 0,
                'total_miles' => $playerState['mile'],
                'notes' => "Paused at the milestone: " . $milestone['title'] . ". \nThere is a delay in progress. You have " . $playerState['delay_days'] . " more days left to wait."
            ];
            break;  // Exit loop once we find the milestone
                }
            }
        } else {
            debugLog($playerState, "Error: Milestones data is missing or invalid.");

             $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => 0,
                'total_miles' => $playerState['mile'],
                'notes' => "Paused at a milestone (delay in progress)."
            ];

        }

        $playerState['day'] += 1; // Increment the day even when paused
        updatePlayerState($player_id, $playerState, $conn);  // Update player state in DB with the new delay_days value
        return $playerState;  // Skip further movement and milestone checks
        
    } else {
        
// this bit if if we move
    movePlayer($playerState);
    $milesTraveled = $playerState['miles_traveled'];
    $newMile = $playerState['mile'];

    



    // Store miles traveled in playerState
    $playerState['miles_traveled'] = $milesTraveled;  // Save miles traveled in playerState

    // Calculate new mile
    $newMile = $previousMile + $milesTraveled;

    // Check milestones along the path
    $milestoneToday = null;
    $milestoneTodayID = null;
    $milestoneTodayTitle = null;
    $milestoneTodayType = null;
    $milestoneTodayForceStop = null;
    $milestoneTodayDelayDay = 0;    
    foreach ($playerState['milestones'] as $milestone) {
        if ($milestone['mile'] > $previousMile && $milestone['mile'] <= $newMile) {
            $milestoneToday = $milestone;
            $milestoneTodayID = $milestone['id'];
            $milestoneTodayTitle = $milestone['title'];
            $milestoneTodayForceStop = $milestone['force_stop'];
            $milestoneTodayType = $milestone['type'];
            $milestoneMoraleMod = $milestoneToday['morale_mod'];
            $milestoneTodayDelayDay = $milestone['delay_day'] ?? 0;
            $newMile = $milestone['mile'];
            break;
        }
    }
        // check if there is a force stop
        if ($milestoneTodayForceStop === true) {
        // Handle the case where the milestone requires a force stop (e.g., no movement allowed)
            if (isset($playerState['delay_days']) && is_int($playerState['delay_days'])) {
                // Add the milestone's delay days to the player's delay days
                $playerState['delay_days'] += $milestoneTodayDelayDay;
               //  echo "Player delay days updated: " . $playerState['delay_days'];
            } else {
                //in case we want to 
            }          
        } else {
        // Handle the case where no force stop is applied, and movement is allowed            
        }



    // Log milestone if reached
    if ($milestoneToday) {
        
       // Apply morale change if morale_mod exists in milestone
        if (isset($milestoneToday['morale_mod'])) {
            $milestoneMoraleMod = $milestoneToday['morale_mod'];
    
            // Loop through each family member and modify morale
            // Check if family data exists and if it's a JSON-encoded string
            if (isset($playerState['family']) && is_string($playerState['family'])) {
                // Decode the family data from JSON to array
                $playerState['family'] = json_decode($playerState['family'], true);
                
                // Check if decoding was successful
                if ($playerState['family'] === null) {
                    debugLog($playerState, "Error: Failed to decode family data from JSON.");
                    return;
                }
            }
            
            // Ensure family data is an array before looping through it
            if (isset($playerState['family']) && is_array($playerState['family'])) {
                // Loop through each family member and modify morale
                foreach ($playerState['family'] as &$familyMember) {
                    // Apply morale modification
                    $familyMember['morale'] += $milestoneMoraleMod;
            
                    // Optional: Ensure morale is within the 0-100 range
                    $familyMember['morale'] = max(0, min(100, $familyMember['morale']));
                }
            
                // After modifications, re-encode family data back to JSON before saving it
                $playerState['family'] = json_encode($playerState['family']);
            } else {
                debugLog($playerState, "Error: Family data is missing or corrupted.");
            }
    
        } else {
            // Log the milestone without morale modification

        }





        // Handle milestone logic based on its type
switch ($milestoneTodayType) {
    case 'fort':
        // Do something at a fort
            // delay days
            if (isset($playerState['delay_days']) && is_int($playerState['delay_days'])) {
                // Add the milestone's delay days to the player's delay days
                $playerState['delay_days'] += $milestoneTodayDelayDay;
               //  echo "Player delay days updated: " . $playerState['delay_days'];
            } else {
                //in case we want to 
            }       

    
        debugLog($playerState, "Milestone type: fort - " . $milestoneTodayTitle);
        // Your logic for forts goes here

            $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => $milesTraveled,
                'total_miles' => $newMile,
                'milestone' => $milestoneToday['title'] ?? null,
                'notes' => "You reached " . $milestoneToday['title'] . ". " . $milestoneToday['extended_description'] . " Your are overjoyed and your morale went up by " . $milestoneMoraleMod . " points. You decided to rest " . $milestoneTodayDelayDay . " days."
            ];
        break;

    case 'natural':
        // Do something at a natural landmark
        debugLog($playerState, "Milestone type: natural - " . $milestoneTodayTitle);
        // Your logic for natural landmarks goes here
            $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => $milesTraveled,
                'total_miles' => $newMile,
                'milestone' => $milestoneToday['title'] ?? null,
                'notes' => "You reached the " . $milestoneToday['title'] . ". " . $milestoneToday['extended_description'] . " You were moved by it's natural beauty and your morale improved by " . $milestoneMoraleMod . " points."
            ];
        break;

    case 'river':

            // Do something at a river
        debugLog($playerState, "Milestone type: river - " . $milestoneTodayTitle);

        // Check if the 'crossing' key exists in the current milestone
        if (isset($milestoneToday['crossing'])) {
            $crossing = $milestoneToday['crossing'];  // Retrieve crossing data

            // Store crossing options and related data in variables
            $crossingOptions = $crossing['options'] ?? [];  // Array of crossing options: ["ford", "float", "ferry"]

            // Set default crossing options if $crossingOptions is empty
            if (empty($crossingOptions)) {
                // Failsafe if no crossing options are available in the JSON
                $crossingOptions = ['ford', 'float', 'ferry'];  // Default available crossing options
            }

            // Default values for crossing data if not present in the JSON
            $ferryBaseCost = $crossing['ferry_base_cost'] ?? 5;  // Cost for ferry
            $fordRisk = $crossing['ford_risk'] ?? 0.5;  // Risk associated with fording
            $fordDelay = $crossing['ford_delay'] ?? 1;  // Delay for fording
            $floatDelay = $crossing['float_delay'] ?? 2;  // Delay for floating
            $ferryDelay = $crossing['ferry_delay'] ?? 1;  // Delay for ferrying

            // Display the crossing options (just for debugging)
            debugLog($playerState, "River crossing options: " . implode(', ', $crossingOptions));
            debugLog($playerState, "Ferry base cost: $ferryBaseCost");
            debugLog($playerState, "Ford success chance: " . ($fordRisk * 100) . "%");
            debugLog($playerState, "Ford delay: $fordDelay days");
            debugLog($playerState, "Float delay: $floatDelay days");
            debugLog($playerState, "Ferry delay: $ferryDelay days");


            // now do a switch for how to cross river


            // Now you can process these variables further, for example, prompting the player for a crossing choice.
            // You can check the player's choice here and apply the relevant delay, risk, cost, etc.


                $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => $milesTraveled,
                'total_miles' => $newMile,
                'milestone' => $milestoneToday['title'] ?? null,
                'notes' => "You reached the " . $milestoneToday['title'] . ". " . $milestoneToday['extended_description'] . " You were moved by it's natural beauty and your morale improved by " . $milestoneMoraleMod . " points."
            ];
        } else {
            debugLog($playerState, "Error: No crossing data available for this river.");
        }   
        break;

    case 'fork':
        // Do something at a fork
        debugLog($playerState, "Milestone type: fork - " . $milestoneTodayTitle);

            // Log the morale modification
            $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => $milesTraveled,
                'total_miles' => $newMile,
                'milestone' => $milestoneToday['title'] ?? null,
                'notes' => "You reached the milestone: " . $milestoneToday['title'] . ". " . $milestoneToday['extended_description'] . " Morale adjusted by " . $milestoneMoraleMod . " points."
            ];

    
        // Your logic for forks goes here
        break;

    case 'final':
        // End of the game
        debugLog($playerState, "Milestone type: final - " . $milestoneTodayTitle);
        // Your logic for the final milestone goes here

            // Log the morale modification
            $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => $milesTraveled,
                'total_miles' => $newMile,
                'milestone' => $milestoneToday['title'] ?? null,
                'notes' => "You reached the milestone: " . $milestoneToday['title'] . ". " . $milestoneToday['extended_description'] . " Morale adjusted by " . $milestoneMoraleMod . " points."
            ];

    
        break;

    default:
        // Handle unknown milestone types (if needed)
        // echo "Unknown milestone type: $milestoneTodayType";

            // Log the morale modification
            $playerState['log'][] = [
                'day' => $playerState['day'],
                'miles_traveled' => $milesTraveled,
                'total_miles' => $newMile,
                'milestone' => $milestoneToday['title'] ?? null,
                'notes' => "You reached the milestone: " . $milestoneToday['title'] . ". " . $milestoneToday['extended_description'] . " Morale adjusted by " . $milestoneMoraleMod . " points."
            ];


    
        break;
}




        
        // MILESTONE STORE
        // Check if the current milestone has a store
        $milestoneStore = null;  // Initialize the store variable
        if (isset($milestoneToday)) {
            // If a milestone is found today, get the store data
            if (isset($milestoneToday['store']) && $milestoneToday['store'] === true) {
                $milestoneStore = $milestoneToday['items_for_sale'];
                
                echo "<h4>" . $milestoneTodayTitle . " Store</h4>\r<ul>";      
                // Loop through the items for sale
                foreach ($milestoneStore as $itemName => $itemDetails) {
                    // Print out the item details: name, description, price, etc.

                    echo "<li>" . $itemName . "</li>";
                    echo "<ul>";
                        echo "<li>Description: " . $itemDetails['description'] . "</li>";
                        echo "<li>Price: $" . $itemDetails['base_price'] . "</li>";
                        echo "<li>Stock limit: " . $itemDetails['stock_limit'] . "</li>";
                    echo "</ul>";

                }
                echo "</ul>";
            } else {
            // don't know if I want to do anything here
            }
        }

        



        



                function checkMilestoneStore($milestone) {
                    // Check if the milestone has a store
                    if (isset($milestone['store']) && $milestone['store'] === true) {
                        echo "Store available at " . $milestone['title'] . "!\n";
                        
                        // Iterate through items for sale
                        foreach ($milestone['items_for_sale'] as $itemName => $itemDetails) {
                            // Print out the item details: name, description, price, etc.
                            echo "Item: " . $itemName . "\n";
                            echo "Description: " . $itemDetails['description'] . "\n";
                            echo "Price: $" . $itemDetails['base_price'] . "\n";
                            echo "Stock limit: " . $itemDetails['stock_limit'] . "\n";
                            echo "----------\n";
                        }
                    } else {
                        echo "No store available at this milestone.\n";
                    }
                }

    } else {
//this is if there isn't a milestone
        
        $playerState['log'][] = [
            'day' => $playerState['day'],
            'miles_traveled' => $milesTraveled,  // Record the miles_traveled here
            'total_miles' => $newMile,
            'milestone' => $milestoneToday['title'] ?? null,
            'notes' => "Today you kept on rolling without a milestone. You travelled " . $milesTraveled . " miles, and ate " . $playerState['foodConsumedToday'] . " lbs of food."        ];
    }

    // Update player state
    $playerState['mile'] = $newMile;
    $playerState['day'] += 1;
//    $playerState['log'][] = [
  //      'day' => $playerState['day'],
    //    'miles_traveled' => $milesTraveled,  // Record the miles_traveled here
      //  'total_miles' => $newMile,
        // 'milestone' => $milestoneToday['title'] ?? null,
        // 'notes' => $milestoneToday ? "Today, you reached " . $milestoneToday['title'] . "." : null
    // ];

    updatePlayerState($player_id, $playerState, $conn);  // Update player state in DB with new values
    return $playerState;
    }
}


















?>
