<?php
include_once(__DIR__ . '/../engine/game_engine.php');

$player_id = 1;
$playerState = getPlayerState($player_id, $conn);

// Bypass pending actions from previous turns
$playerState['pending_action'] = null;

// Set up a test store
$testStore = [
    'Food' => [
        'description' => 'Provisions for the trail.',
        'base_price' => 1,
        'stock_limit' => 200
    ],
    'Oxen' => [
        'description' => 'Strong draft animals.',
        'base_price' => 50,
        'stock_limit' => 10
    ],
    'Ammunition' => [
        'description' => 'For hunting and protection.',
        'base_price' => 1,
        'stock_limit' => 200
    ],
    'Clothes' => [
        'description' => 'Extra clothes for the journey.',
        'base_price' => 10,
        'stock_limit' => 10
    ]
];

// Process purchase if form submitted
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item'], $_POST['quantity'])) {
    $itemName = $_POST['item'];
    $quantity = (int)$_POST['quantity'];
    $result = processPurchase($playerState, $itemName, $quantity, $testStore);
    $message = $result['message'];
    // Save state after purchase
    updatePlayerState($player_id, $playerState, $conn);
}
?>
<!DOCTYPE html>
<html>
<head><title>Store Test</title></head>
<body>
<h2>Store</h2>
<p><strong>Dollars:</strong> $<?= $playerState['dollars'] ?></p>

<?php if ($message): ?>
    <p style="color:green"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<h3>Items for Sale</h3>
<ul>
<?php foreach ($testStore as $itemName => $itemDetails): ?>
    <li>
        <strong><?= $itemName ?></strong> — <?= $itemDetails['description'] ?> 
        Price: $<?= $itemDetails['base_price'] ?> each
    </li>
<?php endforeach; ?>
</ul>

<h3>Make a Purchase</h3>
<form method="POST">
    <label>Item:
        <select name="item">
            <?php foreach ($testStore as $itemName => $itemDetails): ?>
                <option value="<?= $itemName ?>"><?= $itemName ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Quantity: <input type="number" name="quantity" value="1" min="1"></label>
    <button type="submit">Buy</button>
</form>

<h3>Current Inventory</h3>
<ul>
<?php foreach ($playerState['inventory'] as $itemName => $itemData): ?>
    <li><?= $itemName ?>: <?= $itemData['quantity'] ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>