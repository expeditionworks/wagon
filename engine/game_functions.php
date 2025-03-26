<?php
// game_engine.php

// Assuming you're using $playerRow in game_engine.php
if (isset($playerRow['player_state'])) {
    // Access player_state fields safely now
    $playerState = $playerRow['player_state'];
    // Example:
    $day = $playerState['day'];
    $mile = $playerState['mile'];
    // Further logic can follow
} else {
    echo "Error: player_state is not defined.";
    // You might want to handle this situation or set a default value
}


?>
