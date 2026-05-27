<?php
// updatePlayerState.php
// Writes $playerState to the database ONCE at the end of each turn.
// This is the ONLY place DB writes should happen.

function updatePlayerState($player_id, $playerState, $conn) {
    $inventoryJson     = json_encode($playerState['inventory']);
    $logJson           = json_encode($playerState['log']);
    $lastLogItem       = !empty($playerState['log'])
                        ? json_encode(end($playerState['log']))
                        : json_encode(['notes' => 'No log for this turn']);
    $currentTrail      = $playerState['current_trail'];
    $delayDays         = $playerState['delay_days'];
    $milesTraveled     = $playerState['miles_traveled'] ?? 0;
    $weatherJson       = json_encode($playerState['weatherThisTurn']);
    $newDelayState     = $playerState['delay_status'];
    $newFamilyUpdate   = json_encode($playerState['family']);
    $pendingActionJson = null;
if (!empty($playerState['pending_action'])) {
    if (is_array($playerState['pending_action'])) {
        $pendingActionJson = json_encode($playerState['pending_action']);
    } elseif (is_string($playerState['pending_action'])) {
        // Verify it's valid JSON before saving
        $test = json_decode($playerState['pending_action']);
        $pendingActionJson = (json_last_error() === JSON_ERROR_NONE)
            ? $playerState['pending_action']
            : null;
    }
}


    $query = "UPDATE player_state SET 
                day             = ?,
                mile            = ?,
                morale          = ?,
                dollars         = ?,
                inventory       = ?,
                log             = ?,
                current_trail   = ?,
                last_log_item   = ?,
                delay_days      = ?,
                miles_traveled  = ?,
                weather         = ?,
                delay_status    = ?,
                family          = ?,
                pending_action  = ?
              WHERE player_id   = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        debugLog($playerState, "Error preparing statement: " . $conn->error);
        return;
    }

$stmt->bind_param(
        'iissssssssssssi',
        $playerState['day'],
        $playerState['mile'],
        $playerState['morale'],
        $playerState['dollars'],
        $inventoryJson,
        $logJson,
        $currentTrail,
        $lastLogItem,
        $delayDays,
        $milesTraveled,
        $weatherJson,
        $newDelayState,
        $newFamilyUpdate,
        $pendingActionJson,
        $player_id
    );

    if (!$stmt->execute()) {
        debugLog($playerState, "Error executing query: " . $stmt->error);
    }
}
?>