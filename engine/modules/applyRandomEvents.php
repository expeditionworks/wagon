<?php
// applyRandomEvents.php
// Randomly fires trail events during normal travel days.
// Modifies ONLY $playerState — no DB writes, no HTML output.

function applyRandomEvents(&$playerState) {
    // Only fire events on normal travel days (not delays, not day 1)
    if ($playerState['delay_days'] > 0) return;
    if ($playerState['day'] <= 2) return;
    if ($playerState['game_over'] ?? false) return;

    // Load events config
    $eventsPath = __DIR__ . '/../../config/events.json';
    if (!file_exists($eventsPath)) {
        debugLog($playerState, "Error: events.json not found.");
        return;
    }
    $eventsConfig = json_decode(file_get_contents($eventsPath), true);
    $events = $eventsConfig['events'] ?? [];
    $currentTrail = $playerState['current_trail'] ?? 'oregon';

    // Check each event
    foreach ($events as $event) {
        // Check trail eligibility
        $eligibleTrails = $event['trail'] ?? ['oregon', 'california'];
        if (!in_array($currentTrail, $eligibleTrails)) continue;

        // Roll for probability
        $roll = mt_rand(0, 10000) / 10000;
        if ($roll > $event['probability']) continue;

        // Event fires — apply effects
        $effects = $event['effects'];

        // Check for repair kit requirement and use no_kit effects if needed
        if (isset($event['no_kit_narrative']) && ($playerState['inventory']['WagonRepairKit']['quantity'] ?? 0) <= 0) {
            $effects = $event['no_kit_effects'] ?? $effects;
            $narrative = $event['no_kit_narrative'];
        } else {
            $narrative = $event['narrative'];
        }

        // Apply delay
        if (isset($effects['delay_days'])) {
            $playerState['delay_days'] += $effects['delay_days'];
            $playerState['delay_status'] = 'active';
        }

        // Apply morale
        if (isset($effects['morale'])) {
            $playerState['morale'] = max(0, min(100, ($playerState['morale'] ?? 100) + $effects['morale']));
        }

        // Apply dollars
        if (isset($effects['dollars'])) {
            $playerState['dollars'] = max(0, ($playerState['dollars'] ?? 0) + $effects['dollars']);
        }

        // Apply miles bonus
        if (isset($effects['miles_bonus'])) {
            $playerState['mile'] += $effects['miles_bonus'];
            $playerState['miles_traveled'] += $effects['miles_bonus'];
        }

        // Apply inventory changes
        if (isset($effects['inventory'])) {
            foreach ($effects['inventory'] as $item => $change) {
                if (!isset($playerState['inventory'][$item])) {
                    $playerState['inventory'][$item] = ['quantity' => 0, 'durability' => null];
                }
                $playerState['inventory'][$item]['quantity'] = max(0, $playerState['inventory'][$item]['quantity'] + $change);
            }
        }

        // Apply family health effects
        if (isset($effects['family_health']) || isset($effects['family_condition'])) {
            $family = $playerState['family'];
            if (is_string($family)) {
                $family = json_decode($family, true) ?? [];
            }
            if (is_array($family)) {
                // Pick a random living family member
                $livingMembers = array_keys(array_filter($family, fn($m) => !($m['deceased'] ?? false)));
                if (!empty($livingMembers)) {
                    $targetIndex = $livingMembers[array_rand($livingMembers)];
                    if (isset($effects['family_health'])) {
                        $family[$targetIndex]['health'] = max(0, $family[$targetIndex]['health'] + $effects['family_health']);
                        if ($family[$targetIndex]['health'] <= 0) {
                            $family[$targetIndex]['deceased'] = true;
                            // Store event narrative to be combined with travel log
                            $playerState['todayEventNarrative'] = $event['title'] . ": " . $narrative;
                            debugLog($playerState, "Random event fired: " . $event['id']);
                            $playerState['morale'] = max(0, ($playerState['morale'] ?? 100) - 20);
                        }
                    }
                    if (isset($effects['family_condition'])) {
                        $family[$targetIndex]['condition'] = $effects['family_condition'];
                    }
                }
                $playerState['family'] = $family;
            }
        }

        // Log the event
        // Store narrative to combine with travel log in handleMilestones
        $playerState['todayEventNarrative'] = $event['title'] . ": " . $narrative;
        debugLog($playerState, "Random event fired: " . $event['id']);

        // Only fire one event per turn
        break;
    }
}
?>