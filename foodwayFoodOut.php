<?php
require_once(__DIR__ . '/database/dbFoodout.php');

$cartWeight = 56; // lbs
$notice = null;   // [ 'ok' | 'error', message ]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = filter_var($_POST['customer_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $houses   = filter_var($_POST['household_count'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $weightIn = $_POST['scale_weight'] ?? '';
    $cartIn   = $_POST['cart_applied'] ?? '';

    $weightOk = preg_match('/^\d{1,5}(\.\d{1,2})?$/', $weightIn) === 1 && (float)$weightIn > 0;
    // The cart can only be 0 or the real cart weight; any other number is rejected.
    $cart = (is_numeric($cartIn) && in_array((float)$cartIn, [0.0, (float)$cartWeight], true)) ? (float)$cartIn : null;

    if ($customer === false || $houses === false || !$weightOk || $cart === null || (float)$weightIn < $cart) {
        $notice = ['error', 'That entry was not saved because something was missing or invalid. Please enter it again.'];
    } else {
        $id = addFoodOut($customer, $weightIn, $cart, $houses);
        if ($id !== false) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?saved=' . $id);
            exit;
        }
        $notice = ['error', 'The entry was not saved because the database could not be reached. Tell a coordinator, then enter it again.'];
    }
} elseif (isset($_GET['saved'])) {
    // Show what was just saved so volunteers can catch a typo right away.
    $row = getFoodOutEntry((int)$_GET['saved']);
    if ($row) {
        $notice = ['ok', 'Saved: customer ' . $row['customer_id'] . ', ' . (float)$row['total_weight'] . ' lbs of food.'];
    }
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
        <p style="font-size: 32px; text-align:center;">Record Food Out</p>
    </div>

<?php if ($notice): ?>
    <div role="status" class="w-full max-w-5xl mx-auto px-6 pt-4">
        <p class="rounded-2xl px-6 py-4 text-xl font-bold <?= $notice[0] === 'ok' ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' ?>">
            <?= htmlspecialchars($notice[1]) ?></p>
    </div>
<?php endif; ?>

    <form id="foodOutForm" method="post" class="w-full max-w-5xl mx-auto px-6 py-6">
        <input type="hidden" name="customer_id" id="in_customer">
        <input type="hidden" name="scale_weight" id="in_weight">
        <input type="hidden" name="household_count" id="in_houses">
        <input type="hidden" name="cart_applied" id="in_cart">

        <div class="grid gap-6 md:grid-cols-2">

            <!-- Left: what the volunteer is filling in -->
            <div class="flex flex-col gap-5">

                <button type="button" data-field="customer"
                        class="field w-full text-left rounded-2xl bg-white border-2 border-gray-300 px-6 py-4 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                    <span class="block text-lg text-gray-600">Customer ID</span>
                    <span id="val_customer" class="block text-5xl font-bold text-gray-900 min-h-[3.5rem]"></span>
                </button>

                <div>
                    <p class="text-lg text-gray-600 mb-2">Households sharing this weight</p>
                    <div class="flex items-end gap-3">
                        <button type="button" data-house="1"
                                class="house flex-[3] h-24 text-4xl font-bold rounded-2xl border-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">1</button>
                        <button type="button" data-house="2"
                                class="house flex-[2] h-20 text-3xl font-bold rounded-2xl border-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">2</button>
                        <button type="button" data-house="3"
                                class="house flex-[2] h-16 text-2xl font-bold rounded-2xl border-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">3+</button>
                    </div>
                    <button type="button" data-field="houses" id="housesField"
                            class="field hidden mt-3 w-full text-left rounded-2xl bg-white border-2 border-gray-300 px-6 py-3 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                        <span class="block text-lg text-gray-600">How many households? (3 or more)</span>
                        <span id="val_houses" class="block text-4xl font-bold text-gray-900 min-h-[3rem]"></span>
                    </button>
                </div>

                <button type="button" data-field="weight"
                        class="field w-full text-left rounded-2xl bg-white border-2 border-gray-300 px-6 py-4 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                    <span class="block text-lg text-gray-600">Scale weight (lbs)</span>
                    <span id="val_weight" class="block text-5xl font-bold text-gray-900 min-h-[3.5rem]"></span>
                </button>

                <div>
                    <p class="text-lg text-gray-600 mb-2">Shopping cart used?</p>
                    <div class="flex gap-3">
                        <button type="button" data-cart="yes"
                                class="cart flex-1 h-20 text-2xl font-bold rounded-2xl border-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                            Yes, cart (&minus;<?= (int)$cartWeight ?> lbs)</button>
                        <button type="button" data-cart="no"
                                class="cart flex-1 h-20 text-2xl font-bold rounded-2xl border-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                            No cart</button>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border-2 border-gray-300 px-6 py-4">
                    <span class="block text-lg text-gray-600">Food weight</span>
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
                <button type="button" id="nextField"
                        class="h-16 text-2xl font-bold rounded-2xl bg-gray-200 border-2 border-gray-300 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-700">
                    Next field</button>
                <button type="submit" id="saveBtn" disabled
                        class="h-24 text-3xl font-bold rounded-2xl bg-green-700 text-white disabled:bg-gray-300 disabled:text-gray-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-green-900">
                    Save and start next customer</button>
            </div>
        </div>
    </form>
</main>
</div>

<script>
const CART_WEIGHT = <?= json_encode((float)$cartWeight) ?>;
const state = { active: 'customer', customer: '', weight: '', houseChoice: 1, houses: '', cart: null };

const $  = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

const ON  = ['bg-green-700', 'text-white', 'border-green-700'];
const OFF = ['bg-white', 'text-gray-900', 'border-gray-300'];

function fieldOrder() {
    return state.houseChoice === 3 ? ['customer', 'houses', 'weight'] : ['customer', 'weight'];
}

function press(key) {
    const f = state.active;
    if (!['customer', 'weight', 'houses'].includes(f)) return;
    let v = state[f];

    if (key === 'back') {
        v = v.slice(0, -1);
    } else if (key === '.') {
        if (f !== 'weight' || v.includes('.')) return;
        v = v === '' ? '0.' : v + '.';
    } else {
        const limit = { customer: 9, houses: 3 }[f];
        if (limit && v.length >= limit) return;
        if (f === 'weight') {
            const [whole, dec] = v.split('.');
            if (dec !== undefined && dec.length >= 2) return;
            if (dec === undefined && whole.length >= 5) return;
        }
        v = (v === '0' || (v === '' && key === '0' && f !== 'weight')) ? (key === '0' ? v : key) : v + key;
        if (v === '' && key === '0' && f === 'weight') v = '0';
    }
    state[f] = v;
    render();
}

function nextField() {
    const order = fieldOrder();
    const i = order.indexOf(state.active);
    state.active = order[(i + 1) % order.length];
    render();
}

function render() {
    const weight = parseFloat(state.weight);
    const cartApplied = state.cart === 'yes' ? CART_WEIGHT : 0;
    const housesVal = state.houseChoice === 3 ? parseInt(state.houses || '0', 10) : state.houseChoice;

    $('#val_customer').textContent = state.customer;
    $('#val_weight').textContent   = state.weight;
    $('#val_houses').textContent   = state.houses;
    $('#housesField').classList.toggle('hidden', state.houseChoice !== 3);

    $$('.field').forEach(el => {
        const on = el.dataset.field === state.active;
        el.classList.toggle('ring-4', on);
        el.classList.toggle('ring-green-700', on);
        el.classList.toggle('border-green-700', on);
        el.classList.toggle('border-gray-300', !on);
    });
    $$('.house').forEach(el => {
        const on = Number(el.dataset.house) === state.houseChoice;
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
        if (weight < cartApplied) {
            msg = 'The scale reads less than the ' + CART_WEIGHT + ' lb cart. Check the weight, or choose No cart.';
        } else {
            total = Number((weight - cartApplied).toFixed(2)) + ' lbs';
        }
    }
    if (state.houseChoice === 3 && state.houses !== '' && housesVal < 3) {
        msg = 'Enter 3 or more households, or choose 1 or 2.';
    }
    $('#val_total').textContent = total;
    $('#msg').textContent = msg;

    const valid = state.customer !== '' && weightOk && state.cart !== null
        && weight >= cartApplied && housesVal >= (state.houseChoice === 3 ? 3 : 1);
    $('#saveBtn').disabled = !valid;

    $('#in_customer').value = state.customer;
    $('#in_weight').value   = state.weight;
    $('#in_houses').value   = housesVal;
    $('#in_cart').value     = cartApplied;
}

$$('.key').forEach(el => el.addEventListener('click', () => press(el.dataset.key)));
$$('.field').forEach(el => el.addEventListener('click', () => { state.active = el.dataset.field; render(); }));
$$('.house').forEach(el => el.addEventListener('click', () => {
    state.houseChoice = Number(el.dataset.house);
    if (state.houseChoice === 3) state.active = 'houses';
    else { state.houses = ''; if (state.active === 'houses') state.active = 'weight'; }
    render();
}));
$$('.cart').forEach(el => el.addEventListener('click', () => { state.cart = el.dataset.cart; render(); }));
$('#nextField').addEventListener('click', nextField);

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