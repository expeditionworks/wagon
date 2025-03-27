<?php
// moveAndCheckMilestones.php

function moveAndCheckMilestones($playerState) {
    // Debugging: Log player state before making any changes
    echo "<pre>Before Incrementing:</pre>";
    print_r($playerState);

    // Player movement: increment miles and days (only in-memory)
    $miles_traveled = 10;  // Example: 10 miles traveled in this turn
    $playerState['mile'] += $miles_traveled;
    $playerState['day'] += 1;  // Increment day by 1 (each turn represents a day)

    // Debugging: Log miles and days incremented
    echo "<pre>After Incrementing:</pre>";
    print_r($playerState);

    // Check milestones: see if the player has reached any milestones
    $mile = $playerState['mile'];
    $current_trail = $playerState['current_trail'];  // Get the player's current trail
    $milestones = $playerState['milestones'];

    // Iterate through milestones and check if player has reached any
    foreach ($milestones as &$milestone) {
        // Only check milestones that match the player's current trail
        if ($milestone['trail'] === $current_trail && $mile >= $milestone['mile'] && !isset($milestone['reached'])) {
            $milestone['reached'] = true;

            // Log milestone in player state
            $playerState['log'][] = [
                'notes' => "You reached the milestone: " . $milestone['title'] . ". " . $milestone['extended_description']
            ];
        }
    }

    // Debugging: Log after processing milestones
    echo "<pre>After Processing Milestones:</pre>";
    print_r($playerState);

    return $playerState;  // Return updated player state
}
?>
