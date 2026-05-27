<?php
include_once(__DIR__ . '/engine/game_engine.php');

// Load configs
$speedTiers    = json_decode(file_get_contents(__DIR__ . '/config/turnSpeed.json'), true);
$professions   = json_decode(file_get_contents(__DIR__ . '/config/professions.json'), true);
$firstNames    = json_decode(file_get_contents(__DIR__ . '/config/names.json'), true)['first_names'];
$neutralNames  = json_decode(file_get_contents(__DIR__ . '/config/names.json'), true)['neutral_names'];
$surnames      = json_decode(file_get_contents(__DIR__ . '/config/surnames.json'), true);
$allNames      = array_merge($firstNames, $neutralNames);

// Flatten professions into single array
$allProfessions = [];
foreach ($professions as $difficulty => $profs) {
    foreach ($profs as $prof) {
        $prof['difficulty'] = $difficulty;
        $allProfessions[] = $prof;
    }
}

// Generate random family
function generateFamily($allNames, $surnames) {
    $surname = $surnames[array_rand($surnames)];
    $roles = ['spouse', 'child', 'child', 'elder', 'farmhand'];
    
    // Pick party size — weighted toward 5
    $sizeRoll = mt_rand(1, 4);
    if ($sizeRoll === 1) {
        $partySize = 4;
    } elseif ($sizeRoll === 4) {
        $partySize = 6;
    } else {
        $partySize = 5;
    }

    $usedNames = [];
    $family = [];

    // Always add leader first
    $leaderName = $allNames[array_rand($allNames)];
    $usedNames[] = $leaderName;
    $family[] = [
        'first_name'         => $leaderName,
        'last_name'          => $surname,
        'role'               => 'leader',
        'condition'          => 'healthy',
        'health'             => 100,
        'morale'             => 100,
        'skills'             => [],
        'deceased'           => false,
        'condition_duration' => 0
    ];

    // Add remaining members
    shuffle($roles);
    for ($i = 1; $i < $partySize; $i++) {
        do {
            $name = $allNames[array_rand($allNames)];
        } while (in_array($name, $usedNames));
        $usedNames[] = $name;

        $family[] = [
            'first_name'         => $name,
            'last_name'          => $surname,
            'role'               => $roles[$i - 1] ?? 'member',
            'condition'          => 'healthy',
            'health'             => 100,
            'morale'             => 100,
            'skills'             => [],
            'deceased'           => false,
            'condition_duration' => 0
        ];
    }

    return ['family' => $family, 'surname' => $surname];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $speedKey     = $_POST['speed_tier'] ?? 'daily';
    $profTitle    = $_POST['profession'] ?? null;
    $randomProf   = isset($_POST['random_profession']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check email not already registered
        $stmt = $conn->prepare("SELECT id FROM players WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "That email is already registered.";
        } else {
            // Pick profession
            if ($randomProf || !$profTitle) {
                $profession = $allProfessions[array_rand($allProfessions)];
            } else {
                $profession = null;
                foreach ($allProfessions as $p) {
                    if ($p['title'] === $profTitle) {
                        $profession = $p;
                        break;
                    }
                }
                if (!$profession) $profession = $allProfessions[array_rand($allProfessions)];
            }

            // Get speed tier
            $speedTier     = $speedTiers[$speedKey] ?? $speedTiers['daily'];
            $intervalMins  = $speedTier['minutes'];

            // Generate family
            $familyData = generateFamily($allNames, $surnames);
            $family     = $familyData['family'];

            // Build starting inventory from profession supplies
            $inventory = [
                'Oxen'           => ['quantity' => 0,   'durability' => 100],
                'Food'           => ['quantity' => 0,   'durability' => null],
                'Ammunition'     => ['quantity' => 0,   'durability' => null],
                'Clothes'        => ['quantity' => 4,   'durability' => 100],
                'Tools'          => ['quantity' => 1,   'durability' => 100],
                'WagonRepairKit' => ['quantity' => 1,   'durability' => 100],
            ];

            // Apply profession starting supplies
            $supplyMap = [
                'food'   => 'Food',
                'ammo'   => 'Ammunition',
                'tools'  => 'Tools',
                'wood'   => 'Wood',
                'traps'  => 'Traps',
                'books'  => 'Books',
                'gold'   => 'Gold',
            ];
            foreach ($profession['starting_supplies'] ?? [] as $key => $qty) {
                $itemName = $supplyMap[$key] ?? ucfirst($key);
                if (!isset($inventory[$itemName])) {
                    $inventory[$itemName] = ['quantity' => 0, 'durability' => null];
                }
                $inventory[$itemName]['quantity'] += $qty;
            }

            $startingMoney = $profession['money'] ?? 200;

            // Insert player
            $stmt = $conn->prepare("INSERT INTO players (name, email, delivery_method, turn_interval_minutes, next_turn_at) VALUES (?, ?, 'console', ?, NOW())");
            $leaderName = $family[0]['first_name'] . ' ' . $familyData['surname'];
            $stmt->bind_param('ssi', $leaderName, $email, $intervalMins);
            $stmt->execute();
            $playerId = $conn->insert_id;

            // Insert player state
            $familyJson    = json_encode($family);
            $inventoryJson = json_encode($inventory);
            $stmt = $conn->prepare("INSERT INTO player_state 
                (player_id, day, mile, morale, dollars, ration_size, log, current_trail, difficulty, start_date, family, inventory, profession, profession_bonus)
                VALUES (?, 1, 0, 100, ?, 'full', '[]', 'oregon', ?, '1849-05-01', ?, ?, ?, ?)");
            $difficulty = $profession['difficulty'];
            $profTitle  = $profession['title'];
            $profBonus  = $profession['bonus'];
            $stmt->bind_param('idsssss', $playerId, $startingMoney, $difficulty, $familyJson, $inventoryJson, $profTitle, $profBonus);            $stmt->execute();

            // Redirect to game engine - UPDATE THIS
            header('Location: /test/test.php?player_id=' . $playerId);
            exit;
        }
    }
}

// Generate preview family for display
$previewFamily = generateFamily($allNames, $surnames);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Join the Trail — Conestoga Wagon</title>
    <style>
        body { font-family: Georgia, serif; max-width: 600px; margin: 40px auto; padding: 20px; }
        h1 { border-bottom: 2px solid #8B4513; padding-bottom: 10px; }
        .family-preview { background: #f5f0e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .profession-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0; }
        .profession-card { border: 2px solid #ccc; padding: 10px; border-radius: 5px; cursor: pointer; }
        .profession-card:hover { border-color: #8B4513; }
        .profession-card input { margin-right: 8px; }
        .easy { border-left: 4px solid green; }
        .medium { border-left: 4px solid orange; }
        .hard { border-left: 4px solid red; }
        .btn { background: #8B4513; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn-secondary { background: #666; }
        .error { color: red; padding: 10px; background: #fff0f0; border-radius: 5px; }
    </style>
</head>
<body>
<h1>⛺ Conestoga Wagon</h1>
<p><em>The year is 1849. The trail west beckons. Are you ready?</em></p>

<?php if (isset($error)): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">

    <h2>Your Family</h2>
    <div class="family-preview">
        <p><strong>The <?= htmlspecialchars($previewFamily['surname']) ?> Family</strong></p>
        <ul>
        <?php foreach ($previewFamily['family'] as $member): ?>
            <li><?= htmlspecialchars($member['first_name']) ?> — <?= htmlspecialchars($member['role']) ?></li>
        <?php endforeach; ?>
        </ul>
        <p><em>Your family has been chosen for you to keep the trail safe for everyone.</em></p>
    </div>

    <h2>Choose Your Profession</h2>
    <div class="profession-grid">
    <?php foreach ($allProfessions as $prof): ?>
        <label class="profession-card <?= $prof['difficulty'] ?>">
            <input type="radio" name="profession" value="<?= htmlspecialchars($prof['title']) ?>">
            <strong><?= htmlspecialchars($prof['title']) ?></strong>
            <small>(<?= ucfirst($prof['difficulty']) ?>)</small><br>
            <small><?= htmlspecialchars($prof['description']) ?></small><br>
            <small>Starting money: $<?= $prof['money'] ?></small>
        </label>
    <?php endforeach; ?>
    </div>
    <label>
        <input type="checkbox" name="random_profession"> Surprise me — pick my profession randomly
    </label>

    <h2>How Fast Do You Want to Play?</h2>
    <?php foreach ($speedTiers as $key => $tier): ?>
        <label>
            <input type="radio" name="speed_tier" value="<?= $key ?>" <?= $key === 'daily' ? 'checked' : '' ?>>
            <?= htmlspecialchars($tier['label']) ?>
        </label><br>
    <?php endforeach; ?>

    <h2>Your Email</h2>
    <p>We'll send your daily trail update here.</p>
    <input type="email" name="email" required placeholder="you@example.com" style="width:100%;padding:8px;font-size:16px;">

    <br><br>
    <button type="submit" class="btn">Head West →</button>

</form>
</body>
</html>