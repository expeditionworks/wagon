<?php
// Include the necessary files for database connection and game logic
include_once(__DIR__ . '/../engine/game_engine.php'); // Include game engine
include_once(__DIR__ . '/db_connection.php'); // Include database connection

// Set up player ID
$player_id = 1;  // Example player ID

// Call getPlayerState with both the player ID and the database connection
$playerState = getPlayerState($player_id, $conn);  // Pass both $player_id and $conn

// Print the loaded milestones for debugging
$milestones = $playerState['milestones'] ?? null;  // Access milestones from playerState

// Debug: Print $milestones
echo "<pre>";
print_r($milestones);  // Print the loaded milestones
echo "</pre>";



// Set player ID and milestone ID for testing
$playerState = [
    'id' => 1,  // Example player ID
    'dollars' => 200,  // Example: Player has 200 dollars
    'inventory' => [
        'Oxen' => [
            'quantity' => 2,
            'durability' => 100
        ],
        'Food' => [
            'quantity' => 50,
            'durability' => null
        ]
        // Add other inventory items here for testing
    ]
];


// Test the store purchase function
handleStorePurchase($playerState, $milestone_id);

// Close the connection once done
closeConnection();
?>
