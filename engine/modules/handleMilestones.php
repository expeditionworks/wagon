<?php
// handleMilestones.php

function checkMilestones($player_id, $conn) {
    // Get player state
    $playerRow = getPlayerState($player_id, $conn);
    $mile = $playerRow['player_state']['mile'];

    // Correct the file path to load milestones.json from the /config directory
    $milestones = json_decode(file_get_contents(__DIR__ . '/../../config/milestones.json'), true);

    // Initialize milestone HTML output for the weekly digest
    $milestoneHtml = '';

    // Iterate through milestones and check if the player has reached any
    foreach ($milestones as &$milestone) {
        // Initialize 'reached' key if not set
        if (!isset($milestone['reached'])) {
            $milestone['reached'] = false;
        }

        if ($mile >= $milestone['mile'] && !$milestone['reached']) {
            // Mark the milestone as reached
            $milestone['reached'] = true;

            // Check if milestone has already been logged to avoid duplicates
            $milestoneLogged = false;
            foreach ($playerRow['player_state']['log'] as $log) {
                if (strpos($log['notes'], $milestone['title']) !== false) {
                    $milestoneLogged = true;
                    break;
                }
            }

            // Only add to the log if it's not already logged
            if (!$milestoneLogged) {
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
    }

    return $milestoneHtml;
}
?>
