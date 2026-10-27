<?php
// DT Nesimi 
// page for waste [food out]
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // remembers which entries this browser saved, for the previous entry button
}
require_once(__DIR__ . '/database/dbWaste.php');

$cartWeight = 56; // lbs, will eventually have option to change
$wasteTypes = ['pig_food' => 'Pig food', 'trash' => 'Trash']; // value saved => label shown
$self     = strtok($_SERVER['REQUEST_URI'], '?');
$notice   = null;   // [ 'ok' | 'error', message ]
$editId   = null;   // entry_id being edited, or null for a new entry
$editInfo = '';
$start    = null;   // what the form opens with. an earlier entry or what was typed before a failed save

// entries this browser session has saved, oldest first. prev/newer walk this list, & only
// entries on it can be opened for editing. kept apart from the shopping page's list so
// previous here never lands on a shopping entry
$history = array_map('intval', $_SESSION['waste_history'] ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type     = $_POST['waste_type'] ?? '';
    $weightIn = $_POST['scale_weight'] ?? '';
    $cartIn   = $_POST['cart_applied'] ?? '';
    $entryIn  = $_POST['entry_id'] ?? '';
    $postedId = ($entryIn === '') ? null : filter_var($entryIn, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $editOk   = $postedId === null || in_array($postedId, $history, true); // only this session's own entries

    $typeOk   = is_string($type) && isset($wasteTypes[$type]);
    $weightOk = preg_match('/^\d{1,5}(\.\d{1,2})?$/', $weightIn) === 1 && (float)$weightIn > 0;
    // the cart can only be 0 or the real cart weight, any other number is rejected
    $cart = (is_numeric($cartIn) && in_array((float)$cartIn, [0.0, (float)$cartWeight], true)) ? (float)$cartIn : null;

    $id   = false;
    $verb = '';
    if (!$typeOk || !$weightOk || $cart === null || (float)$weightIn <= $cart || !$editOk) {
        $notice = ['error', 'Missing or invalid entry. Please enter it again.'];
    } elseif ($postedId !== null) {
        // editing an earlier entry: overwrite that one row, never add a new one
        if (getWasteEntry($postedId) !== null && updateWaste($postedId, $type, $weightIn, $cart)) {
            $id   = $postedId;
            $verb = 'updated';
        } else {
            $notice = ['error', 'The changes were not saved because the database could not be reached. Tell a coordinator, then try again.'];
        }
    } else {
        $id = addWaste($type, $weightIn, $cart);
        if ($id !== false) {
            $history[] = $id;
            $_SESSION['waste_history'] = array_slice($history, -25);
            $verb = 'saved';
        } else {
            $notice = ['error', 'The entry was not saved because the database could not be reached. Tell a coordinator, then enter it again.'];
        }
    }
    if ($id !== false) {
        header('Location: ' . $self . '?' . $verb . '=' . $id);
        exit;
    }

    // keep what was typed & stay on the same entry if one was being edited so a retry never makes a duplicate
    $start = [
        'weight' => preg_replace('/[^0-9.]/', '', $weightIn),
        'type'   => $typeOk ? $type : null,
        'cart'   => is_numeric($cartIn) ? ((float)$cartIn > 0 ? 'yes' : 'no') : null,
    ];
    if (is_int($postedId) && $editOk) {
        $editId   = $postedId;
        $editInfo = 'your changes are not saved yet';
    }
} else {
    // show what was just saved or changed so volunteers can catch a typo right away
    foreach (['saved' => 'Saved', 'updated' => 'Updated'] as $key => $label) {
        if (isset($_GET[$key])) {
            $row = getWasteEntry((int)$_GET[$key]);
            if ($row) {
                $notice = ['ok', $label . ': ' . ($wasteTypes[$row['waste_type']] ?? $row['waste_type']) . ', ' . (float)$row['total_weight'] . ' lbs.'];
            }
        }
    }

    // open an earlier entry for editing
    if (isset($_GET['edit'])) {
        $eid = (int)$_GET['edit'];
        $row = in_array($eid, $history, true) ? getWasteEntry($eid) : null;
        if ($row) {
            $w = $row['scale_weight'];
            $start = [
                'weight' => (strpos($w, '.') !== false) ? rtrim(rtrim($w, '0'), '.') : $w,
                'type'   => $row['waste_type'],
                'cart'   => ((float)$row['cart_applied'] > 0) ? 'yes' : 'no',
            ];
            $editId   = $eid;
            $editInfo = ($wasteTypes[$row['waste_type']] ?? $row['waste_type']) . ', saved ' . date('M j, g:i a', strtotime($row['entry_date']));
        } else {
            $notice = ['error', 'That entry could not be opened.'];
        }
    }
}

// prev/newer targets walk this session's history around the entry being edited,
// or start from the most recent entry when this is a new entry
$pos = ($editId !== null) ? array_search($editId, $history, true) : false;
if ($pos !== false) {
    $olderId = $history[$pos - 1] ?? null;
    $newerId = $history[$pos + 1] ?? null;
} else {
    $olderId = $history ? end($history) : null;
    $newerId = null;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Foodway | Love Thy Neighbor Community Food Pantry</title>
        <link rel="stylesheet" href="css/layoutInfo.css">
        <link rel="stylesheet" href="header.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet">
        <style>* { font-family: Quicksand, sans-serif; }</style>
    </head>
<body class="bg-gray-100">
<div class="min-h-screen flex flex-col">
<?php
$isFoodwayPage = true;
require_once('header.php');
?>
    <div class="px-6 pt-4">
        <p style="font-size: 32px; text-align:center;">Waste • Food Out</p>
    </div>

<?php if ($notice): ?>
    <div role="status" class="w-full max-w-5xl mx-auto px-6 pt-4">
        <p class="rounded-2xl px-6 py-4 text-xl font-bold <?= $notice[0] === 'ok' ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' ?>">
            <?= htmlspecialchars($notice[1]) ?></p>
    </div>
<?php endif; ?>

<?php if ($editId !== null): ?>
    <div class="w-full max-w-5xl mx-auto px-6 pt-4">
        <p class="rounded-2xl px-6 py-4 text-xl font-bold bg-amber-100 text-amber-900">
            Editing an earlier entry<?= $editInfo !== '' ? ' (' . htmlspecialchars($editInfo) . ')' : '' ?>.</p>
    </div>
<?php endif; ?>

    <form id="foodOutForm" method="post" class="w-full max-w-5xl mx-auto px-6 py-6">
        <input type="hidden" name="scale_weight" id="in_weight">
        <input type="hidden" name="waste_type" id="in_type">
        <input type="hidden" name="cart_applied" id="in_cart">
        <input type="hidden" name="entry_id" value="<?= $editId !== null ? (int)$editId : '' ?>">

        <div class="grid gap-6 md:grid-cols-2">

            <!-- Left: what the volunteer is filling in -->
            <div class="flex flex-col gap-5">

                <div>
                    <p class="text-lg text-gray-600 mb-2">What kind of waste?</p>
                    <div class="flex gap-3">
                        <?php foreach ($wasteTypes as $value => $label): ?>
                        <button type="button" data-type="<?= $value ?>"
                                class="wtype flex-1 h-24 text-3xl font-bold rounded-2xl border-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700"><?= $label ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="w-full rounded-2xl bg-white border-2 border-green-700 ring-4 ring-green-700 px-6 py-4">
                    <span class="block text-lg text-gray-600">Scale weight [lbs]</span>
                    <span id="val_weight" class="block text-5xl font-bold text-gray-900 min-h-[3.5rem]"></span>
                </div>

                <div>
                    <p class="text-lg text-gray-600 mb-2">Cart used?</p>
                    <div class="flex gap-3">
                        <button type="button" data-cart="yes"
                                class="cart flex-1 h-20 text-2xl font-bold rounded-2xl border-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                            Yes, cart [&minus;<?= (int)$cartWeight ?> lbs]</button>
                        <button type="button" data-cart="no"
                                class="cart flex-1 h-20 text-2xl font-bold rounded-2xl border-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                            No cart</button>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border-2 border-gray-300 px-6 py-4">
                    <span class="block text-lg text-gray-600">Waste weight</span>
                    <span id="val_total" class="block text-5xl font-bold text-gray-900 min-h-[3.5rem]"></span>
                    <p id="msg" class="text-lg text-red-700 min-h-[1.75rem]" aria-live="polite"></p>
                </div>
            </div>

            <!-- Right: big number pad -->
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-3 gap-3" id="keypad">
                    <?php foreach (['1','2','3','4','5','6','7','8','9','.','0','back'] as $k): ?>
                        <button type="button" data-key="<?= $k ?>"
                                class="key h-24 text-4xl font-bold rounded-2xl bg-white border-2 border-gray-300 active:bg-gray-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700"
                                <?= $k === 'back' ? 'aria-label="Delete last digit"' : '' ?>>
                            <?= $k === 'back' ? '&#9003;' : $k ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" id="saveBtn" disabled
                        class="h-24 text-3xl font-bold rounded-2xl bg-green-700 text-white disabled:bg-gray-300 disabled:text-gray-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-900">
                    <?= $editId !== null ? 'Save changes & return' : 'Save & start next entry' ?></button>

                <?php if ($olderId !== null || $editId !== null): ?>
                <div class="flex gap-3">
                    <?php if ($olderId !== null): ?>
                        <a href="<?= htmlspecialchars($self) ?>?edit=<?= (int)$olderId ?>"
                           class="flex-1 text-center py-4 text-xl font-bold rounded-2xl bg-white border-2 border-gray-300 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">&lsaquo; Previous entry</a>
                    <?php else: ?>
                        <span class="flex-1 text-center py-4 text-xl font-bold rounded-2xl bg-gray-200 text-gray-500 border-2 border-gray-200">&lsaquo; Previous entry</span>
                    <?php endif; ?>
                    <?php if ($editId !== null): ?>
                        <a href="<?= htmlspecialchars($self) ?><?= $newerId !== null ? '?edit=' . (int)$newerId : '' ?>"
                           class="flex-1 text-center py-4 text-xl font-bold rounded-2xl bg-white border-2 border-gray-300 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                            <?= $newerId !== null ? 'Newer entry &rsaquo;' : 'Back to new entry' ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</main>
</div>

<script>
const CART_WEIGHT = <?= json_encode((float)$cartWeight) ?>;
const START = <?= json_encode($start) ?>; // null for a new entry, the saved values when editing an earlier entry
const state = Object.assign({ weight: '', type: null, cart: null }, START || {});

const $  = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

const ON  = ['bg-green-700', 'text-white', 'border-green-700'];
const OFF = ['bg-white', 'text-gray-900', 'border-gray-300'];

function press(key) {
    let v = state.weight;

    if (key === 'back') {
        v = v.slice(0, -1);
    } else if (key === '.') {
        if (v.includes('.')) return;
        v = v === '' ? '0.' : v + '.';
    } else {
        const [whole, dec] = v.split('.');
        if (dec !== undefined && dec.length >= 2) return;
        if (dec === undefined && whole.length >= 5) return;
        v = (v === '0') ? (key === '0' ? v : key) : v + key;
    }
    state.weight = v;
    render();
}

function render() {
    const weight = parseFloat(state.weight);
    const cartApplied = state.cart === 'yes' ? CART_WEIGHT : 0;

    $('#val_weight').textContent = state.weight;

    $$('.wtype').forEach(el => {
        const on = el.dataset.type === state.type;
        ON.forEach(c => el.classList.toggle(c, on));
        OFF.forEach(c => el.classList.toggle(c, !on));
    });
    $$('.cart').forEach(el => {
        const on = el.dataset.cart === state.cart;
        ON.forEach(c => el.classList.toggle(c, on));
        OFF.forEach(c => el.classList.toggle(c, !on));
    });

    let msg = '';
    let total = '';
    const weightOk = !isNaN(weight) && weight > 0;
    if (weightOk && state.cart !== null) {
        if (weight <= cartApplied) {
            msg = 'The scale reads less than the ' + CART_WEIGHT + ' lb cart. Check the weight, or choose No cart.';
        } else {
            total = Number((weight - cartApplied).toFixed(2)) + ' lbs';
        }
    }
    $('#val_total').textContent = total;
    $('#msg').textContent = msg;

    const valid = state.type !== null && weightOk && state.cart !== null && weight > cartApplied;
    $('#saveBtn').disabled = !valid;

    $('#in_type').value   = state.type || '';
    $('#in_weight').value = state.weight.replace(/\.$/, ''); // a trailing "." would be rejected
    $('#in_cart').value   = cartApplied;
}

$$('.key').forEach(el => el.addEventListener('click', () => press(el.dataset.key)));
$$('.wtype').forEach(el => el.addEventListener('click', () => { state.type = el.dataset.type; render(); }));
$$('.cart').forEach(el => el.addEventListener('click', () => { state.cart = el.dataset.cart; render(); }));

document.addEventListener('keydown', (e) => {
    if (/^[0-9.]$/.test(e.key)) { e.preventDefault(); press(e.key); }
    else if (e.key === 'Backspace') { e.preventDefault(); press('back'); }
});

// One tap, one entry: lock the button once the form is on its way.
$('#foodOutForm').addEventListener('submit', () => { $('#saveBtn').disabled = true; });

render();
</script>
</body>
</html>