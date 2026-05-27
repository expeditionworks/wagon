# Conestoga Wagon — CLAUDE.md

## What This Project Is
A modular PHP/MySQL text-message game engine inspired by Oregon Trail.
Turn-based, CRON-driven, daily processing. May eventually support multiple
journey types (Oregon Trail, Mars mission, etc.).

## Stack
- PHP 8.2 (procedural — no frameworks, no classes)
- MySQL 8.0
- Docker (PHP + MySQL containers)
- JSON config files for all game data
- CRON-based daily turn processing
- SMS/email/iOS push delivery (future — Twilio, SendGrid, APNs)

## Environment
- Local: Docker at http://localhost:8080
- DB host: `db`, user: `wagonuser`, pass: `wagonpass`, db: `wagon`
- All code lives in /var/www/html inside the wagon-php-1 container
- Git commands run from inside the container, not the Mac terminal

## Architecture Rules — READ BEFORE WRITING ANY CODE
- Player state is loaded from DB ONCE at turn start via `getPlayerState()`
- Modules ONLY modify in-memory `$playerState` — NO DB writes inside modules
- ALL database writes happen ONCE at turn end via `updatePlayerState()`
- NEVER add DB writes inside engine modules
- Procedural PHP only — no classes, no OOP, no frameworks
- No new dependencies without explicit discussion
- No surprise variable renames without flagging them first

## Folder Structure
/wagon
  /config         — JSON game configs (milestones.json, terrain.json, etc.)
  /engine         — Core turn processing
  /engine/modules — One file per game system (one job per file)
  /delivery       — Output layer (console, sms, email, push) — NOT YET BUILT
  /handlers       — Input/decision handlers
  /templates      — Message/narrative templates
  /test           — Test scripts
  /logs           — Debug and turn logs

## Module Files (engine/modules/)
- getPlayerState.php      — loads player from DB into $playerState
- updatePlayerState.php   — writes $playerState to DB (end of turn ONLY)
- applyWeather.php        — calculates weather, sets windModifier and precipitationPenalty
- applyRations.php        — calculates food consumption, sets foodConsumedToday and foodMoraleMod
- applyConditions.php     — applies family health/morale effects, sets conditionTravelMod
- movePlayer.php          — calculates miles traveled, updates mile and miles_traveled
- handleMilestones.php    — detects milestones, applies effects, sets pending_action

## $playerState Keys (canonical reference)
family, dollars, day, mile, morale, ration, inventory, log, current_trail,
last_log_item, terrain, terrainCurrent, altitude, milestones, delay_days,
delay_status, difficulty, oxen, miles_traveled, weatherLastTurn, weatherThisTurn,
start_date, month, pending_action, debug, windModifier, precipitationPenalty,
conditionTravelMod, foodConsumedToday, foodMoraleMod, familyCount

## Turn Processing Order (DO NOT CHANGE)
1. Load player state from DB → getPlayerState()
2. Resolve pending decisions → handleDecisions()
3. Apply weather → applyWeather()
4. Apply rations → applyRations()
5. Apply family conditions → applyConditions()
6. Calculate movement → movePlayer()
7. Check milestones → handleMilestones()
8. Persist state ONCE → updatePlayerState()
9. Deliver output → delivery layer (not yet built)

## Delivery Layer (not yet built)
- Engine sets pending_action in $playerState for player-facing decisions
- Delivery layer reads $playerState and formats output for the channel
- console.php → HTML for browser testing
- sms.php → plain text via Twilio
- email.php → HTML email via SendGrid
- push.php → JSON payload via APNs (iOS/Watch)
- Engine NEVER outputs HTML directly

## Debug System
- Use debugLog($playerState, "message") for all debug output
- Never use echo inside engine modules
- $playerState['debug'] array is rendered by the delivery layer or test output
- In production, stop rendering the debug array

## What Claude Should NOT Do
- Do not redesign the architecture
- Do not introduce classes or OOP patterns
- Do not add new library dependencies
- Do not move where DB writes happen
- Do not refactor file organization without being asked
- Do not rename variables without flagging it first
- Do not add echo statements inside engine modules
- Do not write multi-file changes without showing a plan first

## Git Workflow
- Always work on a feature branch, never directly on main
- Branch naming: refactor/description or feature/description
- Commit after each small working change
- Merge to main only when tested and working
- Git commands run from inside wagon-php-1 container

## Testing
- Test file: /test/test.php
- Visit http://localhost:8080/test/test.php to run a turn
- Each page load runs one full turn and advances the player
- Reset player: run the reset PHP script in the container
- Schema: /config/schema.sql