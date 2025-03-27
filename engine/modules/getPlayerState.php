<?php
// getPlayerState.php

function getPlayerState($player_id, $conn) {
    // Query the database to get player state
    $query = "SELECT * FROM player_state WHERE player_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerRow = $result->fetch_assoc();

    // If player data is found, populate variables
    if ($playerRow) {
        // Load JSON configurations for terrain and milestones
        $terrain = json_decode(file_get_contents(__DIR__ . '/../../config/terrain.json'), true);
        $milestones = json_decode(file_get_contents(__DIR__ . '/../../config/milestones.json'), true);

        // Initialize player state with values or defaults if missing
        $playerState = [
            'day' => $playerRow['day'] ?? 1,
            'mile' => $playerRow['mile'] ?? 0,
            'morale' => $playerRow['morale'] ?? 100,
            'inventory' => json_decode($playerRow['inventory'], true) ?? [],
            'log' => json_decode($playerRow['log'], true) ?? [],
            'terrain' => $terrain,
            'milestones' => $milestones
        ];

        return $playerState;  // Return the populated player state
    }

    return null;  // Return null if player not found
}
?>
