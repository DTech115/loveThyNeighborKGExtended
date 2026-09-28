<?php session_cache_expire(30);
    session_start();
    //the purpose of this page is to allow admins to adjust the hours a person worked.

    require_once('include/input-validation.php');
    require_once('database/dbEvents.php');
    require_once('database/dbRoleEvents.php');
    require_once('database/dbRoles.php');
    require_once('database/dbpersonhours.php');
    require_once('domain/Person.php');
    require_once('domain/Event.php');
    require_once('include/output.php');

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

        if(!isset($args['event_id'])){
            echo "No event ID provided.";
            die();
        }
        if(!isset($args['user_id'])){
            echo "No user ID provided.";
            die();
        }
        $event = retrieve_event($args['event_id']);
        //the time12hto24h is in js for whatever reason so i cant use it in php sections. i did it myself
        if($_POST['nav'] === 'adjustEventHours'){
            $date = $event->getStartDate();
            $startTime = $_POST['start_time'];
            $pmTime = FALSE;
            if(preg_match('/p|P/', $startTime)){
                $pmTime = TRUE;
            }
            $startTime = preg_replace('/ [a|p]m|[a|p]m/i', '', $startTime);
            if(preg_match('/^[0-9]:/', $startTime)){
                $startTime = '0' . $startTime;
            }
            if($pmTime){
                $newTime = (int) substr($startTime, 0, 2);
                if($newTime !== 12){
                    $newTime += 12;
                    $startTime = $newTime . substr($startTime, 2, 3);
                }
            }

            $endTime = $_POST['end_time'];
            $pmTime = FALSE;
            if(preg_match('/p|P/', $endTime)){
                $pmTime = TRUE;
            }
            $endTime = preg_replace('/ [a|p]m|[a|p]m/i', '', $endTime);
            if(preg_match('/^[0-9]:/', $endTime)){
                $endTime = '0' . $endTime;
            }
            if($pmTime){
                $newTime = (int) substr($endTime, 0, 2);
                if($newTime !== 12){
                    $newTime += 12;
                    $endTime = $newTime . substr($endTime, 2, 3);
                }
            }
            $startTime = $date . ' ' . $startTime . ':00';
            $endTime = $date . ' ' . $endTime . ':00';
            if(!adjustVolunteerHours($args['event_id'], $args['user_id'], $args['role_id'], $startTime, $endTime)){
                echo "Failed to update hours.";
            }
            else{
                echo "Hours successfully updated";
            }
        }
    }

    
    $date = null;
    if ($event->getStartDate()) {
        $date = $event->getStartDate();
        $datePattern = '/[0-9]{4}-[0-9]{2}-[0-9]{2}/';
        $timeStamp = strtotime($date);
        if (!preg_match($datePattern, $date) || !$timeStamp) {
            header('Location: calendar.php');
            die();
        }
    }

    include_once('database/dbinfo.php'); 
    $con=connect();  

    $allRoles = getRolesForEvent($args['event_id']); // For displaying the form
?><!DOCTYPE html>
<html>
    <head>
        <?php require_once('universal.inc') ?>
        <title>Adjust Hours &#x7c Love Thy Neighbor Community Food Pantry</title>
    </head>
    <body>
        <?php require_once('header.php') ?>
        <h1 style="color: var(--main-color); font-weight: bold;">Adjust <?php $person = get_person_by_id($_POST['user_id']); echo $person['first_name'] . ' ' . $person['last_name'] ;?>'s Volunteer Hours</h1>
        <main class="date">
            <h2 style="color: var(--main-color); font-weight: bold;"></h2>
                <div class="event-sect">
                <label for="name">Event Name</label>
                <p><?php echo $event->getName();?></p> 
                </div>

                <div class="event-sect">
                <div class="event-datetime">
                    <div class="event-time">
                        <div class="event-date">
                            <label for="name">Date</label>
                            <p><?php echo $date;?></p>
                        </div>
                        <div class="event-date">
                            <label for="name">Start Time</label>
                            <p><?php echo time24hto12h($event->getStartTime());?></p>
                        </div>
                        <div class="event-date">
                            <label for="name">End Time</label>
                            <p><?php echo time24hto12h($event->getEndTime());?></p>
                        </div>
                    </div>
                </div>
                </div>

                <div class="event-sect">
                    <label>Roles</label>
                    <table class="roles-table" style="width: 90%;">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th style="text-align: left;">Clock In Time</th>
                                <th>Clock Out Time</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = "SELECT roleID, start_time, end_time FROM dbpersonhours WHERE personID = ? AND eventID = ?";
                                $stmt = mysqli_prepare($con, $query);
                                mysqli_stmt_bind_param($stmt, "ss", $args['user_id'], $args['event_id']);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                $rolesPerformed = array();
                                foreach($result as $row){
                                    $startTime = substr($row['start_time'], 11, 5);
                                    $endTime = substr($row['end_time'], 11, 5);
                                    $rolesPerformed[$row['roleID']] = $startTime . ' ' . $endTime;
                                }
                                mysqli_stmt_close($stmt);
                            ?>
                            <?php foreach ($allRoles as $role): ?>
                                <form method=POST>
                                <tr>
                                    <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                        <?php if(isset($rolesPerformed[$role['roleID']])): ?>
                                            <td>
                                                <input type="text" id="start-time" name="start_time" value="<?php echo time24hto12h(substr($rolesPerformed[$role['roleID']],0,5));?>" pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])" required placeholder="Enter start time. Ex. 12:00 PM">
                                            </td>
                                            <td>
                                                <input type="text" id="end-time" name="end_time" value="<?php echo time24hto12h(substr($rolesPerformed[$role['roleID']],6,5));?>" pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])" required placeholder="Enter start time. Ex. 12:00 PM">
                                            </td>
                                        <?php else: ?>
                                            <td>
                                                <input type="text" id="start-time" name="start_time" pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])" required placeholder="Enter start time. Ex. 12:00 PM">
                                            </td>
                                            <td>
                                                <input type="text" id="end-time" name="end_time" pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])" required placeholder="Enter end time. Ex. 12:00 PM">
                                            </td>
                                        <?php endif; ?>
                                    <td>
                                        <button type="submit" class="button">Adjust Hours</button>
                                    </td>
                                </tr>
                                <input type="hidden" name="nav" value="adjustEventHours"/>
                                <input type="hidden" name="user_id" value="<?php echo $args['user_id'];?>"/>
                                <input type="hidden" name="role_id" value="<?php echo $role['roleID'];?>"/>
                                <input type="hidden" name="event_id" value="<?php echo $role['eventID'];?>"/>
                                </form>
                            <?php endforeach; ?>
                        </tbody>

                    </table>   
                </div>
                </fieldset>

                    <a class="button cancel" href="index.php" style="margin-top: -.5rem; font-weight:bold;">Return to Dashboard</a>

 
        </main>
    </body>
</html>
