<?php
// getPlayerState.php

function getPlayerState($player_id, $conn) {
    // Query to fetch player state from the database
    $query = "SELECT * FROM player_state WHERE player_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerRow = $result->fetch_assoc();

    // If player data exists, populate the player state
    if ($playerRow) {
        // Load JSON configurations for terrain and milestones
        $terrain = json_decode(file_get_contents(__DIR__ . '/../../config/terrain.json'), true);
        $milestones = json_decode(file_get_contents(__DIR__ . '/../../config/milestones.json'), true);

        // Populate player state or set default values if missing
        $playerState = [
            'day' => $playerRow['day'] ?? 1,
            'mile' => $playerRow['mile'] ?? 0,
            'morale' => $playerRow['morale'] ?? 100,
            'inventory' => json_decode($playerRow['inventory'], true) ?? [],
            'log' => json_decode($playerRow['log'], true) ?? [],
            'terrain' => $terrain,
            'milestones' => $milestones,
            'current_trail' => $playerRow['current_trail'] ?? 'Oregon'  // Use 'current_trail' from DB, default to 'Oregon'
        ];

        return $playerState;  // Return the populated player state
    }

    return null;  // Return null if player not found
}
?>
