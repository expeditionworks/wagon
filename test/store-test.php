<?php
// Include the database connection and the game engine module
include_once(__DIR__ . '/../engine/game_engine.php'); // Main game engine (which already includes the necessary modules)


// Debugging: Check if $milestones is defined
echo "<pre>";
print_r($milestones);  // Check what $milestones contains
echo "</pre>";

// Example of accessing a milestone
$milestone_id = 'independence';  // Testing with a known milestone ID

$milestone = $milestones[$milestone_id] ?? null;  // Access milestone by ID

if ($milestone === null) {
    echo "Milestone with ID '$milestone_id' not found.\n";
    return;
}

echo "Milestone loaded successfully!\n";




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
