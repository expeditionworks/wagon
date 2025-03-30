<?php
// Include game engine or DB connection
include('game_engine.php');

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

$milestone_id = 'independence';  // Milestone for testing

// Test the store purchase function
handleStorePurchase($playerState, $milestone_id);
?>
