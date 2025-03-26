<?php
// handleMilestones.php

function checkMilestones($player_id, $conn) {
    // Get player state
    $playerRow = getPlayerState($player_id, $conn);
    $mile = $playerRow['player_state']['mile'];

    // Retrieve the milestones (you can pull from a JSON or database)
    $milestones = json_decode(file_get_contents(__DIR__ . '/../../config/milestones.json'), true);
    $milestoneHtml = '';

    // Iterate through milestones and check if the player has reached any
    foreach ($milestones as $milestone) {
        if ($mile >= $milestone['mile'] && !$milestone['reached']) {
            // Mark the milestone as reached
            $milestone['reached'] = true;

            // Log the milestone
            $playerRow['player_state']['log'][] = [
                'notes' => "You reached the milestone: " . $milestone['title'] . ". " . $milestone['extended_description']
            ];

            // Update milestone section HTML (for the weekly digest)
            $milestoneHtml .= "<strong>📍 {$milestone['title']}</strong> (Mile {$milestone['mile']})<br>";
            $milestoneHtml .= "{$milestone['extended_description']}<br><br>";

            // Save the updated player state
            updatePlayerState($player_id, $playerRow['player_state'], $conn);
        }
    }

    return $milestoneHtml;
}
?>
