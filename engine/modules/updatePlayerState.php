<?php
// updatePlayerState.php
// Writes $playerState to the database ONCE at the end of each turn.
// This is the ONLY place DB writes should happen.

function updatePlayerState($player_id, $playerState, $conn) {
    $inventoryJson     = json_encode($playerState['inventory']);
    // Sync morale from average family member morale
    $family = $playerState['family'];
    if (is_string($family)) {
        $family = json_decode($family, true) ?? [];
    }
    if (is_array($family) && count($family) > 0) {
        $livingMembers = array_filter($family, fn($m) => !($m['deceased'] ?? false));
        if (count($livingMembers) > 0) {
            $totalMorale = array_sum(array_column($livingMembers, 'morale'));
            $playerState['morale'] = round($totalMorale / count($livingMembers));
        }
    }
    // Keep only the last 30 log entries to prevent DB bloat
    $recentLog = array_slice($playerState['log'], -30);
    $logJson   = json_encode($recentLog);    
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
                pending_action  = ?,
                game_over       = ?
              WHERE player_id   = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        debugLog($playerState, "Error preparing statement: " . $conn->error);
        return;
    }
    $gameOver = $playerState['game_over'] ?? 0;

    $stmt->bind_param(
        'iissssssssssssii',
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
        $gameOver,
        $player_id
    );

    if (!$stmt->execute()) {
        debugLog($playerState, "Error executing query: " . $stmt->error);
    }
}


function recordGameHistory($player_id, $playerState, $outcome, $cause, $conn) {
    $stmt = $conn->prepare(
        "INSERT INTO game_history (player_id, outcome, trail, days_traveled, miles_reached, cause, final_dollars)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    if ($stmt === false) {
        debugLog($playerState, "Error preparing game history statement: " . $conn->error);
        return;
    }
    $trail        = $playerState['current_trail'] ?? 'oregon';
    $days         = $playerState['day'] ?? 0;
    $miles        = $playerState['mile'] ?? 0;
    $dollars      = $playerState['dollars'] ?? 0;
    $stmt->bind_param('issiisd', $player_id, $outcome, $trail, $days, $miles, $cause, $dollars);
    if ($stmt->execute()) {
        debugLog($playerState, "Game history recorded: $outcome — $cause");
    } else {
        debugLog($playerState, "Error recording game history: " . $stmt->error);
    }
}

?>