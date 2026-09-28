<?php
session_cache_expire(30);
session_start();

require_once('include/input-validation.php');
require_once('database/dbEvents.php');
require_once('database/dbPersons.php');
require_once('database/dbRoles.php');
require_once('database/dbRoleEvents.php');

$loggedIn = false;
$accessLevel = 0;
$userID = null;

if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 = super admin
    $accessLevel = $_SESSION['access_level'];
    $userID = $_SESSION['_id'];
}

$error = '';
$roleIDs = $_POST['roleIDs'] ?? [];

// Connect to database
include_once('database/dbinfo.php');
$con = connect();

// Get event info from GET parameters
if (isset($_GET['id'])) {
    $event_id = intval($_GET['id']);
} elseif (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
} else {
    $event_id = 0;
}

// Stop signup if event has already ended
if ($event_id !== 0 && is_archived($event_id)) {
    echo "
    <script>
        alert('This event has already ended and signups are closed.');
        window.history.back();
    </script>
    ";
    exit();
}

$event_name = isset($_GET['event_name']) ? htmlspecialchars($_GET['event_name']) : '';
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';

if ($event_id === 0) {
    header('Location: requestFailed.php');
    die();
}

// Retrieve user info from session
$username = $_SESSION['username'] ?? '';
$account_name = $_SESSION['_id'] ?? '';

// Get roles for this event
$roles = getRolesForEvent($event_id);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $args = sanitize($_POST, null);

    $required = array("event-name", "account-name", "event_id");

    if (!wereRequiredFieldsSubmitted($args, $required)) {
        echo 'bad form data';
        die();
    }

    $roleIDs = $_POST['roleIDs'] ?? []; 
    // Convert selected role IDs to integers
    $selectedRoleIDs = array_map('intval', $roleIDs);

    if (empty($roleIDs)) {
        $error = 'Please select at least one role.';
    }

     // Refresh roles so capacity is current
    $roles = getRolesForEvent($event_id);

    /*
     * User may only select ONE role from each group.
     */
    if (empty($error)) {
        $groupCounts = [];

        foreach ($roles as $role) {
            $currentRoleID = isset($role['roleID']) ? (int)$role['roleID'] : 0;

            if (in_array($currentRoleID, $selectedRoleIDs, true)) {
                $group = trim($role['shift_group'] ?? 'Main');

                if (!isset($groupCounts[$group])) {
                    $groupCounts[$group] = 0;
                }

                $groupCounts[$group]++;
            }
        }

        foreach ($groupCounts as $group => $count) {
            if ($count > 1) {
                $error = "You may only select one role from the {$group} group.";
                break;
            }
        }
    }

    // Server-side capacity check
    if (empty($error)) {
        $roleMap = [];

        foreach ($roles as $role) {
            $roleMap[(int)$role['roleID']] = $role;
        }

        foreach ($selectedRoleIDs as $selectedRoleID) {
            if (!isset($roleMap[$selectedRoleID])) {
                $error = 'One of the selected roles is invalid.';
                break;
            }

            $capacity = isset($roleMap[$selectedRoleID]['capacity']) ? (int)$roleMap[$selectedRoleID]['capacity'] : 0;
            $remaining = isset($roleMap[$selectedRoleID]['remaining_spots']) ? (int)$roleMap[$selectedRoleID]['remaining_spots'] : $capacity;

            if ($capacity > 0 && $remaining <= 0) {
                $roleName = $roleMap[$selectedRoleID]['role_name'] ?? 'Selected role';
                $error = $roleName . ' is already full. Please choose another role.';
                break;
            }
        }
    }

    if (empty($error)) {
        $name = htmlspecialchars_decode($args['event-name']);
        $account_name = htmlspecialchars_decode($args['account-name']);
        $event_id = isset($args['event_id']) ? (int)$args['event_id'] : 0;
        $notes = isset($args['notes']) ? trim($args['notes']) : '';

        // Create the event signup record
        $id = sign_up_for_event($event_id, $account_name, '', $notes);

        if ($id) {
            $allRolesAdded = true;

            foreach ($selectedRoleIDs as $roleID) {
                $ok = addPersonRoleToEvent($account_name, $roleID, $event_id);

                if (!$ok) {
                    $allRolesAdded = false;
                    break;
                }
            }

            if ($allRolesAdded) {
                require_once('database/dbMessages.php');
                send_system_message(
                    $userID,
                    "You are now signed up for $name!",
                    "Thank you for signing up for $name!"
                );

                header('Location: signupSuccess.php');
                exit();
            } else {
                // Cleanup roles if partial failure happened
                removePersonRolesFromEventInline($account_name, $event_id);
                $error = 'There was a problem saving your selected role(s). Please try again.';
            }
        } else {
            header('Location: eventFailure.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Sign-Up for Event | Love Thy Neighbor Community Food Pantry</title>
</head>
<body>
    <?php require_once('header.php'); ?>

    <h1 style="color: #004AAD; font-weight: bold;">Sign-Up for Event</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red; font-weight: bold;">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form id="new-event-form" method="post">
        <div class="event-sect">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

            <label for="event-name">* Event Name</label>
            <input
                type="text"
                id="event-name"
                name="event-name"
                required
                value="<?php echo htmlspecialchars_decode($event_name, ENT_QUOTES); ?>"
                placeholder="Event name"
                readonly
            >
        </div>

        <div class="event-sect">
            <label for="account-name">* Your Account Name</label>
            <input
                type="text"
                id="account-name"
                name="account-name"
                <?php echo ($accessLevel >= 2) ? '' : 'readonly'; ?>
                value="<?php echo htmlspecialchars($account_name); ?>"
                placeholder="Enter account name"
            >
        </div>

        <div class="event-sect">
            <label for="notes">Notes</label>
            <input
                type="text"
                id="notes"
                name="notes"
                value="<?php echo htmlspecialchars($_POST['notes'] ?? ''); ?>"
            >
        </div>

        <div class="event-sect">
            <label>* Choose a Role</label>
            <table class="roles-table">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Role</th>
                        <th>Group</th>
                        <th>Capacity</th>
                        <th>Description</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($roles)): ?>
                        <?php foreach ($roles as $role): ?>
                            <?php
                            $roleID = isset($role['roleID']) ? (int)$role['roleID'] : 0;
                            $capacity = isset($role['capacity']) ? (int)$role['capacity'] : 0;
                            $remaining = isset($role['remaining_spots']) ? (int)$role['remaining_spots'] : $capacity;
                            $group = trim($role['shift_group'] ?? 'Main');
                            $isFull = ($capacity > 0 && $remaining <= 0);
                            ?>
                            <?php if ($capacity > 0): ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            name="roleIDs[]"
                                            value="<?php echo $roleID; ?>"
                                            <?php echo in_array($roleID, array_map('intval', $roleIDs), true) ? 'checked' : ''; ?>
                                            <?php echo $isFull ? 'disabled' : ''; ?>
                                        >
                                    </td>

                                    <td><?php echo htmlspecialchars($role['role_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($group); ?></td>

                                    <td class="role-capacity">
                                        <?php
                                        if ($remaining <= 0) {
                                            echo 'Full (0/' . $capacity . ')';
                                        } else {
                                            echo $remaining . '/' . $capacity . ' spots left';
                                        }
                                        ?>
                                    </td>

                                    <td><?php echo htmlspecialchars($role['role_description'] ?? ''); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No roles available for this event.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <br>
        <input type="submit" value="Sign up for Event">
    </form>

    <a class="button cancel" href="index.php" style="margin-top: -.5rem">Return to Dashboard</a>
</body>
</html>