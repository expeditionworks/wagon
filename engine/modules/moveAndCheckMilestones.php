<?php
// moveAndCheckMilestones.php

function moveAndCheckMilestones($playerState, $player_id, $conn) {
    // Player movement: increment miles and days
    $miles_traveled = 10;  // Example: 10 miles traveled in this turn
    $playerState['mile'] += $miles_traveled;
    $playerState['day'] += 1;  // Increment day by 1 (each turn represents a day)

    // Check milestones: see if the player has reached any milestones
    $mile = $playerState['mile'];
    $current_trail = $playerState['current_trail'];  // Get the player's current trail (e.g., 'oregon' or 'california')
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

            // Save updated player state to the database
            updatePlayerState($player_id, $playerState, $conn);
        }
    }

    return $playerState;  // Return updated player state
}
?>
