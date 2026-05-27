<?php
// game_functions.php
// Shared helper functions used across the engine.
// These functions only modify $playerState — no DB writes, no HTML output.

// ---------------------------------------------------------------------------
// debugLog
// Adds a debug message to $playerState['debug']
// In production, stop rendering this array. The engine code stays the same.
// ---------------------------------------------------------------------------
function debugLog(&$playerState, $message) {
    $playerState['debug'][] = $message;
}
