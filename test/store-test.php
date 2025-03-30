<?php
// Include the game engine file (which defines loadMilestones)
include_once(__DIR__ . '/../engine/game_engine.php');

// Set up player ID (or any required data)
$player_id = 1;  // For example

// Call getPlayerState function to load milestones
$playerState = getPlayerState($player_id);  // Assuming this loads $playerState['milestones']

// Now you can access $playerState['milestones']
$milestones = $playerState['milestones'] ?? null;  // Get milestones from playerState

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
?>
