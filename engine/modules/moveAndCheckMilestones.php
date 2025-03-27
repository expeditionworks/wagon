<?php
// moveAndCheckMilestones.php

function moveAndCheckMilestones($playerState) {
    // Player movement: increment miles and days (only in-memory)
    $miles_traveled = 10;  // Example: 10 miles traveled in this turn
    $playerState['mile'] += $miles_traveled;
    $playerState['day'] += 1;  // Increment day by 1 (each turn represents a day)

    // Debugging: Log miles and days incremented
    echo "<p>Before incrementing:</p>";
    echo "<p>Day: " . $playerState['day'] . "</p>";
    echo "<p>Mile: " . $playerState['mile'] . "</p>";

    // Check milestones: see if the player has reached any milestones
    $mile = $playerState['mile'];
    $current_trail = $playerState['current_trail'];  // Get the player's current trail
    $milestones = $playerState['milestones'];

    $milestoneHtml = '';

    // Iterate through milestones and check if player has reached any
    foreach ($milestones as &$milestone) {
        // Only check milestones that match the player's current trail
        if ($milestone['trail'] === $current_trail && $mile >= $milestone['mile'] && !isset($milestone['reached'])) {
            $milestone['reached'] = true;

            // Log milestone in player state
            $playerState['log'][] = [
                'notes' => "You reached the milestone: " . $milestone['title'] . ". " . $milestone['extended_description']
            ];

            // Append milestone details to HTML for reporting purposes
            $milestoneHtml .= "<strong>📍 {$milestone['title']}</strong> (Mile {$milestone['mile']})<br>";
            $milestoneHtml .= "{$milestone['extended_description']}<br><br>";
        }
    }

    // Debugging: Log after processing milestones
    echo "<p>After processing milestones:</p>";
    echo "<p>Day: " . $playerState['day'] . "</p>";
    echo "<p>Mile: " . $playerState['mile'] . "</p>";

    return $playerState;  // Return updated player state
}
?>
