<?php
// cron_turn.php
// Processes one turn for all players whose next_turn_at has passed.
// Run via system CRON every 15-30 minutes.
// Safe to run manually for testing.

// Prevent running from browser in production (remove for local testing)
// if (php_sapi_name() !== 'cli') { die("CLI only"); }

include_once(__DIR__ . '/game_engine.php');

$startTime = microtime(true);
$processed = 0;
$errors    = 0;
$log       = [];

// Find all players due for a turn
$query = "SELECT ps.player_id, p.turn_interval_minutes 
          FROM player_state ps
          JOIN players p ON p.id = ps.player_id
          WHERE p.next_turn_at <= NOW()
          AND ps.game_over = 0
          LIMIT 50";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $player_id            = $row['player_id'];
    $turn_interval_minutes = $row['turn_interval_minutes'];

    try {
        // Load player state
        $playerState = getPlayerState($player_id, $conn);
        if (!$playerState) {
            $log[] = "Player $player_id: state not found — skipping.";
            $errors++;
            continue;
        }

        // Skip if game already over
        if (!empty($playerState['game_over'])) {
            $log[] = "Player $player_id: game already over — skipping.";
            continue;
        }

        // Process the turn
        $playerState = moveAndCheckMilestones($playerState, $player_id, $conn);

        // Update next_turn_at
        $stmt = $conn->prepare(
            "UPDATE players SET next_turn_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?"
        );
        $stmt->bind_param('ii', $turn_interval_minutes, $player_id);
        $stmt->execute();

        $log[] = "Player $player_id: turn processed. Day " . $playerState['day'] . ", Mile " . $playerState['mile'];
        $processed++;

    } catch (Exception $e) {
        $log[] = "Player $player_id: ERROR — " . $e->getMessage();
        $errors++;
    }
}

$elapsed = round(microtime(true) - $startTime, 2);

// Write to log file
$logEntry = date('Y-m-d H:i:s') . " | Processed: $processed | Errors: $errors | Time: {$elapsed}s\n";
foreach ($log as $line) {
    $logEntry .= "  → $line\n";
}
file_put_contents(__DIR__ . '/../logs/cron.log', $logEntry, FILE_APPEND);

// Output for manual testing
echo $logEntry;
?>