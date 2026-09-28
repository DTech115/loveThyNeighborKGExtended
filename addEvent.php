<?php session_cache_expire(30);
    session_start();
    // -Brooke did this for Love Thy Neighbor KG (Create Event)
    // Make session information accessible, allowing us to associate
    // data with the logged-in user.

    require_once('include/input-validation.php');
    require_once('database/dbEvents.php');
    require_once('database/dbRoleEvents.php');
    require_once('database/dbRoles.php');

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
        //echo 'bad access level';
        die();
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        //require_once('include/input-validation.php');
        //require_once('database/dbEvents.php');
        $args = sanitize($_POST, null);
        $required = array(
            "name", "date", "start-time", "end-time", "description", "location"
        );
        $roles = $_POST['roles'] ?? array(); //For the total capacity

        /* For the total capacity */
        $totalCapacity = 0;
        foreach ($roles as $roleID => $count) {
            $count = (int)$count;

            if($count < 0) {
                $count = 0;
            }
            $totalCapacity += $count;
        }

        /* At least one role was chosen */
        if ($totalCapacity <= 0) {
            echo "You must enter at least one volunteer role.";
            die();
        }
        

        if (!wereRequiredFieldsSubmitted($args, $required)) {
            echo 'bad form data';
            die();
        }

        
            // Accept either HTML5 24h time (HH:MM) or 12h times with am/pm
        if (validate24hTimeRange($args['start-time'], $args['end-time'])) {
            $startTime = $args['start-time'];
            $endTime = $args['end-time'];
        } else {
            $validated = validate12hTimeRangeAndConvertTo24h($args["start-time"], $args["end-time"]);
            if (!$validated) {
                echo 'bad time range';
                die();
            }
            $startTime = $args['start-time'] = $validated[0];
            $endTime = $args['end-time'] = $validated[1];
        }
        $date = $args['date'] = validateDate($args["date"]);
        $args["training_level_required"] = $_POST['training_level_required'] ?? 'None';
    
        $args['date']   = $date;   
        $args['startTime'] = $startTime;
        $args['endTime']   = $endTime;


        $errors = [];
        //1. Start of use case #8 recurring, etc
        $isRecurring = isset($_POST['recurring']) ? 1 : 0;
        $recurrenceType = $isRecurring ? ($_POST['recurrence_type'] ?? '') : '';
        $monthlyWeek = $_POST['monthly_week'] ?? '';
        $monthlyDay = $_POST['monthly_day'] ?? '';
        $weeklyDay = $_POST['weekly_day'] ?? '';
        $customDays = ($isRecurring && $recurrenceType === 'custom') ? (int)($_POST['custom_days'] ?? 0) : null;
        $recurrenceEndDate = $_POST['recurrence_end_date'] ?? '';


        // Base recurring validation
        if ($isRecurring) {
            if (!in_array($recurrenceType, ['daily','weekly','monthly','custom'], true)) {
                $errors['recurring'] = 'Invalid recurrence type';
            }
            if ($recurrenceEndDate === '') {
                $errors['recurring_end'] = 'Please select a recurrence end date';
            } elseif (!empty($args['date']) && $recurrenceEndDate < $args['date']) {
                $errors['recurring_end'] = 'Recurrence end date must be on or after the event date';
            }

            if ($recurrenceType === 'custom' && (!$customDays || $customDays < 1)) {
                $errors['custom'] = 'Please enter a valid custom interval';
            }

            if ($recurrenceType === 'weekly' && $weeklyDay === '') {
                $errors['weekly'] = 'Please select a day for weekly recurrence';
            }
            if ($recurrenceType === 'monthly') {
                if (!$monthlyWeek || !$monthlyDay) {
                    $errors['monthly'] = 'Please select week and day for monthly recurrence';
                } else {
                    $weekNames = [
                        '1' => 'first',
                        '2' => 'second',
                        '3' => 'third',
                        '4' => 'fourth',
                        'last' => 'last'
                    ];

                    if ($monthlyWeek === 'all') {
                        $selectedDate = new DateTime($args['date']);
                        if ($selectedDate->format('l') !== $monthlyDay) {
                            $errors['monthly'] = "Date must be a $monthlyDay.";
                        }
                    } else {
                        if (!isset($weekNames[$monthlyWeek])) {
                            $errors['monthly'] = 'Invalid monthly week';
                        } else {
                            $weekText = $weekNames[$monthlyWeek];
                            $month = date('F', strtotime($args['date']));
                            $year  = date('Y', strtotime($args['date']));

                            $expectedDate = new DateTime("$weekText $monthlyDay of $month $year");

                            if ($expectedDate->format('Y-m-d') !== $args['date']) {
                                $correct = $expectedDate->format('F j, Y');
                                $errors['monthly'] = "Date must match pattern. Correct date: $correct";
                            }
                        }
                    }
                }
            }
            $args['is_recurring'] = 1;
            $args['recurrence_type'] = $recurrenceType;                  // daily|weekly|monthly|custom
            $args['recurrence_interval_days'] = ($recurrenceType === 'custom') ? $customDays : null;
        } else {
            $args['is_recurring'] = 0;
            $args['recurrence_type'] = null;
            $args['recurrence_interval_days'] = null;
        }

        // Required normal event validation
        if (!$startTime || !$endTime || !$date){
            $errors['event'] = 'Missing required event date or time';
        }
        $args['capacity'] = $totalCapacity;
        $args['series_id'] = random_int(100, 999999); 
        // Only create event if there are no errors
        if (empty($errors)) {
            $id = create_event($args);

            if (!$id) {
                die('Could not create event');
            } else {
                // Save the roles for the first event
                save_event_roles($id, $roles);

                if ($isRecurring) {
                    $end = new DateTime($recurrenceEndDate);

                    // DAILY
                    if ($recurrenceType === 'daily') {
                        $current = new DateTime($args['date']);

                        while (true) {
                            $current->add(new DateInterval('P1D'));

                            if ($current > $end) {
                                break;
                            }

                            $dup = $args;
                            $dup['date'] = $current->format('Y-m-d');

                            $newId = create_event($dup);
                            if ($newId) {
                                save_event_roles($newId, $roles);
                            }
                        }
                    }

                    // WEEKLY
                    elseif ($recurrenceType === 'weekly') {
                        $current = new DateTime($args['date']);

                        while (true) {
                            $current->add(new DateInterval('P1W'));

                            if ($current > $end) {
                                break;
                            }

                            $dup = $args;
                            $dup['date'] = $current->format('Y-m-d');

                            $newId = create_event($dup);
                            if ($newId) {
                                save_event_roles($newId, $roles);
                            }
                        }
                    }

                    // CUSTOM
                    elseif ($recurrenceType === 'custom') {
                        $current = new DateTime($args['date']);
                        $customDays = max(1, (int)$customDays);

                        while (true) {
                            $current->add(new DateInterval('P' . $customDays . 'D'));

                            if ($current > $end) {
                                break;
                            }

                            $dup = $args;
                            $dup['date'] = $current->format('Y-m-d');

                            $newId = create_event($dup);
                            if ($newId) {
                                save_event_roles($newId, $roles);
                            }
                        }
                    }

                    // MONTHLY
                    elseif ($recurrenceType === 'monthly') {
                        $start = new DateTime($args['date']);
                        $monthOffset = 1;

                        $weekNames = [
                            '1' => 'first',
                            '2' => 'second',
                            '3' => 'third',
                            '4' => 'fourth',
                            'last' => 'last'
                        ];

                        while (true) {
                            $base = clone $start;
                            $base->modify("+$monthOffset month");

                            $year = $base->format('Y');
                            $monthNumber = $base->format('m');
                            $monthName = $base->format('F');

                            if ($monthlyWeek === 'all') {
                                $current = new DateTime("first day of $year-$monthNumber");
                                $lastDayOfMonth = new DateTime("last day of $year-$monthNumber");

                                while ($current <= $lastDayOfMonth) {
                                    if ($current->format('l') === $monthlyDay) {
                                        if ($current > $end) {
                                            break 2;
                                        }

                                        $dup = $args;
                                        $dup['date'] = $current->format('Y-m-d');

                                        $newId = create_event($dup);
                                        if ($newId) {
                                            save_event_roles($newId, $roles);
                                        }
                                    }

                                    $current->modify('+1 day');
                                }
                            } else {
                                $weekText = $weekNames[$monthlyWeek];
                                $newDate = new DateTime("$weekText $monthlyDay of $monthName $year");

                                if ($newDate > $end) {
                                    break;
                                }

                                $dup = $args;
                                $dup['date'] = $newDate->format('Y-m-d');

                                $newId = create_event($dup);
                                if ($newId) {
                                    save_event_roles($newId, $roles);
                                }
                            }

                            $monthOffset++;
                        }
                    }
                }

                header('Location: eventsuccess.php');
                exit();
            }
        }
    }

    
    $date = null;
    if (isset($_GET['date'])) {
        $date = $_GET['date'];
        $datePattern = '/[0-9]{4}-[0-9]{2}-[0-9]{2}/';
        $timeStamp = strtotime($date);
        if (!preg_match($datePattern, $date) || !$timeStamp) {
            header('Location: calendar.php');
            die();
        }
    }

    include_once('database/dbinfo.php'); 
    $con=connect();  

    $allRoles = get_roles(); // For displaying the form
?><!DOCTYPE html>
<html>
    <head>
        <?php require_once('universal.inc') ?>
        <title>Create Event | Love Thy Neighbor Community Food Pantry</title>
    </head>
    <body>
        <?php require_once('header.php') ?>
        <h1 style="color: var(--main-color); font-weight: bold;">Create Event</h1>
        <main class="date">
            <h2 style="color: var(--main-color); font-weight: bold;">New Event Form</h2>
            <form id="new-event-form" method="POST">
                <div class="event-sect">
                <label for="name">* Event Name </label>
                <input type="text" id="name" name="name" required placeholder="Enter name"> 
                </div>

                <div class="event-sect">
                <div class="event-datetime">
                    <div class="event-time">
                        <div class="event-date">
                        <label for="name">* Date </label>
                        <input type="date" id="date" name="date" <?php if ($date) echo 'value="' . $date . '"'; ?> min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="event-date">
                        <label for="name">* Start Time </label>
                        <input type="time" id="start-time" name="start-time" required>
                        </div>
                    </div>
                    <div class="event-time"> 
                        <!-- <div class="event-date">
                        <label for="name">* End Date</label>
                        <input type="date" id="end-date" name="end-date" <?php if ($date) echo 'value="' . $date . '"'; ?> min="<?php echo date('Y-m-d'); ?>" required>
                        </div> -->
                        <div class="event-date">
                        <label for="name">* End Time </label>
                        <input type="time" id="end-time" name="end-time" required>
                        </div>
                    </div>
                </div>
                </div>
                <div class="event-sect">
                <label for="name">* Description </label>
                <input type="text" id="description" name="description" required placeholder="Enter description">

                <label for="name">* Location </label>
                <input type="text" id="location" name="location" placeholder="Enter location" required>
                </div>    

                <div class="event-sect">
                    <label>* Roles</label>
                    <table class="roles-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Number Needed</th>
                                <th>Description</th>
                            </tr>
                        </thead> 
                        <tbody>
                        <tbody>
                            <?php foreach ($allRoles as $role): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($role['role']); ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            class="role-count"
                                            name="roles[<?php echo (int)$role['role_id']; ?>]"
                                            value="0"
                                            min="0"
                                            max="100"
                                            oninput="if(this.value > 100) this.value = 100"
                                            onkeydown="if(event.key === '-') event.preventDefault();"
                                        >
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($role['role_description']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Capacity</th>
                                <th>
                                    <input type="number" id="totalCapacity" name="capacity" placeholder="Enter total capacity" value="0" readonly>
                                </th>
                        </tfoot>

                    </table>   
                </div>

                <div class="event-sect">
                    <fieldset style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <legend style="color: var(--main-color); font-weight:bold;">Make this a recurring event</legend>
                        <label style="margin-top:12px; padding:12px; border:1px solid #e0e0e0; border-radius:8px;">
                            <input type="checkbox" id="recurring" name="recurring" value="1">
                            Recurring
                        </label>
                </div>

                    <div id="recurring-options" style="display:none; margin-top:6px;">
                        <label for="recurrence_type">Recurrence:</label>
                        <select name="recurrence_type" id="recurrence_type">
                            <option value="">-- Select --</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom (Number of Weeks)</option>
                        </select>

                        <div id="weekly-options" style="display:none; margin-top:8px;">
                            <label for="weekly_day">Repeat on:</label>
                            <select name="weekly_day" id="weekly_day">
                                <option value="">-- Select Day --</option>
                                <option value="Sunday">Sunday</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>

                        <div id="monthly-options" style="display:none; margin-top:8px;">
                            <label>Repeat on:</label>
                            <select name="monthly_week" id="monthly_week">
                                <option value="">-- Select Week --</option>
                                <option value="1">First</option>
                                <option value="2">Second</option>
                                <option value="3">Third</option>
                                <option value="4">Fourth</option>
                                <option value="last">Last</option>
                                <option value="all">All Weeks</option>
                            </select>

                            <select name="monthly_day" id="monthly_day">
                                <option value="">-- Select Day --</option>
                                <option value="Sunday">Sunday</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>

                        <div id="custom-interval" style="display:none; margin-top:8px;">
                            <label for="custom_days">Repeat every:</label>
                            <input type="number" min="1" id="custom_days" name="custom_days" placeholder="e.g. 10">
                        </div>

                        <div id="recurrence-end" style="margin-top:8px;">
                            <label for="recurrence_end_date">Repeat until:</label>
                            <input type="date" id="recurrence_end_date" name="recurrence_end_date">
                        </div>
                    </div>
                </fieldset>
                
                <input type="submit" value="Create Event" style="width:100%; font-weight:bold;">
                
            </form>
                <script>
                    // Debug: log submit attempts and list invalid fields
                    (function(){
                        const form = document.getElementById('new-event-form');
                        if(!form) return;
                        form.addEventListener('submit', function(e){
                            try{
                                console.log('addEvent form submit event', e);
                                const ok = form.checkValidity();
                                console.log('form.checkValidity()', ok);
                                if(!ok){
                                    e.preventDefault();
                                    const invalids = [];
                                    form.querySelectorAll(':invalid').forEach(function(el){ invalids.push({name: el.name, type: el.type, value: el.value}); });
                                    console.error('Form invalid fields:', invalids);
                                    alert('Form validation failed for: ' + invalids.map(i=>i.name).join(', '));
                                } else {
                                    console.log('Form appears valid; letting submit proceed');
                                }
                            }catch(err){
                                console.error('Error in submit debug handler', err);
                            }
                        }, false);
                    })();
                </script>
                <?php if ($date): ?>
                    <a class="button cancel" href="calendar.php?month=<?php echo substr($date, 0, 7) ?>" style="margin-top: -.5rem; font-weight:bold;">Return to Calendar</a>
                <?php else: ?>
                    <a class="button cancel" href="index.php" style="margin-top: -.5rem; font-weight:bold;">Return to Dashboard</a>
                <?php endif ?>

                <script type="text/javascript">
                    $(document).ready(function(){
                        var checkboxes = $('.checkboxes');
                        checkboxes.change(function(){
                            if($('.checkboxes:checked').length>0) {
                                checkboxes.removeAttr('required');
                            } else {
                                checkboxes.attr('required', 'required');
                            }
                        });
                    });

                    (function(){
                        const recurring = document.getElementById('recurring');
                        const options = document.getElementById('recurring-options');
                        const recurrenceType = document.getElementById('recurrence_type');
                        const customBlock = document.getElementById('custom-interval');
                        const customDays = document.getElementById('custom_days');
                        const weeklyBlock = document.getElementById('weekly-options');
                        const weeklyDay = document.getElementById('weekly_day');
                        const monthlyBlock = document.getElementById('monthly-options');
                        const monthlyWeek = document.getElementById('monthly_week');
                        const monthlyDay = document.getElementById('monthly_day');
                        const recurrenceEndDate = document.getElementById('recurrence_end_date');

                        function toggleOptions() {
                            const on = recurring && recurring.checked;
                            if (options) options.style.display = on ? 'block' : 'none';

                            if (!on) {
                                if (recurrenceType) recurrenceType.value = '';
                                if (customBlock) customBlock.style.display = 'none';
                                if (weeklyBlock) weeklyBlock.style.display = 'none';
                                if (monthlyBlock) monthlyBlock.style.display = 'none';

                                if (customDays) customDays.value = '';
                                if (weeklyDay) weeklyDay.value = '';
                                if (monthlyWeek) monthlyWeek.value = '';
                                if (monthlyDay) monthlyDay.value = '';
                                if (recurrenceEndDate) recurrenceEndDate.value = '';
                            }
                        }

                        function toggleRecurrenceFields() {
                            if (!recurrenceType) return;

                            const type = recurrenceType.value;

                            if (customBlock) customBlock.style.display = type === 'custom' ? 'block' : 'none';
                            if (weeklyBlock) weeklyBlock.style.display = type === 'weekly' ? 'block' : 'none';
                            if (monthlyBlock) monthlyBlock.style.display = type === 'monthly' ? 'block' : 'none';

                            if (type !== 'custom' && customDays) customDays.value = '';
                            if (type !== 'weekly' && weeklyDay) weeklyDay.value = '';
                            if (type !== 'monthly') {
                                if (monthlyWeek) monthlyWeek.value = '';
                                if (monthlyDay) monthlyDay.value = '';
                            }
                        }


                        if (recurring) {
                            recurring.addEventListener('change', function() {
                                toggleOptions();
                                toggleRecurrenceFields();
                            });
                        }

                        if (recurrenceType) {
                            recurrenceType.addEventListener('change', toggleRecurrenceFields);
                        }

                        toggleOptions();
                        toggleRecurrenceFields();
                        
                        /* The capacity will add up as the roles are incremented by the admin on the form */
                        function updateTotalCapacity() {
                            const roles = document.querySelectorAll(".role-count");
                            let total = 0;

                            roles.forEach(function(role) {
                                total += parseInt(role.value) || 0;
        
                            });
                            document.getElementById("totalCapacity").value = total;
                        }

                        document.querySelectorAll(".role-count").forEach(function(input) {
                            input.addEventListener("input", updateTotalCapacity);
                        });
                        updateTotalCapacity();


                    })();
                </script>
                <script>
                const roleCounts = document.querySelectorAll(".role-count");
                const firstRoleInput = document.querySelector(".role-count");
                const totalCapacity = document.getElementById("totalCapacity");

                // Clear error as soon as user types
                roleCounts.forEach(function (input) {
                    input.addEventListener("input", function () {
                        input.setCustomValidity("");
                    });
                });

                document.getElementById("new-event-form").addEventListener("submit", function (e) {

                    let total = 0;

                    // Clear errors and calculate total
                    roleCounts.forEach(function (countInput) {
                        countInput.setCustomValidity("");
                        total += Number(countInput.value) || 0;
                    });

                    totalCapacity.value = total;

                    // Require at least one role
                    if (total === 0) {
                        e.preventDefault();
                        firstRoleInput.setCustomValidity("At least one role must have a number greater than 0.");
                        firstRoleInput.reportValidity();
                        firstRoleInput.focus();
                    }
                });
                </script>

        </main>
    </body>
</html>
