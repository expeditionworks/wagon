<?php
// Include the game engine file (which defines loadMilestones)
include_once(__DIR__ . '/../engine/game_engine.php');

// Load the milestones by calling the function
$milestones = loadMilestones(); // This will return the $milestones array

// Debug: Print $milestones to verify it's loaded
echo "<pre>";
print_r($milestones);  // Print the loaded milestones
echo "</pre>";

// Example of accessing a specific milestone
$milestone_id = 'independence';  // Example ID
$milestone = $milestones[$milestone_id] ?? null;  // Access by milestone ID

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
