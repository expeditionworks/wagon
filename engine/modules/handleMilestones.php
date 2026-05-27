<?php
// handleMilestones.php
// Checks if player reached a milestone today and applies effects.
// Modifies ONLY $playerState — no DB writes, no HTML output.
// Store UI is flagged as pending_action for the delivery layer to handle.

function handleMilestones(&$playerState, $previousMile, $player_id, $conn) {
    $newMile = $playerState['mile'];
    $milesTraveled = $playerState['miles_traveled'];
    $milestoneToday = null;
    $milestoneTodayTitle = null;
    $milestoneTodayType = null;
    $milestoneTodayForceStop = null;
    $milestoneTodayDelayDay = 0;
    $milestoneMoraleMod = 0;

 // Find milestone crossed today — only check milestones on current trail
    $currentTrail = $playerState['current_trail'] ?? 'oregon';
    foreach ($playerState['milestones'] as $milestone) {
        $milestoneTrails = $milestone['trail'] ?? ['oregon'];
        if (!in_array($currentTrail, $milestoneTrails)) {
            continue; // Skip milestones not on current trail
        }
        if ($milestone['mile'] > $previousMile && $milestone['mile'] <= $newMile) {
            $milestoneToday = $milestone;
            $milestoneTodayTitle = $milestone['title'];
            $milestoneTodayForceStop = $milestone['force_stop'] ?? false;
            $milestoneTodayType = $milestone['type'];
            $milestoneMoraleMod = $milestone['morale_mod'] ?? 0;
            $milestoneTodayDelayDay = $milestone['delay_day'] ?? 0;
            // Snap mile to milestone location
            $playerState['mile'] = $milestone['mile'];
            break;
        }
    }

    // No milestone today
    if (!$milestoneToday) {
        $playerState['log'][] = [
            'day'           => $playerState['day'],
            'miles_traveled'=> $milesTraveled,
            'total_miles'   => $playerState['mile'],
            'milestone'     => null,
            'notes'         => "Today you kept on rolling without a milestone. You travelled " . $milesTraveled . " miles, and ate " . $playerState['foodConsumedToday'] . " lbs of food."
        ];
        return;
    }

    // Apply force stop delay
    if ($milestoneTodayForceStop === true) {
        $playerState['delay_days'] += $milestoneTodayDelayDay;
        debugLog($playerState, "Force stop at " . $milestoneTodayTitle . " for " . $milestoneTodayDelayDay . " days.");
    }

    // Apply morale modifier to all family members
    if ($milestoneMoraleMod !== 0 && is_array($playerState['family'])) {
        foreach ($playerState['family'] as &$familyMember) {
            $familyMember['morale'] = max(0, min(100, $familyMember['morale'] + $milestoneMoraleMod));
        }
        debugLog($playerState, "Morale adjusted by " . $milestoneMoraleMod . " at " . $milestoneTodayTitle);
    }

    // Check for store at milestone — flag as pending_action for delivery layer
    if (isset($milestoneToday['store']) && $milestoneToday['store'] === true) {
        $playerState['pending_action'] = [
            'type'    => 'store',
            'milestone' => $milestoneTodayTitle,
            'items'   => $milestoneToday['items_for_sale'] ?? []
        ];
        debugLog($playerState, "Store available at " . $milestoneTodayTitle . " — pending_action set.");
    }

    // Handle milestone type
    switch ($milestoneTodayType) {
        case 'fort':
            $playerState['delay_days'] += $milestoneTodayDelayDay;
            $playerState['log'][] = [
                'day'           => $playerState['day'],
                'miles_traveled'=> $milesTraveled,
                'total_miles'   => $playerState['mile'],
                'milestone'     => $milestoneTodayTitle,
                'notes'         => "You reached " . $milestoneTodayTitle . ". " . $milestoneToday['extended_description'] . " Your morale went up by " . $milestoneMoraleMod . " points. You decided to rest " . $milestoneTodayDelayDay . " days."
            ];
            break;

        case 'river':
            if (isset($milestoneToday['crossing'])) {
                $crossing = $milestoneToday['crossing'];
                $playerState['pending_action'] = [
                    'type'         => 'river_crossing',
                    'milestone'    => $milestoneTodayTitle,
                    'options'      => $crossing['options'] ?? ['ford', 'float', 'ferry'],
                    'ferry_cost'   => $crossing['ferry_base_cost'] ?? 5,
                    'ford_risk'    => $crossing['ford_risk'] ?? 0.5,
                    'ford_delay'   => $crossing['ford_delay'] ?? 1,
                    'float_delay'  => $crossing['float_delay'] ?? 2,
                    'ferry_delay'  => $crossing['ferry_delay'] ?? 1,
                ];
                debugLog($playerState, "River crossing at " . $milestoneTodayTitle . " — pending_action set.");
            }
            $playerState['log'][] = [
                'day'           => $playerState['day'],
                'miles_traveled'=> $milesTraveled,
                'total_miles'   => $playerState['mile'],
                'milestone'     => $milestoneTodayTitle,
                'notes'         => "You reached the " . $milestoneTodayTitle . ". " . $milestoneToday['extended_description'] . " Your morale improved by " . $milestoneMoraleMod . " points."
            ];
            break;

        case 'natural':
            $playerState['log'][] = [
                'day'           => $playerState['day'],
                'miles_traveled'=> $milesTraveled,
                'total_miles'   => $playerState['mile'],
                'milestone'     => $milestoneTodayTitle,
                'notes'         => "You reached the " . $milestoneTodayTitle . ". " . $milestoneToday['extended_description'] . " You were moved by its natural beauty and your morale improved by " . $milestoneMoraleMod . " points."
            ];
            break;

        case 'fork':
            $playerState['pending_action'] = [
                'type'      => 'fork',
                'milestone' => $milestoneTodayTitle,
                'options'   => $milestoneToday['options'] ?? []
            ];
            $playerState['log'][] = [
                'day'           => $playerState['day'],
                'miles_traveled'=> $milesTraveled,
                'total_miles'   => $playerState['mile'],
                'milestone'     => $milestoneTodayTitle,
                'notes'         => "You reached " . $milestoneTodayTitle . ". " . $milestoneToday['extended_description'] . " You must choose your path."
            ];
            break;

        case 'final':
            $playerState['log'][] = [
                'day'           => $playerState['day'],
                'miles_traveled'=> $milesTraveled,
                'total_miles'   => $playerState['mile'],
                'milestone'     => $milestoneTodayTitle,
                'notes'         => "You reached " . $milestoneTodayTitle . ". " . $milestoneToday['extended_description'] . " You made it!"
            ];
            $playerState['game_over'] = true;
            recordGameHistory($player_id, $playerState, 'success', $milestoneTodayTitle, $conn);
            debugLog($playerState, "Game complete — reached " . $milestoneTodayTitle);
            break;

        default:
            $playerState['log'][] = [
                'day'           => $playerState['day'],
                'miles_traveled'=> $milesTraveled,
                'total_miles'   => $playerState['mile'],
                'milestone'     => $milestoneTodayTitle,
                'notes'         => "You reached " . $milestoneTodayTitle . ". " . $milestoneToday['extended_description']
            ];
            break;
    }

    debugLog($playerState, "Milestone reached: " . $milestoneTodayTitle . " (type: " . $milestoneTodayType . ")");
}
?>