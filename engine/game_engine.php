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
include_once(__DIR__ . '/modules/handleMilestones.php'); // Milestone detection and effects
include_once(__DIR__ . '/modules/manageInventory.php'); // Inventory helpers
include_once(__DIR__ . '/modules/manageStore.php'); // Store purchase processing
include_once(__DIR__ . '/modules/handlePendingAction.php'); // Pending action resolution

function moveAndCheckMilestones($playerState, $player_id, $conn) {
    // Check if game is already over from a previous turn
    if (!empty($playerState['game_over'])) {
        return $playerState;
    }
    // Player movement: increment miles and days
    $previousMile = $playerState['mile'];
    // Retrieve the current mile
    $currentMile = $playerState['mile'];






    applyRations($playerState);
    // Check if leader is deceased — game over
    $family = $playerState['family'];
    if (is_string($family)) {
        $family = json_decode($family, true) ?? [];
    }
    $leader = array_filter($family, fn($m) => $m['role'] === 'leader');
    $leader = reset($leader);
    if ($leader && ($leader['deceased'] ?? false)) {
        $playerState['log'][] = [
            'day'            => $playerState['day'],
            'miles_traveled' => 0,
            'total_miles'    => $playerState['mile'],
            'milestone'      => null,
            'notes'          => "Your party has perished on the trail. The journey ends here at mile " . $playerState['mile'] . "."
        ];
        $playerState['game_over'] = true;
        $playerState['day'] += 1;
        updatePlayerState($player_id, $playerState, $conn);
        return $playerState;
    }


// CONDITIONS SYSTEM
    applyConditions($playerState);
    $conditionTravelMod = $playerState['conditionTravelMod'];


// WEATHER SYSTEM
applyWeather($playerState);
$wind_modifier = $playerState['windModifier'];
$wind_type = $playerState['weatherThisTurn']['wind_type'];
$precipitationPenalty = $playerState['precipitationPenalty'];




   

 // If there's a pending action, save state and return — 
    // the delivery layer handles presenting the choice to the player
    if (!empty($playerState['pending_action'])) {
        updatePlayerState($player_id, $playerState, $conn);
        return $playerState;
    }


        
        
// check if First day
    if ($playerState['mile'] == 0 && $playerState['day'] == 1) {
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

 handleMilestones($playerState, $previousMile);

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
