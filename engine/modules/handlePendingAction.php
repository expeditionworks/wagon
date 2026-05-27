<?php
// handlePendingAction.php
// Processes player responses to pending actions (river crossings, stores, forks).
// Called by the delivery layer when a player submits a decision.
// Modifies $playerState and saves to DB — then continues the turn.

function handlePendingAction(&$playerState, $player_id, $conn) {
    $action = $playerState['pending_action'];
    if (empty($action)) return;

    $type = $action['type'] ?? '';

    switch ($type) {

        case 'store':
            // Player is shopping — process purchases then clear action
            // Purchases are handled separately via processPurchase()
            // When player is done shopping, clear pending_action to continue turn
            $playerState['pending_action'] = null;
            debugLog($playerState, "Store visit complete at " . ($action['milestone'] ?? 'unknown'));
            break;

        case 'river_crossing':
            $choice = $action['chosen_option'] ?? 'ford';
            $milestone = $action['milestone'] ?? 'river';
            debugLog($playerState, "River crossing choice: $choice at $milestone");

            switch ($choice) {
                case 'ferry':
                    $cost = $action['ferry_cost'] ?? 5;
                    if ($playerState['dollars'] >= $cost) {
                        $playerState['dollars'] -= $cost;
                        $playerState['delay_days'] += $action['ferry_delay'] ?? 1;
                        $playerState['log'][] = [
                            'day'            => $playerState['day'],
                            'miles_traveled' => 0,
                            'total_miles'    => $playerState['mile'],
                            'milestone'      => $milestone,
                            'notes'          => "You paid \$$cost to take the ferry across the $milestone. Safe but slow."
                        ];
                    } else {
                        $playerState['log'][] = [
                            'day'            => $playerState['day'],
                            'miles_traveled' => 0,
                            'total_miles'    => $playerState['mile'],
                            'milestone'      => $milestone,
                            'notes'          => "You couldn't afford the ferry. You decided to ford instead."
                        ];
                        $choice = 'ford'; // Fall through to ford
                    }
                    break;

                case 'float':
                    $playerState['delay_days'] += $action['float_delay'] ?? 2;
                    $playerState['log'][] = [
                        'day'            => $playerState['day'],
                        'miles_traveled' => 0,
                        'total_miles'    => $playerState['mile'],
                        'milestone'      => $milestone,
                        'notes'          => "You floated the wagon across the $milestone. It took extra time but you made it safely."
                    ];
                    break;

                case 'ford':
                default:
                    $fordRisk = $action['ford_risk'] ?? 0.3;
                    if (mt_rand(0, 100) / 100 <= $fordRisk) {
                        // Failed ford
                        $playerState['log'][] = [
                            'day'            => $playerState['day'],
                            'miles_traveled' => 0,
                            'total_miles'    => $playerState['mile'],
                            'milestone'      => $milestone,
                            'notes'          => "You attempted to ford the $milestone but the current was too strong. You lost supplies."
                        ];
                        // Lose some food
                        $foodLost = min(20, $playerState['inventory']['Food']['quantity'] ?? 0);
                        $playerState['inventory']['Food']['quantity'] -= $foodLost;
                        $playerState['delay_days'] += $action['ford_delay'] ?? 1;
                    } else {
                        // Successful ford
                        $playerState['log'][] = [
                            'day'            => $playerState['day'],
                            'miles_traveled' => 0,
                            'total_miles'    => $playerState['mile'],
                            'milestone'      => $milestone,
                            'notes'          => "You forded the $milestone successfully. The wagon stayed dry and you pushed on."
                        ];
                    }
                    break;
            }
            $playerState['pending_action'] = null;
            break;

            case 'fork':
            $choice = $action['chosen_option'] ?? null;
            if ($choice) {
                // Map route choices back to valid trail identifiers
                $trailMap = [
                    'oregon'          => 'oregon',
                    'california'      => 'california',
                    'oregon_sublette' => 'oregon',
                    'fort_bridger'    => 'oregon',
                    'barlow_road'     => 'oregon',
                    'columbia_river'  => 'oregon',
                ];
                $playerState['current_trail'] = $trailMap[$choice] ?? 'oregon';
                $playerState['log'][] = [
                    'day'            => $playerState['day'],
                    'miles_traveled' => 0,
                    'total_miles'    => $playerState['mile'],
                    'milestone'      => $action['milestone'] ?? 'fork',
                    'notes'          => "You chose to take the " . ucfirst($choice) . " route. The wagon train turns and a new chapter begins."
                ];
                debugLog($playerState, "Trail changed to: " . $playerState['current_trail'] . " via $choice at " . ($action['milestone'] ?? 'fork'));
            }
            $playerState['pending_action'] = null;
            break;

        default:
            // Unknown action type — clear it and continue
            debugLog($playerState, "Unknown pending_action type: $type — clearing.");
            $playerState['pending_action'] = null;
            break;
    }
}
?>