<?php
// handlers/generate_digest.php

/*
Template Variables:
-------------------
{{trail_name}}         // Player's assigned trail name
{{week_number}}        // Number of weeks since start
{{start_date}}         // Original trail start date
{{days_on_trail}}      // Total days since start
{{food_lbs}}           // Remaining food (from inventory)
{{morale}}             // Current morale score (0–100)
{{oxen}}               // Number of oxen
{{clothing}}           // Number of clothing items
{{ammunition}}         // Ammunition count
{{milestone_section}}  // HTML with milestones + extended descriptions
{{crossing_section}}   // HTML with river crossing outcomes
{{tip_of_the_week}}    // Rotating gameplay advice
{{family_summary}}     // Short description of current family group
*/

function generateWeeklyDigest($playerRow, $milestones, $weekNumber, $tipOfTheWeek) {
  $state = $playerRow['player_state'];
  $inventory = $state['inventory'];

  $template = file_get_contents(__DIR__ . '/../templates/weekly_digest_template.html');

  $familySummary = "Your group includes: " . implode(", ", $state['family']) . ".";

  // Milestone section (with extended descriptions)
  $milestoneHtml = "";
  foreach ($state['log'] as $entry) {
    if (!empty($entry['milestone'])) {
      $milestoneData = array_filter($milestones, fn($m) => $m['title'] === $entry['milestone']);
      $milestone = array_shift($milestoneData);
      if ($milestone) {
        $milestoneHtml .= "<strong>📍 {$milestone['title']}</strong> (Mile {$milestone['mile']})<br>";
        $milestoneHtml .= "{$milestone['extended_description']}<br><br>";
      }
    }
  }

  // Crossing section
  $crossingHtml = "";
  foreach ($state['log'] as $entry) {
    if (!empty($entry['crossing_result'])) {
      $crossingHtml .= "<strong>{$entry['milestone']}:</strong> {$entry['crossing_result']}<br>";
    }
  }

  // Token substitution
  $template = str_replace('{{trail_name}}', $playerRow['trail_name'], $template);
  $template = str_replace('{{week_number}}', $weekNumber, $template);
  $template = str_replace('{{start_date}}', $state['start_date'], $template);
  $template = str_replace('{{days_on_trail}}', $state['day'], $template);
  $template = str_replace('{{food_lbs}}', $inventory['food_lbs'], $template);
  $template = str_replace('{{morale}}', $state['morale'] ?? 100, $template);
  $template = str_replace('{{oxen}}', $inventory['oxen'], $template);
  $template = str_replace('{{clothing}}', $inventory['clothing'], $template);
  $template = str_replace('{{ammunition}}', $inventory['ammunition'], $template);
  $template = str_replace('{{milestone_section}}', $milestoneHtml, $template);
  $template = str_replace('{{crossing_section}}', $crossingHtml, $template);
  $template = str_replace('{{tip_of_the_week}}', $tipOfTheWeek, $template);
  $template = str_replace('{{family_summary}}', $familySummary, $template);

  return $template;
}
?>
