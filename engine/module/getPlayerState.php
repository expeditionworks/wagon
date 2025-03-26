<?php
// modules/getPlayerState.php

/**
 * Fetch player state from the database based on the player's ID.
 *
 * @param int $player_id The ID of the player whose state is to be fetched.
 * @param mysqli $conn The MySQL connection object.
 * @return array|null The player data, including player state, or null if the player does not exist.
 */
function getPlayerState($player_id, $conn) {
    // SQL query to join players and player_state tables to retrieve player data
    $sql = "
        SELECT players.trail_name, players.family, player_state.*
        FROM players
        JOIN player_state ON players.id = player_state.player_id
        WHERE players.id = $player_id
    ";
    
    // Execute the query
    $result = $conn->query($sql);
    
    // Check if player data exists
    if ($result->num_rows > 0) {
        // Fetch the data from the result
        $row = $result->fetch_assoc();

        // Decode JSON fields (family, inventory, conditions, log) into PHP arrays
        $row['family'] = json_decode($row['family'], true); // Decode family field
        $row['inventory'] = json_decode($row['inventory'], true); // Decode inventory field
        $row['conditions'] = json_decode($row['conditions'], true); // Decode conditions field
        $row['log'] = json_decode($row['log'], true); // Decode log field

        // Initialize player_state array if not set in the row
        $row['player_state'] = [
            'day' => $row['day'] ?? 1, // Default to day 1 if not set
            'mile' => $row['mile'] ?? 0, // Default to 0 miles if not set
            'inventory' => $row['inventory'],
            'conditions' => $row['conditions'],
            'log' => $row['log']
        ];

        // Return the full player data including player state
        return $row;
    } else {
        // Return null if no player data is found
        return null;
    }
}
?>
