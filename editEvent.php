<?php
    // Make session information accessible, allowing us to associate
    // data with the logged-in user.
    session_cache_expire(30);
    session_start();

    ini_set("display_errors",1);
    error_reporting(E_ALL);

    $loggedIn = false;
    $accessLevel = 0;
    $userID = null;
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 super admin (TBI)
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    } 
    // Require admin privileges
    if ($accessLevel < 2) {
        header('Location: login.php');
        echo 'bad access level';
        die();
    }

    require_once('include/input-validation.php');
    require_once('database/dbEvents.php');
    require_once('database/dbRoles.php');
    require_once('database/dbRoleEvents.php');


    $errors = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $args = sanitize($_POST, null);
        $required = array(
            "id", "name", "date", "start-time", "description", "end-time", "capacity", "location"
        );

        if (!wereRequiredFieldsSubmitted($args, $required)) {
            echo 'bad form data';
            die();
        } else {
            require_once('database/dbPersons.php');

            $id = $args['id'];
            $existingEvent = fetch_event_by_id($id);

            $validated = validate12hTimeRangeAndConvertTo24h($args["start-time"], $args["end-time"]);
            if (!$validated) {
                $errors .= '<p>The provided time range was invalid.</p>';
            }

            $startTime = $args['start-time'] = $validated[0];
            $endTime   = $args['end-time']   = $validated[1];
            $date      = $args['date']       = validateDate($args["date"]);

            $capacity = intval($args["capacity"]);
            $assignedVolunteerCount = count(getvolunteers_byevent($id));
            $difference = $assignedVolunteerCount - $capacity;
            if ($capacity < $assignedVolunteerCount) {
               $errors .= "<p>There are currently $assignedVolunteerCount volunteers assigned to this event. The new capacity must not exceed this number. You must remove $difference volunteer(s) from the event to reduce the capacity to $capacity.</p>";
            }

            if (!$startTime || !$date > 11){
                $errors .= '<p>Your request was missing arguments.</p>';
            }

            if (!$errors) {
                $success = update_event($id, $args);
                if (!$success){
                    echo "Oopsy!";
                    die();
                }

                $postedRoleCaps = $_POST['role_capacity'] ?? [];


                foreach ($postedRoleCaps as $roleID => $cap) {
                    $roleID = (int)$roleID;
                    $cap = (int)$cap;


                    upsert_role_event_capacity($id, $roleID, $cap);
                }


                $isRecurring    = isset($_POST['recurring']) ? 1 : 0;
                $recurrenceType = $isRecurring ? ($_POST['recurrence_type'] ?? '') : '';
                $customDays     = ($isRecurring && $recurrenceType === 'custom')
                                  ? (int)($_POST['custom_days'] ?? 0)
                                  : 0;

                if (
                    $isRecurring &&
                    in_array($recurrenceType, ['daily','weekly','monthly','custom'], true) &&
                    (!$existingEvent || empty($existingEvent['series_id']))
                ) {
                    require_once('database/dbinfo.php');
                    $con = connect();

                    $counts = [
                        'daily'   => 30,
                        'weekly'  => 12,
                        'monthly' => 6,
                        'custom'  => 12,
                    ];
                    $intervalMap = [
                        'daily'   => 'P1D',
                        'weekly'  => 'P1W',
                        'monthly' => 'P1M',
                    ];

                    if ($recurrenceType === 'custom') {
                        if ($customDays < 1) {
                            $customDays = 1;
                        }
                        $intervalSpec = 'P' . $customDays . 'D';
                    } else {
                        $intervalSpec = $intervalMap[$recurrenceType] ?? null;
                    }

                    if ($intervalSpec && isset($counts[$recurrenceType])) {
                        $current = new DateTime($date);
                        $step    = new DateInterval($intervalSpec);
                        $times   = $counts[$recurrenceType];

                        for ($i = 0; $i < $times; $i++) {
                            $current->add($step);
                            $ymd = $current->format('Y-m-d');

                            $dup = $args;
                            $dup['date']       = $ymd;
                            $dup['start-time'] = $args['start-time'];
                            $dup['end-time']   = $args['end-time'];
                            $dup['series_id']  = $series_id;

                            create_event($dup);
                        }
                    }

                    mysqli_close($con);
                }

                header('Location: event.php?id=' . $id . '&editSuccess');
                die();
            }
        }
    }

    if (!isset($_GET['id'])) {
        die();
    }

    $args  = sanitize($_GET);
    $id    = $args['id'];
    $event = fetch_event_by_id($id);
    if (!$event) {
        echo "Event does not exist";
        die();
    }

    require_once('include/output.php');

    include_once('database/dbinfo.php'); 
    $con = connect();

    $allRoles = get_roles(); // For displaying the form
    $eventRoleCaps = get_event_role_capacities($id);   // [roleID => capacity]
    $assignedByRole = get_event_role_signup_counts($id); // [roleID => assigned count]

?>
<!DOCTYPE html>
<html>
<head>
    <!-- <?php require_once('universal.inc') ?> -->
    <title>Edit Event | Love Thy Neighbor Community Food Pantry</title>
    <link rel="stylesheet" href="css/layoutInfo.css">
</head>

<body>
<?php require_once('header.php') ?>

<div class="info-form">
    <div class="page-wrapper">
        <div class="info-card">

            <div class="info-header">
                <h1>Edit Event</h1>
            </div>

            <?php if ($errors): ?>
                <div class="error-toast"><?php echo $errors ?></div>
            <?php endif; ?>

            <form id="new-event-form" method="post">

                <!-- Hidden ID -->
                <input type="hidden" name="id" value="<?php echo $id ?>" />

                <!-- Event Name -->
                <label for="name">Event Name</label>
                <input type="text" id="name" name="name"
                       value="<?php echo $event['name'] ?>"
                       required placeholder="Enter name">

                <!-- Date -->
                <label for="date">Date</label>
                <input type="date" id="date" name="date"
                       value="<?php echo $event['date'] ?>"
                       min="<?php echo date('Y-m-d'); ?>"
                       required>

                <!-- Start Time -->
                <label for="start-time">Start Time</label>
                <input type="text" id="start-time" name="start-time"
                       value="<?php echo time24hto12h($event['startTime']) ?>"
                       pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])"
                       required placeholder="Ex. 12:00 PM">

                <!-- End Time -->
                <label for="end-time">End Time</label>
                <input type="text" id="end-time" name="end-time"
                       value="<?php echo time24hto12h($event['endTime']) ?>"
                       pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])"
                       required placeholder="Ex. 12:00 PM">

                <!-- Description -->
                <label for="description">Description</label>
                <input type="text" id="description" name="description"
                       value="<?php echo $event['description'] ?>"
                       required placeholder="Enter description">

                <!-- Location -->
                <label for="location">Location</label>
                <input type="text" name="location"
                       value="<?= htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8') ?>">

                <!-- Total Capacity -->
                <label for="capacity">Total Capacity</label>
                <input type="number" id="capacity" name="capacity"
                       value="<?php echo (int)$event['capacity']; ?>" readonly>

                <small style="display:block; margin-top:4px; color:#666;">
                    This is automatically calculated from the role capacities below.
                </small>

                <!-- Roles Table -->
                <div class="event-sect">
                    <label>* Edit Role Capacities</label>

                    <table class="roles-table">
                        <thead>
                        <tr>
                            <th>Role</th>
                            <th>Currently Assigned</th>
                            <th>Capacity</th>
                            <th>Description</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (!empty($allRoles)): ?>
                            <?php foreach ($allRoles as $role): ?>

                                <?php
                                $roleID = (int)$role['role_id'];
                                $roleName = $role['role'] ?? '';
                                $roleDescription = $role['role_description'] ?? '';

                                $currentCapacity = isset($eventRoleCaps[$roleID])
                                    ? (int)$eventRoleCaps[$roleID] : 0;

                                $assignedCount = isset($assignedByRole[$roleID])
                                    ? (int)$assignedByRole[$roleID] : 0;

                                $minAllowed = $assignedCount;
                                ?>

                                <tr>
                                    <td><?php echo htmlspecialchars($roleName); ?></td>

                                    <td><?php echo $assignedCount; ?></td>

                                    <td>
                                        <input
                                            type="number"
                                            name="role_capacity[<?php echo $roleID; ?>]"
                                            value="<?php echo $currentCapacity; ?>"
                                            min="<?php echo $minAllowed; ?>"
                                            step="1"
                                            class="role-cap-input"
                                            data-assigned="<?php echo $assignedCount; ?>"
                                        >

                                        <?php if ($assignedCount > 0): ?>
                                            <small style="display:block; margin-top:4px; color:#666;">
                                                Cannot go below <?php echo $assignedCount; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo htmlspecialchars($roleDescription); ?></td>
                                </tr>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No roles available.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Buttons -->
                <div class="email-actions">
                    <button type="submit" name="action" value="send" class="submit-btn">
                        Update Event
                    </button>
                </div>

                <div class="email-actions">
                    <a class="submit-btn"
                       href="event.php?id=<?php echo htmlspecialchars($_GET['id']) ?>"
                       style="margin-top: .5rem">
                        Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleInputs = document.querySelectorAll('.role-cap-input');
    const totalCapacityInput = document.getElementById('capacity');

    function updateTotalCapacity() {
        let total = 0;

        roleInputs.forEach(input => {
            let assigned = parseInt(input.dataset.assigned || '0', 10);
            let value = parseInt(input.value || '0', 10);

            if (value < assigned) {
                value = assigned;
                input.value = assigned;
            }

            total += value;
        });

        if (totalCapacityInput) {
            totalCapacityInput.value = total;
        }
    }

    roleInputs.forEach(input => {
        input.addEventListener('input', updateTotalCapacity);
        input.addEventListener('change', updateTotalCapacity);
    });

    updateTotalCapacity();
});
</script>

</body>
</html>